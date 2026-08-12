<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case AdminLembaga = 'admin_lembaga';
    case AdminPembayaran = 'admin_pembayaran';
    case Ustaz = 'ustaz';
    case Member = 'member';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::AdminLembaga => 'Admin Lembaga',
            self::AdminPembayaran => 'Admin Pembayaran',
            self::Ustaz => 'Ustaz',
            self::Member => 'Member',
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::ManagePosts,
                TeamPermission::ManageCategories,
                TeamPermission::ManageSantri,
                TeamPermission::ManageLembaga,
                TeamPermission::ManageMonitoring,
                TeamPermission::ManageSetoranHafalan,
                TeamPermission::ManagePembayaran,
                TeamPermission::ManageSarpras,
                TeamPermission::ManageWali,
            ],
            self::AdminLembaga => [
                TeamPermission::ManagePosts,
                TeamPermission::ManageCategories,
                TeamPermission::ManageSantri,
                TeamPermission::ManageLembaga,
                TeamPermission::ManageMonitoring,
                TeamPermission::ManageSetoranHafalan,
                TeamPermission::ManagePembayaran,
                TeamPermission::ManageSarpras,
                TeamPermission::ManageWali,
            ],
            self::AdminPembayaran => [
                TeamPermission::ManagePembayaran,
            ],
            self::Ustaz => [
                TeamPermission::ManagePosts,
                TeamPermission::ManageCategories,
                TeamPermission::ManageSetoranHafalan,
            ],
            self::Member => [],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 4,
            self::Admin => 3,
            self::AdminLembaga => 2,
            self::AdminPembayaran => 2,
            self::Ustaz => 1,
            self::Member => 0,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
