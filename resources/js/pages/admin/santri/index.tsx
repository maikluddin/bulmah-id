import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserRound,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import ConfirmDialog from '@/components/confirm-dialog';
import Heading from '@/components/heading';
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
import { Input } from '@/components/ui/input';
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
import {
    create,
    destroy,
    edit,
    index as santriIndex,
    monitoring,
} from '@/routes/admin/santri';
import type {
    AdminOption,
    AdminPagination,
    AdminSantri,
    AdminSantriFilters,
    AdminTeam,
} from '@/types';

type Props = {
    santris: AdminPagination<AdminSantri>;
    statuses: AdminOption[];
    filters: AdminSantriFilters;
    santriScope?: string;
    isAggregate?: boolean;
    team: AdminTeam;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    aktif: 'default',
    cuti: 'secondary',
    lulus: 'outline',
    keluar: 'outline',
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

export default function SantriIndex({
    santris,
    statuses,
    filters,
    santriScope,
    isAggregate = false,
    team,
}: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || 'all');
    const [deleteTarget, setDeleteTarget] = useState<AdminSantri | null>(null);
    const [deleting, setDeleting] = useState(false);

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
        setStatus(filters.status || 'all');
    }

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                santriIndex.url(team.slug, { query }),
                {},
                {
                    only: ['santris', 'filters'],
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
                toast.error(errors.message ?? 'Gagal menghapus santri.');
            },
        });
    };

    return (
        <>
            <Head title="Santri" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Santri"
                    description="Kelola data santri lembaga."
                />
                <Button asChild>
                    <Link href={create(team.slug)}>
                        <Plus />
                        Tambah Santri
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Santri</CardTitle>
                    <CardDescription>
                        {santris.total} santri ditemukan.
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
                                placeholder="Cari nama, NIS, atau NISN..."
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
                            <SelectTrigger className="w-full sm:w-44">
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
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>NIS</TableHead>
                                <TableHead>Nama</TableHead>
                                {isAggregate ? (
                                    <TableHead>Lembaga</TableHead>
                                ) : null}
                                <TableHead>Jenis Kelamin</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Tanggal Masuk</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {santris.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={isAggregate ? 8 : 7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Tidak ada santri ditemukan.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                santris.data.map((santri) => (
                                    <TableRow key={santri.id}>
                                        <TableCell className="font-medium">
                                            {santri.nis}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                {santri.photo_url ? (
                                                    <img
                                                        src={santri.photo_url}
                                                        alt=""
                                                        className="size-9 shrink-0 rounded-full bg-muted object-cover"
                                                    />
                                                ) : (
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                        <UserRound className="size-4" />
                                                    </span>
                                                )}
                                                <span>{santri.name}</span>
                                            </div>
                                        </TableCell>
                                        {isAggregate ? (
                                            <TableCell>
                                                {santri.team.name}
                                            </TableCell>
                                        ) : null}
                                        <TableCell>
                                            {santri.gender_label}
                                        </TableCell>
                                        <TableCell>
                                            {santri.kelas?.nama ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        santri.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {santri.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(santri.entry_date)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={monitoring([
                                                            santri.team.slug,
                                                            santri.id,
                                                        ])}
                                                    >
                                                        <Activity />
                                                        Monitoring
                                                    </Link>
                                                </Button>
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={edit([
                                                            santri.team.slug,
                                                            santri.id,
                                                        ])}
                                                    >
                                                        <Pencil />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() =>
                                                        setDeleteTarget(santri)
                                                    }
                                                >
                                                    <Trash2 />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>

                <CardFooter>
                    <Pagination links={santris.links} className="w-full" />
                </CardFooter>
            </Card>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus santri"
                description={`Data santri "${deleteTarget?.name}" (NIS ${deleteTarget?.nis}) akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`}
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

SantriIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [{ title: 'Santri', href: santriIndex(props.team.slug) }],
});
