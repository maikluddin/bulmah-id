import { Head, Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { show as lembagaShow } from '@/routes/lembaga';
import type { PublicLembaga } from '@/types/public';

const LEMBAGA_IMAGES = [
    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=600',
    'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?q=80&w=600',
    'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=600',
];

type LembagaProps = {
    lembagas: PublicLembaga[];
};

export default function Lembaga({ lembagas }: LembagaProps) {
    return (
        <>
            <Head title="Lembaga" />

            <section className="border-y border-slate-200 bg-slate-100 py-20">
                <div className="mx-auto max-w-7xl px-6 md:px-12">
                    <div className="mx-auto mb-16 max-w-2xl text-center">
                        <h1 className="text-3xl font-extrabold text-emerald-900 md:text-4xl">
                            Lembaga Pendidikan
                        </h1>
                        <p className="mt-2 text-xs text-slate-600 md:text-sm">
                            Integrasi pendidikan formal, non-formal, dan
                            pengembangan keterampilan untuk santri Bustanul
                            Hikmah.
                        </p>
                    </div>

                    {lembagas.length === 0 ? (
                        <div className="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                            <AppLogoIcon className="mx-auto size-10 fill-current text-slate-300" />
                            <p className="mt-4 font-semibold">
                                Belum ada lembaga pendidikan yang terdaftar.
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                            {lembagas.map((lembaga, index) => (
                                <Link
                                    key={lembaga.id}
                                    href={lembagaShow(lembaga.slug)}
                                    className="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                                >
                                    <img
                                        src={
                                            LEMBAGA_IMAGES[
                                                index % LEMBAGA_IMAGES.length
                                            ]
                                        }
                                        alt={lembaga.name}
                                        className="h-48 w-full object-cover transition group-hover:scale-[1.02]"
                                    />
                                    <div className="p-6">
                                        {lembaga.type_label ? (
                                            <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold tracking-wider text-emerald-800 uppercase">
                                                {lembaga.type_label}
                                            </span>
                                        ) : null}
                                        <h2 className="mt-3 mb-3 border-b pb-2 text-lg font-bold text-slate-800 transition group-hover:text-emerald-800">
                                            {lembaga.name}
                                        </h2>
                                        <p className="line-clamp-3 text-xs text-slate-600">
                                            {lembaga.description}
                                        </p>
                                        <div className="mt-4 flex items-center justify-between text-xs">
                                            <span className="font-medium text-emerald-700">
                                                {lembaga.santris_count} santri
                                                aktif
                                            </span>
                                            <span className="font-bold text-gold-600 transition group-hover:text-gold-500">
                                                Selengkapnya &rarr;
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}
