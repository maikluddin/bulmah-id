import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Plus, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import {
    accept,
    index as pendaftaranIndex,
    reject,
} from '@/routes/admin/pendaftaran';
import { create as santriCreate } from '@/routes/admin/santri';
import type {
    AdminOption,
    AdminPagination,
    AdminPendaftaran,
    AdminPendaftaranFilters,
    AdminTeam,
} from '@/types';

type Props = {
    pendaftarans: AdminPagination<AdminPendaftaran>;
    statuses: AdminOption[];
    filters: AdminPendaftaranFilters;
    pendingCount: number;
    santriScope?: string;
    isAggregate?: boolean;
    team: AdminTeam;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    pendaftar: 'default',
    ditolak: 'outline',
};

const scopeLabel: Record<string, string> = {
    laki_laki: 'Putra',
    perempuan: 'Putri',
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
        new Date(value),
    );
}

export default function PendaftaranIndex({
    pendaftarans,
    statuses,
    filters,
    pendingCount,
    santriScope,
    isAggregate = false,
    team,
}: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || 'all');
    const [acceptTarget, setAcceptTarget] = useState<AdminPendaftaran | null>(
        null,
    );
    const [rejectTarget, setRejectTarget] = useState<AdminPendaftaran | null>(
        null,
    );
    const [accepting, setAccepting] = useState(false);

    const { data, setData, post, reset, processing, errors } = useForm({
        alasan: '',
    });

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
        setStatus(filters.status || 'all');
    }

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                pendaftaranIndex.url(team.slug, { query }),
                {},
                {
                    only: ['pendaftarans', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug],
    );

    const buildQuery = useCallback(
        (overrides: {
            search?: string;
            status?: string;
        }): Record<string, string> => {
            const query: Record<string, string> = {};

            const search = overrides.search ?? searchInput;
            const nextStatus = overrides.status ?? status;

            if (search.trim()) {
                query.search = search.trim();
            }

            if (nextStatus !== 'all') {
                query.status = nextStatus;
            }

            return query;
        },
        [searchInput, status],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate(buildQuery({}));
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, buildQuery, navigate, filters.search]);

    const handleStatusChange = (value: string) => {
        setStatus(value);
        navigate(buildQuery({ status: value }));
    };

    const confirmAccept = () => {
        if (!acceptTarget) {
            return;
        }

        setAccepting(true);
        router.post(
            accept.url([team.slug, acceptTarget.id]),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAcceptTarget(null);
                    setAccepting(false);
                },
                onError: (submitErrors) => {
                    setAccepting(false);
                    toast.error(
                        submitErrors.message ?? 'Gagal menerima pendaftaran.',
                    );
                },
            },
        );
    };

    const openReject = (target: AdminPendaftaran) => {
        reset();
        setRejectTarget(target);
    };

    const confirmReject = (event: FormEvent) => {
        event.preventDefault();

        if (!rejectTarget) {
            return;
        }

        post(reject.url([team.slug, rejectTarget.id]), {
            preserveScroll: true,
            onSuccess: () => {
                setRejectTarget(null);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Pendaftaran" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Pendaftaran"
                    description="Terima atau tolak calon santri baru."
                />
                <Button asChild>
                    <Link href={santriCreate(team.slug)}>
                        <Plus />
                        Tambah Manual
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Calon Santri</CardTitle>
                    <CardDescription>
                        {pendingCount} pendaftaran menunggu keputusan.
                        {santriScope
                            ? ` (${scopeLabel[santriScope] ?? 'Scoped'})`
                            : isAggregate
                              ? ' (semua lembaga)'
                              : ''}
                    </CardDescription>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama atau NISN..."
                                className="pl-9"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </div>
                        <Select
                            value={status}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger className="sm:w-44">
                                <SelectValue placeholder="Semua status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua status
                                </SelectItem>
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
                    </div>
                </CardHeader>

                <CardContent>
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Alamat</TableHead>
                                    <TableHead>NISN</TableHead>
                                    <TableHead>Wali</TableHead>
                                    <TableHead>Tanggal Daftar</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pendaftarans.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="h-24 text-center text-muted-foreground"
                                        >
                                            Belum ada pendaftaran.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    pendaftarans.data.map((pendaftaran) => (
                                        <TableRow key={pendaftaran.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {pendaftaran.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {pendaftaran.gender_label}
                                                </div>
                                                {pendaftaran.ditolak_alasan ? (
                                                    <div className="mt-1 max-w-xs text-xs text-destructive">
                                                        {
                                                            pendaftaran.ditolak_alasan
                                                        }
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    {[
                                                        pendaftaran.address_village,
                                                        pendaftaran.address_district,
                                                        pendaftaran.address_city,
                                                        pendaftaran.address_province,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(', ') || '—'}
                                                </div>
                                                {pendaftaran.address ? (
                                                    <div className="text-xs text-muted-foreground">
                                                        {pendaftaran.address}
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                {pendaftaran.nisn || '—'}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    {pendaftaran.wali_name ||
                                                        '—'}
                                                </div>
                                                {pendaftaran.wali_phone ? (
                                                    <div className="text-xs text-muted-foreground">
                                                        {pendaftaran.wali_phone}
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    pendaftaran.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        statusVariant[
                                                            pendaftaran.status
                                                        ] ?? 'secondary'
                                                    }
                                                >
                                                    {pendaftaran.status_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {pendaftaran.status ===
                                                'pendaftar' ? (
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                setAcceptTarget(
                                                                    pendaftaran,
                                                                )
                                                            }
                                                        >
                                                            <Check />
                                                            Terima
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                openReject(
                                                                    pendaftaran,
                                                                )
                                                            }
                                                        >
                                                            <X />
                                                            Tolak
                                                        </Button>
                                                    </div>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>

                {pendaftarans.total > 0 ? (
                    <CardFooter className="border-t px-6 py-4">
                        <Pagination
                            links={pendaftarans.links}
                            className="w-full"
                        />
                    </CardFooter>
                ) : null}
            </Card>

            <Dialog
                open={acceptTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setAcceptTarget(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Terima pendaftaran?</DialogTitle>
                        <DialogDescription>
                            {acceptTarget
                                ? `${acceptTarget.name} akan diaktifkan sebagai santri ${acceptTarget.gender_label}, diberi NIS otomatis, dan dibuatkan tabungan.`
                                : ''}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            disabled={accepting}
                            onClick={() => setAcceptTarget(null)}
                        >
                            Batal
                        </Button>
                        <Button disabled={accepting} onClick={confirmAccept}>
                            Terima Pendaftaran
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={rejectTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRejectTarget(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={confirmReject}>
                        <DialogHeader>
                            <DialogTitle>Tolak pendaftaran</DialogTitle>
                            <DialogDescription>
                                {rejectTarget
                                    ? `Berikan alasan penolakan untuk ${rejectTarget.name}.`
                                    : ''}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="mt-4 space-y-2">
                            <Label htmlFor="alasan">Alasan penolakan</Label>
                            <Textarea
                                id="alasan"
                                value={data.alasan}
                                onChange={(event) =>
                                    setData('alasan', event.target.value)
                                }
                                placeholder="Contoh: kuota kelas penuh, berkas tidak lengkap..."
                            />
                            <InputError message={errors.alasan} />
                        </div>
                        <DialogFooter className="mt-6">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={processing}
                                onClick={() => setRejectTarget(null)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                Tolak Pendaftaran
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
