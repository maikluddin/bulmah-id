import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';
import type { FormEvent } from 'react';
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
import {
    destroy,
    index as kategoriIndex,
    store,
    template,
    update,
} from '@/routes/admin/hafalan-kategori';
import type {
    AdminHafalanKategori,
    AdminHafalanKategoriTemplate,
    AdminOption,
    AdminTeam,
} from '@/types';

type Props = {
    kategoris: AdminHafalanKategori[];
    jenisOptions: AdminOption[];
    filters: { jenis: string };
    jenjang?: string | null;
    templateKategoris: AdminHafalanKategoriTemplate[];
    team: AdminTeam;
};

export default function HafalanKategoriIndex({
    kategoris,
    jenisOptions,
    filters,
    jenjang,
    templateKategoris,
    team,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AdminHafalanKategori | null>(null);
    const [deleteTarget, setDeleteTarget] =
        useState<AdminHafalanKategori | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [importing, setImporting] = useState(false);
    const [filterJenis, setFilterJenis] = useState(filters.jenis || 'all');

    const { data, setData, post, put, reset, processing, errors } = useForm({
        jenis: '',
        nama: '',
    });

    const navigate = useCallback(
        (jenis: string) => {
            router.get(
                kategoriIndex(team.slug, {
                    query: jenis !== 'all' ? { jenis } : {},
                }),
                {},
                {
                    only: ['kategoris', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug],
    );

    const openCreate = () => {
        setEditing(null);
        reset();
        setDialogOpen(true);
    };

    const openEdit = (kategori: AdminHafalanKategori) => {
        setEditing(kategori);
        setData({
            jenis: kategori.jenis,
            nama: kategori.nama,
        });
        setDialogOpen(true);
    };

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
            onError: (errors) => {
                setDeleting(false);
                toast.error(
                    errors.message ??
                        errors.kategori ??
                        'Gagal menghapus kategori hafalan.',
                );
            },
        });
    };

    const missingTemplate = templateKategoris.filter(
        (item) =>
            !kategoris.some(
                (kategori) =>
                    kategori.nama === item.nama && kategori.jenis === item.jenis,
            ),
    );

    const importTemplate = () => {
        if (missingTemplate.length === 0) {
            return;
        }

        setImporting(true);
        router.post(template.url(team.slug), {}, {
            preserveScroll: true,
            onFinish: () => setImporting(false),
        });
    };

    return (
        <>
            <Head title="Kategori Hafalan" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Kategori Hafalan"
                    description="Kelola kategori hafalan lembaga sesuai kurikulum."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Buat Kategori
                </Button>
            </div>

            {jenjang && templateKategoris.length > 0 ? (
                <Card className="mt-4">
                    <CardHeader>
                        <CardTitle>Template Kurikulum</CardTitle>
                        <CardDescription>
                            Kategori bawaan untuk jenjang ini. Tambahkan
                            kategori yang belum tersedia dengan sekali klik.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <ul className="flex flex-wrap gap-2">
                            {templateKategoris.map((item) => {
                                const exists = kategoris.some(
                                    (kategori) =>
                                        kategori.nama === item.nama &&
                                        kategori.jenis === item.jenis,
                                );

                                return (
                                    <Badge
                                        key={`${item.jenis}-${item.nama}`}
                                        variant={
                                            exists ? 'default' : 'secondary'
                                        }
                                    >
                                        {item.nama}
                                        {exists ? ' · tersedia' : ''}
                                    </Badge>
                                );
                            })}
                        </ul>
                        <Button
                            variant="outline"
                            onClick={importTemplate}
                            disabled={
                                importing || missingTemplate.length === 0
                            }
                            className="shrink-0"
                        >
                            <Plus />
                            {importing
                                ? 'Menambahkan...'
                                : missingTemplate.length === 0
                                  ? 'Semua tersedia'
                                  : `Tambahkan ${missingTemplate.length} kategori`}
                        </Button>
                    </CardContent>
                </Card>
            ) : null}

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>Daftar Kategori Hafalan</CardTitle>
                    <CardDescription>
                        {kategoris.length} kategori tersedia.
                    </CardDescription>
                    <div className="mt-4">
                        <Select
                            value={filterJenis}
                            onValueChange={(value) => {
                                setFilterJenis(value);
                                navigate(value);
                            }}
                        >
                            <SelectTrigger className="w-full sm:w-56">
                                <SelectValue placeholder="Semua jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua jenis</SelectItem>
                                {jenisOptions.map((item) => (
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
                    {kategoris.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada kategori. Buat kategori hafalan pertama
                            Anda.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="h-10 px-2 font-medium">
                                        Nama
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Jenis
                                    </th>
                                    <th className="h-10 px-2 text-center font-medium">
                                        Jumlah Catatan
                                    </th>
                                    <th className="h-10 px-2 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {kategoris.map((kategori) => (
                                    <tr
                                        key={kategori.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-2 align-middle font-medium">
                                            {kategori.nama}
                                        </td>
                                        <td className="p-2 align-middle">
                                            <Badge
                                                variant={
                                                    kategori.jenis === 'alquran'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {kategori.jenis_label}
                                            </Badge>
                                        </td>
                                        <td className="p-2 text-center align-middle">
                                            <Badge variant="secondary">
                                                {kategori.hafalans_count}
                                            </Badge>
                                        </td>
                                        <td className="p-2 text-right align-middle">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(kategori)
                                                    }
                                                >
                                                    <Pencil />
                                                    Edit
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(
                                                            kategori,
                                                        )
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
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
                            {editing
                                ? 'Edit Kategori Hafalan'
                                : 'Buat Kategori Hafalan'}
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perbarui kategori hafalan.'
                                : 'Tambahkan kategori hafalan baru untuk lembaga.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="jenis">Jenis</Label>
                            <Select
                                value={data.jenis}
                                onValueChange={(value) =>
                                    setData('jenis', value)
                                }
                            >
                                <SelectTrigger id="jenis">
                                    <SelectValue placeholder="Pilih jenis" />
                                </SelectTrigger>
                                <SelectContent>
                                    {jenisOptions.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.jenis} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="nama">Nama Kategori</Label>
                            <Input
                                id="nama"
                                value={data.nama}
                                onChange={(event) =>
                                    setData('nama', event.target.value)
                                }
                                placeholder="Contoh: Juz Amma"
                            />
                            <InputError message={errors.nama} />
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
                title="Hapus kategori hafalan"
                description={`Kategori "${deleteTarget?.nama}" akan dihapus.`}
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

HafalanKategoriIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        {
            title: 'Kategori Hafalan',
            href: kategoriIndex(props.team.slug),
        },
    ],
});
