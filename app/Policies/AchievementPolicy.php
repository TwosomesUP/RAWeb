<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,

            /*
             * moderators may remove unfit content from achievements
             */
            Role::MODERATOR,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            Role::ARTIST,

            /*
             * writers may update achievement title and description if the respective achievements are open for editing
             */
            Role::WRITER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Achievement $achievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function update(User $user, Achievement $achievement): bool
    {
        $canEditAnyAchievement = $user->hasAnyRole([
            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            // Role::ARTIST,

            /*
             * writers may update achievement title and description if the respective achievements are open for editing
             */
            Role::WRITER,
        ]);

        if ($canEditAnyAchievement) {
            return true;
        }

        // Junior Developers have additional specific criteria that must be satisfied
        // before they are allowed to edit achievement fields.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $this->juniorDeveloperCanUpdate($user, $achievement);
        }

        return false;
    }

    private function juniorDeveloperCanUpdate(User $user, Achievement $achievement): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game and the achievement must not be promoted to Core/Official.
        return !$achievement->is_published && $user->hasActiveClaimOnGameId($achievement->game->id);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        if ($achievement->is_published || $achievement->unlocks_total) {
            return false;
        }

        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function restore(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function forceDelete(User $user, Achievement $achievement): bool
    {
        // TODO allow GAME_HASH_MANAGER to force delete any achievement
        return false;
    }

    public function updateField(User $user, ?Achievement $achievement, string $fieldName): bool
    {
        $roleFieldPermissions = [
            Role::DEVELOPER_JUNIOR => ['Title', 'Description', 'type', 'Points', 'DisplayOrder'],
            Role::DEVELOPER => ['Title', 'Description', 'Flags', 'type', 'Points', 'DisplayOrder'],
            Role::WRITER => ['Title', 'Description'],
        ];

        // Root can edit everything.
        if ($user->hasRole(Role::ROOT)) {
            return true;
        }

        $userRoles = $user->getRoleNames();

        // Aggregate the allowed fields for all roles the user has.
        $allowedFieldsForUser = collect($roleFieldPermissions)
            ->filter(function ($fields, $role) use ($userRoles, $user, $achievement) {
                if (!$userRoles->contains($role)) {
                    return false;
                }

                // Junior Developers have additional specific criteria that must be satisfied
                // before they are allowed to edit achievement fields.
                if ($role === Role::DEVELOPER_JUNIOR) {
                    return isset($achievement) && $this->juniorDeveloperCanUpdate($user, $achievement);
                }

                return true;
            })
            ->collapse()
            ->unique()
            ->all();

        // If any of the user's roles allow updating the specified field, return true.
        // Otherwise, they can't edit the field.
        if (in_array($fieldName, $allowedFieldsForUser, true)) {
            return true;
        }

        if ($user->hasRole(Role::EVENT_MANAGER) && isset($achievement)) {
            if ($achievement->game->ConsoleID === System::Events) {
                return true;
            }
        }

        return false;
    }

    public function assignMaintainer(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEV_COMPLIANCE,
        ]);
    }
}
