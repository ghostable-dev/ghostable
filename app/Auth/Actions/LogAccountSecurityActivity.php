<?php

namespace App\Auth\Actions;

use App\Account\Models\User;

class LogAccountSecurityActivity
{
    public function passwordResetRequested(string $email, ?User $user = null): void
    {
        $this->record(
            event: 'password_reset_requested',
            user: $user,
            description: "Password reset requested for \"{$email}\"",
            context: [
                'email' => $email,
            ]
        );
    }

    public function passwordResetCompleted(User $user): void
    {
        $this->record(
            event: 'password_reset',
            user: $user,
            description: "Password reset for \"{$user->email}\""
        );
    }

    public function twoFactorEnabled(User $user): void
    {
        $this->record(
            event: '2fa-enabled',
            user: $user,
            description: "Enabled two-factor authentication for \"{$user->email}\"",
            causer: $user
        );
    }

    public function twoFactorDisabled(User $user): void
    {
        $this->record(
            event: '2fa-disabled',
            user: $user,
            description: "Disabled two-factor authentication for \"{$user->email}\"",
            causer: $user
        );
    }

    public function mfaChallenge(User $user, array $context = []): void
    {
        $this->record(
            event: 'mfa_challenge',
            user: $user,
            description: "MFA challenge for \"{$user->email}\"",
            context: $context,
            causer: $user
        );
    }

    public function failedMfa(User $user, array $context = []): void
    {
        $this->record(
            event: 'failed_mfa',
            user: $user,
            description: "Failed MFA for \"{$user->email}\"",
            context: $context,
            causer: $user
        );
    }

    public function successfulMfa(User $user, array $context = []): void
    {
        $this->record(
            event: 'mfa_succeeded',
            user: $user,
            description: "Successful MFA for \"{$user->email}\"",
            context: $context,
            causer: $user
        );
    }

    public function adminAccess(User $user, array $context = []): void
    {
        $this->record(
            event: 'admin_access',
            user: $user,
            description: "Admin access for \"{$user->email}\"",
            context: $context,
            causer: $user
        );
    }

    public function roleChanged(
        User $member,
        string $fromRole,
        string $toRole,
        array $context = [],
        ?User $actor = null
    ): void {
        $this->record(
            event: 'role_changed',
            user: $member,
            description: "Changed role for \"{$member->email}\" from {$fromRole} to {$toRole}",
            context: array_merge($context, [
                'role' => [
                    'from' => $fromRole,
                    'to' => $toRole,
                ],
            ]),
            causer: $actor
        );
    }

    public function permissionOverrideGranted(
        User $member,
        string $permission,
        array $context = [],
        ?User $actor = null
    ): void {
        $this->record(
            event: 'permission_override_granted',
            user: $member,
            description: "Granted {$permission} override for \"{$member->email}\"",
            context: array_merge($context, [
                'permission' => $permission,
            ]),
            causer: $actor
        );
    }

    public function permissionOverrideRevoked(
        User $member,
        string $permission,
        array $context = [],
        ?User $actor = null
    ): void {
        $this->record(
            event: 'permission_override_revoked',
            user: $member,
            description: "Revoked {$permission} override for \"{$member->email}\"",
            context: array_merge($context, [
                'permission' => $permission,
            ]),
            causer: $actor
        );
    }

    protected function record(
        string $event,
        ?User $user,
        string $description,
        array $context = [],
        ?User $causer = null
    ): void {
        $logger = activity('user')->event($event)->withProperties($context);

        if ($user) {
            $logger->performedOn($user);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->log($description);
    }
}
