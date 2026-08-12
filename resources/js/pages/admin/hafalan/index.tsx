import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, Plus, Search, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
import { monitoring } from '@/routes/admin/santri';
import {
    index as hafalanIndex,
    store,
    destroy,
} from '@/routes/admin/santri/hafalan';
import type {
    AdminHafalan,
    AdminMonitoringSantri,
    AdminOption,
    AdminPagination,
    AdminTeam,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    hafalans: AdminPagination<AdminHafalan>;
    types: AdminOption[];
    kategoris: (AdminOption & { jenis: string; jenis_label: string })[];
    filters: { search: string; type: string; kategori: string };
};

function KategoriSelectItems({
    items,
}: {
    items: (AdminOption & { jenis: string; jenis_label: string })[];
}) {
    const groups = items.reduce<
        Record<string, (AdminOption & { jenis: string; jenis_label: string })[]>
    >((acc, item) => {
        (acc[item.jenis] ??= []).push(item);

        return acc;
    }, {});

    return (
        <>
            {Object.entries(groups).map(([jenis, groupItems]) => (
                <SelectGroup key={jenis}>
                    <SelectLabel>{groupItems[0].jenis_label}</SelectLabel>
                    {groupItems.map((item) => (
                        <SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>
                    ))}
                </SelectGroup>
            ))}
        </>
    );
}

const typeVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    hafalan_baru: 'default',
    murajaah: 'secondary',
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function HafalanIndex({
    santri,
    team,
    hafalans,
    types,
    kategoris,
    filters,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<AdminHafalan | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [searchInput, setSearchInput] = useState(filters.search);
    const [type, setType] = useState(filters.type || 'all');
    const [kategori, setKategori] = useState(filters.kategori || 'all');

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
        setType(filters.type || 'all');
        setKategori(filters.kategori || 'all');
    }

    const { data, setData, post, reset, processing, errors } = useForm({
        kategori_id: '',
        type: '',
        juz: '',
        surat: '',
        ayat_from: '',
        ayat_to: '',
        tanggal: new Date().toISOString().slice(0, 10),
        keterangan: '',
    });

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                hafalanIndex([team.slug, santri.id], { query }),
                {},
                {
                    only: ['hafalans', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug, santri.id],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate({
                    search: searchInput.trim(),
                    type: type !== 'all' ? type : '',
                    kategori: kategori !== 'all' ? kategori : '',
                });
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, type, kategori, navigate, filters.search]);

    const openCreate = () => {
        reset();
        setDialogOpen(true);
    };

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(store.url([team.slug, santri.id]), {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        });
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(destroy.url([team.slug, santri.id, deleteTarget.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors) => {
                setDeleting(false);
                toast.error(errors.message ?? 'Gagal menghapus hafalan.');
            },
        });
    };

    return (
        <>
            <Head title={`Hafalan ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={monitoring([team.slug, santri.id])}>
                    <ChevronLeft className="size-4" />
                    Kembali ke monitoring
                </Link>
            </Button>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={`Hafalan ${santri.name}`}
                    description="Catat dan pantau setoran hafalan santri."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Catat Hafalan
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Hafalan</CardTitle>
                    <CardDescription>
                        {hafalans.total} catatan hafalan.
                    </CardDescription>
                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari surat..."
                                className="pl-9"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </div>
                        <Select
                            value={type}
                            onValueChange={(value) => {
                                setType(value);
                                navigate({
                                    search: searchInput.trim(),
                                    type: value !== 'all' ? value : '',
                                    kategori: kategori !== 'all' ? kategori : '',
                                });
                            }}
                        >
                            <SelectTrigger className="w-full sm:w-44">
                                <SelectValue placeholder="Semua tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua tipe</SelectItem>
                                {types.map((item) => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={kategori}
                            onValueChange={(value) => {
                                setKategori(value);
                                navigate({
                                    search: searchInput.trim(),
                                    type: type !== 'all' ? type : '',
                                    kategori: value !== 'all' ? value : '',
                                });
                            }}
                        >
                            <SelectTrigger className="w-full sm:w-48">
                                <SelectValue placeholder="Semua kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua kategori
                                </SelectItem>
                                <KategoriSelectItems items={kategoris} />
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>

                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="h-10 px-2 font-medium">
                                        Tanggal
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Tipe
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Kategori
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Materi
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Ustad
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Keterangan
                                    </th>
                                    <th className="h-10 px-2 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {hafalans.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            Belum ada catatan hafalan.
                                        </td>
                                    </tr>
                                ) : (
                                    hafalans.data.map((hafalan) => (
                                        <tr
                                            key={hafalan.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-2 align-middle whitespace-nowrap">
                                                {formatDate(hafalan.tanggal)}
                                            </td>
                                            <td className="p-2 align-middle">
                                                <Badge
                                                    variant={
                                                        typeVariant[
                                                            hafalan.type
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {hafalan.type_label}
                                                </Badge>
                                            </td>
                                            <td className="p-2 align-middle">
                                                {hafalan.kategori?.nama ?? '—'}
                                            </td>
                                            <td className="p-2 align-middle">
                                                <span className="font-medium">
                                                    {hafalan.surat}
                                                </span>
                                                {hafalan.ayat_from ? (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        ayat {hafalan.ayat_from}
                                                        {hafalan.ayat_to &&
                                                        hafalan.ayat_to !==
                                                            hafalan.ayat_from
                                                            ? `–${hafalan.ayat_to}`
                                                            : ''}
                                                    </span>
                                                ) : null}
                                                {hafalan.juz ? (
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        (Juz {hafalan.juz})
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="p-2 align-middle text-muted-foreground">
                                                {hafalan.ustad?.name ?? '—'}
                                            </td>
                                            <td className="max-w-xs p-2 align-middle text-muted-foreground">
                                                <span className="line-clamp-1">
                                                    {hafalan.keterangan ?? '—'}
                                                </span>
                                            </td>
                                            <td className="p-2 text-right align-middle">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(hafalan)
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>

                <CardFooter>
                    <Pagination links={hafalans.links} className="w-full" />
                </CardFooter>
            </Card>

            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setDialogOpen(false);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Catat Hafalan</DialogTitle>
                        <DialogDescription>
                            Tambahkan setoran hafalan untuk {santri.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="type">Tipe Setoran</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData('type', value)
                                    }
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="Pilih tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="kategori_id">
                                    Kategori (opsional)
                                </Label>
                                <Select
                                    value={data.kategori_id}
                                    onValueChange={(value) =>
                                        setData('kategori_id', value)
                                    }
                                >
                                    <SelectTrigger id="kategori_id">
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <KategoriSelectItems
                                            items={kategoris}
                                        />
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.kategori_id} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="tanggal">Tanggal</Label>
                            <Input
                                id="tanggal"
                                type="date"
                                value={data.tanggal}
                                onChange={(event) =>
                                    setData('tanggal', event.target.value)
                                }
                            />
                            <InputError message={errors.tanggal} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="surat">Surat</Label>
                                <Input
                                    id="surat"
                                    value={data.surat}
                                    onChange={(event) =>
                                        setData('surat', event.target.value)
                                    }
                                    placeholder="Contoh: Al-Baqarah"
                                />
                                <InputError message={errors.surat} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="juz">Juz (opsional)</Label>
                                <Input
                                    id="juz"
                                    type="number"
                                    min={1}
                                    max={30}
                                    value={data.juz}
                                    onChange={(event) =>
                                        setData('juz', event.target.value)
                                    }
                                    placeholder="1–30"
                                />
                                <InputError message={errors.juz} />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="ayat_from">
                                    Ayat Awal (opsional)
                                </Label>
                                <Input
                                    id="ayat_from"
                                    type="number"
                                    min={1}
                                    value={data.ayat_from}
                                    onChange={(event) =>
                                        setData('ayat_from', event.target.value)
                                    }
                                    placeholder="1"
                                />
                                <InputError message={errors.ayat_from} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="ayat_to">
                                    Ayat Akhir (opsional)
                                </Label>
                                <Input
                                    id="ayat_to"
                                    type="number"
                                    min={1}
                                    value={data.ayat_to}
                                    onChange={(event) =>
                                        setData('ayat_to', event.target.value)
                                    }
                                    placeholder="2"
                                />
                                <InputError message={errors.ayat_to} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="keterangan">Keterangan</Label>
                            <Textarea
                                id="keterangan"
                                value={data.keterangan}
                                onChange={(event) =>
                                    setData('keterangan', event.target.value)
                                }
                                placeholder="Keterangan tambahan (opsional)"
                            />
                            <InputError message={errors.keterangan} />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus catatan hafalan"
                description={`Catatan "${deleteTarget?.surat}" pada ${deleteTarget ? formatDate(deleteTarget.tanggal) : ''} akan dihapus.`}
                processing={deleting}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setDeleteTarget(null);
                    }
                }}
                onConfirm={confirmDelete}
            />
        </>
    );
}

HafalanIndex.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        {
            title: 'Santri',
            href: monitoring([props.team.slug, props.santri.id]),
        },
        {
            title: 'Hafalan',
            href: hafalanIndex([props.team.slug, props.santri.id]),
        },
    ],
});
