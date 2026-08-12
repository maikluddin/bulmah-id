import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { index as newsIndex, show as newsShow } from '@/routes/news';
import { local as storageLocal } from '@/routes/storage';
import type {
    PublicCategory,
    PublicLembaga,
    PublicPagination,
    PublicPost,
} from '@/types/public';

type NewsProps = {
    posts: PublicPagination<PublicPost>;
    lembagas: PublicLembaga[];
    categories: PublicCategory[];
    filters: {
        lembaga: string;
        kategori: string;
        search: string;
    };
};

export default function News({
    posts,
    lembagas,
    categories,
    filters,
}: NewsProps) {
    const [searchInput, setSearchInput] = useState(filters.search);
    const [lembaga, setLembaga] = useState(filters.lembaga || 'all');
    const [kategori, setKategori] = useState(filters.kategori || 'all');
    const [syncedFilters, setSyncedFilters] = useState(filters);

    if (syncedFilters !== filters) {
        setSyncedFilters(filters);
        setSearchInput(filters.search);
        setLembaga(filters.lembaga || 'all');
        setKategori(filters.kategori || 'all');
    }

    const navigate = useCallback((query: Record<string, string>) => {
        router.get(
            newsIndex({ query }).url,
            {},
            {
                only: ['posts', 'filters'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    }, []);

    const buildQuery = useCallback(
        (overrides: {
            search?: string;
            lembaga?: string;
            kategori?: string;
        }): Record<string, string> => {
            const query: Record<string, string> = {};

            const nextSearch = overrides.search ?? searchInput;
            const nextLembaga = overrides.lembaga ?? lembaga;
            const nextKategori = overrides.kategori ?? kategori;

            if (nextSearch.trim()) {
                query.q = nextSearch.trim();
            }

            if (nextLembaga !== 'all') {
                query.lembaga = nextLembaga;
            }

            if (nextKategori !== 'all') {
                query.kategori = nextKategori;
            }

            return query;
        },
        [searchInput, lembaga, kategori],
    );

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput.trim() !== filters.search) {
                navigate(buildQuery({}));
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchInput, buildQuery, navigate, filters.search]);

    const handleLembagaChange = (value: string) => {
        setLembaga(value);
        setKategori('all');
        navigate(buildQuery({ lembaga: value, kategori: 'all' }));
    };

    const handleKategoriChange = (value: string) => {
        setKategori(value);
        navigate(buildQuery({ kategori: value }));
    };

    const visibleCategories =
        lembaga === 'all'
            ? categories
            : categories.filter((category) => category.team_slug === lembaga);

    return (
        <>
            <Head title="Berita" />

            <div className="mx-auto max-w-7xl px-6 py-12 md:px-12">
                <div className="mb-10 text-center">
                    <span className="inline-flex items-center rounded-full bg-gold-400/20 px-4 py-2 text-xs font-black tracking-widest text-gold-600 uppercase">
                        Berita & Artikel
                    </span>
                    <h1 className="mt-6 text-4xl font-extrabold text-emerald-900">
                        Kabar Pesantren Terbaru
                    </h1>
                    <p className="mt-3 text-sm text-slate-600">
                        Informasi terbaru dari yayasan dan kegiatan santri.
                    </p>
                </div>

                <div className="mb-8">
                    <div className="relative mx-auto max-w-3xl">
                        <Search className="pointer-events-none absolute top-1/2 left-5 size-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            placeholder="Cari berita..."
                            className="rounded-full border-slate-200 bg-white py-4 pl-12 shadow-sm"
                            value={searchInput}
                            onChange={(event) =>
                                setSearchInput(event.target.value)
                            }
                        />
                    </div>
                    <div className="mx-auto mt-4 flex max-w-3xl flex-col gap-3 sm:flex-row">
                        <Select
                            value={lembaga}
                            onValueChange={handleLembagaChange}
                        >
                            <SelectTrigger className="w-full bg-white sm:w-56">
                                <SelectValue placeholder="Semua lembaga" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua lembaga
                                </SelectItem>
                                {lembagas.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={item.slug}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={kategori}
                            onValueChange={handleKategoriChange}
                        >
                            <SelectTrigger className="w-full bg-white sm:w-48">
                                <SelectValue placeholder="Semua kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua kategori
                                </SelectItem>
                                {visibleCategories.map((item) => (
                                    <SelectItem
                                        key={item.id}
                                        value={item.slug}
                                    >
                                        {item.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {filters.search ||
                        filters.lembaga ||
                        filters.kategori ? (
                            <Button
                                variant="outline"
                                className="bg-white"
                                onClick={() => navigate({})}
                            >
                                Reset
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    {posts.data.length === 0 ? (
                        <div className="col-span-3 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                            <AppLogoIcon className="mx-auto size-10 fill-current text-slate-300" />
                            <p className="mt-4 font-semibold">
                                Belum ada berita yang diterbitkan.
                            </p>
                            <p className="mt-2 text-sm">
                                Silakan kembali nanti atau coba kata kunci lain.
                            </p>
                        </div>
                    ) : (
                        posts.data.map((post) => (
                            <Link
                                key={post.id}
                                href={newsShow(post.slug)}
                                className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                            >
                                {post.cover_path ? (
                                    <img
                                        src={storageLocal(post.cover_path).url}
                                        alt={post.title}
                                        className="h-52 w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-52 w-full items-center justify-center bg-emerald-100 text-emerald-700">
                                        <AppLogoIcon className="size-12 fill-current" />
                                    </div>
                                )}
                                <div className="p-6">
                                    {post.category ? (
                                        <span className="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold tracking-widest text-emerald-800 uppercase">
                                            {post.category.name}
                                        </span>
                                    ) : null}
                                    <h2 className="mt-4 text-xl font-bold text-slate-900 transition group-hover:text-emerald-800">
                                        {post.title}
                                    </h2>
                                    <p className="mt-3 line-clamp-4 text-sm text-slate-600">
                                        {post.excerpt}
                                    </p>
                                    <div className="mt-6 flex items-center justify-between text-[11px] text-slate-400">
                                        <span>
                                            {formatDate(post.published_at)}
                                        </span>
                                        <span className="font-bold text-gold-600 transition group-hover:text-gold-500">
                                            Baca Selengkapnya &rarr;
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))
                    )}
                </div>

                {posts.last_page > 1 ? (
                    <Pagination
                        currentPage={posts.current_page}
                        lastPage={posts.last_page}
                    />
                ) : null}
            </div>
        </>
    );
}

function Pagination({
    currentPage,
    lastPage,
}: {
    currentPage: number;
    lastPage: number;
}) {
    const pages = Array.from({ length: lastPage }, (_, i) => i + 1);

    return (
        <nav className="mt-10 flex items-center justify-center gap-1.5">
            {currentPage > 1 ? (
                <Link
                    href={pageUrl(currentPage - 1)}
                    className="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Sebelumnya
                </Link>
            ) : null}
            {pages.map((page) => (
                <Link
                    key={page}
                    href={pageUrl(page)}
                    className={cn(
                        'rounded-md border border-slate-200 px-3 py-2 text-sm',
                        page === currentPage
                            ? 'bg-emerald-800 text-white'
                            : 'text-slate-600 hover:bg-slate-100',
                    )}
                >
                    {page}
                </Link>
            ))}
            {currentPage < lastPage ? (
                <Link
                    href={pageUrl(currentPage + 1)}
                    className="rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
                >
                    Berikutnya
                </Link>
            ) : null}
        </nav>
    );
}

function pageUrl(page: number): string {
    return newsIndex({ query: { page } }).url;
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date));
}
