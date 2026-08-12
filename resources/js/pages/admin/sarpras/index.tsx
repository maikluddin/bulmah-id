import { Head, router, useForm } from '@inertiajs/react';
import { Hammer, ImageIcon, Plus, Trash2, TriangleAlert, Wrench } from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';
import { toast } from 'sonner';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    index as sarprasIndex,
    store,
    update,
} from '@/routes/admin/sarpras';
import {
    destroy as perbaikanDestroy,
    store as perbaikanStore,
    update as perbaikanUpdate,
} from '@/routes/admin/sarpras/perbaikan';
import type {
    AdminOption,
    AdminPerbaikanSarpras,
    AdminSarprasItem,
    AdminTeam,
} from '@/types';

type Props = {
    team: AdminTeam;
    items: AdminSarprasItem[];
    perbaikans: AdminPerbaikanSarpras[];
    summary: {
        total_items: number;
        total_jumlah: number;
        rusak_aktif: number;
        barang_rusak: number;
    };
    kategoriOptions: AdminOption[];
    kondisiOptions: AdminOption[];
    statusOptions: AdminOption[];
};

const kondisiVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    baik: 'default',
    rusak_ringan: 'secondary',
    rusak_berat: 'outline',
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    pending: 'outline',
    diproses: 'secondary',
    selesai: 'default',
};

export default function SarprasIndex({
    team,
    items,
    perbaikans,
    summary,
    kategoriOptions,
    kondisiOptions,
    statusOptions,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AdminSarprasItem | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AdminSarprasItem | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);
    const [deletePerbaikan, setDeletePerbaikan] =
        useState<AdminPerbaikanSarpras | null>(null);
    const [deletingPerbaikan, setDeletingPerbaikan] = useState(false);
    const [fotoPreview, setFotoPreview] = useState<string | null>(null);

    const { data, setData, post, put, reset, processing, errors } = useForm({
        nama: '',
        kategori: '',
        lokasi: '',
        jumlah: '1',
        kondisi: '',
        keterangan: '',
        foto: null as File | null,
        remove_foto: false,
    });

    const laporanForm = useForm({
        sarpras_item_id: '',
        deskripsi: '',
    });

    const statusForm = useForm({
        status: '',
        catatan: '',
    });

    const handleFotoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        setData('foto', file);
        setData('remove_foto', false);

        if (fotoPreview) {
            URL.revokeObjectURL(fotoPreview);
        }

        setFotoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleRemoveFoto = () => {
        setData('foto', null);
        setData('remove_foto', true);

        if (fotoPreview) {
            URL.revokeObjectURL(fotoPreview);
        }

        setFotoPreview(null);
    };

    const openCreate = () => {
        setEditing(null);
        reset();
        setFotoPreview(null);
        setDialogOpen(true);
    };

    const openEdit = (item: AdminSarprasItem) => {
        setEditing(item);
        setData({
            nama: item.nama,
            kategori: item.kategori,
            lokasi: item.lokasi ?? '',
            jumlah: String(item.jumlah),
            kondisi: item.kondisi,
            keterangan: item.keterangan ?? '',
            foto: null,
            remove_foto: false,
        });
        setFotoPreview(null);
        setDialogOpen(true);
    };

    const fotoPreviewUrl =
        fotoPreview ?? (editing?.foto_url && !data.remove_foto ? editing.foto_url : null);

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (editing) {
            put(update.url([team.slug, editing.id]), {
                preserveScroll: true,
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            post(store.url(team.slug), {
                preserveScroll: true,
                onSuccess: () => setDialogOpen(false),
            });
        }
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(destroy.url([team.slug, deleteTarget.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (err) => {
                setDeleting(false);
                toast.error(err.message ?? 'Gagal menghapus inventaris.');
            },
        });
    };

    const submitLaporan = (event: FormEvent) => {
        event.preventDefault();
        laporanForm.post(perbaikanStore.url(team.slug), {
            preserveScroll: true,
            onSuccess: () => laporanForm.reset(),
        });
    };

    const updateStatus = (perbaikan: AdminPerbaikanSarpras, status: string) => {
        statusForm.setData({
            status,
            catatan: perbaikan.catatan ?? '',
        });
        statusForm.patch(perbaikanUpdate.url([team.slug, perbaikan.id]), {
            preserveScroll: true,
        });
    };

    const confirmDeletePerbaikan = () => {
        if (!deletePerbaikan) {
            return;
        }

        setDeletingPerbaikan(true);
        router.delete(perbaikanDestroy.url([team.slug, deletePerbaikan.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setDeletePerbaikan(null);
                setDeletingPerbaikan(false);
            },
            onError: (err) => {
                setDeletingPerbaikan(false);
                toast.error(err.message ?? 'Gagal menghapus laporan.');
            },
        });
    };

    return (
        <>
            <Head title="E-Sarpras" />

            <Heading
                title="E-Sarpras"
                description="Kelola inventaris sarana prasarana dan laporan kerusakan lembaga."
            />

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-xl border bg-card p-4 shadow-xs">
                    <p className="text-xs text-muted-foreground">
                        Jenis Inventaris
                    </p>
                    <p className="mt-1 text-2xl font-semibold">
                        {summary.total_items}
                    </p>
                </div>
                <div className="rounded-xl border bg-card p-4 shadow-xs">
                    <p className="text-xs text-muted-foreground">
                        Total Unit
                    </p>
                    <p className="mt-1 text-2xl font-semibold">
                        {summary.total_jumlah}
                    </p>
                </div>
                <div className="rounded-xl border bg-card p-4 shadow-xs">
                    <p className="text-xs text-muted-foreground">
                        Barang Rusak
                    </p>
                    <p className="mt-1 text-2xl font-semibold">
                        {summary.barang_rusak}
                    </p>
                </div>
                <div className="rounded-xl border bg-card p-4 shadow-xs">
                    <p className="text-xs text-muted-foreground">
                        Perbaikan Aktif
                    </p>
                    <p className="mt-1 text-2xl font-semibold">
                        {summary.rusak_aktif}
                    </p>
                </div>
            </div>

            <Card className="mt-6">
                <CardHeader className="flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Inventaris</CardTitle>
                        <CardDescription>
                            Daftar sarana prasarana lembaga.
                        </CardDescription>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus />
                        Tambah
                    </Button>
                </CardHeader>
                <CardContent>
                    {items.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada inventaris. Tambahkan item pertama Anda.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="h-10 px-2 font-medium">
                                        Inventaris
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Kategori
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Lokasi
                                    </th>
                                    <th className="h-10 px-2 text-center font-medium">
                                        Jumlah
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Kondisi
                                    </th>
                                    <th className="h-10 px-2 text-center font-medium">
                                        Perbaikan
                                    </th>
                                    <th className="h-10 px-2 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-2 align-middle">
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                                    {item.foto_url ? (
                                                        <img
                                                            src={item.foto_url}
                                                            alt={item.nama}
                                                            className="size-full object-cover"
                                                        />
                                                    ) : (
                                                        <ImageIcon className="size-5 text-muted-foreground" />
                                                    )}
                                                </div>
                                                <span className="font-medium">
                                                    {item.nama}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="p-2 align-middle text-muted-foreground">
                                            {item.kategori_label}
                                        </td>
                                        <td className="p-2 align-middle text-muted-foreground">
                                            {item.lokasi ?? '—'}
                                        </td>
                                        <td className="p-2 text-center align-middle">
                                            {item.jumlah}
                                        </td>
                                        <td className="p-2 align-middle">
                                            <Badge
                                                variant={
                                                    kondisiVariant[
                                                        item.kondisi
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {item.kondisi_label}
                                            </Badge>
                                        </td>
                                        <td className="p-2 text-center align-middle">
                                            {item.perbaikans_aktif > 0 ? (
                                                <Badge variant="outline">
                                                    {item.perbaikans_aktif}
                                                </Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="p-2 text-right align-middle">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(item)
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(item)
                                                    }
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </CardContent>
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-5">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TriangleAlert className="size-5" />
                            Lapor Kerusakan
                        </CardTitle>
                        <CardDescription>
                            Catat kerusakan sarana untuk ditindaklanjuti.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submitLaporan}
                            className="space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="sarpras_item_id">
                                    Inventaris
                                </Label>
                                <Select
                                    value={laporanForm.data.sarpras_item_id}
                                    onValueChange={(value) =>
                                        laporanForm.setData(
                                            'sarpras_item_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger id="sarpras_item_id">
                                        <SelectValue placeholder="Pilih item" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {items.map((item) => (
                                            <SelectItem
                                                key={item.id}
                                                value={String(item.id)}
                                            >
                                                {item.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={laporanForm.errors.sarpras_item_id}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="deskripsi">
                                    Deskripsi Kerusakan
                                </Label>
                                <Textarea
                                    id="deskripsi"
                                    value={laporanForm.data.deskripsi}
                                    onChange={(event) =>
                                        laporanForm.setData(
                                            'deskripsi',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Contoh: meja patah di ruang kelas 2"
                                />
                                <InputError
                                    message={laporanForm.errors.deskripsi}
                                />
                            </div>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={laporanForm.processing}
                                >
                                    <Wrench className="size-4" />
                                    Kirim Laporan
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Hammer className="size-5" />
                            Laporan Perbaikan
                        </CardTitle>
                        <CardDescription>
                            Status penanganan kerusakan sarana.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {perbaikans.length === 0 ? (
                            <p className="py-12 text-center text-muted-foreground">
                                Belum ada laporan kerusakan.
                            </p>
                        ) : (
                            <ul className="space-y-4">
                                {perbaikans.map((perbaikan) => (
                                    <li
                                        key={perbaikan.id}
                                        className="rounded-lg border p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-medium">
                                                    {perbaikan.item?.nama ??
                                                        'Inventaris dihapus'}
                                                </p>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {perbaikan.deskripsi}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Dilaporkan oleh{' '}
                                                    {perbaikan.pelapor?.name ??
                                                        '—'}{' '}
                                                    · {perbaikan.created_at}
                                                </p>
                                                {perbaikan.catatan ? (
                                                    <p className="mt-2 rounded-md bg-muted px-3 py-2 text-xs">
                                                        {perbaikan.catatan}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-2">
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            perbaikan.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {perbaikan.status_label}
                                                </Badge>
                                                <div className="flex items-center gap-1">
                                                    <Select
                                                        value={
                                                            perbaikan.status
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateStatus(
                                                                perbaikan,
                                                                value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="h-8 w-32">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {statusOptions.map(
                                                                (option) => (
                                                                    <SelectItem
                                                                        key={
                                                                            option.value
                                                                        }
                                                                        value={
                                                                            option.value
                                                                        }
                                                                    >
                                                                        {
                                                                            option.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            setDeletePerbaikan(
                                                                perbaikan,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

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
                        <DialogTitle>
                            {editing ? 'Edit Inventaris' : 'Tambah Inventaris'}
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perbarui data inventaris.'
                                : 'Tambahkan sarana prasarana baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div className="flex aspect-[4/3] w-36 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                {fotoPreviewUrl ? (
                                    <img
                                        src={fotoPreviewUrl}
                                        alt="Foto inventaris"
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <ImageIcon className="size-8 text-muted-foreground" />
                                )}
                            </div>
                            <div className="flex-1 space-y-2">
                                <Label htmlFor="foto">Foto Inventaris</Label>
                                <Input
                                    id="foto"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={handleFotoChange}
                                />
                                <InputError message={errors.foto} />
                                {fotoPreviewUrl ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-destructive hover:text-destructive"
                                        onClick={handleRemoveFoto}
                                    >
                                        <Trash2 />
                                        Hapus foto
                                    </Button>
                                ) : null}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="nama">Nama Inventaris</Label>
                            <Input
                                id="nama"
                                value={data.nama}
                                onChange={(event) =>
                                    setData('nama', event.target.value)
                                }
                                placeholder="Contoh: Meja dan Kursi"
                            />
                            <InputError message={errors.nama} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="kategori">Kategori</Label>
                                <Select
                                    value={data.kategori}
                                    onValueChange={(value) =>
                                        setData('kategori', value)
                                    }
                                >
                                    <SelectTrigger id="kategori">
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kategoriOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.kategori} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="kondisi">Kondisi</Label>
                                <Select
                                    value={data.kondisi}
                                    onValueChange={(value) =>
                                        setData('kondisi', value)
                                    }
                                >
                                    <SelectTrigger id="kondisi">
                                        <SelectValue placeholder="Pilih kondisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kondisiOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.kondisi} />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="lokasi">Lokasi</Label>
                                <Input
                                    id="lokasi"
                                    value={data.lokasi}
                                    onChange={(event) =>
                                        setData('lokasi', event.target.value)
                                    }
                                    placeholder="Contoh: Ruang 2"
                                />
                                <InputError message={errors.lokasi} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="jumlah">Jumlah</Label>
                                <Input
                                    id="jumlah"
                                    type="number"
                                    min={1}
                                    value={data.jumlah}
                                    onChange={(event) =>
                                        setData('jumlah', event.target.value)
                                    }
                                />
                                <InputError message={errors.jumlah} />
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
                                {editing ? 'Simpan Perubahan' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus inventaris"
                description={`Inventaris "${deleteTarget?.nama}" akan dihapus.`}
                processing={deleting}
                onOpenChange={(open) => {
                    if (!open && !deleting) {
                        setDeleteTarget(null);
                    }
                }}
                onConfirm={confirmDelete}
            />

            <ConfirmDialog
                open={deletePerbaikan !== null}
                title="Hapus laporan"
                description="Laporan kerusakan akan dihapus."
                processing={deletingPerbaikan}
                onOpenChange={(open) => {
                    if (!open && !deletingPerbaikan) {
                        setDeletePerbaikan(null);
                    }
                }}
                onConfirm={confirmDeletePerbaikan}
            />
        </>
    );
}

SarprasIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'E-Sarpras', href: sarprasIndex(props.team.slug) },
    ],
});
