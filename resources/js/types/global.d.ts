import type { Auth } from '@/types/auth';
import type { PublicSite } from '@/types/public';
import type { Team, TeamPermissions } from '@/types/teams';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            site: PublicSite | null;
            currentTeam: Team | null;
            teamPermissions: TeamPermissions | null;
            teams: Team[];
            [key: string]: unknown;
        };
    }
}
