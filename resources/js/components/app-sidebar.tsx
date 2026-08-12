import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BellRing,
    BookOpen,
    Building2,
    ClipboardList,
    ExternalLink,
    HeartHandshake,
    Layers,
    LayoutGrid,
    Newspaper,
    Package,
    School,
    Tags,
    UserPlus,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as kategoriHafalanIndex } from '@/routes/admin/hafalan-kategori';
import { index as kelasIndex } from '@/routes/admin/kelas';
import { index as keuanganIndex } from '@/routes/admin/keuangan';
import { setoran as laporanSetoranIndex } from '@/routes/admin/laporan';
import { index as lembagaIndex } from '@/routes/admin/lembaga';
import { index as pendaftaranIndex } from '@/routes/admin/pendaftaran';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as santriIndex } from '@/routes/admin/santri';
import { index as sarprasIndex } from '@/routes/admin/sarpras';
import { index as setoranIndex } from '@/routes/admin/setoran';
import { index as adminWaliIndex } from '@/routes/admin/wali';
import { index as waliIndex } from '@/routes/wali';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const teamSlug = page.props.currentTeam?.slug;
    const permissions = page.props.teamPermissions;
    const dashboardUrl = teamSlug ? dashboard(teamSlug) : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(permissions?.canManagePosts && teamSlug
            ? [
                  {
                      title: 'Berita',
                      href: postsIndex(teamSlug),
                      icon: Newspaper,
                  },
              ]
            : []),
        ...(permissions?.canManageCategories && teamSlug
            ? [
                  {
                      title: 'Kategori',
                      href: categoriesIndex(teamSlug),
                      icon: Tags,
                  },
              ]
            : []),
        ...(permissions?.canManageSantri && teamSlug
            ? [
                  {
                      title: 'Santri',
                      href: santriIndex(teamSlug),
                      icon: Users,
                  },
                  {
                      title: 'Pendaftaran',
                      href: pendaftaranIndex(teamSlug),
                      icon: UserPlus,
                  },
                  {
                      title: 'Kelas',
                      href: kelasIndex(teamSlug),
                      icon: School,
                  },
              ]
            : []),
        ...(permissions?.canManageWali && teamSlug
            ? [
                  {
                      title: 'Wali Santri',
                      href: adminWaliIndex(teamSlug),
                      icon: HeartHandshake,
                  },
              ]
            : []),
        ...(permissions?.canManageMonitoring && teamSlug
            ? [
                  {
                      title: 'Monitoring',
                      href: santriIndex(teamSlug),
                      icon: Activity,
                  },
                  {
                      title: 'Kategori Hafalan',
                      href: kategoriHafalanIndex(teamSlug),
                      icon: Layers,
                  },
                  {
                      title: 'Laporan Setoran',
                      href: laporanSetoranIndex(teamSlug),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(permissions?.canManageSetoranHafalan && teamSlug
            ? [
                  {
                      title: 'Setoran Hafalan',
                      href: setoranIndex(teamSlug),
                      icon: BookOpen,
                  },
              ]
            : []),
        ...(permissions?.canManagePembayaran && teamSlug
            ? [
                  {
                      title: 'Keuangan',
                      href: keuanganIndex(teamSlug),
                      icon: Wallet,
                  },
              ]
            : []),
        ...(permissions?.canManageSarpras && teamSlug
            ? [
                  {
                      title: 'E-Sarpras',
                      href: sarprasIndex(teamSlug),
                      icon: Package,
                  },
              ]
            : []),
        ...(permissions?.canManageLembaga && teamSlug
            ? [
                  {
                      title: 'Lembaga',
                      href: lembagaIndex(teamSlug),
                      icon: Building2,
                  },
              ]
            : []),
        ...(permissions?.canAccessWaliPortal && teamSlug
            ? [
                  {
                      title: 'Portal Wali',
                      href: waliIndex(teamSlug),
                      icon: BellRing,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Lihat Situs Publik',
            href: '/',
            icon: ExternalLink,
        },
    ];

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
