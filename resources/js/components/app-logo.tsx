import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

type Props = {
    className?: string;
};

export default function AppLogo({ className }: Props) {
    const { name } = usePage().props;

    return (
        <div className={cn('flex items-center gap-2.5', className)}>
            <div className="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-700 to-emerald-500 text-gold-400 shadow-md">
                <AppLogoIcon className="size-5 fill-current" />
            </div>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-extrabold tracking-tight">
                    {name}
                </span>
                <span className="text-[10px] font-bold tracking-wider text-gold-600 uppercase">
                    Bustanul Hikmah
                </span>
            </div>
        </div>
    );
}
