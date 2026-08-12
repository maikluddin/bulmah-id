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
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    index as categoriesIndex,
    store,
    update,
} from '@/routes/admin/categories';
import type { AdminCategory, AdminTeam } from '@/types';

type Props = {
    categories: AdminCategory[];
    isAggregate?: boolean;
    team: AdminTeam;
};

export default function CategoriesIndex({
    categories,
    isAggregate = false,
    team,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AdminCategory | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AdminCategory | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, put, reset, processing, errors } = useForm({
        name: '',
        description: '',
    });

    const openCreate = () => {
        setEditing(null);
        reset();
        setDialogOpen(true);
    };

    const openEdit = (category: AdminCategory) => {
        setEditing(category);
        setData({
            name: category.name,
            description: category.description ?? '',
        });
        setDialogOpen(true);
    };

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (editing) {
            put(update.url([editing.team.slug, editing.id]), {
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
        router.delete(destroy.url([deleteTarget.team.slug, deleteTarget.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors) => {
                setDeleting(false);
                toast.error(
                    errors.message ??
                        errors.category ??
                        'Gagal menghapus kategori.',
                );
            },
        });
    };

    return (
        <>
            <Head title="Kategori" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Kategori"
                    description="Kelola kategori berita lembaga."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Buat Kategori
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Kategori</CardTitle>
                    <CardDescription>
                        {categories.length} kategori tersedia.
                        {isAggregate ? ' (semua lembaga)' : ''}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {categories.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada kategori. Buat kategori pertama Anda.
                        </p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="h-10 px-2 font-medium">
                                        Nama
                                    </th>
                                    {isAggregate ? (
                                        <th className="h-10 px-2 font-medium">
                                            Lembaga
                                        </th>
                                    ) : null}
                                    <th className="h-10 px-2 font-medium">
                                        Deskripsi
                                    </th>
                                    <th className="h-10 px-2 text-center font-medium">
                                        Jumlah Berita
                                    </th>
                                    <th className="h-10 px-2 text-right font-medium">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {categories.map((category) => (
                                    <tr
                                        key={category.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-2 align-middle font-medium">
                                            {category.name}
                                        </td>
                                        {isAggregate ? (
                                            <td className="p-2 align-middle text-muted-foreground">
                                                {category.team.name}
                                            </td>
                                        ) : null}
                                        <td className="max-w-md p-2 align-middle text-muted-foreground">
                                            <span className="line-clamp-1">
                                                {category.description ?? '—'}
                                            </span>
                                        </td>
                                        <td className="p-2 text-center align-middle">
                                            <Badge variant="secondary">
                                                {category.posts_count}
                                            </Badge>
                                        </td>
                                        <td className="p-2 text-right align-middle">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        openEdit(category)
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
                                                            category,
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
                            {editing ? 'Edit Kategori' : 'Buat Kategori'}
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perbarui informasi kategori berita.'
                                : 'Tambahkan kategori berita baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="Nama kategori"
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Deskripsi kategori (opsional)"
                            />
                            <InputError message={errors.description} />
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
                title="Hapus kategori"
                description={`Kategori "${deleteTarget?.name}" akan dihapus.`}
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

CategoriesIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'Kategori', href: categoriesIndex(props.team.slug) },
    ],
});
