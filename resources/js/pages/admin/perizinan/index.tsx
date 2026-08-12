import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, ChevronLeft, Clock3, Plus, Trash2, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { monitoring } from '@/routes/admin/santri';
import {
    index as perizinanIndex,
    store,
    update,
    destroy,
} from '@/routes/admin/santri/perizinan';
import type {
    AdminMonitoringSantri,
    AdminOption,
    AdminPagination,
    AdminPerizinan,
    AdminTeam,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    perizinans: AdminPagination<AdminPerizinan>;
    jenisOptions: AdminOption[];
    pendingCount: number;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'secondary',
    disetujui: 'default',
    ditolak: 'destructive',
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function PerizinanIndex({
    santri,
    team,
    perizinans,
    jenisOptions,
    pendingCount,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<AdminPerizinan | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);
    const [processingId, setProcessingId] = useState<number | null>(null);

    const { data, setData, post, reset, processing, errors } = useForm({
        jenis: '',
        alasan: '',
        tanggal_mulai: new Date().toISOString().slice(0, 10),
        tanggal_selesai: '',
        catatan: '',
    });

    const openCreate = () => {
        reset();
        setData('tanggal_mulai', new Date().toISOString().slice(0, 10));
        setDialogOpen(true);
    };

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(store.url([team.slug, santri.id]), {
            preserveScroll: true,
            onSuccess: () => setDialogOpen(false),
        });
    };

    const updateStatus = (
        perizinan: AdminPerizinan,
        status: 'disetujui' | 'ditolak',
    ) => {
        setProcessingId(perizinan.id);
        router.patch(
            update.url([team.slug, santri.id, perizinan.id]),
            {
                status,
                catatan: '',
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
                onError: (errors) => {
                    toast.error(
                        errors.message ?? 'Gagal memperbarui status izin.',
                    );
                },
            },
        );
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
                toast.error(errors.message ?? 'Gagal menghapus izin.');
            },
        });
    };

    return (
        <>
            <Head title={`Perizinan ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={monitoring([team.slug, santri.id])}>
                    <ChevronLeft className="size-4" />
                    Kembali ke monitoring
                </Link>
            </Button>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={`Perizinan ${santri.name}`}
                    description="Catat dan kelola izin santri."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Catat Izin
                </Button>
            </div>

            {pendingCount > 0 ? (
                <Card className="mb-6">
                    <CardHeader>
                        <CardDescription className="flex items-center gap-1.5">
                            <Clock3 className="size-4 text-amber-600" />
                            {pendingCount} izin menunggu persetujuan.
                        </CardDescription>
                    </CardHeader>
                </Card>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Perizinan</CardTitle>
                    <CardDescription>
                        {perizinans.total} izin tercatat.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {perizinans.data.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada catatan izin.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="h-10 px-2 font-medium">
                                            Tanggal
                                        </th>
                                        <th className="h-10 px-2 font-medium">
                                            Jenis
                                        </th>
                                        <th className="h-10 px-2 font-medium">
                                            Alasan
                                        </th>
                                        <th className="h-10 px-2 font-medium">
                                            Status
                                        </th>
                                        <th className="h-10 px-2 text-right font-medium">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {perizinans.data.map((perizinan) => (
                                        <tr
                                            key={perizinan.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-2 align-middle whitespace-nowrap">
                                                {formatDate(
                                                    perizinan.tanggal_mulai,
                                                )}
                                                {perizinan.tanggal_selesai
                                                    ? ` – ${formatDate(perizinan.tanggal_selesai)}`
                                                    : ''}
                                            </td>
                                            <td className="p-2 align-middle font-medium">
                                                {perizinan.jenis_label}
                                            </td>
                                            <td className="max-w-xs p-2 align-middle text-muted-foreground">
                                                <span className="line-clamp-1">
                                                    {perizinan.alasan}
                                                </span>
                                            </td>
                                            <td className="p-2 align-middle">
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            perizinan.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {perizinan.status_label}
                                                </Badge>
                                            </td>
                                            <td className="p-2 text-right align-middle">
                                                <div className="flex justify-end gap-1">
                                                    {perizinan.status ===
                                                        'pending' && (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-emerald-600 hover:text-emerald-600"
                                                                disabled={
                                                                    processingId ===
                                                                    perizinan.id
                                                                }
                                                                onClick={() =>
                                                                    updateStatus(
                                                                        perizinan,
                                                                        'disetujui',
                                                                    )
                                                                }
                                                            >
                                                                <Check />
                                                                Setujui
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="text-destructive hover:text-destructive"
                                                                disabled={
                                                                    processingId ===
                                                                    perizinan.id
                                                                }
                                                                onClick={() =>
                                                                    updateStatus(
                                                                        perizinan,
                                                                        'ditolak',
                                                                    )
                                                                }
                                                            >
                                                                <X />
                                                                Tolak
                                                            </Button>
                                                        </>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive"
                                                        onClick={() =>
                                                            setDeleteTarget(
                                                                perizinan,
                                                            )
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
                        </div>
                    )}
                </CardContent>
                <CardFooter>
                    <Pagination links={perizinans.links} className="w-full" />
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
                        <DialogTitle>Catat Izin</DialogTitle>
                        <DialogDescription>
                            Tambahkan catatan izin untuk {santri.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="jenis">Jenis Izin</Label>
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
                            <Label htmlFor="alasan">Alasan</Label>
                            <Textarea
                                id="alasan"
                                value={data.alasan}
                                onChange={(event) =>
                                    setData('alasan', event.target.value)
                                }
                                placeholder="Alasan izin"
                            />
                            <InputError message={errors.alasan} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="tanggal_mulai">
                                    Tanggal Mulai
                                </Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="date"
                                    value={data.tanggal_mulai}
                                    onChange={(event) =>
                                        setData(
                                            'tanggal_mulai',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.tanggal_mulai} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tanggal_selesai">
                                    Tanggal Selesai (opsional)
                                </Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="date"
                                    value={data.tanggal_selesai}
                                    onChange={(event) =>
                                        setData(
                                            'tanggal_selesai',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.tanggal_selesai} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="catatan">Catatan</Label>
                            <Textarea
                                id="catatan"
                                value={data.catatan}
                                onChange={(event) =>
                                    setData('catatan', event.target.value)
                                }
                                placeholder="Catatan tambahan (opsional)"
                            />
                            <InputError message={errors.catatan} />
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
                title="Hapus izin"
                description={`Izin "${deleteTarget?.jenis_label ?? ''}" akan dihapus.`}
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

PerizinanIndex.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        {
            title: 'Santri',
            href: monitoring([props.team.slug, props.santri.id]),
        },
        {
            title: 'Perizinan',
            href: perizinanIndex([props.team.slug, props.santri.id]),
        },
    ],
});
