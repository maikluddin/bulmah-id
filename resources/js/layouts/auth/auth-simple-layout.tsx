import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-slate-100 p-6 sm:justify-center sm:pt-0 dark:bg-slate-900">
            <Link
                href={home()}
                className="flex items-center gap-2 font-medium"
            >
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-700 to-emerald-500 text-gold-400 shadow-lg">
                    <AppLogoIcon className="size-8 fill-current" />
                </div>
            </Link>

            <div className="mt-6 w-full max-w-md rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-md dark:border-slate-700 dark:bg-slate-800 sm:rounded-lg">
                {title || description ? (
                    <div className="space-y-2 text-center">
                        {title ? (
                            <h1 className="text-xl font-bold text-emerald-900 dark:text-white">
                                {title}
                            </h1>
                        ) : null}
                        {description ? (
                            <p className="text-sm text-slate-600 dark:text-slate-300">
                                {description}
                            </p>
                        ) : null}
                    </div>
                ) : null}
                {children}
            </div>
        </div>
    );
}
