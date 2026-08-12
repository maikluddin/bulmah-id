import { Head, router, useForm } from '@inertiajs/react';
import { BookOpen, Check, ChevronsUpDown, Search } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent as ReactKeyboardEvent } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { index as setoranIndex, store } from '@/routes/admin/setoran';
import type {
    AdminHafalanKategoriOption,
    AdminOption,
    AdminSetoranRecord,
    AdminSetoranSantri,
    AdminTeam,
} from '@/types';

type Props = {
    team: AdminTeam;
    santris: AdminSetoranSantri[];
    recent: AdminSetoranRecord[];
    types: AdminOption[];
    kategoris: AdminHafalanKategoriOption[];
    filters: { search: string };
};

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}

export default function SetoranIndex({
    team,
    santris,
    recent,
    types,
    kategoris,
    filters,
}: Props) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [prevFilters, setPrevFilters] = useState(filters);

    const { data, setData, post, reset, processing, errors } = useForm({
        santri_id: '',
        kategori_id: '',
        type: '',
        juz: '',
        surat: '',
        ayat_from: '',
        ayat_to: '',
        tanggal: new Date().toISOString().slice(0, 10),
        keterangan: '',
    });

    const kategoriGroups = kategoris.reduce<
        Record<string, AdminHafalanKategoriOption[]>
    >((acc, kategori) => {
        (acc[kategori.jenis] ??= []).push(kategori);

        return acc;
    }, {});

    if (filters !== prevFilters) {
        setPrevFilters(filters);
        setSearchInput(filters.search);
    }

    const navigate = useCallback(() => {
        router.get(
            setoranIndex(team.slug, {
                query: searchInput.trim() ? { search: searchInput.trim() } : {},
            }),
            {},
            {
                only: ['santris', 'filters'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, [team.slug, searchInput]);

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate();
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, navigate, filters.search]);

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(store.url(team.slug), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setData('tanggal', new Date().toISOString().slice(0, 10));
            },
        });
    };

    const handleSantriSelect = (id: string) => {
        setData('santri_id', id);
        setSearchInput('');
    };

    return (
        <>
            <Head title="Setoran Hafalan" />

            <Heading
                title="Setoran Hafalan"
                description="Catat setoran hafalan santri dengan cepat."
            />

            <div className="grid gap-6 lg:grid-cols-5">
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Catat Setoran</CardTitle>
                        <CardDescription>
                            Pilih santri lalu isi materi hafalan yang
                            disetorkan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={onSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="santri_id">Santri</Label>
                                <SantriCombobox
                                    santris={santris}
                                    value={data.santri_id}
                                    onSelect={handleSantriSelect}
                                    searchInput={searchInput}
                                    onSearchInputChange={setSearchInput}
                                />
                                <InputError message={errors.santri_id} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="type">Tipe Setoran</Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={(value) =>
                                            setData('type', value)
                                        }
                                    >
                                        <SelectTrigger id="type">
                                            <SelectValue placeholder="Pilih tipe" />
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
                                <div className="space-y-2">
                                    <Label htmlFor="tanggal">Tanggal</Label>
                                    <Input
                                        id="tanggal"
                                        type="date"
                                        value={data.tanggal}
                                        onChange={(event) =>
                                            setData(
                                                'tanggal',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.tanggal} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="kategori_id">
                                    Kategori (opsional)
                                </Label>
                                <Select
                                    value={data.kategori_id}
                                    onValueChange={(value) =>
                                        setData('kategori_id', value)
                                    }
                                >
                                    <SelectTrigger id="kategori_id">
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(kategoriGroups).map(
                                            ([jenis, groupItems]) => (
                                                <SelectGroup key={jenis}>
                                                    <SelectLabel>
                                                        {
                                                            groupItems[0]
                                                                .jenis_label
                                                        }
                                                    </SelectLabel>
                                                    {groupItems.map(
                                                        (kategori) => (
                                                            <SelectItem
                                                                key={
                                                                    kategori.id
                                                                }
                                                                value={String(
                                                                    kategori.id,
                                                                )}
                                                            >
                                                                {kategori.nama}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectGroup>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.kategori_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="surat">Surat</Label>
                                <Input
                                    id="surat"
                                    value={data.surat}
                                    onChange={(event) =>
                                        setData('surat', event.target.value)
                                    }
                                    placeholder="Contoh: Al-Baqarah"
                                />
                                <InputError message={errors.surat} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="juz">Juz (opsional)</Label>
                                    <Input
                                        id="juz"
                                        type="number"
                                        min={1}
                                        max={30}
                                        value={data.juz}
                                        onChange={(event) =>
                                            setData('juz', event.target.value)
                                        }
                                        placeholder="1–30"
                                    />
                                    <InputError message={errors.juz} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ayat_from">
                                        Ayat Awal (opsional)
                                    </Label>
                                    <Input
                                        id="ayat_from"
                                        type="number"
                                        min={1}
                                        value={data.ayat_from}
                                        onChange={(event) =>
                                            setData(
                                                'ayat_from',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="1"
                                    />
                                    <InputError message={errors.ayat_from} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ayat_to">
                                        Ayat Akhir (opsional)
                                    </Label>
                                    <Input
                                        id="ayat_to"
                                        type="number"
                                        min={1}
                                        value={data.ayat_to}
                                        onChange={(event) =>
                                            setData(
                                                'ayat_to',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="2"
                                    />
                                    <InputError message={errors.ayat_to} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Textarea
                                    id="keterangan"
                                    value={data.keterangan}
                                    onChange={(event) =>
                                        setData(
                                            'keterangan',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Keterangan tambahan (opsional)"
                                />
                                <InputError message={errors.keterangan} />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    <BookOpen className="size-4" />
                                    Simpan Setoran
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Setoran Terbaru</CardTitle>
                        <CardDescription>
                            10 setoran hafalan terakhir.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recent.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">
                                Belum ada setoran.
                            </p>
                        ) : (
                            <ul className="space-y-4">
                                {recent.map((record) => (
                                    <li
                                        key={record.id}
                                        className="flex items-start justify-between gap-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {record.santri.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {record.surat}
                                                {record.ayat_from
                                                    ? ` ayat ${record.ayat_from}${record.ayat_to && record.ayat_to !== record.ayat_from ? `–${record.ayat_to}` : ''}`
                                                    : ''}
                                                {record.juz
                                                    ? ` (Juz ${record.juz})`
                                                    : ''}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(record.tanggal)}
                                                {record.ustad?.name
                                                    ? ` · ${record.ustad.name}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1">
                                            {record.kategori ? (
                                                <span className="text-xs text-muted-foreground">
                                                    {record.kategori.nama}
                                                </span>
                                            ) : null}
                                            <Badge variant="secondary">
                                                {record.type_label}
                                            </Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

type SantriComboboxProps = {
    santris: AdminSetoranSantri[];
    value: string;
    onSelect: (id: string) => void;
    searchInput: string;
    onSearchInputChange: (value: string) => void;
};

function SantriCombobox({
    santris,
    value,
    onSelect,
    searchInput,
    onSearchInputChange,
}: SantriComboboxProps) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);

    const selected = santris.find((santri) => String(santri.id) === value);

    useEffect(() => {
        if (open) {
            searchRef.current?.focus();
        }
    }, [open]);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);

        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleKeyDown = (event: ReactKeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Escape') {
            setOpen(false);
        } else if (event.key === 'Enter' && santris.length > 0) {
            const first = santris[0];

            onSelect(String(first.id));
            onSearchInputChange('');
            setOpen(false);
        }
    };

    return (
        <div ref={containerRef} className="relative">
            <Button
                id="santri_id"
                type="button"
                variant="outline"
                onClick={() => setOpen((isOpen) => !isOpen)}
                className="w-full justify-between"
            >
                {selected ? (
                    <span className="text-left font-normal">
                        {selected.name}{' '}
                        <span className="text-muted-foreground">
                            ({selected.nis} · {selected.gender_label}
                            {selected.kelas ? ` · ${selected.kelas.nama}` : ''})
                        </span>
                    </span>
                ) : (
                    <span className="font-normal text-muted-foreground">
                        Cari nama santri...
                    </span>
                )}
                <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
            </Button>

            {open && (
                <div className="absolute z-50 mt-2 w-full rounded-lg border bg-popover text-popover-foreground shadow-md">
                    <div className="flex items-center gap-2 border-b px-3">
                        <Search className="h-4 w-4 shrink-0 text-muted-foreground" />
                        <Input
                            ref={searchRef}
                            value={searchInput}
                            onChange={(event) =>
                                onSearchInputChange(event.target.value)
                            }
                            onKeyDown={handleKeyDown}
                            placeholder="Ketik nama untuk mencari..."
                            className="border-0 shadow-none focus-visible:ring-0"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto p-1">
                        {santris.length === 0 ? (
                            <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                                Tidak ada santri yang cocok.
                            </p>
                        ) : (
                            santris.map((santri) => {
                                const isSelected =
                                    String(santri.id) === value;

                                return (
                                    <button
                                        key={santri.id}
                                        type="button"
                                        onClick={() => {
                                            onSelect(String(santri.id));
                                            onSearchInputChange('');
                                            setOpen(false);
                                        }}
                                        className={cn(
                                            'flex w-full items-center justify-between gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground',
                                            isSelected && 'bg-accent',
                                        )}
                                    >
                                        <span>
                                            {santri.name}
                                            <span className="block text-xs text-muted-foreground">
                                                {santri.nis} ·{' '}
                                                {santri.gender_label}
                                                {santri.kelas
                                                    ? ` · ${santri.kelas.nama}`
                                                    : ''}
                                            </span>
                                        </span>
                                        {isSelected ? (
                                            <Check className="h-4 w-4 shrink-0" />
                                        ) : null}
                                    </button>
                                );
                            })
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

SetoranIndex.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'Setoran Hafalan', href: setoranIndex(props.team.slug) },
    ],
});
