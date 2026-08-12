import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    CalendarDays,
    FileText,
    Newspaper,
    Tags,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { edit as lembagaEdit } from '@/routes/admin/lembaga';
import { edit as postEdit, index as postsIndex } from '@/routes/admin/posts';
import {
    edit as santriEdit,
    index as santriIndex,
} from '@/routes/admin/santri';
import { index as setoranIndex } from '@/routes/admin/setoran';
import type {
    AdminTeam,
    DashboardInvitation,
    DashboardRecentPost,
    DashboardRecentSantri,
    DashboardRecentSetoran,
    DashboardStats,
    TeamPermissions,
} from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    stats: DashboardStats;
    recentPosts: DashboardRecentPost[];
    recentSantri: DashboardRecentSantri[];
    recentSetorans?: DashboardRecentSetoran[];
    isAggregate?: boolean;
    team: AdminTeam;
};

const postStatusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
};

const santriStatusVariant: Record<string, 'default' | 'secondary' | 'outline'> =
    {
        aktif: 'default',
        alumni: 'secondary',
        keluar: 'outline',
    };

function StatCard({
    icon: Icon,
    label,
    value,
    href,
}: {
    icon: LucideIcon;
    label: string;
    value: number;
    href?: RouteDefinition<'get'>;
}) {
    const className =
        'group flex flex-col gap-2 rounded-xl border bg-card p-5 shadow-xs transition-colors hover:border-emerald-300';

    const inner = (
        <>
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">
                    {label}
                </span>
                <span className="flex size-9 items-center justify-center rounded-lg bg-muted">
                    <Icon className="size-4" />
                </span>
            </div>
            <div className="flex items-end justify-between">
                <span className="text-3xl font-semibold tracking-tight">
                    {value}
                </span>
                {href ? (
                    <ArrowUpRight className="size-4 text-muted-foreground transition-colors group-hover:text-emerald-600" />
                ) : null}
            </div>
        </>
    );

    if (href) {
        return (
            <Link href={href} className={className}>
                {inner}
            </Link>
        );
    }

    return <div className={className}>{inner}</div>;
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
        new Date(value),
    );
}

export default function Dashboard({
    pendingInvitations = [],
    stats,
    recentPosts,
    recentSantri,
    recentSetorans = [],
    isAggregate = false,
    team,
}: Props) {
    const page = usePage();
    const { auth, teamPermissions } = page.props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const permissions = (teamPermissions ?? {}) as Partial<TeamPermissions>;

    const statCards: {
        icon: LucideIcon;
        label: string;
        value: number;
        href?: RouteDefinition<'get'>;
    }[] = [
        ...(permissions.canManagePosts
            ? [
                  {
                      icon: Newspaper,
                      label: 'Total Berita',
                      value: stats.posts,
                      href:
                          !isAggregate && permissions.canManagePosts
                              ? postsIndex(team.slug)
                              : undefined,
                  },
              ]
            : []),
        ...(permissions.canManagePosts
            ? [
                  {
                      icon: FileText,
                      label: 'Berita Terbit',
                      value: stats.published_posts,
                      href:
                          !isAggregate && permissions.canManagePosts
                              ? postsIndex(team.slug)
                              : undefined,
                  },
              ]
            : []),
        ...(permissions.canManageSantri
            ? [
                  {
                      icon: Users,
                      label: 'Santri Aktif',
                      value: stats.active_santri,
                      href:
                          !isAggregate && permissions.canManageSantri
                              ? santriIndex(team.slug)
                              : undefined,
                  },
              ]
            : []),
        ...(permissions.canManageCategories
            ? [
                  {
                      icon: Tags,
                      label: 'Kategori',
                      value: stats.categories,
                      href:
                          !isAggregate && permissions.canManageCategories
                              ? categoriesIndex(team.slug)
                              : undefined,
                  },
              ]
            : []),
        ...(permissions.canManageSetoranHafalan
            ? [
                  {
                      icon: BookOpen,
                      label: 'Total Setoran',
                      value: stats.setoran,
                      href:
                          !isAggregate && permissions.canManageSetoranHafalan
                              ? setoranIndex(team.slug)
                              : undefined,
                  },
                  {
                      icon: CalendarDays,
                      label: 'Setoran Bulan Ini',
                      value: stats.setoran_bulan_ini,
                      href:
                          !isAggregate && permissions.canManageSetoranHafalan
                              ? setoranIndex(team.slug)
                              : undefined,
                  },
              ]
            : []),
    ];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <Heading
                title={`Selamat datang, ${auth.user.name}!`}
                description={
                    isAggregate
                        ? 'Ringkasan aktivitas seluruh lembaga.'
                        : `Ringkasan aktivitas ${team.name}.`
                }
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {statCards.map((card) => (
                    <StatCard key={card.label} {...card} />
                ))}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <CardTitle>Berita Terbaru</CardTitle>
                                <CardDescription>
                                    Artikel terakhir yang ditulis di lembaga
                                    ini.
                                </CardDescription>
                            </div>
                            {permissions.canManagePosts && !isAggregate ? (
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={postsIndex(team.slug)}>
                                        Lihat semua
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {recentPosts.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Belum ada berita.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {recentPosts.map((post) => (
                                    <li key={post.id}>
                                        <Link
                                            href={
                                                permissions.canManagePosts
                                                    ? postEdit([
                                                          post.team.slug,
                                                          post.slug,
                                                      ])
                                                    : postsIndex(post.team.slug)
                                            }
                                            className="flex items-center justify-between gap-3 py-3 transition-colors hover:bg-accent/50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {post.title}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {post.category?.name ??
                                                        'Tanpa kategori'}
                                                    {isAggregate
                                                        ? ` · ${post.team.name}`
                                                        : ''}{' '}
                                                    ·{' '}
                                                    {formatDate(
                                                        post.published_at,
                                                    )}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    postStatusVariant[
                                                        post.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {post.status_label}
                                            </Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <CardTitle>Santri Terbaru</CardTitle>
                                <CardDescription>
                                    Santri terakhir yang terdaftar di lembaga
                                    ini.
                                </CardDescription>
                            </div>
                            {permissions.canManageSantri && !isAggregate ? (
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={santriIndex(team.slug)}>
                                        Lihat semua
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {recentSantri.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Belum ada santri.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {recentSantri.map((santri) => (
                                    <li key={santri.id}>
                                        <Link
                                            href={
                                                permissions.canManageSantri
                                                    ? santriEdit([
                                                          santri.team.slug,
                                                          santri.id,
                                                      ])
                                                    : santriIndex(
                                                          santri.team.slug,
                                                      )
                                            }
                                            className="flex items-center justify-between gap-3 py-3 transition-colors hover:bg-accent/50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {santri.name}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    NIS {santri.nis}
                                                    {isAggregate
                                                        ? ` · ${santri.team.name}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    santriStatusVariant[
                                                        santri.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {santri.status_label}
                                            </Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {permissions.canManageSetoranHafalan ? (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <CardTitle>Setoran Terbaru</CardTitle>
                                    <CardDescription>
                                        Setoran hafalan terakhir yang tercatat
                                        {isAggregate
                                            ? ' di seluruh lembaga.'
                                            : ' di lembaga ini.'}
                                    </CardDescription>
                                </div>
                                {!isAggregate ? (
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <Link
                                            href={setoranIndex(team.slug)}
                                        >
                                            Buka setoran
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {recentSetorans.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    Belum ada setoran hafalan.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {recentSetorans.map((setoran) => (
                                        <li
                                            key={setoran.id}
                                            className="flex items-center justify-between gap-3 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {setoran.santri.name}
                                                </p>
                                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                    {setoran.surat}
                                                    {setoran.ayat_from
                                                        ? ` ayat ${setoran.ayat_from}${setoran.ayat_to && setoran.ayat_to !== setoran.ayat_from ? `–${setoran.ayat_to}` : ''}`
                                                        : ''}
                                                    {setoran.juz
                                                        ? ` (Juz ${setoran.juz})`
                                                        : ''}
                                                    {isAggregate
                                                        ? ` · ${setoran.team.name}`
                                                        : ''}{' '}
                                                    · {formatDate(setoran.tanggal)}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-1">
                                                {setoran.kategori ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        {setoran.kategori.nama}
                                                    </span>
                                                ) : null}
                                                <Badge variant="secondary">
                                                    {setoran.type_label}
                                                </Badge>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                ) : null}
            </div>

            {permissions.canManageLembaga ? (
                <div className="mt-6">
                    <Card className="bg-muted/40">
                        <CardContent className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <p className="font-medium">Profil lembaga</p>
                                <p className="text-sm text-muted-foreground">
                                    Perbarui informasi dan kontak {team.name}.
                                </p>
                            </div>
                            <Button asChild>
                                <Link href={lembagaEdit(team.slug)}>
                                    Kelola profil
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            ) : null}
        </>
    );
}

Dashboard.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(props.team.slug),
        },
    ],
});
