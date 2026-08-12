import { Head, router } from '@inertiajs/react';
import { FileBarChart, FolderOpen, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
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
import { setoran as laporanSetoran } from '@/routes/admin/laporan';
import type {
    AdminHafalanKategoriOption,
    AdminLaporanFilters,
    AdminLaporanPerKategori,
    AdminLaporanPerSantri,
    AdminLaporanRecord,
    AdminLaporanSummary,
    AdminPagination,
    AdminTeam,
} from '@/types';

type Props = {
    team: AdminTeam;
    records: AdminPagination<AdminLaporanRecord>;
    summary: AdminLaporanSummary;
    perKategori: AdminLaporanPerKategori[];
    perSantri: AdminLaporanPerSantri[];
    kategoris: AdminHafalanKategoriOption[];
    filters: AdminLaporanFilters;
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function LaporanSetoran({
    team,
    records,
    summary,
    perKategori,
    perSantri,
    kategoris,
    filters,
}: Props) {
    const [fromInput, setFromInput] = useState(filters.from);
    const [toInput, setToInput] = useState(filters.to);
    const [kategori, setKategori] = useState(filters.kategori || 'all');
    const [searchInput, setSearchInput] = useState(filters.search);
    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setFromInput(filters.from);
        setToInput(filters.to);
        setKategori(filters.kategori || 'all');
        setSearchInput(filters.search);
    }

    const kategoriGroups = kategoris.reduce<
        Record<string, AdminHafalanKategoriOption[]>
    >((acc, item) => {
        (acc[item.jenis] ??= []).push(item);

        return acc;
    }, {});

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                laporanSetoran.url(team.slug, { query }),
                {},
                {
                    only: [
                        'records',
                        'summary',
                        'perKategori',
                        'perSantri',
                        'filters',
                    ],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug],
    );

    const buildQuery = useCallback(
        (overrides: {
            from?: string;
            to?: string;
            kategori?: string;
            search?: string;
        }): Record<string, string> => {
            const query: Record<string, string> = {};

            const from = overrides.from ?? fromInput;
            const to = overrides.to ?? toInput;
            const nextKategori = overrides.kategori ?? kategori;
            const search = overrides.search ?? searchInput;

            if (from.trim()) {
                query.from = from.trim();
            }

            if (to.trim()) {
                query.to = to.trim();
            }

            if (nextKategori !== 'all') {
                query.kategori = nextKategori;
            }

            if (search.trim()) {
                query.search = search.trim();
            }

            return query;
        },
        [fromInput, toInput, kategori, searchInput],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate(buildQuery({}));
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, buildQuery, navigate, filters.search]);

    const handleFromChange = (value: string) => {
        setFromInput(value);
        navigate(buildQuery({ from: value }));
    };

    const handleToChange = (value: string) => {
        setToInput(value);
        navigate(buildQuery({ to: value }));
    };

    const handleKategoriChange = (value: string) => {
        setKategori(value);
        navigate(buildQuery({ kategori: value }));
    };

    const statCards: {
        icon: LucideIcon;
        label: string;
        value: number;
    }[] = [
        {
            icon: FileBarChart,
            label: 'Total Setoran',
            value: summary.total,
        },
        {
            icon: Users,
            label: 'Santri Menyetor',
            value: summary.santri,
        },
        {
            icon: FolderOpen,
            label: 'Kategori Terpakai',
            value: summary.kategori_terpakai,
        },
    ];

    return (
        <>
            <Head title="Laporan Setoran" />

            <Heading
                title="Laporan Setoran"
                description="Rekap setoran hafalan santri berdasarkan rentang waktu."
            />

            <Card>
                <CardHeader>
                    <CardTitle>Filter Laporan</CardTitle>
                    <CardDescription>
                        Pilih rentang tanggal, kategori, atau cari nama/NIS
                        santri.
                    </CardDescription>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-2">
                            <Label htmlFor="from">Dari tanggal</Label>
                            <Input
                                id="from"
                                type="date"
                                value={fromInput}
                                onChange={(event) =>
                                    handleFromChange(event.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="to">Sampai tanggal</Label>
                            <Input
                                id="to"
                                type="date"
                                value={toInput}
                                onChange={(event) =>
                                    handleToChange(event.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="kategori">Kategori</Label>
                            <Select
                                value={kategori}
                                onValueChange={handleKategoriChange}
                            >
                                <SelectTrigger id="kategori">
                                    <SelectValue placeholder="Semua kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Semua kategori
                                    </SelectItem>
                                    {Object.entries(kategoriGroups).map(
                                        ([jenis, items]) => (
                                            <SelectGroup key={jenis}>
                                                <SelectLabel>
                                                    {items[0].jenis_label}
                                                </SelectLabel>
                                                {items.map((item) => (
                                                    <SelectItem
                                                        key={item.id}
                                                        value={String(
                                                            item.id,
                                                        )}
                                                    >
                                                        {item.nama}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="search">Cari santri</Label>
                            <Input
                                id="search"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                                placeholder="Nama atau NIS"
                            />
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <div className="mt-6 grid gap-4 sm:grid-cols-3">
                {statCards.map((card) => (
                    <Card key={card.label}>
                        <CardHeader>
                            <CardDescription className="flex items-center gap-2">
                                <card.icon className="size-4" />
                                {card.label}
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {card.value}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                ))}
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Setoran per Kategori</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {perKategori.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Belum ada setoran pada rentang ini.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {perKategori.map((item) => (
                                    <li
                                        key={item.id ?? 'none'}
                                        className="flex items-center justify-between gap-3 rounded-lg bg-muted/50 px-3 py-2"
                                    >
                                        <span className="text-sm font-medium">
                                            {item.nama}
                                        </span>
                                        <Badge variant="secondary">
                                            {item.jumlah}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Santri Teraktif</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {perSantri.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">
                                Belum ada setoran pada rentang ini.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {perSantri.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex items-center justify-between gap-3 rounded-lg bg-muted/50 px-3 py-2"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {item.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                NIS {item.nis}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {item.jumlah}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Detail Setoran</CardTitle>
                    <CardDescription>
                        {records.total} catatan ditemukan.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Santri</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead>Tipe</TableHead>
                                <TableHead>Surat</TableHead>
                                <TableHead>Ustad</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {records.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Tidak ada setoran ditemukan.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                records.data.map((record) => (
                                    <TableRow key={record.id}>
                                        <TableCell>
                                            {formatDate(record.tanggal)}
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">
                                                    {record.santri.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {record.santri.nis}
                                                </p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {record.kategori?.nama ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {record.type_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {record.surat}
                                            {record.ayat_from
                                                ? ` ayat ${record.ayat_from}${record.ayat_to && record.ayat_to !== record.ayat_from ? `–${record.ayat_to}` : ''}`
                                                : ''}
                                            {record.juz
                                                ? ` (Juz ${record.juz})`
                                                : ''}
                                        </TableCell>
                                        <TableCell>
                                            {record.ustad?.name ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
                <CardFooter>
                    <Pagination links={records.links} className="w-full" />
                </CardFooter>
            </Card>
        </>
    );
}

LaporanSetoran.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        {
            title: 'Laporan Setoran',
            href: laporanSetoran(props.team.slug),
        },
    ],
});
