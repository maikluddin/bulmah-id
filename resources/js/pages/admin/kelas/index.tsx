import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
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
import { destroy, index as kelasIndex, store, update } from '@/routes/admin/kelas';
import type { AdminKelas, AdminTeam, AdminUstadOption } from '@/types';

type Props = {
    kelas: AdminKelas[];
    ustads: AdminUstadOption[];
    team: AdminTeam;
};

export default function KelasIndex({ kelas, ustads, team }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AdminKelas | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AdminKelas | null>(null);
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, put, reset, processing, errors } = useForm({
        nama: '',
        ustad_user_id: '',
    });

    const openCreate = () => {
        setEditing(null);
        reset();
        setDialogOpen(true);
    };

    const openEdit = (item: AdminKelas) => {
        setEditing(item);
        setData({
            nama: item.nama,
            ustad_user_id: item.ustad ? String(item.ustad.id) : '',
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
                    errors.message ?? errors.kelas ?? 'Gagal menghapus kelas.',
                );
            },
        });
    };

    return (
        <>
            <Head title="Kelas" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Kelas"
                    description="Kelola daftar kelas santri lembaga."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Buat Kelas
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Kelas</CardTitle>
                    <CardDescription>{kelas.length} kelas tersedia.</CardDescription>
                </CardHeader>
                <CardContent>
                    {kelas.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada kelas. Buat kelas pertama Anda.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="h-10 px-2 font-medium">
                                        Nama
                                    </th>
                                    <th className="h-10 px-2 font-medium">
                                        Ustad
                                    </th>
                                    <th className="h-10 px-2 text-center font-medium">
                                        Jumlah Santri
                                    </th>
                                    <th className="h-10 px-2 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {kelas.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-2 align-middle font-medium">
                                            {item.nama}
                                        </td>
                                        <td className="p-2 align-middle text-muted-foreground">
                                            {item.ustad ? (
                                                item.ustad.name
                                            ) : (
                                                <span className="text-muted-foreground/60">
                                                    Belum ditugaskan
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-2 text-center align-middle">
                                            <Badge variant="secondary">
                                                {item.santris_count}
                                            </Badge>
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
                                                    <Pencil />
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
                            {editing ? 'Edit Kelas' : 'Buat Kelas'}
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perbarui nama kelas.'
                                : 'Tambahkan kelas santri baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="nama">Nama Kelas</Label>
                            <Input
                                id="nama"
                                value={data.nama}
                                onChange={(event) =>
                                    setData('nama', event.target.value)
                                }
                                placeholder="Contoh: Kelas 1"
                            />
                            <InputError message={errors.nama} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="ustad">Ustad / Ustazah</Label>
                            <Select
                                value={data.ustad_user_id}
                                onValueChange={(value) =>
                                    setData('ustad_user_id', value)
                                }
                            >
                                <SelectTrigger id="ustad">
                                    <SelectValue placeholder="Belum ditugaskan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">
                                        Belum ditugaskan
                                    </SelectItem>
                                    {ustads.map((ustad) => (
                                        <SelectItem
                                            key={ustad.id}
                                            value={String(ustad.id)}
                                        >
                                            {ustad.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.ustad_user_id} />
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
                title="Hapus kelas"
                description={`Kelas "${deleteTarget?.nama}" akan dihapus.`}
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

KelasIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'Kelas', href: kelasIndex(props.team.slug) },
    ],
});
