import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Search, Wallet } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as keuanganIndex } from '@/routes/admin/keuangan';
import { index as pembayaranRoute } from '@/routes/admin/santri/pembayaran';
import { index as walletRoute } from '@/routes/admin/santri/wallet';
import type {
    AdminKeuanganFilters,
    AdminKeuanganSantri,
    AdminKeuanganSummary,
    AdminOption,
    AdminPagination,
    AdminTeam,
} from '@/types';

type Props = {
    santris: AdminPagination<AdminKeuanganSantri>;
    summary: AdminKeuanganSummary;
    statuses: AdminOption[];
    filters: AdminKeuanganFilters;
    santriScope?: string;
    isAggregate?: boolean;
    team: AdminTeam;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    aktif: 'default',
    cuti: 'secondary',
    lulus: 'outline',
    keluar: 'outline',
};

const scopeLabel: Record<string, string> = {
    laki_laki: 'Putra',
    perempuan: 'Putri',
};

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function SummaryCard({
    label,
    value,
    sub,
}: {
    label: string;
    value: string;
    sub?: string;
}) {
    return (
        <Card>
            <CardHeader>
                <CardDescription>{label}</CardDescription>
                <CardTitle className="text-2xl">{value}</CardTitle>
            </CardHeader>
            {sub ? (
                <CardContent>
                    <p className="text-sm text-muted-foreground">{sub}</p>
                </CardContent>
            ) : null}
        </Card>
    );
}

export default function KeuanganIndex({
    santris,
    summary,
    statuses,
    filters,
    santriScope,
    isAggregate = false,
    team,
}: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || 'all');

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
        setStatus(filters.status || 'all');
    }

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                keuanganIndex.url(team.slug, { query }),
                {},
                {
                    only: ['santris', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug],
    );

    const buildQuery = useCallback(
        (overrides: { search?: string; status?: string }): Record<string, string> => {
            const query: Record<string, string> = {};

            const search = overrides.search ?? searchInput;
            const nextStatus = overrides.status ?? status;

            if (search.trim()) {
                query.search = search.trim();
            }

            if (nextStatus !== 'all') {
                query.status = nextStatus;
            }

            return query;
        },
        [searchInput, status],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate(buildQuery({}));
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, buildQuery, navigate, filters.search]);

    const handleStatusChange = (value: string) => {
        setStatus(value);
        navigate(buildQuery({ status: value }));
    };

    return (
        <>
            <Head title="Keuangan" />

            <Heading
                title="Keuangan"
                description="Rekap pembayaran dan saldo dompet santri lembaga."
            />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <SummaryCard
                    label="Total Santri"
                    value={String(summary.total_santri)}
                    sub={`${summary.santri_aktif} santri aktif`}
                />
                <SummaryCard
                    label="Saldo Dompet"
                    value={formatRupiah(summary.saldo_dompet)}
                />
                <SummaryCard
                    label="Pembayaran Lunas"
                    value={formatRupiah(summary.pembayaran_lunas)}
                />
                <SummaryCard
                    label="Menunggu Pembayaran"
                    value={formatRupiah(summary.pembayaran_pending)}
                />
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Pembayaran per Santri</CardTitle>
                    <CardDescription>
                        {santris.total} santri ditemukan.
                        {santriScope
                            ? ` (${scopeLabel[santriScope] ?? 'Scoped'})`
                            : isAggregate
                              ? ' (semua lembaga)'
                              : ''}
                    </CardDescription>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama atau NIS..."
                                className="pl-9"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </div>
                        <Select
                            value={status}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger className="w-full sm:w-44">
                                <SelectValue placeholder="Semua status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua status
                                </SelectItem>
                                {statuses.map((item) => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>NIS</TableHead>
                                <TableHead>Nama</TableHead>
                                {isAggregate ? (
                                    <TableHead>Lembaga</TableHead>
                                ) : null}
                                <TableHead>Kelas</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Saldo Dompet
                                </TableHead>
                                <TableHead className="text-right">
                                    Lunas
                                </TableHead>
                                <TableHead className="text-right">
                                    Menunggu
                                </TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {santris.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={isAggregate ? 9 : 8}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Tidak ada santri ditemukan.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                santris.data.map((santri) => (
                                    <TableRow key={santri.id}>
                                        <TableCell className="font-medium">
                                            {santri.nis}
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-medium">
                                                {santri.name}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {santri.gender_label}
                                            </span>
                                        </TableCell>
                                        {isAggregate ? (
                                            <TableCell>
                                                {santri.team.name}
                                            </TableCell>
                                        ) : null}
                                        <TableCell>
                                            {santri.kelas?.nama ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        santri.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {santri.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatRupiah(santri.saldo)}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums text-emerald-600">
                                            {formatRupiah(
                                                santri.pembayaran_lunas,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums text-amber-600">
                                            {formatRupiah(
                                                santri.pembayaran_pending,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Link
                                                    href={walletRoute([
                                                        santri.team.slug,
                                                        santri.id,
                                                    ])}
                                                    className="inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                                >
                                                    <Wallet className="size-4" />
                                                    Dompet
                                                </Link>
                                                <Link
                                                    href={pembayaranRoute([
                                                        santri.team.slug,
                                                        santri.id,
                                                    ])}
                                                    className="inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                                >
                                                    Pembayaran
                                                    <ArrowRight className="size-4" />
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>

                <CardFooter>
                    <Pagination links={santris.links} className="w-full" />
                </CardFooter>
            </Card>
        </>
    );
}

KeuanganIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [{ title: 'Keuangan', href: keuanganIndex(props.team.slug) }],
});

