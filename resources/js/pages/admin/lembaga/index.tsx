import { Head, Link } from '@inertiajs/react';
import { Building2, Pencil } from 'lucide-react';
import Heading from '@/components/heading';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    edit as lembagaEdit,
    index as lembagaIndex,
} from '@/routes/admin/lembaga';
import type { AdminLembagaListItem, AdminTeam } from '@/types';

type Props = {
    lembagas: AdminLembagaListItem[];
    isAggregate?: boolean;
};

export default function LembagaIndex({ lembagas, isAggregate = false }: Props) {
    return (
        <>
            <Head title="Lembaga" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title="Lembaga"
                    description="Kelola profil dan data lembaga pendidikan."
                />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Lembaga</CardTitle>
                    <CardDescription>
                        {lembagas.length} lembaga tersedia.
                        {isAggregate ? ' (semua lembaga)' : ''}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {lembagas.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Belum ada lembaga.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Lembaga</TableHead>
                                    <TableHead>Jenis</TableHead>
                                    <TableHead className="text-center">
                                        Berita
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Santri
                                    </TableHead>
                                    <TableHead>Kontak</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lembagas.map((lembaga) => (
                                    <TableRow key={lembaga.id}>
                                        <TableCell className="max-w-xs">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                                    <Building2 className="size-4" />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium">
                                                        {lembaga.name}
                                                    </p>
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        {lembaga.description ??
                                                            '—'}
                                                    </p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {lembaga.type_label ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge variant="secondary">
                                                {lembaga.posts_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge variant="secondary">
                                                {lembaga.santris_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {lembaga.email ??
                                                lembaga.phone ??
                                                '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Link
                                                    href={lembagaEdit(
                                                        lembaga.slug,
                                                    )}
                                                >
                                                    <Pencil />
                                                    Edit Profil
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </>
    );
}

LembagaIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [{ title: 'Lembaga', href: lembagaIndex(props.team.slug) }],
});
