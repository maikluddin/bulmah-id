import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, home, login, register } from '@/routes';


export default function Welcome() {
    const { auth, currentTeam } = usePage().props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-slate-50 p-6 dark:bg-slate-950">
                <div className="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div className="mx-auto flex size-14 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-700 to-emerald-500 text-gold-400">
                        <AppLogoIcon className="size-7 fill-current" />
                    </div>
                    <h1 className="mt-6 text-2xl font-extrabold text-emerald-900 dark:text-slate-100">
                        Selamat Datang
                    </h1>
                    <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Masuk ke dashboard atau kunjungi halaman publik
                        Bustanul Hikmah.
                    </p>
                    <div className="mt-8 flex flex-col gap-3">
                        <Link
                            href={home()}
                            className="inline-flex items-center justify-center rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-900"
                        >
                            Kunjungi Situs
                        </Link>
                        {auth.user ? (
                            <Link
                                href={dashboardUrl}
                                className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-100"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <div className="grid grid-cols-2 gap-3">
                                <Link
                                    href={login()}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-100"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-100"
                                >
                                    Daftar
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
