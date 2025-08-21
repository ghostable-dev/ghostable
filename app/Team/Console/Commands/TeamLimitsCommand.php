<?php

namespace App\Team\Console\Commands;

use App\Team\Models\Team;
use Illuminate\Console\Command;

class TeamLimitsCommand extends Command
{
    protected $signature = 'ghostable:limits {action : show|set|clear} {team_id} {--projects=} {--envs=}';

    protected $description = 'Inspect or update team limits.';

    public function handle(): int
    {
        $team = Team::findOrFail($this->argument('team_id'));
        $action = $this->argument('action');

        $data = $team->getRawOriginal('limits');
        $json = $data ? json_decode($data, true) : [];

        switch ($action) {
            case 'show':
                $limits = $team->limits;
                $this->line('kind: '.$limits->kind);
                $this->line('projects: '.var_export($limits->projects, true));
                $this->line('environments_per_project: '.var_export($limits->environments_per_project, true));
                return self::SUCCESS;
            case 'set':
                $projects = $this->option('projects');
                $envs = $this->option('envs');
                if ($projects !== null) {
                    $json['projects'] = (int) $projects;
                }
                if ($envs !== null) {
                    $json['environments_per_project'] = (int) $envs;
                }
                $json['kind'] = $json['kind'] ?? ($team->isPersonal() ? 'personal' : 'org');
                $team->update(['limits' => $json]);
                $this->info('Limits updated.');
                return self::SUCCESS;
            case 'clear':
                $team->update(['limits' => []]);
                $this->info('Limits cleared.');
                return self::SUCCESS;
        }

        $this->error('Invalid action.');
        return self::INVALID;
    }
}
