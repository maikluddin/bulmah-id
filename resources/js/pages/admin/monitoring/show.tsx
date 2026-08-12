import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    Clock3,
    ReceiptText,
    Wallet,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index as santriIndex, monitoring } from '@/routes/admin/santri';
import { index as absensiRoute } from '@/routes/admin/santri/absensi';
import { index as hafalanRoute } from '@/routes/admin/santri/hafalan';
import { index as pembayaranRoute } from '@/routes/admin/santri/pembayaran';
import { index as perizinanRoute } from '@/routes/admin/santri/perizinan';
import { index as walletRoute } from '@/routes/admin/santri/wallet';
import type {
    AdminMonitoringKategoriProgress,
    AdminMonitoringSantri,
    AdminMonitoringSummary,
    AdminTeam,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    summary: AdminMonitoringSummary;
    kategoriProgress: AdminMonitoringKategoriProgress[];
    month: string;
};

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

export default function MonitoringShow({
    santri,
    team,
    summary,
    kategoriProgress,
}: Props) {
    const modules = [
        {
            title: 'Hafalan',
            description: `${summary.total_hafalan} total · ${summary.hafalan_bulan_ini} bulan ini`,
            icon: BookOpen,
            href: hafalanRoute([team.slug, santri.id]),
            accent: 'text-blue-600',
        },
        {
            title: 'Absensi',
            description: `${summary.total_absensi} total catatan`,
            icon: CalendarDays,
            href: absensiRoute([team.slug, santri.id]),
            accent: 'text-emerald-600',
        },
        {
            title: 'Dompet Santri',
            description: `${formatRupiah(summary.saldo)} tersimpan`,
            icon: Wallet,
            href: walletRoute([team.slug, santri.id]),
            accent: 'text-amber-600',
        },
        {
            title: 'Pembayaran',
            description: `Lunas ${formatRupiah(summary.pembayaran_lunas)}`,
            icon: ReceiptText,
            href: pembayaranRoute([team.slug, santri.id]),
            accent: 'text-violet-600',
        },
        {
            title: 'Perizinan',
            description: `${summary.perizinan_pending} menunggu persetujuan`,
            icon: Clock3,
            href: perizinanRoute([team.slug, santri.id]),
            accent: 'text-rose-600',
        },
    ];

    const attendance = [
        {
            label: 'Hadir',
            value: summary.absensi_hadir,
            className: 'text-emerald-600',
        },
        {
            label: 'Sakit',
            value: summary.absensi_sakit,
            className: 'text-amber-600',
        },
        {
            label: 'Izin',
            value: summary.absensi_izin,
            className: 'text-blue-600',
        },
        {
            label: 'Alpa',
            value: summary.absensi_alpa,
            className: 'text-rose-600',
        },
    ];

    const progressGroups = kategoriProgress.reduce<
        Record<string, AdminMonitoringKategoriProgress[]>
    >((acc, item) => {
        (acc[item.jenis] ??= []).push(item);

        return acc;
    }, {});

    return (
        <>
            <Head title={`Monitoring ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={santriIndex(team.slug)}>
                    <ChevronLeft className="size-4" />
                    Kembali ke daftar santri
                </Link>
            </Button>

            <Heading
                title={`Monitoring ${santri.name}`}
                description={`NIS ${santri.nis} · ${santri.gender_label} · ${santri.team.name}`}
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardDescription>
                            Rekap Absensi Bulan Ini
                        </CardDescription>
                        <CardTitle className="text-2xl">
                            {attendance.reduce(
                                (total, item) => total + item.value,
                                0,
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {attendance.map((item) => (
                            <Badge key={item.label} variant="secondary">
                                {item.label}:{' '}
                                <span
                                    className={`font-semibold ${item.className}`}
                                >
                                    {item.value}
                                </span>
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Total Hafalan</CardDescription>
                        <CardTitle className="text-2xl">
                            {summary.total_hafalan}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            {summary.hafalan_bulan_ini} setoran pada bulan ini.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Saldo Dompet</CardDescription>
                        <CardTitle className="text-2xl">
                            {formatRupiah(summary.saldo)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Pembayaran lunas{' '}
                            {formatRupiah(summary.pembayaran_lunas)}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardDescription>Izin Menunggu</CardDescription>
                        <CardTitle className="text-2xl">
                            {summary.perizinan_pending}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <CheckCircle2 className="size-4 text-emerald-600" />
                            Perlu diproses oleh pengelola
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {modules.map((module) => (
                    <Link
                        key={module.title}
                        href={module.href}
                        className="group flex flex-col gap-3 rounded-lg border bg-card p-5 text-card-foreground shadow-sm transition-colors hover:bg-accent/50"
                    >
                        <span
                            className={`flex size-10 items-center justify-center rounded-lg bg-muted ${module.accent}`}
                        >
                            <module.icon className="size-5" />
                        </span>
                        <span>
                            <span className="block font-medium">
                                {module.title}
                            </span>
                            <span className="block text-sm text-muted-foreground">
                                {module.description}
                            </span>
                        </span>
                    </Link>
                ))}
            </div>

            <div className="mt-8">
                <Card>
                    <CardHeader>
                        <CardDescription>Progres Hafalan</CardDescription>
                        <CardTitle>Setoran per Kategori</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {kategoriProgress.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Belum ada kategori hafalan untuk lembaga ini.
                            </p>
                        ) : (
                            <div className="grid gap-6 sm:grid-cols-2">
                                {Object.entries(progressGroups).map(
                                    ([jenis, items]) => (
                                        <div key={jenis}>
                                            <p className="mb-2 text-sm font-medium text-muted-foreground">
                                                {items[0].jenis_label}
                                            </p>
                                            <ul className="space-y-2">
                                                {items.map((item) => (
                                                    <li
                                                        key={item.id}
                                                        className="flex items-center justify-between gap-3 rounded-lg bg-muted/50 px-3 py-2"
                                                    >
                                                        <span className="text-sm font-medium">
                                                            {item.nama}
                                                        </span>
                                                        <Badge variant="secondary">
                                                            {item.jumlah}{' '}
                                                            setoran
                                                        </Badge>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ),
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

MonitoringShow.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        { title: 'Santri', href: santriIndex(props.team.slug) },
        {
            title: `Monitoring ${props.santri.name}`,
            href: monitoring([props.team.slug, props.santri.id]),
        },
    ],
});
