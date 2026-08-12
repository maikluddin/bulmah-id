import { Head, Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { show as lembagaShow } from '@/routes/lembaga';
import { index as newsIndex, show as newsShow } from '@/routes/news';
import { local as storageLocal } from '@/routes/storage';
import type { PublicPost } from '@/types/public';

type NewsShowProps = {
    post: PublicPost;
    related: PublicPost[];
};

export default function NewsShow({ post, related }: NewsShowProps) {
    return (
        <>
            <Head title={post.title} />

            <div className="mx-auto max-w-5xl px-6 py-10 md:px-12">
                <Link
                    href={newsIndex()}
                    className="mb-6 inline-flex items-center gap-2 text-sm text-slate-700 hover:text-emerald-800"
                >
                    <span aria-hidden="true">&larr;</span> Kembali ke Berita
                </Link>

                <article className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
                    {post.cover_path ? (
                        <img
                            src={storageLocal(post.cover_path).url}
                            alt={post.title}
                            className="h-80 w-full object-cover"
                        />
                    ) : null}
                    <div className="p-10">
                        <div className="flex flex-wrap items-center gap-2">
                            {post.category ? (
                                <span className="inline-flex items-center rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold tracking-widest text-emerald-700 uppercase">
                                    {post.category.name}
                                </span>
                            ) : null}
                            <Link
                                href={lembagaShow(post.team.slug)}
                                className="text-xs font-semibold tracking-widest text-emerald-700 uppercase hover:underline"
                            >
                                {post.team.name}
                            </Link>
                        </div>
                        <h1 className="mt-6 text-4xl font-extrabold text-emerald-900 text-balance">
                            {post.title}
                        </h1>
                        <div className="mt-4 text-sm text-slate-500">
                            <span>{formatDate(post.published_at)}</span>
                        </div>
                        <div className="prose prose-slate mt-10 max-w-none text-slate-700">
                            {post.body}
                        </div>
                    </div>
                </article>
            </div>

            {related.length > 0 ? (
                <section className="border-t border-slate-200 bg-slate-100">
                    <div className="mx-auto max-w-7xl px-6 py-14 md:px-12">
                        <div className="mb-8 text-center">
                            <span className="inline-flex items-center rounded-full bg-gold-400/20 px-4 py-2 text-xs font-black tracking-widest text-gold-600 uppercase">
                                Berita & Artikel
                            </span>
                            <h2 className="mt-4 text-3xl font-extrabold text-emerald-900">
                                Berita Lainnya
                            </h2>
                        </div>
                        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            {related.map((item) => (
                                <Link
                                    key={item.id}
                                    href={newsShow(item.slug)}
                                    className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                                >
                                    {item.cover_path ? (
                                        <img
                                            src={
                                                storageLocal(item.cover_path)
                                                    .url
                                            }
                                            alt={item.title}
                                            className="h-48 w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-48 w-full items-center justify-center bg-emerald-100 text-emerald-700">
                                            <AppLogoIcon className="size-10 fill-current" />
                                        </div>
                                    )}
                                    <div className="p-6">
                                        <h3 className="line-clamp-2 text-lg font-bold text-slate-900 transition group-hover:text-emerald-800">
                                            {item.title}
                                        </h3>
                                        <div className="mt-4 flex items-center justify-between text-[11px] text-slate-400">
                                            <span>
                                                {formatDate(item.published_at)}
                                            </span>
                                            <span className="font-bold text-gold-600 transition group-hover:text-gold-500">
                                                Baca &rarr;
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                        <div className="mt-10 text-center">
                            <Button className="rounded-xl" asChild>
                                <Link href={newsIndex()}>
                                    Lihat Semua Berita
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>
            ) : null}
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
