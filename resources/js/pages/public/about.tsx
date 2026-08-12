import { Head, Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { index as lembagaIndex } from '@/routes/lembaga';
import type { PublicYayasan } from '@/types/public';

type AboutProps = {
    yayasan: PublicYayasan | null;
};

export default function About({ yayasan }: AboutProps) {
    return (
        <>
            <Head title="Profil" />

            <section className="mx-auto max-w-7xl px-6 py-20 md:px-12">
                <div className="mx-auto mb-16 max-w-3xl text-center">
                    <span className="inline-flex items-center rounded-full bg-gold-400/20 px-4 py-2 text-xs font-black tracking-widest text-gold-600 uppercase">
                        Profil Pondok
                    </span>
                    <h1 className="mt-3 text-3xl font-extrabold text-emerald-900 md:text-4xl">
                        Yayasan {yayasan?.name ?? 'Bustanul Hikmah'}
                    </h1>
                    <p className="mt-3 text-xs text-slate-600 md:text-sm">
                        {yayasan?.description ??
                            'Pondok yang menguatkan penguasaan ilmu agama dan keterampilan abad 21 untuk generasi muda.'}
                    </p>
                </div>

                <div className="mb-16 grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                    <div className="relative">
                        <div className="absolute -top-4 -left-4 -z-10 size-24 rounded-2xl bg-gold-400/30" />
                        <img
                            src="https://images.unsplash.com/photo-1544717305-2782549b5136?q=80&w=800"
                            alt="Pondok Pesantren Bustanul Hikmah"
                            className="h-[420px] w-full rounded-2xl border-4 border-white object-cover shadow-xl"
                        />
                    </div>

                    <div className="space-y-4">
                        <div className="flex size-14 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-700 to-emerald-500 text-gold-400 shadow-sm">
                            <AppLogoIcon className="size-7 fill-current" />
                        </div>
                        <h2 className="text-2xl font-bold text-slate-800">
                            {yayasan?.name ?? 'PP Bustanul Hikmah'}
                        </h2>
                        <div className="rounded-r-xl border-l-4 border-emerald-700 bg-emerald-50 p-4">
                            <h3 className="text-xs font-bold text-emerald-900 uppercase">
                                Sekilas Tentang Kami
                            </h3>
                            <p className="mt-1 text-xs italic text-slate-700">
                                {yayasan?.description ??
                                    'Menjadi pusat perkembangan pendidikan unggul di wilayah Lamongan bagian selatan dengan berhaluan Ahlussunnah wal Jama\'ah An-Nahdliyah.'}
                            </p>
                        </div>
                        <p className="text-xs leading-relaxed text-slate-600 md:text-sm">
                            Pondok pesantren dan lembaga pendidikan Islam yang
                            berkomitmen mencetak santri unggul, mandiri, dan
                            berkarakter.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 className="mb-4 text-xl font-bold text-emerald-900">
                            Informasi Kontak
                        </h3>
                        <ul className="space-y-3 text-sm text-slate-600">
                            {yayasan?.address ? (
                                <li>
                                    <span className="block font-medium text-slate-700">
                                        Alamat
                                    </span>
                                    <span className="text-slate-500">
                                        {yayasan.address}
                                    </span>
                                </li>
                            ) : null}
                            {yayasan?.phone ? (
                                <li>
                                    <span className="block font-medium text-slate-700">
                                        Telepon
                                    </span>
                                    <a
                                        href={`tel:${yayasan.phone}`}
                                        className="text-emerald-700 hover:underline"
                                    >
                                        {yayasan.phone}
                                    </a>
                                </li>
                            ) : null}
                            {yayasan?.email ? (
                                <li>
                                    <span className="block font-medium text-slate-700">
                                        Email
                                    </span>
                                    <a
                                        href={`mailto:${yayasan.email}`}
                                        className="text-emerald-700 hover:underline"
                                    >
                                        {yayasan.email}
                                    </a>
                                </li>
                            ) : null}
                            {yayasan?.website ? (
                                <li>
                                    <span className="block font-medium text-slate-700">
                                        Website
                                    </span>
                                    <a
                                        href={yayasan.website}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-emerald-700 hover:underline"
                                    >
                                        {yayasan.website}
                                    </a>
                                </li>
                            ) : null}
                        </ul>
                    </div>

                    {yayasan?.lembaga_count ? (
                        <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                            <h3 className="mb-4 text-xl font-bold text-emerald-900">
                                Lembaga Binaan
                            </h3>
                            <p className="text-sm leading-relaxed text-slate-600">
                                {yayasan.name} menaungi{' '}
                                <span className="font-bold text-emerald-700">
                                    {yayasan.lembaga_count}
                                </span>{' '}
                                lembaga pendidikan dengan berbagai jenjang,
                                mulai dari pendidikan formal, non-formal,
                                hingga pengembangan keterampilan santri.
                            </p>
                            <Link
                                href={lembagaIndex()}
                                className="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-900"
                            >
                                Lihat Lembaga
                            </Link>
                        </div>
                    ) : null}
                </div>
            </section>
        </>
    );
}
