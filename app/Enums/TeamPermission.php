<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ManagePosts = 'content:posts';
    case ManageCategories = 'content:categories';
    case ManageSantri = 'content:santri';
    case ManageLembaga = 'content:lembaga';
    case ManageMonitoring = 'content:monitoring';
    case ManageSetoranHafalan = 'content:setoran-hafalan';
    case ManagePembayaran = 'content:pembayaran';
    case ManageSarpras = 'content:sarpras';
    case ManageWali = 'content:wali';
}
