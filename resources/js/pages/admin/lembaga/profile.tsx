import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
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
    edit as lembagaEdit,
    index as lembagaIndex,
    update,
} from '@/routes/admin/lembaga';
import type { AdminLembaga, AdminOption, AdminTeam } from '@/types';

type YayasanOption = {
    id: number;
    name: string;
};

type Props = {
    team: AdminLembaga;
    types: AdminOption[];
    jenjangs: AdminOption[];
    yayasans: YayasanOption[];
};

export default function LembagaProfile({
    team,
    types,
    jenjangs,
    yayasans,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        type: team.type ?? '',
        jenjang: team.jenjang ?? '',
        description: team.description ?? '',
        address: team.address ?? '',
        phone: team.phone ?? '',
        email: team.email ?? '',
        yayasan_id: team.yayasan_id ? String(team.yayasan_id) : '',
    });

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        put(update.url(team.slug), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Profil Lembaga" />

            <Heading
                title="Profil Lembaga"
                description="Informasi publik tentang lembaga Anda. Halaman ini tampil di situs lembaga."
            />

            <form onSubmit={onSubmit} className="max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Detail Lembaga</CardTitle>
                        <CardDescription>
                            Profil yang ditampilkan kepada pengunjung.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="type">Jenis Lembaga</Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value)
                                }
                            >
                                <SelectTrigger id="type" className="w-full">
                                    <SelectValue placeholder="Pilih jenis lembaga" />
                                </SelectTrigger>
                                <SelectContent>
                                    {types.map((item) => (
                                        <SelectItem
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.type} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="jenjang">Jenjang Pendidikan</Label>
                            <Select
                                value={data.jenjang}
                                onValueChange={(value) =>
                                    setData('jenjang', value)
                                }
                            >
                                <SelectTrigger id="jenjang" className="w-full">
                                    <SelectValue placeholder="Pilih jenjang pendidikan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="lainnya">
                                        Tanpa jenjang
                                    </SelectItem>
                                    {jenjangs
                                        .filter(
                                            (item) =>
                                                item.value !== 'lainnya',
                                        )
                                        .map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.jenjang} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                className="min-h-32"
                                value={data.description}
                                onChange={(event) =>
                                    setData('description', event.target.value)
                                }
                                placeholder="Deskripsi singkat lembaga"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="address">Alamat</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(event) =>
                                    setData('address', event.target.value)
                                }
                                placeholder="Alamat lengkap lembaga"
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="phone">No. Telepon</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    placeholder="Nomor telepon lembaga"
                                />
                                <InputError message={errors.phone} />
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
                                    placeholder="Email resmi lembaga"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="yayasan">Yayasan</Label>
                            <Select
                                value={data.yayasan_id}
                                onValueChange={(value) =>
                                    setData('yayasan_id', value)
                                }
                            >
                                <SelectTrigger id="yayasan" className="w-full">
                                    <SelectValue placeholder="Tidak terafiliasi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {yayasans.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.yayasan_id} />
                        </div>
                    </CardContent>
                    <CardFooter className="justify-end">
                        <Button type="submit" disabled={processing}>
                            <Save />
                            Simpan Profil
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}

LembagaProfile.layout = (props: { team: AdminTeam }) => ({
    breadcrumbs: [
        { title: 'Lembaga', href: lembagaIndex(props.team.slug) },
        { title: 'Profil Lembaga', href: lembagaEdit(props.team.slug) },
    ],
});
