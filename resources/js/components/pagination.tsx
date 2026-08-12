import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { AdminPagination } from '@/types';

type PaginationLink = AdminPagination<unknown>['links'][number];

type Props = {
    links: PaginationLink[];
    className?: string;
};

export default function Pagination({ links, className }: Props) {
    if (links.length < 3) {
        return null;
    }

    const [prev, ...pages] = links;
    const last = pages.pop();

    return (
        <nav
            className={cn('flex items-center justify-between gap-2', className)}
            aria-label="Pagination"
        >
            {prev.url ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={prev.url} preserveScroll>
                        <ChevronLeft className="size-4" />
                        Sebelumnya
                    </Link>
                </Button>
            ) : (
                <Button variant="outline" size="sm" disabled>
                    <ChevronLeft className="size-4" />
                    Sebelumnya
                </Button>
            )}

            <div className="flex items-center gap-1">
                {pages.map((link) => {
                    const label = link.label.toLowerCase().includes('...')
                        ? '…'
                        : link.label;

                    if (!link.url) {
                        return (
                            <Button
                                key={label}
                                variant="ghost"
                                size="sm"
                                className="pointer-events-none opacity-50"
                            >
                                {label}
                            </Button>
                        );
                    }

                    return (
                        <Button
                            key={label}
                            asChild
                            variant={link.active ? 'default' : 'ghost'}
                            size="sm"
                            className={cn(link.active && 'pointer-events-none')}
                        >
                            <Link href={link.url} preserveScroll>
                                {label}
                            </Link>
                        </Button>
                    );
                })}
            </div>

            {last?.url ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={last.url} preserveScroll>
                        Berikutnya
                        <ChevronRight className="size-4" />
                    </Link>
                </Button>
            ) : (
                <Button variant="outline" size="sm" disabled>
                    Berikutnya
                    <ChevronRight className="size-4" />
                </Button>
            )}
        </nav>
    );
}
