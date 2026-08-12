import { Head, Link, useForm } from '@inertiajs/react';
import { ClipboardCheck, MapPin, UserRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
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
import { useWilayahIndonesia } from '@/hooks/use-wilayah-indonesia';
import { store as daftarStore } from '@/routes/daftar';
import { show as lembagaShow } from '@/routes/lembaga';
import type { AdminOption } from '@/types';

type Props = {
    lembaga: {
        id: number;
        name: string;
        slug: string;
        jenjang_label: string | null;
    };
    genders: AdminOption[];
};

export default function PendaftaranCreate({ lembaga, genders }: Props) {
    const {
        provinces,
        regencies,
        districts,
        villages,
        loading,
        error,
        loadProvinces,
        handleProvinceChange,
        handleCityChange,
        handleDistrictChange,
    } = useWilayahIndonesia();

    const [provinceId, setProvinceId] = useState('');
    const [cityId, setCityId] = useState('');
    const [districtId, setDistrictId] = useState('');
    const [villageId, setVillageId] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        nisn: '',
        name: '',
        gender: '',
        birth_place: '',
        birth_date: '',
        address: '',
        address_province: '',
        address_city: '',
        address_district: '',
        address_village: '',
        wali_name: '',
        wali_phone: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(daftarStore.url(lembaga.slug), {
            preserveScroll: true,
        });
    };

    const onProvinceChange = (value: string) => {
        setProvinceId(value);
        setCityId('');
        setDistrictId('');
        setVillageId('');
        setData((previous) => ({
            ...previous,
            address_province:
                provinces.find((item) => item.id === value)?.name ?? '',
            address_city: '',
            address_district: '',
            address_village: '',
        }));
        handleProvinceChange(value);
    };

    const onCityChange = (value: string) => {
        setCityId(value);
        setDistrictId('');
        setVillageId('');
        setData((previous) => ({
            ...previous,
            address_city:
                regencies.find((item) => item.id === value)?.name ?? '',
            address_district: '',
            address_village: '',
        }));
        handleCityChange(value);
    };

    const onDistrictChange = (value: string) => {
        setDistrictId(value);
        setData((previous) => ({
            ...previous,
            address_district:
                districts.find((item) => item.id === value)?.name ?? '',
            address_village: '',
        }));
        handleDistrictChange(value);
    };

    const onVillageChange = (value: string) => {
        setVillageId(value);
        setData((previous) => ({
            ...previous,
            address_village:
                villages.find((item) => item.id === value)?.name ?? '',
        }));
    };

    return (
        <>
            <Head title={`Pendaftaran - ${lembaga.name}`} />

            <section className="border-b border-slate-200 bg-slate-100">
                <div className="mx-auto max-w-7xl px-6 py-14 md:px-12">
                    <Link
                        href={lembagaShow(lembaga.slug)}
                        className="text-sm font-medium text-emerald-700 hover:underline"
                    >
                        &larr; Kembali ke {lembaga.name}
                    </Link>

                    <div className="mt-6 flex items-start gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                            <ClipboardCheck className="size-6" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-extrabold text-emerald-900 sm:text-4xl">
                                Pendaftaran Santri Baru
                            </h1>
                            <p className="mt-1 text-slate-600">
                                {lembaga.name}
                                {lembaga.jenjang_label
                                    ? ` • ${lembaga.jenjang_label}`
                                    : null}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
                <Card className="rounded-3xl border-slate-200 shadow-sm">
                    <form onSubmit={submit} noValidate>
                        <CardHeader>
                            <CardTitle>Data Calon Santri</CardTitle>
                            <CardDescription>
                                Lengkapi formulir berikut. Tim lembaga akan
                                menghubungi Anda setelah pendaftaran
                                diverifikasi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nama lengkap</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Nama lengkap calon santri"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="gender">
                                        Jenis kelamin
                                    </Label>
                                    <Select
                                        value={data.gender}
                                        onValueChange={(value) =>
                                            setData('gender', value)
                                        }
                                    >
                                        <SelectTrigger id="gender">
                                            <SelectValue placeholder="Pilih jenis kelamin" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {genders.map((gender) => (
                                                <SelectItem
                                                    key={gender.value}
                                                    value={gender.value}
                                                >
                                                    {gender.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.gender} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="nisn">NISN</Label>
                                    <Input
                                        id="nisn"
                                        value={data.nisn}
                                        onChange={(e) =>
                                            setData('nisn', e.target.value)
                                        }
                                        placeholder="Nomor induk siswa nasional"
                                    />
                                    <InputError message={errors.nisn} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="birth_place">
                                        Tempat lahir
                                    </Label>
                                    <Input
                                        id="birth_place"
                                        value={data.birth_place}
                                        onChange={(e) =>
                                            setData(
                                                'birth_place',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Kota"
                                    />
                                    <InputError message={errors.birth_place} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="birth_date">
                                        Tanggal lahir
                                    </Label>
                                    <Input
                                        id="birth_date"
                                        type="date"
                                        value={data.birth_date}
                                        onChange={(e) =>
                                            setData(
                                                'birth_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.birth_date} />
                                </div>
                            </div>

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-4 flex items-center gap-2 text-sm font-medium text-emerald-700">
                                    <MapPin className="size-4" />
                                    Alamat Tempat Tinggal
                                </div>
                                <div className="space-y-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="address_province">
                                                Provinsi
                                            </Label>
                                            <Select
                                                value={provinceId}
                                                onValueChange={onProvinceChange}
                                                disabled={
                                                    loading.provinces ||
                                                    provinces.length === 0
                                                }
                                            >
                                                <SelectTrigger id="address_province">
                                                    <SelectValue placeholder="Pilih provinsi" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {provinces.map((item) => (
                                                        <SelectItem
                                                            key={item.id}
                                                            value={item.id}
                                                        >
                                                            {item.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors.address_province
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="address_city">
                                                Kota / Kabupaten
                                            </Label>
                                            <Select
                                                value={cityId}
                                                onValueChange={onCityChange}
                                                disabled={
                                                    provinceId === '' ||
                                                    loading.regencies
                                                }
                                            >
                                                <SelectTrigger id="address_city">
                                                    <SelectValue placeholder="Pilih kota / kabupaten" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {regencies.map((item) => (
                                                        <SelectItem
                                                            key={item.id}
                                                            value={item.id}
                                                        >
                                                            {item.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.address_city}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="address_district">
                                                Kecamatan
                                            </Label>
                                            <Select
                                                value={districtId}
                                                onValueChange={onDistrictChange}
                                                disabled={
                                                    cityId === '' ||
                                                    loading.districts
                                                }
                                            >
                                                <SelectTrigger id="address_district">
                                                    <SelectValue placeholder="Pilih kecamatan" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {districts.map((item) => (
                                                        <SelectItem
                                                            key={item.id}
                                                            value={item.id}
                                                        >
                                                            {item.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={
                                                    errors.address_district
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="address_village">
                                                Kelurahan / Desa
                                            </Label>
                                            <Select
                                                value={villageId}
                                                onValueChange={onVillageChange}
                                                disabled={
                                                    districtId === '' ||
                                                    loading.villages
                                                }
                                            >
                                                <SelectTrigger id="address_village">
                                                    <SelectValue placeholder="Pilih kelurahan / desa" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {villages.map((item) => (
                                                        <SelectItem
                                                            key={item.id}
                                                            value={item.id}
                                                        >
                                                            {item.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.address_village}
                                            />
                                        </div>
                                    </div>

                                    {provinces.length === 0 &&
                                    !loading.provinces ? (
                                        <div className="flex flex-col gap-2 rounded-lg border border-destructive/30 bg-destructive/5 p-3 sm:flex-row sm:items-center sm:justify-between">
                                            <p className="text-sm text-destructive">
                                                Gagal memuat daftar provinsi.
                                            </p>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={loadProvinces}
                                            >
                                                Muat ulang
                                            </Button>
                                        </div>
                                    ) : error ? (
                                        <p className="text-sm text-destructive">
                                            {error}
                                        </p>
                                    ) : null}

                                    <div className="space-y-2">
                                        <Label htmlFor="address">
                                            Detail alamat
                                        </Label>
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(e) =>
                                                setData(
                                                    'address',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jalan, RT/RW, nomor rumah, patokan"
                                        />
                                        <InputError message={errors.address} />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="mb-4 flex items-center gap-2 text-sm font-medium text-emerald-700">
                                    <UserRound className="size-4" />
                                    Data Wali / Orang Tua
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="wali_name">
                                            Nama wali
                                        </Label>
                                        <Input
                                            id="wali_name"
                                            value={data.wali_name}
                                            onChange={(e) =>
                                                setData(
                                                    'wali_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Nama ayah/wali"
                                        />
                                        <InputError
                                            message={errors.wali_name}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="wali_phone">
                                            No. HP wali
                                        </Label>
                                        <Input
                                            id="wali_phone"
                                            value={data.wali_phone}
                                            onChange={(e) =>
                                                setData(
                                                    'wali_phone',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="08xxxxxxxxxx"
                                        />
                                        <InputError
                                            message={errors.wali_phone}
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Button asChild variant="outline">
                                <Link href={lembagaShow(lembaga.slug)}>
                                    Batal
                                </Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl"
                            >
                                Kirim Pendaftaran
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </section>
        </>
    );
}
