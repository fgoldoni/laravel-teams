# Goldoni Laravel Teams

Team management for Laravel 12 with Livewire (Volt) + Flux UI. Ships as a reusable package with ULID routing, rich actions, events, policies, and optional UI components.

* PHP 8.4+
* Laravel 12.x
* Namespaces under `Goldoni\LaravelTeams\...`
* Primary keys are `id` (bigint auto-increment) with an extra `ulid` column on every model
* Route-model binding uses the `ulid` via a shared trait
* Pivot is materialized as `TeamUser` with enum roles

---

## Table of contents

1. [Features](#features)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Database](#database)
5. [Models & Traits](#models--traits)
6. [Enums](#enums)
7. [Policies & Authorization](#policies--authorization)
8. [Actions (Service Layer)](#actions-service-layer)
9. [Events & Notifications](#events--notifications)
10. [UI (Livewire Volt + Flux UI)](#ui-livewire-volt--flux-ui)
11. [Facades & Helpers](#facades--helpers)
12. [Factories & Seeding](#factories--seeding)
13. [Testing (Pest)](#testing-pest)
14. [Troubleshooting](#troubleshooting)
15. [License](#license)

---

## Features

* Teams and memberships (owner/admin/member/viewer)
* ULID-based routing for all models
* Solid service actions with DB transactions and domain exceptions
* Complete event stream for Teams and Members
* Policy-driven authorization, ready for Spatie Permission integration
* Optional Livewire Volt + Flux UI screens and components
* Factories, seeders, and Pest tests scaffold
* Publishable config and migrations

---

## Installation

```bash
composer require goldoni/laravel-teams
```

The service provider auto-registers via Laravel package discovery.

Publish the config and optional user migration stub:

```bash
php artisan vendor:publish --tag=teams-config
php artisan vendor:publish --tag=teams-migrations
```

Run migrations:

```bash
php artisan migrate
```

---

## Configuration

`config/teams.php` is publishable and includes:

* `roles`: mapping of enum keys to display labels
* `default_role`: default role for new members
* `max_teams_per_user`: 0 = unlimited
* `invite_notifications`: enable/disable invite notifications
* `super_admin_role`: optional role name for Gate::before short-circuit

Example:

```php
return [
    'roles' => [
        'OWNER'  => 'Owner',
        'ADMIN'  => 'Admin',
        'MEMBER' => 'Member',
        'VIEWER' => 'Viewer',
    ],
    'default_role'         => 'MEMBER',
    'max_teams_per_user'   => 0,
    'invite_notifications' => true,
    'super_admin_role'     => 'Super Admin',
];
```

---

## Database

### Tables

* `teams`

    * `id` bigint PK
    * `ulid` char(26) unique
    * `name` string(255)
    * `owner_id` bigint FK → users(id), cascade on delete
    * `timestamps`, `softDeletes`

* `team_user` (materialized pivot via `TeamUser`)

    * `id` bigint PK
    * `ulid` char(26) unique
    * `team_id` bigint FK → teams(id) cascade on delete
    * `user_id` bigint FK → users(id) cascade on delete
    * `role` string(32)
    * `timestamps`, `softDeletes`
    * unique index (`team_id`, `user_id`)

### Users table integration

The package provides a publishable migration stub to add `current_team_id` to `users` referencing `teams(id)`.

---

## Models & Traits

### HasExtraUlid

Applied to every package model to auto-assign `ulid` and use it for route key.

```php
use Goldoni\LaravelTeams\Concerns\HasExtraUlid;
```

### Team

```php
namespace Goldoni\LaravelTeams\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['ulid','name','owner_id'];
}
```

Relations:

* `owner(): BelongsTo`
* `users(): BelongsToMany` through `team_user` with pivot attributes
* `memberships(): HasMany` to `TeamUser`

### TeamUser

```php
namespace Goldoni\LaravelTeams\Models;

use Illuminate\Database\Eloquent\Model;
use Goldoni\LaravelTeams\Enums\TeamRoleEnum;

class TeamUser extends Model
{
    protected $table = 'team_user';
    protected $fillable = ['ulid','team_id','user_id','role'];

    protected function casts(): array
    {
        return ['role' => TeamRoleEnum::class];
    }
}
```

Convenience:

* `isOwner()`, `isAdmin()`, `isMember()`, `isViewer()`
* `scopeForUser(int $userId)`

### User integration

Add the trait to your `App\Models\User`:

```php
use Goldoni\LaravelTeams\Concerns\HasTeams;

class User extends Authenticatable
{
    use HasTeams;
}
```

Trait capabilities:

* `teams()`, `ownedTeams()`, `currentTeam()`
* `belongsToTeam(Team $team)`
* `switchTeam(Team $team)`
* `isOnTeam(Team $team)`
* `ownsTeam(Team $team)`
* `isCurrentTeam(Team $team)`
* `allTeams()`

It also supports a fallback to the oldest owned team or membership when `current_team_id` is null.

---

## Enums

```php
enum TeamRoleEnum: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';
}
```

Helpers are provided on the User for role checks, including:

* `teamRole(Team $team): ?TeamRoleEnum`
* `hasTeamRole(Team $team, TeamRoleEnum|string $role): bool`
* `hasAnyTeamRole(Team $team, array $roles): bool`
* `hasTeamRoleOwner/Admin/Member/Viewer(Team $team): bool`
* `hasTeamRoleAtLeast(Team $team, TeamRoleEnum $min): bool`
  OWNER ≥ ADMIN ≥ MEMBER ≥ VIEWER

---

## Policies & Authorization

The package registers:

```php
Gate::policy(\Goldoni\LaravelTeams\Models\Team::class, \Goldoni\LaravelTeams\Policies\TeamPolicy::class);
Gate::before(fn ($user, $ability) => $user->hasRole(config('teams.super_admin_role')) ? true : null);
```

`TeamPolicy` supports:

* `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
* `manageMembers`
* Extended abilities: `transferOwnership`, `leave`, `invite`, `acceptInvite`, `declineInvite`

If you use Spatie Permission, ensure your guard is consistent (`web` by default) and that roles/permissions exist.

---

## Actions (Service Layer)

All actions are transactional and throw domain exceptions. They emit events after commit.

* `CreateTeam(Authenticatable $owner, string $name): Team`
* `UpdateTeam(Team $team, string $name): Team`
* `DeleteTeam(Team $team): void`
* `AddTeamMember(Team $team, Model $user, TeamRoleEnum $role = MEMBER): TeamUser`
* `RemoveTeamMember(Team $team, Model $user): void`
* `ChangeTeamMemberRole(Team $team, Model $user, TeamRoleEnum $role): void`
* `TransferOwnership(Team $team, Model $newOwner): void`
* `SwitchTeam(Authenticatable $user, Team $team): void`
* `LeaveTeam(Team $team, Authenticatable $user): void`
* `InviteTeamMember(Team $team, Model $invitee, TeamRoleEnum $role = MEMBER): void`
* `AcceptInvite(Team $team, Model $user, TeamRoleEnum $role = MEMBER): void`
* `DeclineInvite(Team $team, Model $user): void`

### Usage examples

Create a team:

```php
$team = app(\Goldoni\LaravelTeams\Actions\CreateTeam::class)->handle($user, "{$user->name}'s Team");
```

Add a member:

```php
app(\Goldoni\LaravelTeams\Actions\AddTeamMember::class)
    ->handle($team, $anotherUser, \Goldoni\LaravelTeams\Enums\TeamRoleEnum::ADMIN);
```

Transfer ownership:

```php
app(\Goldoni\LaravelTeams\Actions\TransferOwnership::class)->handle($team, $newOwnerUser);
```

Switch team:

```php
app(\Goldoni\LaravelTeams\Actions\SwitchTeam::class)->handle($user, $team);
```

---

## Events & Notifications

### Events

* `TeamCreated`
* `TeamDeleted`
* `MemberAdded`
* `MemberRemoved`
* `MemberRoleChanged`
* `MemberInvited`
* `InviteAccepted`
* `InviteDeclined`
* `TeamOwnershipTransferred`

Payloads use the real models and primitive identifiers consistently: team model, user model, role string/enum, and owner ids when relevant.

### Notifications

* `Notifications\MemberAdded` sent when `teams.invite_notifications` is true

---

## UI (Livewire Volt + Flux UI)

Optional Volt pages and components can be included under `resources/views`:

* `teams.index`: list of teams with quick actions
* `teams.create`: create form
* `teams.members`: manage members with role select and removal
* `teams.switch`: switcher dropdown component
* Header component to show current team

Use Flux UI components for inputs and actions.

---

## Facades & Helpers

A minimal `Teams` facade can expose:

* `current(): ?Team`
* `forUser(User $user): \Illuminate\Support\Collection`
* `isOwner(User $user, Team $team): bool`

Example:

```php
use Goldoni\LaravelTeams\Facades\Teams;

$current = Teams::current();
$mine = Teams::forUser($user);
$owner = Teams::isOwner($user, $team);
```

---

## Factories & Seeding

Factories are included for `Team` and `TeamUser`. Typical seeding flow:

```php
use App\Models\User;
use Goldoni\LaravelTeams\Actions\CreateTeam;

$user = User::factory()->create();
$team = app(CreateTeam::class)->handle($user, "{$user->name}'s Team");
```

If you assign roles via Spatie, ensure the role guard matches your user guard and that cache is cleared after sync.

---

## Testing (Pest)

* Unit and feature tests for migrations, relations, actions, and policies
* Use factories and enums in tests
* Example:

```php
use Goldoni\LaravelTeams\Actions\CreateTeam;
use App\Models\User;

it('creates a team and sets current_team_id', function () {
    $owner = User::factory()->create();
    $team = app(CreateTeam::class)->handle($owner, 'My Team');
    expect($owner->current_team_id)->toBe($team->id);
});
```

Run:

```bash
php artisan test
```

---

## Troubleshooting

### Guard mismatch

If you see `GuardDoesNotMatch`, confirm:

* `config('model-permissions.guard_name')` equals your auth guard (`web`)
* Roles and permissions use the same guard
* Clear permission cache when syncing roles/permissions

### Authorization denied

Confirm your policies are registered and that the user has the required permission or team role. Owners/Admins can be allowed to manage members regardless of Spatie permissions via `TeamPolicy::manageMembers`.

### ULID routing

All package models use ULID route keys. Ensure your routes type-hint the model and that you don’t override `getRouteKeyName()`.

---

## License

MIT © Goldoni.
