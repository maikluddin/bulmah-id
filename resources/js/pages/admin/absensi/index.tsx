import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    ChevronLeft,
    ChevronLeft as Prev,
    ChevronRight as Next,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import { monitoring } from '@/routes/admin/santri';
import {
    index as absensiIndex,
    store,
    destroy,
} from '@/routes/admin/santri/absensi';
import type {
    AdminAbsensi,
    AdminAbsensiStats,
    AdminMonitoringSantri,
    AdminOption,
    AdminTeam,
} from '@/types';

type Props = {
    santri: AdminMonitoringSantri;
    team: AdminTeam;
    records: AdminAbsensi[];
    month: string;
    stats: AdminAbsensiStats;
    statuses: AdminOption[];
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    hadir: 'default',
    sakit: 'secondary',
    izin: 'outline',
    alpa: 'destructive',
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function AbsensiIndex({
    santri,
    team,
    records,
    month,
    stats,
    statuses,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<AdminAbsensi | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AdminAbsensi | null>(null);
    const [deleting, setDeleting] = useState(false);

    const { data, setData, post, reset, processing, errors } = useForm({
        tanggal: new Date().toISOString().slice(0, 10),
        status: '',
        keterangan: '',
    });

    const openCreate = () => {
        setEditing(null);
        reset();
        setData('tanggal', new Date().toISOString().slice(0, 10));
        setDialogOpen(true);
    };

    const openEdit = (record: AdminAbsensi) => {
        setEditing(record);
        setData({
            tanggal: record.tanggal,
            status: record.status,
            keterangan: record.keterangan ?? '',
        });
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
                toast.error(errors.message ?? 'Gagal menghapus absensi.');
            },
        });
    };

    const statItems = [
        { label: 'Hadir', value: stats.hadir, className: 'text-emerald-600' },
        { label: 'Sakit', value: stats.sakit, className: 'text-amber-600' },
        { label: 'Izin', value: stats.izin, className: 'text-blue-600' },
        { label: 'Alpa', value: stats.alpa, className: 'text-rose-600' },
    ];

    return (
        <>
            <Head title={`Absensi ${santri.name}`} />

            <Button asChild variant="ghost" size="sm" className="mb-2 -ml-2">
                <Link href={monitoring([team.slug, santri.id])}>
                    <ChevronLeft className="size-4" />
                    Kembali ke monitoring
                </Link>
            </Button>

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={`Absensi ${santri.name}`}
                    description="Rekap kehadiran santri per bulan."
                />
                <Button onClick={openCreate}>
                    <Plus />
                    Catat Absensi
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between gap-4">
                        <CardTitle className="flex items-center gap-2">
                            <CalendarDays className="size-5" />
                            {formatDate(`${month}-01`)}
                        </CardTitle>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    router.get(
                                        absensiIndex([team.slug, santri.id], {
                                            query: {
                                                month: prevMonth(month),
                                            },
                                        }),
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                            only: ['records', 'stats', 'month'],
                                        },
                                    )
                                }
                            >
                                <Prev className="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={month >= currentMonth()}
                                onClick={() =>
                                    router.get(
                                        absensiIndex([team.slug, santri.id], {
                                            query: {
                                                month: nextMonth(month),
                                            },
                                        }),
                                        {},
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                            only: ['records', 'stats', 'month'],
                                        },
                                    )
                                }
                            >
                                <Next className="size-4" />
                            </Button>
                        </div>
                    </div>
                    <CardDescription className="flex flex-wrap gap-2 pt-2">
                        {statItems.map((item) => (
                            <Badge key={item.label} variant="secondary">
                                {item.label}:{' '}
                                <span
                                    className={`font-semibold ${item.className}`}
                                >
                                    {item.value}
                                </span>
                            </Badge>
                        ))}
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {records.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada catatan absensi pada bulan ini.
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
                                            Status
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
                                    {records.map((record) => (
                                        <tr
                                            key={record.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-2 align-middle whitespace-nowrap">
                                                {formatDate(record.tanggal)}
                                            </td>
                                            <td className="p-2 align-middle">
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            record.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {record.status_label}
                                                </Badge>
                                            </td>
                                            <td className="max-w-xs p-2 align-middle text-muted-foreground">
                                                <span className="line-clamp-1">
                                                    {record.keterangan ?? '—'}
                                                </span>
                                            </td>
                                            <td className="p-2 text-right align-middle">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            openEdit(record)
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
                                                                record,
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
                        </div>
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
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Ubah Absensi' : 'Catat Absensi'}
                        </DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perbarui catatan kehadiran santri.'
                                : 'Tambahkan catatan kehadiran santri.'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={onSubmit} className="space-y-4">
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
                        <div className="space-y-2">
                            <Label htmlFor="status">Status Kehadiran</Label>
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
                            <InputError message={errors.status} />
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
                title="Hapus catatan absensi"
                description={`Catatan absensi ${deleteTarget ? formatDate(deleteTarget.tanggal) : ''} akan dihapus.`}
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

function prevMonth(month: string): string {
    const [year, value] = month.split('-').map(Number);

    return value === 1
        ? `${year - 1}-12`
        : `${year}-${String(value - 1).padStart(2, '0')}`;
}

function nextMonth(month: string): string {
    const [year, value] = month.split('-').map(Number);

    return value === 12
        ? `${year + 1}-01`
        : `${year}-${String(value + 1).padStart(2, '0')}`;
}

function currentMonth(): string {
    return new Date().toISOString().slice(0, 7);
}

AbsensiIndex.layout = (props: {
    team: AdminTeam;
    santri: AdminMonitoringSantri;
}) => ({
    breadcrumbs: [
        {
            title: 'Santri',
            href: monitoring([props.team.slug, props.santri.id]),
        },
        {
            title: 'Absensi',
            href: absensiIndex([props.team.slug, props.santri.id]),
        },
    ],
});
