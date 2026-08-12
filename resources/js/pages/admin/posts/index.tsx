import { Head, Link, router } from '@inertiajs/react';
import { Plus, Pencil, Search, Trash2 } from 'lucide-react';
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
    index as postsIndex,
} from '@/routes/admin/posts';
import type {
    AdminCategoryOption,
    AdminOption,
    AdminPagination,
    AdminPost,
    AdminPostFilters,
    AdminTeam,
} from '@/types';

type Props = {
    posts: AdminPagination<AdminPost>;
    categories: AdminCategoryOption[];
    statuses: AdminOption[];
    filters: AdminPostFilters;
    isAggregate?: boolean;
    team: AdminTeam;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
        new Date(value),
    );
}

function normalizeCategory(value: number | null | undefined): string {
    const stringValue = String(value ?? '');

    return stringValue === '' || stringValue === '0' ? 'all' : stringValue;
}

export default function PostsIndex({
    posts,
    categories,
    statuses,
    filters,
    isAggregate = false,
    team,
}: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [category, setCategory] = useState(
        normalizeCategory(filters.category),
    );
    const [status, setStatus] = useState(filters.status || 'all');
    const [deleteTarget, setDeleteTarget] = useState<AdminPost | null>(null);
    const [deleting, setDeleting] = useState(false);

    const [prevFilters, setPrevFilters] = useState(filters);

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
        setCategory(normalizeCategory(filters.category));
        setStatus(filters.status || 'all');
    }

    const navigate = useCallback(
        (query: Record<string, string>) => {
            router.get(
                postsIndex.url(team.slug, { query }),
                {},
                {
                    only: ['posts', 'filters'],
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
            category?: string;
            status?: string;
        }): Record<string, string> => {
            const query: Record<string, string> = {};

            const search = overrides.search ?? searchInput;
            const nextCategory = overrides.category ?? category;
            const nextStatus = overrides.status ?? status;

            if (search.trim()) {
                query.search = search.trim();
            }

            if (nextCategory !== 'all') {
                query.category = nextCategory;
            }

            if (nextStatus !== 'all') {
                query.status = nextStatus;
            }

            return query;
        },
        [searchInput, category, status],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate(buildQuery({}));
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, buildQuery, navigate, filters.search]);

    const handleCategoryChange = (value: string) => {
        setCategory(value);
        navigate(buildQuery({ category: value }));
    };

    const handleStatusChange = (value: string) => {
        setStatus(value);
        navigate(buildQuery({ status: value }));
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        router.delete(
            destroy.url([deleteTarget.team.slug, deleteTarget.slug]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteTarget(null);
                    setDeleting(false);
                },
                onError: (errors) => {
                    setDeleting(false);
                    toast.error(errors.message ?? 'Gagal menghapus berita.');
                },
            },
        );
    };

    return (
        <>
            <Head title="Berita" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Berita"
                    description="Kelola artikel dan informasi lembaga."
                />
                <Button asChild>
                    <Link href={create(team.slug)}>
                        <Plus />
                        Buat Berita
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Berita</CardTitle>
                    <CardDescription>
                        {posts.total} berita ditemukan.
                        {isAggregate ? ' (semua lembaga)' : ''}
                    </CardDescription>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <div className="relative sm:max-w-xs sm:flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Cari judul berita..."
                                className="pl-9"
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </div>
                        <Select
                            value={category}
                            onValueChange={handleCategoryChange}
                        >
                            <SelectTrigger className="w-full sm:w-48">
                                <SelectValue placeholder="Semua kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua kategori
                                </SelectItem>
                                {categories.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={String(item.id)}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                                <TableHead>Judul</TableHead>
                                {isAggregate ? (
                                    <TableHead>Lembaga</TableHead>
                                ) : null}
                                <TableHead>Kategori</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Tanggal Terbit</TableHead>
                                <TableHead>Penulis</TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={isAggregate ? 7 : 6}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Tidak ada berita ditemukan.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                posts.data.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell className="max-w-xs font-medium">
                                            <div className="flex items-center gap-3">
                                                {post.cover_url ? (
                                                    <img
                                                        src={post.cover_url}
                                                        alt=""
                                                        className="aspect-[16/9] w-14 shrink-0 rounded bg-muted object-cover"
                                                    />
                                                ) : null}
                                                <span className="line-clamp-1">
                                                    {post.title}
                                                </span>
                                            </div>
                                        </TableCell>
                                        {isAggregate ? (
                                            <TableCell>
                                                {post.team.name}
                                            </TableCell>
                                        ) : null}
                                        <TableCell>
                                            {post.category?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        post.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {post.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(post.published_at)}
                                        </TableCell>
                                        <TableCell>
                                            {post.author?.name ?? '—'}
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
                                                            post.team.slug,
                                                            post.slug,
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
                                                        setDeleteTarget(post)
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
                    <Pagination links={posts.links} className="w-full" />
                </CardFooter>
            </Card>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Hapus berita"
                description={`Berita "${deleteTarget?.title}" akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`}
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

PostsIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [{ title: 'Berita', href: postsIndex(props.team.slug) }],
});
