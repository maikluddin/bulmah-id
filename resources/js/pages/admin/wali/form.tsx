import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create,
    edit,
    index as waliIndex,
    store,
    update,
} from '@/routes/admin/wali';
import type {
    AdminTeam,
    AdminWaliForm,
    AdminWaliSantriOption,
} from '@/types';

type Props = {
    wali: AdminWaliForm | null;
    santriOptions: AdminWaliSantriOption[];
    linkedSantriIds: number[];
    team: AdminTeam;
};

export default function WaliForm({
    wali,
    santriOptions,
    linkedSantriIds,
    team,
}: Props) {
    const isEdit = wali !== null;

    const {
        data,
        setData,
        post: submit,
        put,
        processing,
        errors,
    } = useForm({
        name: wali?.name ?? '',
        email: wali?.email ?? '',
        phone: wali?.phone ?? '',
        password: '',
        santri_ids: linkedSantriIds,
    });

    const toggleSantri = (id: number) => {
        const linked = data.santri_ids.includes(id);

        setData(
            'santri_ids',
            linked
                ? data.santri_ids.filter((santriId) => santriId !== id)
                : [...data.santri_ids, id],
        );
    };

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(update.url([team.slug, wali.id]), { preserveScroll: true });
        } else {
            submit(store.url(team.slug), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit Akun Wali' : 'Tambah Akun Wali'} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={isEdit ? 'Edit Akun Wali' : 'Tambah Akun Wali'}
                    description="Kelola akun login wali santri dan tautkan santri binaannya."
                />
                <Button variant="outline" asChild>
                    <Link href={waliIndex(team.slug)}>
                        <ArrowLeft />
                        Kembali
                    </Link>
                </Button>
            </div>

            <form onSubmit={onSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Data Akun</CardTitle>
                        <CardDescription>
                            Informasi login yang digunakan wali di portal wali.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Nama Wali</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="Nama lengkap wali"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                placeholder="email wali untuk login"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phone">No. HP</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(event) =>
                                    setData('phone', event.target.value)
                                }
                                placeholder="Nomor HP yang dapat dihubungi"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="password">
                                Kata Sandi{isEdit ? ' (opsional)' : ''}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                placeholder={
                                    isEdit
                                        ? 'Kosongkan jika tidak ingin mengubah'
                                        : 'Minimal 8 karakter'
                                }
                            />
                            <InputError message={errors.password} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tautkan Santri</CardTitle>
                        <CardDescription>
                            Pilih santri yang berada di bawah pengasuhan wali
                            ini. Satu wali dapat membina lebih dari satu santri.
                            Nomor HP di atas akan tersalin ke data santri yang
                            dipilih.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <InputError message={errors.santri_ids} />

                        {santriOptions.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                Tidak ada santri yang tersedia untuk ditautkan.
                            </p>
                        ) : (
                            <div className="grid max-h-80 gap-1 overflow-y-auto pr-1 sm:grid-cols-2">
                                {santriOptions.map((santri) => (
                                    <label
                                        key={santri.id}
                                        className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <Checkbox
                                            checked={data.santri_ids.includes(
                                                santri.id,
                                            )}
                                            onCheckedChange={() =>
                                                toggleSantri(santri.id)
                                            }
                                            className="mt-0.5"
                                        />
                                        <span className="min-w-0">
                                            <span className="flex flex-wrap items-center gap-1.5">
                                                <span className="truncate text-sm font-medium">
                                                    {santri.name}
                                                </span>
                                                <Badge variant="outline">
                                                    {santri.gender_label}
                                                </Badge>
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {santri.nis}
                                                {santri.kelas
                                                    ? ` · ${santri.kelas.nama}`
                                                    : ''}{' '}
                                                · {santri.team.name}
                                            </span>
                                        </span>
                                    </label>
                                ))}
                            </div>
                        )}

                        <p className="mt-3 text-xs text-muted-foreground">
                            {data.santri_ids.length} santri dipilih
                            {!isEdit
                                ? ' (minimal satu santri harus dipilih)'
                                : ''}
                            .
                        </p>
                    </CardContent>
                    <CardFooter className="justify-end gap-2">
                        <Button type="button" variant="ghost" asChild>
                            <Link href={waliIndex(team.slug)}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {isEdit ? 'Simpan Perubahan' : 'Simpan Akun Wali'}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}

WaliForm.layout = (props: {
    team: AdminTeam;
    wali: AdminWaliForm | null;
}) => ({
    breadcrumbs: [
        { title: 'Wali Santri', href: waliIndex(props.team.slug) },
        {
            title: props.wali ? 'Edit Akun Wali' : 'Tambah Akun Wali',
            href: props.wali
                ? edit([props.team.slug, props.wali.id])
                : create(props.team.slug),
        },
    ],
});
