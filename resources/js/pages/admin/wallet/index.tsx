import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    ChevronLeft,
    Plus,
    Trash2,
    Wallet,
} from 'lucide-react';
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
import { monitoring } from '@/routes/admin/santri';
import {
    index as walletIndex,
    store,
    destroy,
} from '@/routes/admin/santri/wallet';
import type {
    AdminMonitoringSantri,
    AdminOption,
    AdminPagination,
    AdminTeam,
    AdminWalletTransaction,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    transactions: AdminPagination<AdminWalletTransaction>;
    balance: number;
    types: AdminOption[];
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

export default function WalletIndex({
    santri,
    team,
    transactions,
    balance,
    types,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] =
        useState<AdminWalletTransaction | null>(null);
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, reset, processing, errors } = useForm({
        type: '',
        amount: '',
        tanggal: new Date().toISOString().slice(0, 10),
        description: '',
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
                toast.error(errors.message ?? 'Gagal menghapus transaksi.');
            },
        });
    };

    return (
        <>
            <Head title={`Dompet ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={monitoring([team.slug, santri.id])}>
                    <ChevronLeft className="size-4" />
                    Kembali ke monitoring
                </Link>
            </Button>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={`Dompet ${santri.name}`}
                    description="Kelola uang saku dan saldo dompet santri."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Catat Transaksi
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Wallet className="size-5 text-amber-600" />
                        Saldo Dompet
                    </CardTitle>
                    <CardDescription>
                        Saldo saat ini tersimpan untuk santri.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-3xl font-semibold tracking-tight">
                        {formatRupiah(balance)}
                    </p>
                </CardContent>
            </Card>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Riwayat Transaksi</CardTitle>
                    <CardDescription>
                        {transactions.total} transaksi tercatat.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {transactions.data.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada transaksi dompet.
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
                                            Tipe
                                        </th>
                                        <th className="h-10 px-2 text-right font-medium">
                                            Nominal
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
                                    {transactions.data.map((transaction) => (
                                        <tr
                                            key={transaction.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-2 align-middle whitespace-nowrap">
                                                {formatDate(
                                                    transaction.tanggal,
                                                )}
                                            </td>
                                            <td className="p-2 align-middle">
                                                <Badge
                                                    variant={
                                                        transaction.type ===
                                                        'setor'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {transaction.type ===
                                                    'setor' ? (
                                                        <ArrowDownCircle className="mr-1 size-3.5" />
                                                    ) : (
                                                        <ArrowUpCircle className="mr-1 size-3.5" />
                                                    )}
                                                    {transaction.type_label}
                                                </Badge>
                                            </td>
                                            <td className="p-2 text-right align-middle font-medium">
                                                <span
                                                    className={
                                                        transaction.type ===
                                                        'setor'
                                                            ? 'text-emerald-600'
                                                            : 'text-rose-600'
                                                    }
                                                >
                                                    {transaction.type ===
                                                    'setor'
                                                        ? '+'
                                                        : '−'}
                                                    {formatRupiah(
                                                        transaction.amount,
                                                    )}
                                                </span>
                                            </td>
                                            <td className="max-w-xs p-2 align-middle text-muted-foreground">
                                                <span className="line-clamp-1">
                                                    {transaction.description ??
                                                        '—'}
                                                </span>
                                            </td>
                                            <td className="p-2 text-right align-middle">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(
                                                            transaction,
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
                    <Pagination links={transactions.links} className="w-full" />
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
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Catat Transaksi</DialogTitle>
                        <DialogDescription>
                            Tambahkan transaksi dompet untuk {santri.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="type">Jenis Transaksi</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value)
                                }
                            >
                                <SelectTrigger id="type">
                                    <SelectValue placeholder="Pilih jenis" />
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
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="amount">Nominal (Rp)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min={1}
                                    step="500"
                                    value={data.amount}
                                    onChange={(event) =>
                                        setData('amount', event.target.value)
                                    }
                                    placeholder="10000"
                                />
                                <InputError message={errors.amount} />
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
                            <Label htmlFor="description">Keterangan</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Contoh: uang saku mingguan"
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
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus transaksi"
                description={`Transaksi ${deleteTarget?.type_label ?? ''} ${deleteTarget ? formatRupiah(deleteTarget.amount) : ''} akan dihapus.`}
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

WalletIndex.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        {
            title: 'Santri',
            href: monitoring([props.team.slug, props.santri.id]),
        },
        {
            title: 'Dompet',
            href: walletIndex([props.team.slug, props.santri.id]),
        },
    ],
});
