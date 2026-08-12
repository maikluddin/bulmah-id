<?php

namespace App\Data;

readonly class TeamPermissions
{
    public function __construct(
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
        public bool $canManagePosts,
        public bool $canManageCategories,
        public bool $canManageSantri,
        public bool $canManageLembaga,
        public bool $canManageMonitoring,
        public bool $canManageSetoranHafalan,
        public bool $canManagePembayaran,
        public bool $canManageSarpras,
        public bool $canManageWali,
        public bool $canAccessWaliPortal,
    ) {
        //
    }
}
