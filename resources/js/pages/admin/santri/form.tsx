import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Trash2, UserRound } from 'lucide-react';
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
    index as santriIndex,
    store,
    update,
} from '@/routes/admin/santri';
import type { AdminOption, AdminSantri, AdminTeam } from '@/types';

type Props = {
    santri: AdminSantri | null;
    genders: AdminOption[];
    statuses: AdminOption[];
    kelas: AdminOption[];
    santriScope?: string;
    team: AdminTeam;
};

export default function SantriForm({
    santri,
    genders,
    statuses,
    kelas,
    santriScope,
    team,
}: Props) {
    const isEdit = santri !== null;
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);

    const {
        data,
        setData,
        post: submit,
        put,
        processing,
        errors,
    } = useForm({
        nis: santri?.nis ?? '',
        nisn: santri?.nisn ?? '',
        name: santri?.name ?? '',
        gender: santri?.gender ?? santriScope ?? 'laki-laki',
        birth_place: santri?.birth_place ?? '',
        birth_date: santri?.birth_date
            ? String(santri.birth_date).slice(0, 10)
            : '',
        address: santri?.address ?? '',
        status: santri?.status ?? 'aktif',
        kelas_id: santri?.kelas_id ? String(santri.kelas_id) : '',
        wali_name: santri?.wali_name ?? '',
        wali_phone: santri?.wali_phone ?? '',
        entry_date: santri?.entry_date
            ? String(santri.entry_date).slice(0, 10)
            : '',
        exit_date: santri?.exit_date
            ? String(santri.exit_date).slice(0, 10)
            : '',
        photo: null as File | null,
        remove_photo: false,
    });

    const handlePhotoChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        setData('photo', file);
        setData('remove_photo', false);

        if (photoPreview) {
            URL.revokeObjectURL(photoPreview);
        }

        setPhotoPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleRemovePhoto = () => {
        setData('photo', null);
        setData('remove_photo', true);

        if (photoPreview) {
            URL.revokeObjectURL(photoPreview);
        }

        setPhotoPreview(null);
    };

    const previewUrl =
        photoPreview ??
        (santri?.photo_url && !data.remove_photo ? santri.photo_url : null);

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            put(update.url([team.slug, santri.id]), { preserveScroll: true });
        } else {
            submit(store.url(team.slug), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit Santri' : 'Tambah Santri'} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <Heading
                    title={isEdit ? 'Edit Santri' : 'Tambah Santri'}
                    description="Lengkapi data identitas dan wali santri."
                />
                <Button variant="outline" asChild>
                    <Link href={santriIndex(team.slug)}>
                        <ArrowLeft />
                        Kembali
                    </Link>
                </Button>
            </div>

            <form onSubmit={onSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Identitas Santri</CardTitle>
                        <CardDescription>Data pribadi santri.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="photo">Foto Santri</Label>
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div className="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-full border border-border bg-muted">
                                    {previewUrl ? (
                                        <img
                                            src={previewUrl}
                                            alt="Foto santri"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <UserRound className="size-8 text-muted-foreground" />
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Input
                                        id="photo"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handlePhotoChange}
                                    />
                                    <InputError message={errors.photo} />
                                    {previewUrl ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={handleRemovePhoto}
                                        >
                                            <Trash2 />
                                            Hapus foto
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="nis">NIS</Label>
                            <Input
                                id="nis"
                                value={data.nis}
                                onChange={(event) =>
                                    setData('nis', event.target.value)
                                }
                                placeholder="Nomor induk santri"
                            />
                            <InputError message={errors.nis} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="nisn">NISN</Label>
                            <Input
                                id="nisn"
                                value={data.nisn}
                                onChange={(event) =>
                                    setData('nisn', event.target.value)
                                }
                                placeholder="Nomor induk siswa nasional (opsional)"
                            />
                            <InputError message={errors.nisn} />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="Nama lengkap santri"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="gender">Jenis Kelamin</Label>
                            <Select
                                value={data.gender}
                                onValueChange={(value) =>
                                    setData('gender', value)
                                }
                                disabled={santriScope !== undefined}
                            >
                                <SelectTrigger id="gender" className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {genders.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.gender} />
                            {santriScope !== undefined ? (
                                <p className="text-xs text-muted-foreground">
                                    Jenis kelamin ditentukan oleh peran admin
                                    Anda.
                                </p>
                            ) : null}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) =>
                                    setData('status', value)
                                }
                            >
                                <SelectTrigger id="status" className="w-full">
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
                            <Label htmlFor="kelas_id">Kelas</Label>
                            <Select
                                value={data.kelas_id}
                                onValueChange={(value) =>
                                    setData('kelas_id', value)
                                }
                            >
                                <SelectTrigger id="kelas_id" className="w-full">
                                    <SelectValue placeholder="Pilih kelas (opsional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {kelas.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.kelas_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="birth_place">Tempat Lahir</Label>
                            <Input
                                id="birth_place"
                                value={data.birth_place}
                                onChange={(event) =>
                                    setData('birth_place', event.target.value)
                                }
                                placeholder="Kota tempat lahir"
                            />
                            <InputError message={errors.birth_place} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="birth_date">Tanggal Lahir</Label>
                            <Input
                                id="birth_date"
                                type="date"
                                value={data.birth_date}
                                onChange={(event) =>
                                    setData('birth_date', event.target.value)
                                }
                            />
                            <InputError message={errors.birth_date} />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                            <Label htmlFor="address">Alamat</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(event) =>
                                    setData('address', event.target.value)
                                }
                                placeholder="Alamat tempat tinggal santri"
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="entry_date">Tanggal Masuk</Label>
                            <Input
                                id="entry_date"
                                type="date"
                                value={data.entry_date}
                                onChange={(event) =>
                                    setData('entry_date', event.target.value)
                                }
                            />
                            <InputError message={errors.entry_date} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="exit_date">Tanggal Keluar</Label>
                            <Input
                                id="exit_date"
                                type="date"
                                value={data.exit_date}
                                onChange={(event) =>
                                    setData('exit_date', event.target.value)
                                }
                            />
                            <InputError message={errors.exit_date} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Data Wali</CardTitle>
                        <CardDescription>
                            Informasi orang tua atau wali santri.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="wali_name">Nama Wali</Label>
                            <Input
                                id="wali_name"
                                value={data.wali_name}
                                onChange={(event) =>
                                    setData('wali_name', event.target.value)
                                }
                                placeholder="Nama wali santri"
                            />
                            <InputError message={errors.wali_name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="wali_phone">No. HP Wali</Label>
                            <Input
                                id="wali_phone"
                                value={data.wali_phone}
                                onChange={(event) =>
                                    setData('wali_phone', event.target.value)
                                }
                                placeholder="Nomor HP yang dapat dihubungi"
                            />
                            <InputError message={errors.wali_phone} />
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end gap-2">
                        <Button type="button" variant="ghost" asChild>
                            <Link href={santriIndex(team.slug)}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save />
                            {isEdit ? 'Simpan Perubahan' : 'Simpan Santri'}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}

SantriForm.layout = (props: {
    team: AdminTeam;
    santri: AdminSantri | null;
}) => ({
    breadcrumbs: [
        { title: 'Santri', href: santriIndex(props.team.slug) },
        {
            title: props.santri ? 'Edit Santri' : 'Tambah Santri',
            href: props.santri
                ? edit([props.team.slug, props.santri.id])
                : create(props.team.slug),
        },
    ],
});
