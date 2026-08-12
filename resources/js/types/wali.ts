export type WaliSetoranRecord = {
    id: number;
    type: string;
    type_label: string;
    juz: number | null;
    surat: string;
    ayat_from: number | null;
    ayat_to: number | null;
    tanggal: string;
    kategori: { nama: string } | null;
    ustad: { name: string } | null;
};

export type WaliSantri = {
    id: number;
    nis: string;
    name: string;
    gender: string;
    gender_label: string;
    status: string;
    status_label: string;
    kelas: { id: number; nama: string } | null;
    summary: {
        total_hafalan: number;
        hafalan_baru: number;
        murajaah: number;
        bulan_ini: number;
    };
    recent: WaliSetoranRecord[];
};

export type WaliNotification = {
    id: string;
    read: boolean;
    created_at: string;
    data: {
        hafalan_id: number;
        team_id: number;
        santri_id: number;
        santri_name: string | null;
        santri_nis: string | null;
        type: string;
        type_label: string;
        surat: string;
        juz: number | null;
        ayat_from: number | null;
        ayat_to: number | null;
        tanggal: string;
        kategori: { id: number; nama: string } | null;
        ustad_name: string | null;
    };
};
