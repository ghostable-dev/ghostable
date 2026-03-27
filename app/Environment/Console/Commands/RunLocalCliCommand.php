<?php

declare(strict_types=1);

namespace App\Environment\Console\Commands;

use App\Account\Models\User;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class RunLocalCliCommand extends Command
{
    private const ACTION_PENDING_ACTOR = 'pending-actor';

    private const ACTION_PENDING_RECIPIENT = 'pending-recipient';

    private const ACTION_FULFILL = 'fulfill';

    private const ACTION_FULFILL_ALL = 'fulfill-all';

    private const ACTION_CUSTOM = 'custom';

    private const CUSTOM_OPTION = '__custom';

    private const MAX_REQUEST_OPTIONS = 25;

    private const FULFILL_ALL_MAX_ATTEMPTS = 4;

    private const FULFILL_ALL_BASE_DELAY_SECONDS = 1;

    private const FULFILL_ALL_INTER_REQUEST_DELAY_MICROSECONDS = 300000;

    private ?string $personaDeviceResolutionHint = null;

    /**
     * @var array<string, string>
     */
    private array $personaDeviceLabelCache = [];

    protected $signature = 'local:cli
        {--persona= : Persona key from persona file}
        {--persona-file= : Persona file path (default: ../.ghostable-reshare-lab/personas.env)}
        {--email= : User email override}
        {--prefix= : Keychain prefix override}
        {--workdir= : Working directory for CLI execution}
        {--api-base= : Ghostable API base URL override}
        {--organization= : Organization UUID override}
        {--action= : pending-actor|pending-recipient|fulfill|fulfill-all|custom}
        {--request= : Key re-share request UUID (used by action=fulfill)}
        {--command= : Raw ghostable args (used by action=custom)}
        {--yes : Skip execution confirmation}';

    protected $description = 'Run Ghostable CLI as a local persona with interactive prompts.';

    public function handle(): int
    {
        $runnerScript = base_path('scripts/local-cli-as.sh');

        if (! is_file($runnerScript)) {
            $this->error('Missing runner script: scripts/local-cli-as.sh');

            return self::FAILURE;
        }

        $personaFile = $this->resolvePersonaFilePath();
        $personas = $this->loadPersonas($personaFile);

        $identity = $this->resolveIdentity($personas, $personaFile);
        if ($identity === null) {
            return self::FAILURE;
        }

        $action = $this->resolveAction();
        if ($action === null) {
            return self::FAILURE;
        }

        $personaDeviceId = null;
        if ($action === self::ACTION_FULFILL || $action === self::ACTION_FULFILL_ALL) {
            $personaDeviceId = $this->resolvePersonaDeviceId($identity);
            if ($personaDeviceId !== null) {
                $this->line(sprintf('Resolved persona device: %s', $personaDeviceId));
            } else {
                $this->warn('Could not resolve current persona device ID. Fulfillability hints will be unavailable.');
                if ($this->personaDeviceResolutionHint !== null) {
                    $this->line($this->personaDeviceResolutionHint);
                }
            }
        }

        if ($action === self::ACTION_FULFILL_ALL) {
            return $this->runFulfillAllAction($identity, $personaDeviceId);
        }

        $ghostableArgs = $this->resolveGhostableArguments(
            action: $action,
            email: $identity['email'],
            personaDeviceId: $personaDeviceId
        );

        if ($ghostableArgs === null) {
            return self::FAILURE;
        }

        $runnerArgs = [
            'bash',
            $runnerScript,
            '--email', $identity['email'],
            '--prefix', $identity['prefix'],
            '--workdir', $identity['workdir'],
            '--token-name', 'local-cli-as-artisan',
        ];

        $apiBase = trim((string) ($this->option('api-base') ?? ''));
        if ($apiBase !== '') {
            array_push($runnerArgs, '--api-base', $apiBase);
        }

        $runnerArgs[] = '--';
        array_push($runnerArgs, ...$ghostableArgs);

        $this->newLine();
        $this->line(sprintf('Persona: %s', $identity['email']));
        $this->line(sprintf('Prefix: %s', $identity['prefix']));
        $this->line(sprintf('Workdir: %s', $identity['workdir']));
        $this->line(sprintf('Action: %s', $action));
        $this->line('Command: ghostable '.implode(' ', $ghostableArgs));
        $this->newLine();

        if (! (bool) $this->option('yes')) {
            $shouldRun = confirm(
                label: 'Run this command now?',
                default: true,
            );

            if (! $shouldRun) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        $process = new Process($runnerArgs, base_path());
        $process->setTimeout(null);

        $exitCode = $process->run(function (string $type, string $output): void {
            $this->output->write($output);
        });

        return is_int($exitCode) ? $exitCode : self::FAILURE;
    }

    /**
     * @return array<string, array{email: string, prefix: string, workdir: string}>
     */
    private function loadPersonas(string $personaFile): array
    {
        if (! is_file($personaFile)) {
            return [];
        }

        $lines = file($personaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $personas = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $payload = trim($parts[1]);

            if ($key === '' || $payload === '') {
                continue;
            }

            [$email, $prefix, $workdir] = array_pad(explode('|', $payload, 3), 3, '');
            $email = trim($email);
            $prefix = trim($prefix);
            $workdir = trim($workdir);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $personas[$key] = [
                'email' => $email,
                'prefix' => $prefix !== '' ? $prefix : $this->defaultPrefixFromEmail($email),
                'workdir' => $workdir !== '' ? $workdir : base_path(),
            ];
        }

        return $personas;
    }

    /**
     * @param  array<string, array{email: string, prefix: string, workdir: string}>  $personas
     * @return array{email: string, prefix: string, workdir: string}|null
     */
    private function resolveIdentity(array $personas, string $personaFile): ?array
    {
        $selectedPersona = trim((string) ($this->option('persona') ?? ''));
        $email = trim((string) ($this->option('email') ?? ''));
        $prefix = trim((string) ($this->option('prefix') ?? ''));
        $workdir = trim((string) ($this->option('workdir') ?? ''));

        if ($selectedPersona !== '') {
            if (! isset($personas[$selectedPersona])) {
                $this->error(sprintf('Persona "%s" was not found in %s.', $selectedPersona, $personaFile));

                return null;
            }

            $persona = $personas[$selectedPersona];
            $email = $email !== '' ? $email : $persona['email'];
            $prefix = $prefix !== '' ? $prefix : $persona['prefix'];
            $workdir = $workdir !== '' ? $workdir : $persona['workdir'];
        }

        if ($email === '' && ! empty($personas)) {
            $options = [];
            $selectionMap = [];
            $uniquePersonas = $this->uniquePersonas($personas);

            foreach ($uniquePersonas as $key => $persona) {
                $options[$key] = sprintf(
                    '%s (%s)',
                    $persona['email'],
                    $this->describePersonaDevice($persona)
                );
                $selectionMap[$key] = $persona;
            }
            $options[self::CUSTOM_OPTION] = 'Custom email/prefix';

            $picked = select(
                label: 'Who do you want to run as?',
                options: $options,
                default: array_key_first($uniquePersonas) ?: self::CUSTOM_OPTION
            );

            if ($picked !== self::CUSTOM_OPTION && isset($selectionMap[$picked])) {
                $persona = $selectionMap[$picked];
                $email = $persona['email'];
                $prefix = $prefix !== '' ? $prefix : $persona['prefix'];
                $workdir = $workdir !== '' ? $workdir : $persona['workdir'];
            }
        }

        if ($email === '') {
            $email = $this->promptForLocalUserEmail();
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email address is required.');

            return null;
        }

        if ($prefix === '') {
            $prefix = text(
                label: 'Keychain prefix (device persona)',
                default: $this->defaultPrefixFromEmail($email),
                required: 'Keychain prefix is required.'
            );
        }

        if ($workdir === '') {
            $workdir = base_path();
        }

        if (! is_dir($workdir)) {
            $this->error(sprintf('Workdir does not exist: %s', $workdir));

            return null;
        }

        return [
            'email' => $email,
            'prefix' => $prefix,
            'workdir' => $workdir,
        ];
    }

    /**
     * @param  array<string, array{email: string, prefix: string, workdir: string}>  $personas
     * @return array<string, array{email: string, prefix: string, workdir: string}>
     */
    private function uniquePersonas(array $personas): array
    {
        $unique = [];
        $seen = [];

        foreach ($personas as $key => $persona) {
            $fingerprint = strtolower(sprintf(
                '%s|%s|%s',
                $persona['email'],
                $persona['prefix'],
                $persona['workdir'],
            ));

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $unique[$key] = $persona;
        }

        return $unique;
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $persona
     */
    private function describePersonaDevice(array $persona): string
    {
        $cacheKey = strtolower(sprintf(
            '%s|%s|%s',
            $persona['email'],
            $persona['prefix'],
            $persona['workdir'],
        ));

        if (isset($this->personaDeviceLabelCache[$cacheKey])) {
            return $this->personaDeviceLabelCache[$cacheKey];
        }

        $result = $this->runPersonaGhostableCommand(
            identity: $persona,
            cliArgs: ['device', 'status'],
            tokenName: 'local-cli-as-artisan-persona-menu-status',
            timeoutSeconds: 20
        );

        if (! $result['successful']) {
            $label = $this->personaStatusFailureLabel($result['output']);
            $this->personaDeviceLabelCache[$cacheKey] = $label;

            return $label;
        }

        $deviceName = $this->extractDeviceNameFromStatusOutput($result['output']);
        if ($deviceName === null) {
            $label = sprintf('device: linked (%s)', $this->shortPrefixLabel($persona['prefix']));
            $this->personaDeviceLabelCache[$cacheKey] = $label;

            return $label;
        }

        $label = sprintf('device: %s', $deviceName);
        $this->personaDeviceLabelCache[$cacheKey] = $label;

        return $label;
    }

    private function extractDeviceNameFromStatusOutput(string $output): ?string
    {
        $plain = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $output) ?? $output;
        $lines = preg_split('/\r\n|\n|\r/', $plain);

        if (! is_array($lines)) {
            return null;
        }

        foreach ($lines as $line) {
            if (! str_contains($line, 'Name:')) {
                continue;
            }

            $start = strpos($line, 'Name:');
            if ($start === false) {
                continue;
            }

            $candidate = trim(substr($line, $start + strlen('Name:')));
            $candidate = trim($candidate, " \t\n\r\0\x0B│|");

            if ($candidate === '') {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    private function shortPrefixLabel(string $prefix): string
    {
        $parts = array_values(array_filter(explode('.', $prefix), static fn (string $part): bool => $part !== ''));
        $count = count($parts);

        if ($count <= 3) {
            return $prefix;
        }

        return implode('.', array_slice($parts, -3));
    }

    private function personaStatusFailureLabel(string $output): string
    {
        $normalized = strtolower($output);

        if (str_contains($normalized, 'selected device is invalid')
            || str_contains($normalized, 'device not found')
            || str_contains($normalized, 'unable to fetch device status')) {
            return 'device: stale';
        }

        if (str_contains($normalized, 'no device identity')
            || str_contains($normalized, 'device identity')
            || str_contains($normalized, 'device link')) {
            return 'device: not linked';
        }

        return 'device: unknown';
    }

    private function promptForLocalUserEmail(): string
    {
        $users = User::query()
            ->orderBy('email')
            ->limit(50)
            ->get(['name', 'email']);

        if ($users->isEmpty()) {
            return text(
                label: 'User email',
                required: 'Email is required.'
            );
        }

        $options = [];
        foreach ($users as $user) {
            $name = trim((string) $user->name);
            $email = trim((string) $user->email);
            $options[$email] = $name !== '' ? sprintf('%s <%s>', $name, $email) : $email;
        }
        $options[self::CUSTOM_OPTION] = 'Enter email manually';

        $picked = select(
            label: 'Select a local user',
            options: $options,
            default: array_key_first($options) ?: self::CUSTOM_OPTION
        );

        if ($picked === self::CUSTOM_OPTION) {
            return text(
                label: 'User email',
                required: 'Email is required.'
            );
        }

        return $picked;
    }

    private function resolveAction(): ?string
    {
        $options = [
            self::ACTION_PENDING_ACTOR => 'List pending key re-share requests (actor view)',
            self::ACTION_PENDING_RECIPIENT => 'List pending key re-share requests (recipient view)',
            self::ACTION_FULFILL => 'Fulfill a pending key re-share request',
            self::ACTION_FULFILL_ALL => 'Fulfill all pending key re-share requests (ready for this device)',
            self::ACTION_CUSTOM => 'Run a custom ghostable command',
        ];

        $action = trim((string) ($this->option('action') ?? ''));
        if ($action !== '') {
            if (! array_key_exists($action, $options)) {
                $this->error('Invalid --action value.');

                return null;
            }

            return $action;
        }

        return select(
            label: 'What do you want to do?',
            options: $options,
            default: self::ACTION_PENDING_ACTOR
        );
    }

    /**
     * @return list<string>|null
     */
    private function resolveGhostableArguments(string $action, string $email, ?string $personaDeviceId): ?array
    {
        return match ($action) {
            self::ACTION_PENDING_ACTOR => $this->buildPendingCommand($email, 'actor'),
            self::ACTION_PENDING_RECIPIENT => $this->buildPendingCommand($email, 'recipient'),
            self::ACTION_FULFILL => $this->buildFulfillCommand($email, $personaDeviceId),
            self::ACTION_FULFILL_ALL => null,
            self::ACTION_CUSTOM => $this->buildCustomCommand(),
            default => null,
        };
    }

    /**
     * @return list<string>|null
     */
    private function buildPendingCommand(string $email, string $role): ?array
    {
        $organizationId = $this->resolveOrganizationId($email);
        if ($organizationId === null) {
            return null;
        }

        return [
            'env',
            'reshare',
            'pending',
            '--role',
            $role,
            '--organization',
            $organizationId,
        ];
    }

    /**
     * @return list<string>|null
     */
    private function buildFulfillCommand(string $email, ?string $personaDeviceId): ?array
    {
        $organizationId = $this->resolveOrganizationId($email);
        if ($organizationId === null) {
            return null;
        }

        $requestId = trim((string) ($this->option('request') ?? ''));
        if ($requestId === '') {
            $requestId = $this->resolveRequestIdForOrganization($organizationId, $personaDeviceId);
        }

        if ($requestId === '') {
            $this->error('A request ID is required for fulfill.');

            return null;
        }

        if ($personaDeviceId !== null) {
            $request = EnvironmentKeyReshareRequest::query()
                ->whereKey($requestId)
                ->where('organization_id', $organizationId)
                ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
                ->first();

            if ($request) {
                $fulfillable = $this->isRequestFulfillableByDevice($request, $personaDeviceId);
                if ($fulfillable === false) {
                    if ((bool) $this->option('yes')) {
                        $this->error('Selected request is blocked for this persona device. Choose a [ready] request or switch persona.');

                        return null;
                    }

                    $shouldContinue = confirm(
                        label: 'This request is not fulfillable from the selected persona device. Continue anyway?',
                        default: false,
                    );

                    if (! $shouldContinue) {
                        $this->warn('Select a different request or persona.');

                        return null;
                    }
                }
            }
        }

        return [
            'env',
            'reshare',
            'fulfill',
            $requestId,
            '--organization',
            $organizationId,
        ];
    }

    /**
     * @return list<string>|null
     */
    private function buildCustomCommand(): ?array
    {
        $command = trim((string) ($this->option('command') ?? ''));

        if ($command === '') {
            $command = text(
                label: 'Ghostable command (without "ghostable")',
                placeholder: 'env reshare pending --role actor',
                required: 'Command is required.'
            );
        }

        $args = $this->splitShellArguments($command);

        if ($args === []) {
            $this->error('Custom command could not be parsed.');

            return null;
        }

        return $args;
    }

    private function resolveOrganizationId(string $email): ?string
    {
        $provided = trim((string) ($this->option('organization') ?? ''));
        if ($provided !== '') {
            return $provided;
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            return text(
                label: 'Organization UUID',
                required: 'Organization UUID is required.'
            );
        }

        $organizations = $user->organizations()
            ->select('organizations.id', 'organizations.name')
            ->orderBy('organizations.name')
            ->get();

        if ($organizations->isEmpty()) {
            return text(
                label: 'Organization UUID',
                required: 'Organization UUID is required.'
            );
        }

        if ($organizations->count() === 1) {
            return (string) $organizations->first()->id;
        }

        $options = [];
        foreach ($organizations as $organization) {
            $options[(string) $organization->id] = sprintf(
                '%s (%s)',
                (string) $organization->name,
                (string) $organization->id
            );
        }

        return select(
            label: 'Select organization',
            options: $options,
            default: array_key_first($options)
        );
    }

    private function resolveRequestIdForOrganization(string $organizationId, ?string $personaDeviceId): string
    {
        $requests = EnvironmentKeyReshareRequest::query()
            ->with([
                'project:id,name',
                'environment:id,name',
                'targetUser:id,email',
                'targetDevice:id,name',
            ])
            ->where('organization_id', $organizationId)
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->latest('created_at')
            ->limit(self::MAX_REQUEST_OPTIONS)
            ->get();

        if ($requests->isEmpty()) {
            return text(
                label: 'Pending request UUID',
                required: 'Request UUID is required.'
            );
        }

        $options = [];
        foreach ($requests as $request) {
            $project = $request->project?->name ?? 'unknown-project';
            $environment = $request->environment?->name ?? 'unknown-environment';
            $targetUser = $request->targetUser?->email ?? 'unknown-user';
            $targetDevice = $request->targetDevice?->name ?? 'unknown-device';
            $createdAt = $request->created_at?->format('Y-m-d H:i') ?? 'unknown-time';
            $readiness = 'unknown';

            if ($personaDeviceId !== null) {
                $fulfillable = $this->isRequestFulfillableByDevice($request, $personaDeviceId);
                $readiness = match ($fulfillable) {
                    true => 'ready',
                    false => 'blocked',
                    default => 'unknown',
                };
            }

            $options[(string) $request->id] = sprintf(
                '[%s] %s / %s -> %s (%s) at %s',
                $readiness,
                $project,
                $environment,
                $targetUser,
                $targetDevice,
                $createdAt
            );
        }

        $options[self::CUSTOM_OPTION] = 'Enter request UUID manually';

        $picked = select(
            label: 'Select pending request',
            options: $options,
            default: array_key_first($options) ?: self::CUSTOM_OPTION
        );

        if ($picked === self::CUSTOM_OPTION) {
            return text(
                label: 'Pending request UUID',
                required: 'Request UUID is required.'
            );
        }

        return $picked;
    }

    private function isRequestFulfillableByDevice(EnvironmentKeyReshareRequest $request, string $deviceId): ?bool
    {
        $requiredVersion = (int) $request->required_key_version;
        if ($requiredVersion <= 0) {
            return null;
        }

        $environmentKey = EnvironmentKey::query()
            ->where('environment_id', (string) $request->environment_id)
            ->where('version', $requiredVersion)
            ->with('envelope')
            ->first();

        if (! $environmentKey) {
            return null;
        }

        $recipients = $environmentKey->envelope?->recipients;
        if (! is_array($recipients)) {
            return false;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $type = strtolower((string) ($recipient['type'] ?? ''));
            $recipientId = (string) ($recipient['id'] ?? '');
            if ($type === 'device' && $recipientId === $deviceId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function splitShellArguments(string $value): array
    {
        $parts = str_getcsv($value, ' ', '"', '\\');
        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($part): string => trim((string) $part),
            $parts
        ), static fn (string $part): bool => $part !== ''));
    }

    private function resolvePersonaFilePath(): string
    {
        $provided = trim((string) ($this->option('persona-file') ?? ''));
        if ($provided !== '') {
            return $provided;
        }

        return dirname(base_path()).'/.ghostable-reshare-lab/personas.env';
    }

    private function defaultPrefixFromEmail(string $email): string
    {
        $localPart = strtolower((string) strtok($email, '@'));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $localPart) ?? '';
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'user';
        }

        return 'local.ghostable.local.'.$slug;
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $identity
     */
    private function resolvePersonaDeviceId(array $identity): ?string
    {
        $this->personaDeviceResolutionHint = null;
        $statusOutput = null;
        $deviceId = $this->readPersonaDeviceId(identity: $identity, output: $statusOutput);
        if ($deviceId !== null) {
            return $deviceId;
        }

        $this->warn('Persona device is not currently resolvable. Attempting to link/relink this persona automatically...');

        $linked = $this->runPersonaGhostableCommand(
            identity: $identity,
            cliArgs: [
                'device',
                'link',
                '--name',
                $this->defaultPersonaDeviceName($identity['email']),
                '--platform',
                'macos-local',
                '--relink-stale',
            ],
            tokenName: 'local-cli-as-artisan-device-link',
            timeoutSeconds: 60
        );

        if (! $linked['successful']) {
            $this->personaDeviceResolutionHint = $this->buildPersonaDeviceResolutionHint((string) ($statusOutput ?? '')).PHP_EOL
                .'Automatic relink failed. Run custom command `device link --name "'.$this->defaultPersonaDeviceName($identity['email']).'" --relink-stale`.';

            return null;
        }

        $this->line('Persona device link/relink completed. Re-checking device status...');

        $deviceId = $this->readPersonaDeviceId(identity: $identity, output: $statusOutput);
        if ($deviceId === null) {
            $this->personaDeviceResolutionHint = $this->buildPersonaDeviceResolutionHint((string) ($statusOutput ?? ''));
        }

        return $deviceId;
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $identity
     */
    private function readPersonaDeviceId(array $identity, ?string &$output = null): ?string
    {
        $result = $this->runPersonaGhostableCommand(
            identity: $identity,
            cliArgs: ['device', 'status'],
            tokenName: 'local-cli-as-artisan-device-status',
            timeoutSeconds: 30
        );

        $output = $result['output'];

        if (! $result['successful']) {
            return null;
        }

        if (! preg_match('/ID:\s*([0-9a-f-]{36})/i', $result['output'], $matches)) {
            return null;
        }

        return (string) $matches[1];
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $identity
     * @param  list<string>  $cliArgs
     * @return array{successful: bool, output: string}
     */
    private function runPersonaGhostableCommand(
        array $identity,
        array $cliArgs,
        string $tokenName,
        int $timeoutSeconds
    ): array {
        $runnerScript = base_path('scripts/local-cli-as.sh');

        $args = [
            'bash',
            $runnerScript,
            '--email', $identity['email'],
            '--prefix', $identity['prefix'],
            '--workdir', $identity['workdir'],
            '--token-name', $tokenName,
            '--',
            ...$cliArgs,
        ];

        $process = new Process($args, base_path());
        $process->setTimeout($timeoutSeconds);
        $process->run();

        return [
            'successful' => $process->isSuccessful(),
            'output' => trim($process->getOutput().$process->getErrorOutput()),
        ];
    }

    private function defaultPersonaDeviceName(string $email): string
    {
        $localPart = strtolower(trim((string) strtok($email, '@')));
        $parts = preg_split('/[^a-z0-9]+/', $localPart) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
        $short = $parts !== [] ? (string) end($parts) : 'user';
        $short = ucfirst($short);

        return sprintf('%s MacBook Pro', $short);
    }

    private function buildPersonaDeviceResolutionHint(string $output): string
    {
        $normalized = strtolower($output);

        if (str_contains($normalized, 'selected device is invalid')
            || str_contains($normalized, 'device not found')
            || str_contains($normalized, 'unable to fetch device status')) {
            return 'This persona prefix points to a stale device (common after `app:setup`).';
        }

        if (str_contains($normalized, 'device identity') || str_contains($normalized, 'device link')) {
            return 'This persona has no linked local device yet.';
        }

        return 'Run custom command `device status` as this persona to inspect the current device state.';
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $identity
     */
    private function runFulfillAllAction(array $identity, ?string $personaDeviceId): int
    {
        $organizationId = $this->resolveOrganizationId($identity['email']);
        if ($organizationId === null || trim($organizationId) === '') {
            $this->error('Organization UUID is required.');

            return self::FAILURE;
        }

        $requests = EnvironmentKeyReshareRequest::query()
            ->with([
                'project:id,name',
                'environment:id,name',
                'targetUser:id,email',
                'targetDevice:id,name',
            ])
            ->where('organization_id', $organizationId)
            ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
            ->latest('created_at')
            ->limit(250)
            ->get();

        if ($requests->isEmpty()) {
            $this->info('No pending key re-share requests found for this organization.');

            return self::SUCCESS;
        }

        $readyRequests = [];
        $blockedCount = 0;
        $unknownCount = 0;

        foreach ($requests as $request) {
            if ($personaDeviceId === null) {
                $readyRequests[] = $request;

                continue;
            }

            $fulfillable = $this->isRequestFulfillableByDevice($request, $personaDeviceId);

            if ($fulfillable === true) {
                $readyRequests[] = $request;

                continue;
            }

            if ($fulfillable === false) {
                $blockedCount++;

                continue;
            }

            $unknownCount++;
            $readyRequests[] = $request;
        }

        $this->newLine();
        $this->line(sprintf(
            'Pending requests: %d (ready: %d, blocked: %d, unknown: %d)',
            $requests->count(),
            count($readyRequests),
            $blockedCount,
            $unknownCount
        ));

        if ($readyRequests === []) {
            $this->warn('No fulfillable requests found for this persona device.');

            return self::SUCCESS;
        }

        if (! (bool) $this->option('yes')) {
            $shouldRun = confirm(
                label: sprintf('Fulfill %d ready request(s) now?', count($readyRequests)),
                default: true,
            );

            if (! $shouldRun) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        $successes = 0;
        $failures = 0;

        foreach ($readyRequests as $request) {
            $environmentName = $request->environment?->name ?? (string) $request->environment_id;
            $targetUser = $request->targetUser?->email ?? 'unknown-user';
            $targetDevice = $request->targetDevice?->name ?? 'unknown-device';

            $this->line(sprintf(
                '- Fulfilling %s for %s (%s)...',
                $environmentName,
                $targetUser,
                $targetDevice
            ));

            $result = $this->runFulfillWithRetry(
                identity: $identity,
                requestId: (string) $request->id,
                organizationId: $organizationId
            );

            if ($result['successful']) {
                $successes++;
                $this->line(sprintf('  [ok] %s', (string) $request->id));

                usleep(self::FULFILL_ALL_INTER_REQUEST_DELAY_MICROSECONDS);

                continue;
            }

            $failures++;
            $message = trim($result['output']);
            if ($message === '') {
                $message = 'Unknown error.';
            }

            $this->line(sprintf('  [fail] %s - %s', (string) $request->id, $message));
        }

        $this->newLine();
        $this->line(sprintf('Fulfill-all summary: success=%d failed=%d skipped=%d', $successes, $failures, $blockedCount));

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{email: string, prefix: string, workdir: string}  $identity
     * @return array{successful: bool, output: string}
     */
    private function runFulfillWithRetry(array $identity, string $requestId, string $organizationId): array
    {
        $attempt = 1;

        while ($attempt <= self::FULFILL_ALL_MAX_ATTEMPTS) {
            $result = $this->runPersonaGhostableCommand(
                identity: $identity,
                cliArgs: [
                    'env',
                    'reshare',
                    'fulfill',
                    $requestId,
                    '--organization',
                    $organizationId,
                ],
                tokenName: 'local-cli-as-artisan-fulfill-all',
                timeoutSeconds: 90
            );

            if ($result['successful']) {
                return $result;
            }

            if ($this->isAlreadyCompletedResult($result['output'])) {
                return [
                    'successful' => true,
                    'output' => $result['output'],
                ];
            }

            if (! $this->isRateLimitFailure($result['output']) || $attempt === self::FULFILL_ALL_MAX_ATTEMPTS) {
                return $result;
            }

            $delaySeconds = self::FULFILL_ALL_BASE_DELAY_SECONDS * $attempt;
            $this->warn(sprintf(
                '  Rate limited while fulfilling %s (attempt %d/%d). Retrying in %ds...',
                $requestId,
                $attempt,
                self::FULFILL_ALL_MAX_ATTEMPTS,
                $delaySeconds
            ));
            sleep($delaySeconds);
            $attempt++;
        }

        return [
            'successful' => false,
            'output' => 'Retry loop exited unexpectedly.',
        ];
    }

    private function isRateLimitFailure(string $output): bool
    {
        $normalized = strtolower($output);

        return str_contains($normalized, 'too many requests')
            || str_contains($normalized, 'status code 429')
            || str_contains($normalized, ' http 429')
            || str_contains($normalized, 'rate limit');
    }

    private function isAlreadyCompletedResult(string $output): bool
    {
        $normalized = strtolower($output);

        return str_contains($normalized, 'already completed')
            || str_contains($normalized, 'request is already completed');
    }
}
