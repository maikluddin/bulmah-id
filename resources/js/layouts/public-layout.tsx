import { Link, usePage } from '@inertiajs/react';
import { Instagram, Menu, Moon, Sun, X, Youtube } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { useAppearance } from '@/hooks/use-appearance';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { cn, toUrl } from '@/lib/utils';
import { about, dashboard, home, login, register } from '@/routes';
import { index as lembagaIndex } from '@/routes/lembaga';
import { index as newsIndex } from '@/routes/news';

const navigation = [
    { name: 'Beranda', href: home() },
    { name: 'Berita & Artikel', href: newsIndex() },
    { name: 'Lembaga', href: lembagaIndex() },
    { name: 'Profil', href: about() },
];

export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, currentTeam, site } = usePage().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';
    useFlashToast();

    const brandName = site?.name ?? 'Bustanul Hikmah';
    const isDark = resolvedAppearance === 'dark';

    const isActive = (href: Parameters<typeof isCurrentUrl>[0]) => {
        const path = toUrl(href);

        return path === '/' ? isCurrentUrl(path) : isCurrentOrParentUrl(path);
    };

    return (
        <div className="font-public relative flex min-h-svh flex-col bg-slate-50 text-slate-800 antialiased dark:bg-slate-900 dark:text-slate-100">
            {/* Topbar Info */}
            <div className="border-b border-emerald-800 bg-emerald-900 px-4 py-2.5 text-xs text-emerald-100 md:px-12">
                <div className="mx-auto flex max-w-7xl items-center justify-between">
                    <div className="flex items-center space-x-6">
                        {site?.address ? (
                            <span className="flex items-center">
                                <svg
                                    className="mr-2 size-3.5 fill-current text-gold-400"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M9.69 18.933l.003-.001C9.89 18.71 15.5 12.53 15.5 7.5a5.5 5.5 0 10-11 0c0 5.03 5.61 11.21 5.807 11.432a1 1 0 001.383.001zM10 10.5a3 3 0 100-6 3 3 0 000 6z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                                {site.address}
                            </span>
                        ) : null}
                        {site?.phone ? (
                            <span className="hidden md:inline-flex items-center">
                                <svg
                                    className="mr-2 size-3.5 fill-current text-gold-400"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" />
                                </svg>
                                {site.phone}
                            </span>
                        ) : null}
                    </div>
                    <div className="flex items-center space-x-4 text-sm">
                        <a
                            href="https://www.tiktok.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="transition hover:text-gold-400"
                            aria-label="TikTok"
                        >
                            <svg className="size-3.5 fill-current" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z" />
                            </svg>
                        </a>
                        <a
                            href="https://www.instagram.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="transition hover:text-gold-400"
                            aria-label="Instagram"
                        >
                            <Instagram className="size-3.5" />
                        </a>
                        <a
                            href="https://www.youtube.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="transition hover:text-gold-400"
                            aria-label="YouTube"
                        >
                            <Youtube className="size-3.5" />
                        </a>
                    </div>
                </div>
            </div>

            {/* Main Navigation Bar */}
            <nav className="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900">
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-3 md:px-12">
                    <Link href={home()} className="flex shrink-0 items-center space-x-3">
                        <div className="flex size-11 items-center justify-center rounded-xl border border-gold-400/40 bg-gradient-to-tr from-emerald-700 to-emerald-500 text-xl font-black text-gold-400 shadow-md">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <div>
                            <h1 className="text-lg leading-none font-extrabold tracking-tight text-emerald-900 dark:text-emerald-400">
                                {brandName}
                            </h1>
                            <p className="mt-0.5 text-[10px] font-bold tracking-wider text-gold-600 uppercase dark:text-gold-400">
                                {site?.address?.split(',').slice(-2).join(' - ') || 'Lamongan - Jawa Timur'}
                            </p>
                        </div>
                    </Link>

                    {/* Desktop Menu */}
                    <div className="hidden items-center space-x-6 text-xs font-bold text-slate-700 lg:flex dark:text-slate-200">
                        {navigation.map((item) => (
                            <Link
                                key={toUrl(item.href)}
                                href={item.href}
                                className={cn(
                                    'transition hover:text-emerald-700 dark:hover:text-gold-400',
                                    isActive(item.href) && 'text-emerald-700 dark:text-gold-400',
                                )}
                            >
                                {item.name}
                            </Link>
                        ))}
                    </div>

                    {/* Actions / Auth Buttons */}
                    <div className="flex items-center gap-3">
                        <div className="hidden shrink-0 lg:block">
                            {auth.user ? (
                                <Link
                                    href={dashboardUrl}
                                    className="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white shadow transition hover:bg-emerald-900"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-1.5 rounded-xl border border-emerald-700 px-4 py-2.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-slate-800"
                                    >
                                        Login
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-gold-500 to-gold-400 px-4 py-2.5 text-xs font-bold text-emerald-950 shadow transition hover:from-gold-600 hover:to-gold-500"
                                    >
                                        Daftar
                                    </Link>
                                </div>
                            )}
                        </div>

                        {/* Hamburger Button Mobile */}
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen((open) => !open)}
                            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-slate-700 shadow-sm transition hover:bg-slate-100 lg:hidden dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            aria-label="Toggle navigation"
                        >
                            {mobileMenuOpen ? (
                                <X className="size-5" />
                            ) : (
                                <Menu className="size-5" />
                            )}
                        </button>
                    </div>
                </div>

                {/* Mobile Menu */}
                {mobileMenuOpen ? (
                    <div className="border-t border-slate-200 bg-white lg:hidden dark:border-slate-800 dark:bg-slate-900">
                        <div className="mx-auto max-w-7xl space-y-2 px-4 pt-2 pb-4 text-sm font-bold text-slate-700 dark:text-slate-200">
                            {navigation.map((item) => (
                                <Link
                                    key={toUrl(item.href)}
                                    href={item.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className={cn(
                                        'block rounded-xl px-4 py-2.5 transition hover:bg-emerald-50 hover:text-emerald-900 dark:hover:bg-slate-800',
                                        isActive(item.href) &&
                                            'text-emerald-900 dark:text-gold-400',
                                    )}
                                >
                                    {item.name}
                                </Link>
                            ))}

                            <div className="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                                {auth.user ? (
                                    <Link
                                        href={dashboardUrl}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className="col-span-2 block rounded-xl bg-emerald-800 px-4 py-3 text-center font-bold text-white shadow-sm transition hover:bg-emerald-900"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className="block rounded-xl border border-emerald-700 px-4 py-2.5 text-center font-bold text-emerald-800 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-slate-800"
                                        >
                                            Login
                                        </Link>
                                        <Link
                                            href={register()}
                                            onClick={() => setMobileMenuOpen(false)}
                                            className="block rounded-xl bg-gradient-to-r from-gold-500 to-gold-400 px-4 py-2.5 text-center font-bold text-emerald-950 shadow-sm transition hover:from-gold-600 hover:to-gold-500"
                                        >
                                            Daftar
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                ) : null}
            </nav>

            {/* Main Content */}
            <main className="flex-1">{children}</main>

            {/* Floating Dark Mode Toggle */}
            <div className="fixed right-6 bottom-6 z-50">
                <button
                    type="button"
                    onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
                    title="Beralih Tema"
                    className={cn(
                        'relative inline-flex h-11 w-20 cursor-pointer items-center rounded-full border-2 border-gold-400/50 p-1 shadow-2xl transition-colors duration-300 focus:outline-none',
                        isDark ? 'bg-slate-800' : 'bg-emerald-800',
                    )}
                >
                    <span
                        className={cn(
                            'absolute left-2.5 text-xs font-bold text-gold-400 transition-opacity duration-300',
                            isDark ? 'opacity-40' : 'opacity-100',
                        )}
                    >
                        <Sun className="size-4" />
                    </span>
                    <span
                        className={cn(
                            'absolute right-2.5 text-xs font-bold text-gold-400 transition-opacity duration-300',
                            isDark ? 'opacity-100' : 'opacity-40',
                        )}
                    >
                        <Moon className="size-4" />
                    </span>
                    <span
                        className={cn(
                            'z-10 flex size-8 items-center justify-center rounded-full bg-gold-400 shadow-lg transition-transform duration-300 ease-in-out',
                            isDark ? 'translate-x-9' : 'translate-x-0',
                        )}
                    >
                        {isDark ? (
                            <Moon className="size-4 text-slate-900" />
                        ) : (
                            <Sun className="size-4 text-emerald-950" />
                        )}
                    </span>
                </button>
            </div>

            {/* Footer */}
            <footer className="border-t border-emerald-900 bg-emerald-950 pt-16 pb-8 text-white">
                <div className="mx-auto mb-10 grid max-w-7xl grid-cols-1 gap-8 px-6 text-sm md:grid-cols-2 md:px-12 xl:grid-cols-4">
                    <div className="md:col-span-2 xl:col-span-1">
                        <div className="mb-4 flex items-center space-x-3">
                            <div className="flex size-10 items-center justify-center rounded-xl bg-gold-400 text-xl font-black text-emerald-950">
                                <AppLogoIcon className="size-5 fill-current" />
                            </div>
                            <h3 className="text-lg font-extrabold tracking-tight text-gold-400 uppercase">
                                {brandName}
                            </h3>
                        </div>
                        <p className="leading-relaxed text-emerald-200/80">
                            {site?.description ??
                                'Yayasan Pondok Pesantren Bustanul Hikmah adalah lembaga pendidikan Islam modern yang menyeimbangkan ilmu agama, pembinaan akhlak, dan pengembangan potensi santri di Lamongan.'}
                        </p>
                    </div>

                    <div>
                        <h4 className="mb-4 text-sm font-bold text-gold-400 uppercase">
                            Informasi Yayasan
                        </h4>
                        <ul className="space-y-2 text-emerald-200/80">
                            {site?.address ? (
                                <li className="flex items-start">
                                    <svg className="mr-2 mt-0.5 size-4 shrink-0 fill-current text-gold-400" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M9.69 18.933l.003-.001C9.89 18.71 15.5 12.53 15.5 7.5a5.5 5.5 0 10-11 0c0 5.03 5.61 11.21 5.807 11.432a1 1 0 001.383.001zM10 10.5a3 3 0 100-6 3 3 0 000 6z" clipRule="evenodd" />
                                    </svg>
                                    {site.address}
                                </li>
                            ) : null}
                            {site?.phone ? (
                                <li className="flex items-center">
                                    <svg className="mr-2 size-4 fill-current text-gold-400" viewBox="0 0 20 20">
                                        <path d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" />
                                    </svg>
                                    {site.phone}
                                </li>
                            ) : null}
                            {site?.email ? (
                                <li className="flex items-center">
                                    <svg className="mr-2 size-4 fill-current text-gold-400" viewBox="0 0 20 20">
                                        <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                                        <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                                    </svg>
                                    {site.email}
                                </li>
                            ) : null}
                        </ul>
                    </div>

                    <div>
                        <h4 className="mb-4 text-sm font-bold text-gold-400 uppercase">Navigasi</h4>
                        <ul className="space-y-2 text-emerald-200/80">
                            {navigation.map((item) => (
                                <li key={toUrl(item.href)}>
                                    <Link href={item.href} className="transition hover:text-gold-400">
                                        {item.name}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div>
                        <h4 className="mb-4 text-sm font-bold text-gold-400 uppercase">Media Sosial</h4>
                        <div className="flex items-center gap-3 text-lg text-emerald-200/80">
                            <a href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" className="transition hover:text-gold-400" aria-label="TikTok">
                                <svg className="size-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z" />
                                </svg>
                            </a>
                            <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" className="transition hover:text-gold-400" aria-label="Instagram">
                                <Instagram className="size-5" />
                            </a>
                            <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" className="transition hover:text-gold-400" aria-label="YouTube">
                                <Youtube className="size-5" />
                            </a>
                        </div>
                        <div className="mt-4 overflow-hidden rounded-2xl border border-emerald-800">
                            <iframe
                                className="h-40 w-full"
                                src="https://www.google.com/maps?q=Bustanul%20Hikmah%20Lamongan&z=14&output=embed"
                                loading="lazy"
                                referrerPolicy="no-referrer-when-downgrade"
                                title="Peta Pondok Pesantren Bustanul Hikmah Lamongan"
                            />
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-7xl border-t border-emerald-900 px-6 pt-6 text-center text-xs text-emerald-400/60 md:px-12">
                    &copy; {new Date().getFullYear()} {brandName}. All Rights Reserved.
                </div>
            </footer>
        </div>
    );
}
