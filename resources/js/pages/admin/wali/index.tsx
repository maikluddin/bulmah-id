import { Head, Link, router } from '@inertiajs/react';
import { Phone, Pencil, Plus, Search, Trash2, UserRound } from 'lucide-react';
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
    index as waliIndex,
} from '@/routes/admin/wali';
import type {
    AdminPagination,
    AdminTeam,
    AdminWali,
    AdminWaliFilters,
} from '@/types';

type Props = {
    walis: AdminPagination<AdminWali>;
    filters: AdminWaliFilters;
    team: AdminTeam;
};

export default function WaliIndex({ walis, filters, team }: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [deleteTarget, setDeleteTarget] = useState<AdminWali | null>(null);
    const [deleting, setDeleting] = useState(false);

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
    }

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                waliIndex.url(team.slug, { query }),
                {},
                {
                    only: ['walis', 'filters'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        },
        [team.slug],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate({ search: searchInput.trim() });
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, navigate, filters.search]);

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
                toast.error(errors.message ?? 'Gagal menghapus akun wali.');
            },
        });
    };

    return (
        <>
            <Head title="Wali Santri" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Wali Santri"
                    description="Kelola akun wali (orang tua) santri lembaga."
                />
                <Button asChild>
                    <Link href={create(team.slug)}>
                        <Plus />
                        Tambah Akun Wali
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Akun Wali</CardTitle>
                    <CardDescription>
                        {walis.total} akun wali ditemukan.
                    </CardDescription>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama atau email..."
                                className="pl-9"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Wali</TableHead>
                                <TableHead>No. HP</TableHead>
                                <TableHead>Santri Terhubung</TableHead>
                                <TableHead>Status Email</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {walis.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Tidak ada akun wali ditemukan.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                walis.data.map((wali) => (
                                    <TableRow key={wali.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                    <UserRound className="size-4" />
                                                </span>
                                                <div>
                                                    <div className="font-medium">
                                                        {wali.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {wali.email}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {wali.phone ? (
                                                <span className="inline-flex items-center gap-1.5">
                                                    <Phone className="size-3.5 text-muted-foreground" />
                                                    {wali.phone}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <span className="font-medium">
                                                {wali.santris_count}
                                            </span>
                                            {wali.santri_names.length > 0 ? (
                                                <span className="ml-1 text-xs text-muted-foreground">
                                                    · {wali.santri_names.join(', ')}
                                                    {wali.santris_count >
                                                    wali.santri_names.length
                                                        ? ', dll.'
                                                        : ''}
                                                </span>
                                            ) : null}
                                        </TableCell>
                                        <TableCell>
                                            {wali.email_verified_at ? (
                                                <Badge>Terverifikasi</Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Belum verifikasi
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={edit([
                                                            wali.team.slug,
                                                            wali.id,
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
                                                        setDeleteTarget(wali)
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
                    <Pagination links={walis.links} className="w-full" />
                </CardFooter>
            </Card>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus akun wali"
                description={`Akun "${deleteTarget?.name}" akan diputus dari ${deleteTarget?.santris_count} santri dan keanggotaan lembaga dihapus. Data santri tetap aman.`}
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

WaliIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'Wali Santri', href: waliIndex(props.team.slug) },
    ],
});
