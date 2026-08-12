import { Head } from '@inertiajs/react';
import { BellRing, BookOpen, GraduationCap, TrendingUp } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as waliIndex } from '@/routes/wali';
import type { AdminTeam, WaliNotification, WaliSantri } from '@/types';

type Props = {
    team: AdminTeam;
    santris: WaliSantri[];
    notifications: WaliNotification[];
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function WaliIndex({ santris, notifications }: Props) {
    return (
        <>
            <Head title="Portal Wali" />

            <Heading
                title="Portal Wali"
                description="Pantau perkembangan hafalan dan setoran anak Anda."
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {santris.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center text-muted-foreground">
                                Belum ada santri yang terhubung dengan akun
                                Anda.
                            </CardContent>
                        </Card>
                    ) : (
                        santris.map((santri) => (
                            <Card key={santri.id}>
                                <CardHeader>
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <GraduationCap className="size-5" />
                                                {santri.name}
                                            </CardTitle>
                                            <CardDescription className="mt-1">
                                                NIS {santri.nis} ·{' '}
                                                {santri.gender_label}
                                                {santri.kelas
                                                    ? ` · ${santri.kelas.nama}`
                                                    : ''}
                                            </CardDescription>
                                        </div>
                                        <Badge variant="secondary">
                                            {santri.status_label}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        <div className="rounded-xl border bg-muted/40 p-3 text-center">
                                            <p className="text-xs text-muted-foreground">
                                                Total Setoran
                                            </p>
                                            <p className="text-2xl font-semibold">
                                                {santri.summary.total_hafalan}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border bg-muted/40 p-3 text-center">
                                            <p className="text-xs text-muted-foreground">
                                                Hafalan Baru
                                            </p>
                                            <p className="text-2xl font-semibold">
                                                {santri.summary.hafalan_baru}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border bg-muted/40 p-3 text-center">
                                            <p className="text-xs text-muted-foreground">
                                                Murajaah
                                            </p>
                                            <p className="text-2xl font-semibold">
                                                {santri.summary.murajaah}
                                            </p>
                                        </div>
                                        <div className="rounded-xl border bg-muted/40 p-3 text-center">
                                            <p className="text-xs text-muted-foreground">
                                                Bulan Ini
                                            </p>
                                            <p className="text-2xl font-semibold">
                                                {santri.summary.bulan_ini}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="mb-2 flex items-center gap-2 text-sm font-medium">
                                            <TrendingUp className="size-4" />
                                            Setoran Terbaru
                                        </h3>
                                        {santri.recent.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                Belum ada setoran yang
                                                tercatat.
                                            </p>
                                        ) : (
                                            <ul className="space-y-3">
                                                {santri.recent.map((record) => (
                                                    <li
                                                        key={record.id}
                                                        className="flex items-start justify-between gap-3 rounded-lg border p-3"
                                                    >
                                                        <div className="min-w-0">
                                                            <p className="font-medium">
                                                                {record.surat}
                                                                {record.juz
                                                                    ? ` (Juz ${record.juz})`
                                                                    : ''}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {formatDate(
                                                                    record.tanggal,
                                                                )}
                                                                {record.ustad
                                                                    ? ` · ${record.ustad.name}`
                                                                    : ''}
                                                            </p>
                                                        </div>
                                                        <div className="flex shrink-0 flex-col items-end gap-1">
                                                            {record.kategori ? (
                                                                <span className="text-xs text-muted-foreground">
                                                                    {
                                                                        record
                                                                            .kategori
                                                                            .nama
                                                                    }
                                                                </span>
                                                            ) : null}
                                                            <Badge variant="secondary">
                                                                {
                                                                    record.type_label
                                                                }
                                                            </Badge>
                                                        </div>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BellRing className="size-5" />
                                Notifikasi
                            </CardTitle>
                            <CardDescription>
                                Pemberitahuan setoran hafalan terbaru.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {notifications.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    Belum ada notifikasi.
                                </p>
                            ) : (
                                <ul className="space-y-4">
                                    {notifications.map((notification) => (
                                        <li
                                            key={notification.id}
                                            className="flex gap-3"
                                        >
                                            <div className="mt-1 flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted">
                                                <BookOpen className="size-4" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium">
                                                    {notification.data
                                                        .santri_name ?? 'Santri'}{' '}
                                                    setoran{' '}
                                                    {notification.data.surat}
                                                    {notification.data.juz
                                                        ? ` (Juz ${notification.data.juz})`
                                                        : ''}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {notification.created_at}
                                                    {notification.data
                                                        .ustad_name
                                                        ? ` · ${notification.data.ustad_name}`
                                                        : ''}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

WaliIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [{ title: 'Portal Wali', href: waliIndex(props.team.slug) }],
});
