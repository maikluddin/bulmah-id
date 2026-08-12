import { Head, Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { create as daftarCreate } from '@/routes/daftar';
import { index as lembagaIndex } from '@/routes/lembaga';
import { show as newsShow } from '@/routes/news';
import { local as storageLocal } from '@/routes/storage';
import type { PublicLembaga, PublicPost } from '@/types/public';

type LembagaShowProps = {
    lembaga: PublicLembaga;
    posts: PublicPost[];
};

export default function LembagaShow({ lembaga, posts }: LembagaShowProps) {
    return (
        <>
            <Head title={lembaga.name} />

            <section className="border-b border-slate-200 bg-slate-100">
                <div className="mx-auto max-w-7xl px-6 py-14 md:px-12">
                    <Link
                        href={lembagaIndex()}
                        className="text-sm font-medium text-emerald-700 hover:underline"
                    >
                        &larr; Kembali ke Lembaga
                    </Link>

                    <div className="mt-6 flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div className="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                            <AppLogoIcon className="size-10 fill-current" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-extrabold text-emerald-900 sm:text-4xl">
                                {lembaga.name}
                            </h1>
                            <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                                {lembaga.type_label ? (
                                    <span>{lembaga.type_label}</span>
                                ) : null}
                                <span>{lembaga.santris_count} santri aktif</span>
                                {lembaga.yayasan ? (
                                    <span>
                                        Bernaung di bawah{' '}
                                        {lembaga.yayasan.name}
                                    </span>
                                ) : null}
                            </div>
                        </div>
                        <div className="sm:ml-auto">
                            <Link
                                href={daftarCreate(lembaga.slug)}
                                className="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-6 py-3 font-bold text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-gold-400"
                            >
                                Daftar Sekarang
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-6 py-14 md:px-12">
                <div className="grid gap-8 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                            <h2 className="text-xl font-bold text-emerald-900">
                                Tentang Lembaga
                            </h2>
                            <p className="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-600">
                                {lembaga.description}
                            </p>
                        </div>
                    </div>

                    <aside>
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 className="mb-4 text-xs font-bold tracking-widest text-emerald-700 uppercase">
                                Informasi Kontak
                            </h3>
                            <ul className="space-y-3 text-sm">
                                {lembaga.address ? (
                                    <li>
                                        <span className="block font-medium text-slate-700">
                                            Alamat
                                        </span>
                                        <span className="text-slate-500">
                                            {lembaga.address}
                                        </span>
                                    </li>
                                ) : null}
                                {lembaga.phone ? (
                                    <li>
                                        <span className="block font-medium text-slate-700">
                                            Telepon
                                        </span>
                                        <a
                                            href={`tel:${lembaga.phone}`}
                                            className="text-emerald-700 hover:underline"
                                        >
                                            {lembaga.phone}
                                        </a>
                                    </li>
                                ) : null}
                                {lembaga.email ? (
                                    <li>
                                        <span className="block font-medium text-slate-700">
                                            Email
                                        </span>
                                        <a
                                            href={`mailto:${lembaga.email}`}
                                            className="text-emerald-700 hover:underline"
                                        >
                                            {lembaga.email}
                                        </a>
                                    </li>
                                ) : null}
                            </ul>
                        </div>
                    </aside>
                </div>

                {posts.length > 0 ? (
                    <div className="mt-14">
                        <div className="mb-8 text-center">
                            <span className="inline-flex items-center rounded-full bg-gold-400/20 px-4 py-2 text-xs font-black tracking-widest text-gold-600 uppercase">
                                Berita & Artikel
                            </span>
                            <h2 className="mt-4 text-3xl font-extrabold text-emerald-900">
                                Berita {lembaga.name}
                            </h2>
                        </div>
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {posts.map((post) => (
                                <Link
                                    key={post.id}
                                    href={newsShow(post.slug)}
                                    className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                                >
                                    {post.cover_path ? (
                                        <img
                                            src={
                                                storageLocal(post.cover_path)
                                                    .url
                                            }
                                            alt={post.title}
                                            className="h-48 w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-48 w-full items-center justify-center bg-emerald-100 text-emerald-700">
                                            <AppLogoIcon className="size-10 fill-current" />
                                        </div>
                                    )}
                                    <div className="p-6">
                                        <h3 className="line-clamp-2 text-lg font-bold text-slate-900 transition group-hover:text-emerald-800">
                                            {post.title}
                                        </h3>
                                        <div className="mt-4 flex items-center justify-between text-[11px] text-slate-400">
                                            <span>
                                                {formatDate(post.published_at)}
                                            </span>
                                            <span className="font-bold text-gold-600 transition group-hover:text-gold-500">
                                                Baca &rarr;
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                ) : null}
            </section>
        </>
    );
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date));
}
