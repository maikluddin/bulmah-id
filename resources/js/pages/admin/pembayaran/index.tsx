import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, Plus, ReceiptText, Trash2 } from 'lucide-react';
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
    index as pembayaranIndex,
    store,
    destroy,
} from '@/routes/admin/santri/pembayaran';
import type {
    AdminMonitoringSantri,
    AdminOption,
    AdminPagination,
    AdminPembayaran,
    AdminTeam,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    pembayarans: AdminPagination<AdminPembayaran>;
    total_lunas: number;
    total_pending: number;
    jenisOptions: AdminOption[];
    metodeOptions: AdminOption[];
    statusOptions: AdminOption[];
};

const statusVariant: Record<string, 'default' | 'secondary'> = {
    lunas: 'default',
    pending: 'secondary',
};

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function PembayaranIndex({
    santri,
    team,
    pembayarans,
    total_lunas,
    total_pending,
    jenisOptions,
    metodeOptions,
    statusOptions,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<AdminPembayaran | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, reset, processing, errors } = useForm({
        jenis: '',
        jumlah: '',
        tanggal: new Date().toISOString().slice(0, 10),
        metode: '',
        status: 'lunas',
        keterangan: '',
    });

    const openCreate = () => {
        reset();
        setData('tanggal', new Date().toISOString().slice(0, 10));
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
                toast.error(errors.message ?? 'Gagal menghapus pembayaran.');
            },
        });
    };

    return (
        <>
            <Head title={`Pembayaran ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={monitoring([team.slug, santri.id])}>
                    <ChevronLeft className="size-4" />
                    Kembali ke monitoring
                </Link>
            </Button>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={`Pembayaran ${santri.name}`}
                    description="Kelola riwayat pembayaran santri."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Catat Pembayaran
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardDescription className="flex items-center gap-1.5">
                            <ReceiptText className="size-4 text-emerald-600" />
                            Total Lunas
                        </CardDescription>
                        <CardTitle className="text-2xl">
                            {formatRupiah(total_lunas)}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription className="flex items-center gap-1.5">
                            <ReceiptText className="size-4 text-amber-600" />
                            Total Pending
                        </CardDescription>
                        <CardTitle className="text-2xl">
                            {formatRupiah(total_pending)}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Riwayat Pembayaran</CardTitle>
                    <CardDescription>
                        {pembayarans.total} pembayaran tercatat.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {pembayarans.data.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada catatan pembayaran.
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
                                        <th className="h-10 px-2 text-right font-medium">
                                            Jumlah
                                        </th>
                                        <th className="h-10 px-2 font-medium">
                                            Metode
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
                                    {pembayarans.data.map((pembayaran) => (
                                        <tr
                                            key={pembayaran.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-2 align-middle whitespace-nowrap">
                                                {formatDate(pembayaran.tanggal)}
                                            </td>
                                            <td className="p-2 align-middle font-medium">
                                                {pembayaran.jenis_label}
                                            </td>
                                            <td className="p-2 text-right align-middle font-medium">
                                                {formatRupiah(
                                                    pembayaran.jumlah,
                                                )}
                                            </td>
                                            <td className="p-2 align-middle text-muted-foreground">
                                                {pembayaran.metode_label}
                                            </td>
                                            <td className="p-2 align-middle">
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            pembayaran.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {pembayaran.status_label}
                                                </Badge>
                                            </td>
                                            <td className="p-2 text-right align-middle">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(
                                                            pembayaran,
                                                        )
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
                <CardFooter>
                    <Pagination links={pembayarans.links} className="w-full" />
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
                        <DialogTitle>Catat Pembayaran</DialogTitle>
                        <DialogDescription>
                            Tambahkan catatan pembayaran untuk {santri.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="jenis">Jenis Pembayaran</Label>
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
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statusOptions.map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="jumlah">Jumlah (Rp)</Label>
                                <Input
                                    id="jumlah"
                                    type="number"
                                    min={1}
                                    step="500"
                                    value={data.jumlah}
                                    onChange={(event) =>
                                        setData('jumlah', event.target.value)
                                    }
                                    placeholder="50000"
                                />
                                <InputError message={errors.jumlah} />
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
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="metode">Metode Pembayaran</Label>
                            <Select
                                value={data.metode}
                                onValueChange={(value) =>
                                    setData('metode', value)
                                }
                            >
                                <SelectTrigger id="metode">
                                    <SelectValue placeholder="Pilih metode" />
                                </SelectTrigger>
                                <SelectContent>
                                    {metodeOptions.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.metode} />
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
                title="Hapus pembayaran"
                description={`Pembayaran ${deleteTarget?.jenis_label ?? ''} ${deleteTarget ? formatRupiah(deleteTarget.jumlah) : ''} akan dihapus.`}
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

PembayaranIndex.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        {
            title: 'Santri',
            href: monitoring([props.team.slug, props.santri.id]),
        },
        {
            title: 'Pembayaran',
            href: pembayaranIndex([props.team.slug, props.santri.id]),
        },
    ],
});
