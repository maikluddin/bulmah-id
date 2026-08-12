import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ImageIcon, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    create,
    edit,
    index as postsIndex,
    store,
    update,
} from '@/routes/admin/posts';
import type {
    AdminCategoryOption,
    AdminOption,
    AdminPostForm,
    AdminTeam,
} from '@/types';

type Props = {
    post: AdminPostForm | null;
    categories: AdminCategoryOption[];
    statuses: AdminOption[];
    team: AdminTeam;
};

export default function PostForm({ post, categories, statuses, team }: Props) {
    const isEdit = post !== null;
    const [coverPreview, setCoverPreview] = useState<string | null>(null);

    const {
        data,
        setData,
        post: submit,
        put,
        processing,
        errors,
    } = useForm({
        title: post?.title ?? '',
        category_id: post?.category_id ? String(post.category_id) : '',
        excerpt: post?.excerpt ?? '',
        body: post?.body ?? '',
        status: post?.status ?? 'draft',
        published_at: post?.published_at
            ? String(post.published_at).slice(0, 10)
            : '',
        cover: null as File | null,
        remove_cover: false,
    });

    const handleCoverChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        setData('cover', file);
        setData('remove_cover', false);

        if (coverPreview) {
            URL.revokeObjectURL(coverPreview);
        }

        setCoverPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleRemoveCover = () => {
        setData('cover', null);
        setData('remove_cover', true);

        if (coverPreview) {
            URL.revokeObjectURL(coverPreview);
        }

        setCoverPreview(null);
    };

    const previewUrl =
        coverPreview ??
        (post?.cover_url && !data.remove_cover ? post.cover_url : null);

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(update.url([team.slug, post.slug]), { preserveScroll: true });
        } else {
            submit(store.url(team.slug), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit Berita' : 'Buat Berita'} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={isEdit ? 'Edit Berita' : 'Buat Berita'}
                    description="Lengkapi detail artikel berita lembaga."
                />
                <Button variant="outline" asChild>
                    <Link href={postsIndex(team.slug)}>
                        <ArrowLeft />
                        Kembali
                    </Link>
                </Button>
            </div>

            <form onSubmit={onSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Berita</CardTitle>
                        <CardDescription>
                            Judul dan konten artikel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="cover">Sampul Berita</Label>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <div className="flex aspect-[16/9] w-56 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                        {previewUrl ? (
                                            <img
                                                src={previewUrl}
                                                alt="Sampul berita"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <ImageIcon className="size-8 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Input
                                            id="cover"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            onChange={handleCoverChange}
                                        />
                                        <InputError message={errors.cover} />
                                        {previewUrl ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive"
                                                onClick={handleRemoveCover}
                                            >
                                                <Trash2 />
                                                Hapus sampul
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(event) =>
                                        setData('title', event.target.value)
                                    }
                                    placeholder="Judul berita"
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="category">Kategori</Label>
                                <Select
                                    value={data.category_id || undefined}
                                    onValueChange={(value) =>
                                        setData('category_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((item) => (
                                            <SelectItem
                                                key={item.id}
                                                value={String(item.id)}
                                            >
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="status"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="published_at">
                                    Tanggal Terbit
                                </Label>
                                <Input
                                    id="published_at"
                                    type="date"
                                    value={data.published_at}
                                    onChange={(event) =>
                                        setData(
                                            'published_at',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.published_at} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="excerpt">Ringkasan</Label>
                                <Textarea
                                    id="excerpt"
                                    value={data.excerpt}
                                    onChange={(event) =>
                                        setData('excerpt', event.target.value)
                                    }
                                    placeholder="Ringkasan singkat berita"
                                />
                                <InputError message={errors.excerpt} />
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="body">Konten</Label>
                                <Textarea
                                    id="body"
                                    className="min-h-64"
                                    value={data.body}
                                    onChange={(event) =>
                                        setData('body', event.target.value)
                                    }
                                    placeholder="Tulis konten berita di sini..."
                                />
                                <InputError message={errors.body} />
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end gap-2">
                        <Button type="button" variant="ghost" asChild>
                            <Link href={postsIndex(team.slug)}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {isEdit ? 'Simpan Perubahan' : 'Simpan Berita'}
                        </Button>
                    </CardFooter>
                </Card>
            </form>

            {!isEdit && (
                <p className="text-sm text-muted-foreground">
                    Jika status berita "Terbit" dan tanggal terbit kosong, maka
                    tanggal terbit akan diisi otomatis dengan waktu sekarang.
                </p>
            )}
        </>
    );
}

PostForm.layout = (props: { team: AdminTeam; post: AdminPostForm | null }) => ({
    breadcrumbs: [
        { title: 'Berita', href: postsIndex(props.team.slug) },
        {
            title: props.post ? 'Edit Berita' : 'Buat Berita',
            href: props.post
                ? edit([props.team.slug, props.post.slug])
                : create(props.team.slug),
        },
    ],
});
