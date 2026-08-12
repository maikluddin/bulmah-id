import { Head, Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { index as lembagaIndex, show as lembagaShow } from '@/routes/lembaga';
import { index as newsIndex, show as newsShow } from '@/routes/news';
import { local as storageLocal } from '@/routes/storage';
import type {
    PublicLembaga,
    PublicPost,
    PublicStats,
    PublicYayasan,
} from '@/types/public';

type HomeProps = {
    yayasan: PublicYayasan | null;
    lembagas: PublicLembaga[];
    posts: PublicPost[];
    stats: PublicStats;
};

export default function Home({ yayasan, lembagas, posts, stats }: HomeProps) {
    const brandName = yayasan?.name ?? 'PP Bustanul Hikmah';

    return (
        <>
            <Head title="Beranda" />

            {/* Hero */}
            <section className="relative overflow-hidden bg-emerald-950 text-white">
                <div className="relative h-[520px] w-full md:h-[620px]">
                    <div className="absolute inset-0 z-10 bg-gradient-to-t from-emerald-950 via-emerald-950/60 to-black/30" />
                    <img
                        src="https://images.unsplash.com/photo-1542810634-71277d95dcbb?q=80&w=1200"
                        alt={brandName}
                        className="h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 z-20 mx-auto flex max-w-5xl flex-col items-center justify-center px-6 text-center">
                        <span className="mb-4 rounded-full bg-gold-400 px-4 py-1.5 text-xs font-black tracking-widest text-emerald-950 uppercase shadow-lg">
                            Selamat Datang di Portal Resmi
                        </span>
                        <h1 className="mb-4 text-3xl leading-tight font-extrabold text-white drop-shadow-md md:text-5xl">
                            {brandName}
                        </h1>
                        <p className="mb-8 max-w-2xl font-light text-emerald-100 text-sm md:text-lg">
                            {yayasan?.description ??
                                'Pondok pesantren dan lembaga pendidikan Islam yang berkomitmen mencetak santri unggul, mandiri, dan berkarakter.'}
                        </p>
                        <div className="flex flex-wrap justify-center gap-4">
                            <Link
                                href={lembagaIndex()}
                                className="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-7 py-3 font-bold text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-gold-400"
                            >
                                Daftar PPDB Online
                            </Link>
                            <Link
                                href={newsIndex()}
                                className="inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3 font-bold text-emerald-950 shadow-lg transition hover:-translate-y-0.5"
                            >
                                Lihat Berita & Artikel
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Profil Singkat */}
            <section className="mx-auto max-w-7xl px-6 py-20 md:px-12">
                <div className="mx-auto mb-16 max-w-3xl text-center">
                    <span className="rounded-full bg-gold-400/20 px-3.5 py-1.5 text-xs font-black tracking-widest text-gold-600 uppercase">
                        Profil Lengkap
                    </span>
                    <h2 className="mt-3 text-3xl font-extrabold text-emerald-900 md:text-4xl">
                        Yayasan {brandName}
                    </h2>
                    <p className="mt-3 text-xs text-slate-600 md:text-sm">
                        {yayasan?.address ??
                            'Jalan Raya Mantup, Desa Dumpi Agung, Kecamatan Kembangbahu, Kabupaten Lamongan, Jawa Timur.'}
                    </p>
                </div>

                <div className="mb-16 grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                    <div className="relative">
                        <div className="absolute -top-4 -left-4 -z-10 size-24 rounded-2xl bg-gold-400/30" />
                        <img
                            src="https://images.unsplash.com/photo-1544717305-2782549b5136?q=80&w=800"
                            alt="Pengasuh Pondok"
                            className="h-[420px] w-full rounded-2xl border-4 border-white object-cover shadow-xl"
                        />
                    </div>
                    <div className="space-y-4">
                        <span className="text-xs font-bold tracking-widest text-emerald-700 uppercase">
                            Pengasuh Utama
                        </span>
                        <h3 className="text-2xl font-bold text-slate-800">
                            Dr. H. Ahmad Darmawan, S.H.I., M.H.I.
                        </h3>
                        <p className="text-xs leading-relaxed text-slate-600 md:text-sm">
                            "Berdiri pada tahun 2008 dan resmi menerima santri
                            pertama pada tahun 2010, Pondok Pesantren Bustanul
                            Hikmah terus berikhtiar mencetak generasi penerus
                            bangsa yang alim, berakhlak mulia, serta siap
                            menghadapi tantangan zaman."
                        </p>
                        <div className="rounded-r-xl border-l-4 border-emerald-700 bg-emerald-50 p-4">
                            <h4 className="text-xs font-bold text-emerald-900 uppercase">
                                Visi Utama
                            </h4>
                            <p className="mt-1 text-xs italic text-slate-700">
                                "Menjadi pusat perkembangan pendidikan unggul di
                                wilayah Lamongan bagian selatan dengan berhaluan
                                Ahlussunnah wal Jama'ah An-Nahdliyah."
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Statistik */}
            <section className="border-y border-slate-200 bg-slate-100 py-20">
                <div className="mx-auto grid max-w-7xl gap-6 px-6 sm:grid-cols-3 md:px-12">
                    {[
                        { label: 'Lembaga Pendidikan', value: stats.lembaga },
                        { label: 'Santri Aktif', value: stats.santri },
                        { label: 'Artikel Diterbitkan', value: stats.post },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-sm"
                        >
                            <p className="text-4xl font-bold text-emerald-700">
                                {stat.value}
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                {stat.label}
                            </p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Lembaga Pendidikan */}
            <section className="mx-auto max-w-7xl px-6 py-20 md:px-12">
                <div className="mb-12 text-center">
                    <span className="rounded-full bg-emerald-100 px-3.5 py-1.5 text-xs font-black tracking-widest text-emerald-800 uppercase">
                        Lembaga Binaan
                    </span>
                    <h2 className="mt-3 text-3xl font-extrabold text-emerald-900 md:text-4xl">
                        Lembaga Pendidikan
                    </h2>
                </div>

                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {lembagas.slice(0, 3).map((lembaga) => (
                        <Link
                            key={lembaga.id}
                            href={lembagaShow(lembaga.slug)}
                            className="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                        >
                            <div className="flex h-40 items-center justify-center bg-gradient-to-tr from-emerald-700 to-emerald-500 text-gold-400">
                                <AppLogoIcon className="size-12 fill-current" />
                            </div>
                            <div className="p-6">
                                <h3 className="text-lg font-bold text-slate-800 transition group-hover:text-emerald-800">
                                    {lembaga.name}
                                </h3>
                                {lembaga.type_label ? (
                                    <span className="mt-2 inline-block rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold tracking-wider text-emerald-800 uppercase">
                                        {lembaga.type_label}
                                    </span>
                                ) : null}
                                <p className="mt-3 line-clamp-3 text-xs text-slate-600">
                                    {lembaga.description}
                                </p>
                                <p className="mt-4 text-xs font-bold text-emerald-700">
                                    {lembaga.santris_count} santri aktif
                                </p>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* Berita Terbaru */}
            <section className="border-y border-slate-200 bg-slate-100 py-20">
                <div className="mx-auto max-w-7xl px-6 md:px-12">
                    <div className="mb-12 text-center">
                        <span className="rounded-full bg-gold-400/20 px-3.5 py-1.5 text-xs font-black tracking-widest text-gold-600 uppercase">
                            Berita & Artikel
                        </span>
                        <h2 className="mt-3 text-3xl font-extrabold text-emerald-900 md:text-4xl">
                            Kabar Pesantren Terbaru
                        </h2>
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.map((post) => (
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
                                    <div className="flex items-center gap-2">
                                        {post.category ? (
                                            <span className="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold tracking-widest text-emerald-800 uppercase">
                                                {post.category.name}
                                            </span>
                                        ) : null}
                                        <span className="text-[11px] text-slate-400">
                                            {post.team.name}
                                        </span>
                                    </div>
                                    <h3 className="mt-4 line-clamp-2 text-xl font-bold text-slate-900 transition group-hover:text-emerald-800">
                                        {post.title}
                                    </h3>
                                    <p className="mt-3 line-clamp-2 text-sm text-slate-600">
                                        {post.excerpt}
                                    </p>
                                    <div className="mt-6 flex items-center justify-between text-[11px] text-slate-400">
                                        <span>{formatDate(post.published_at)}</span>
                                        <span className="font-bold text-gold-600 transition group-hover:text-gold-500">
                                            Baca Selengkapnya &rarr;
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="mx-auto max-w-7xl px-6 py-20 md:px-12">
                <div className="rounded-3xl bg-emerald-950 px-6 py-16 text-center text-white shadow-lg sm:px-12">
                    <h2 className="text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Siap Mendaftar Sebagai Santri?
                    </h2>
                    <p className="mx-auto mt-3 max-w-xl text-sm text-emerald-200/80">
                        Bergabunglah bersama kami untuk pendidikan agama dan
                        umum yang berkualitas. Hubungi lembaga pilihan Anda
                        untuk informasi pendaftaran lebih lanjut.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={lembagaIndex()}
                            className="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-7 py-3 font-bold text-emerald-950 shadow-lg transition hover:-translate-y-0.5 hover:bg-gold-400"
                        >
                            Cari Lembaga
                        </Link>
                        {yayasan?.phone ? (
                            <a
                                href={`tel:${yayasan.phone}`}
                                className="inline-flex items-center gap-2 rounded-xl border border-white/40 px-7 py-3 font-bold text-white transition hover:bg-white/10"
                            >
                                {yayasan.phone}
                            </a>
                        ) : null}
                    </div>
                </div>
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
