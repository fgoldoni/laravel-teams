<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Console\Commands;

use BackedEnum;
use Closure;
use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\InviteAccepted;
use Goldoni\LaravelTeams\Events\InviteDeclined;
use Goldoni\LaravelTeams\Events\MemberAdded;
use Goldoni\LaravelTeams\Events\MemberInvited;
use Goldoni\LaravelTeams\Events\MemberRemoved;
use Goldoni\LaravelTeams\Events\MemberRoleChanged;
use Goldoni\LaravelTeams\Events\TeamCreated;
use Goldoni\LaravelTeams\Events\TeamDeleted;
use Goldoni\LaravelTeams\Events\TeamOwnershipTransferred;
use Goldoni\LaravelTeams\Events\TeamsHealthCheckFailed;
use Goldoni\LaravelTeams\Events\TeamsHealthCheckPassed;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

final class TeamsHealthCheckCommand extends Command
{
    protected $signature = 'teams:health {--json : Output as JSON}';

    protected $description = 'Validate Goldoni Laravel Teams setup';

    public function handle(): int
    {
        $results = collect();

        $results->push($this->check('php.version', fn (): bool => PHP_VERSION_ID >= 80400, PHP_VERSION));
        $results->push($this->check('laravel.version', fn (): bool => version_compare(App::version(), '12.0.0', '>='), App::version()));
        $results->push($this->check('config.teams.loaded', fn (): bool => is_array(Config::get('teams')), (string) json_encode(Config::get('teams'), JSON_UNESCAPED_SLASHES)));
        $results->push($this->check('config.teams.roles', fn (): bool => $this->hasTeamRoles(), (string) json_encode(Config::get('teams.roles'))));
        $results->push($this->check('config.teams.super_admin_role', fn (): bool => is_string(Config::get('teams.super_admin_role')) && Config::get('teams.super_admin_role') !== '', (string) Config::get('teams.super_admin_role')));
        $results->push($this->check('db.table.teams', fn () => Schema::hasTable('teams')));
        $results->push($this->check('db.table.team_user', fn () => Schema::hasTable('team_user')));
        $results->push($this->check('db.column.users.current_team_id', fn () => Schema::hasColumn('users', 'current_team_id')));
        $results->push($this->check('db.columns.teams', fn () => Schema::hasColumns('teams', ['id', 'ulid', 'name', 'owner_id'])));
        $results->push($this->check('db.columns.team_user', fn () => Schema::hasColumns('team_user', ['id', 'ulid', 'team_id', 'user_id', 'role'])));
        $results->push($this->check('models.team.ulidRoute', function (): bool {
            $teamClass = ResolveModel::team();

            return (new $teamClass)->getRouteKeyName() === 'ulid';
        }, function (): string {
            $teamClass = ResolveModel::team();

            return (new $teamClass)->getRouteKeyName();
        }));
        $results->push($this->check('models.teamuser.casts.roleEnum', fn (): bool => $this->teamUserRoleIsEnum()));
        $results->push($this->check('policy.team.registered', function (): bool {
            $policy = Gate::getPolicyFor(ResolveModel::team());

            if (is_object($policy)) {
                return true;
            }

            return is_string($policy) && class_exists($policy);
        }, $this->policyDetail()));
        $results->push($this->check('gate.before.super_admin', fn (): bool => $this->gateBeforeActive()));
        $results->push($this->check('events.TeamCreated', fn (): bool => class_exists(TeamCreated::class)));
        $results->push($this->check('events.TeamDeleted', fn (): bool => class_exists(TeamDeleted::class)));
        $results->push($this->check('events.MemberAdded', fn (): bool => class_exists(MemberAdded::class)));
        $results->push($this->check('events.MemberRemoved', fn (): bool => class_exists(MemberRemoved::class)));
        $results->push($this->check('events.MemberRoleChanged', fn (): bool => class_exists(MemberRoleChanged::class)));
        $results->push($this->check('events.MemberInvited', fn (): bool => class_exists(MemberInvited::class)));
        $results->push($this->check('events.InviteAccepted', fn (): bool => class_exists(InviteAccepted::class)));
        $results->push($this->check('events.InviteDeclined', fn (): bool => class_exists(InviteDeclined::class)));
        $results->push($this->check('events.TeamOwnershipTransferred', fn (): bool => class_exists(TeamOwnershipTransferred::class)));
        $results->push($this->check('indexes.teams.ulid.unique', fn (): bool => $this->hasUniqueIndex('teams', ['ulid'])));
        $results->push($this->check('indexes.team_user.ulid.unique', fn (): bool => $this->hasUniqueIndex('team_user', ['ulid'])));
        $results->push($this->check('indexes.team_user.team_user.unique', fn (): bool => $this->hasUniqueIndex('team_user', ['team_id', 'user_id'])));

        $spatieInstalled = class_exists(PermissionRegistrar::class);

        if ($spatieInstalled) {
            $results->push($this->check('spatie.guard', fn (): bool => is_string(Config::get('model-permissions.guard_name')) && Config::get('model-permissions.guard_name') !== '', (string) Config::get('model-permissions.guard_name')));
        }

        $allOk = $results->every(fn (array $r): bool => $r['status'] === 'ok');

        if ($allOk) {
            Event::dispatch(new TeamsHealthCheckPassed($results->toArray()));
        } else {
            Event::dispatch(new TeamsHealthCheckFailed($results->toArray()));
        }

        if ($this->option('json')) {
            $this->line(json_encode(['ok' => $allOk, 'checks' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Check', 'Status', 'Detail'], $results->map(fn ($r): array => [$r['key'], $r['status'], (string) ($r['detail'] ?? '')]));
            $this->line($allOk ? '<info>OK</info>' : '<error>FAIL</error>');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $key, callable $fn, mixed $detail = null): array
    {
        try {
            $passed = (bool) value($fn);

            if ($detail instanceof Closure) {
                $detail = value($detail);
            }

            return ['key' => $key, 'status' => $passed ? 'ok' : 'fail', 'detail' => $detail];
        } catch (Throwable $throwable) {
            return ['key' => $key, 'status' => 'error', 'detail' => $throwable->getMessage()];
        }
    }

    private function hasTeamRoles(): bool
    {
        $roles = Config::get('teams.roles');

        if (! is_array($roles)) {
            return false;
        }

        $keys = array_keys($roles);

        return in_array(TeamRoleEnum::OWNER->name, $keys, true)
            && in_array(TeamRoleEnum::ADMIN->name, $keys, true)
            && in_array(TeamRoleEnum::MEMBER->name, $keys, true)
            && in_array(TeamRoleEnum::VIEWER->name, $keys, true);
    }

    private function teamUserRoleIsEnum(): bool
    {
        $teamUserClass = ResolveModel::teamUser();
        $casts         = (new $teamUserClass)->getCasts();
        $cast          = $casts['role'] ?? null;

        if ($cast instanceof BackedEnum) {
            return true;
        }

        if (is_string($cast)) {
            return str_contains($cast, TeamRoleEnum::class);
        }

        return false;
    }

    private function gateBeforeActive(): bool
    {
        $role = Config::get('teams.super_admin_role');

        return is_string($role) && $role !== '';
    }

    private function hasUniqueIndex(string $table, array $columns): bool
    {
        try {
            $conn = DB::connection();

            if (! method_exists($conn, 'getDoctrineSchemaManager')) {
                return true;
            }

            $schema  = $conn->getDoctrineSchemaManager();
            $indexes = $schema->listTableIndexes($table);

            foreach ($indexes as $index) {
                if ($index->isUnique() && $this->sameColumns($index->getColumns(), $columns)) {
                    return true;
                }
            }

            return false;
        } catch (Throwable) {
            return true;
        }
    }

    private function sameColumns(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }

    private function policyDetail(): string
    {
        $policy = Gate::getPolicyFor(ResolveModel::team());

        if (is_object($policy)) {
            return $policy::class;
        }

        if (is_string($policy)) {
            return $policy;
        }

        return 'null';
    }
}
