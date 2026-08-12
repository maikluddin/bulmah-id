export type AdminTeam = {
    id: number;
    name: string;
    slug: string;
};

export type AdminOption = {
    value: string;
    label: string;
};

export type AdminPagination<T> = {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type AdminPost = {
    id: number;
    title: string;
    slug: string;
    status: string;
    status_label: string;
    published_at: string | null;
    category: { id: number; name: string } | null;
    author: { id: number; name: string } | null;
    cover_path: string | null;
    cover_url: string | null;
    team: AdminTeam;
};

export type AdminPostForm = {
    id: number;
    title: string;
    slug: string;
    category_id: number | null;
    excerpt: string | null;
    body: string;
    status: string;
    published_at: string | null;
    cover_path: string | null;
    cover_url: string | null;
};

export type AdminPostFilters = {
    search: string;
    category: number | null;
    status: string;
};

export type AdminCategoryOption = {
    id: number;
    name: string;
};

export type AdminCategory = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    posts_count: number;
    team: AdminTeam;
};

export type AdminKelas = {
    id: number;
    nama: string;
    santris_count: number;
    ustad: { id: number; name: string } | null;
};

export type AdminUstadOption = {
    id: number;
    name: string;
};

export type AdminHafalanKategori = {
    id: number;
    nama: string;
    jenis: string;
    jenis_label: string;
    hafalans_count: number;
};

export type AdminHafalanKategoriOption = {
    id: number;
    nama: string;
    jenis: string;
    jenis_label: string;
};

export type AdminHafalanKategoriTemplate = {
    jenis: string;
    jenis_label: string;
    nama: string;
};

export type AdminSantri = {
    id: number;
    nis: string;
    nisn: string | null;
    name: string;
    gender: string;
    gender_label: string;
    birth_place: string | null;
    birth_date: string | null;
    address: string | null;
    status: string;
    status_label: string;
    kelas_id: number | null;
    kelas: { id: number; nama: string } | null;
    wali_name: string | null;
    wali_phone: string | null;
    entry_date: string | null;
    exit_date: string | null;
    photo_path: string | null;
    photo_url: string | null;
    team: AdminTeam;
};

export type AdminSantriFilters = {
    search: string;
    status: string;
};

export type AdminPendaftaran = {
    id: number;
    nisn: string | null;
    name: string;
    gender: string;
    gender_label: string;
    birth_place: string | null;
    birth_date: string | null;
    address: string | null;
    address_province: string | null;
    address_city: string | null;
    address_district: string | null;
    address_village: string | null;
    status: string;
    status_label: string;
    ditolak_alasan: string | null;
    wali_name: string | null;
    wali_phone: string | null;
    created_at: string | null;
    team: AdminTeam;
};

export type AdminPendaftaranFilters = {
    search: string;
    status: string;
};

export type AdminLembaga = {
    id: number;
    name: string;
    slug: string;
    type: string | null;
    type_label: string | null;
    jenjang: string | null;
    jenjang_label: string | null;
    description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    yayasan_id: number | null;
    is_personal: boolean;
};

export type AdminLembagaListItem = AdminLembaga & {
    posts_count: number;
    santris_count: number;
};

export type DashboardStats = {
    posts: number;
    published_posts: number;
    categories: number;
    santri: number;
    active_santri: number;
    setoran: number;
    setoran_bulan_ini: number;
};

export type DashboardRecentSetoran = {
    id: number;
    santri: { id: number; name: string; nis: string };
    type: string;
    type_label: string;
    juz: number | null;
    surat: string;
    ayat_from: number | null;
    ayat_to: number | null;
    tanggal: string;
    kategori: { id: number; nama: string } | null;
    team: AdminTeam;
};

export type DashboardRecentPost = {
    id: number;
    title: string;
    slug: string;
    status: string;
    status_label: string;
    published_at: string | null;
    category: { id: number; name: string } | null;
    team: AdminTeam;
};

export type DashboardRecentSantri = {
    id: number;
    nis: string;
    name: string;
    status: string;
    status_label: string;
    team: AdminTeam;
};

export type AdminKeuanganSummary = {
    total_santri: number;
    santri_aktif: number;
    saldo_dompet: number;
    pembayaran_lunas: number;
    pembayaran_pending: number;
};

export type AdminKeuanganSantri = {
    id: number;
    nis: string;
    name: string;
    gender: string;
    gender_label: string;
    status: string;
    status_label: string;
    kelas: { id: number; nama: string } | null;
    saldo: number;
    pembayaran_lunas: number;
    pembayaran_pending: number;
    team: AdminTeam;
};

export type AdminKeuanganFilters = {
    search: string;
    status: string;
};

export type AdminMonitoringSantri = {
    id: number;
    nis: string;
    name: string;
    gender: string;
    gender_label: string;
    team: AdminTeam;
};

export type AdminMonitoringSummary = {
    total_hafalan: number;
    hafalan_bulan_ini: number;
    total_absensi: number;
    absensi_hadir: number;
    absensi_sakit: number;
    absensi_izin: number;
    absensi_alpa: number;
    saldo: number;
    pembayaran_lunas: number;
    perizinan_pending: number;
};

export type AdminMonitoringKategoriProgress = {
    id: number;
    nama: string;
    jenis: string;
    jenis_label: string;
    jumlah: number;
};

export type AdminHafalan = {
    id: number;
    type: string;
    type_label: string;
    juz: number | null;
    surat: string;
    ayat_from: number | null;
    ayat_to: number | null;
    tanggal: string;
    keterangan: string | null;
    kategori: { id: number; nama: string } | null;
    ustad: { id: number; name: string } | null;
};

export type AdminAbsensi = {
    id: number;
    tanggal: string;
    status: string;
    status_label: string;
    keterangan: string | null;
};

export type AdminAbsensiStats = {
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
};

export type AdminWalletTransaction = {
    id: number;
    type: string;
    type_label: string;
    amount: number;
    tanggal: string;
    description: string | null;
};

export type AdminPembayaran = {
    id: number;
    jenis: string;
    jenis_label: string;
    jumlah: number;
    tanggal: string;
    metode: string;
    metode_label: string;
    status: string;
    status_label: string;
    keterangan: string | null;
};

export type AdminPerizinan = {
    id: number;
    jenis: string;
    jenis_label: string;
    alasan: string;
    tanggal_mulai: string;
    tanggal_selesai: string | null;
    status: string;
    status_label: string;
    catatan: string | null;
    recorded_by: number | null;
};

export type AdminSetoranSantri = {
    id: number;
    nis: string;
    name: string;
    gender: string;
    gender_label: string;
    kelas: { id: number; nama: string } | null;
};

export type AdminSetoranRecord = {
    id: number;
    santri: { id: number; name: string; nis: string };
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

export type AdminLaporanFilters = {
    from: string;
    to: string;
    kategori: string;
    search: string;
};

export type AdminLaporanSummary = {
    total: number;
    santri: number;
    kategori_terpakai: number;
};

export type AdminLaporanPerKategori = {
    id: number | null;
    nama: string;
    jumlah: number;
};

export type AdminLaporanPerSantri = {
    id: number;
    name: string;
    nis: string;
    jumlah: number;
};

export type AdminLaporanRecord = {
    id: number;
    santri: { id: number; name: string; nis: string };
    type: string;
    type_label: string;
    juz: number | null;
    surat: string;
    ayat_from: number | null;
    ayat_to: number | null;
    tanggal: string;
    kategori: { id: number; nama: string } | null;
    ustad: { name: string } | null;
};

export type AdminSarprasItem = {
    id: number;
    nama: string;
    kategori: string;
    kategori_label: string;
    lokasi: string | null;
    jumlah: number;
    kondisi: string;
    kondisi_label: string;
    keterangan: string | null;
    foto_path: string | null;
    foto_url: string | null;
    perbaikans_aktif: number;
};

export type AdminPerbaikanSarpras = {
    id: number;
    item: { id: number; nama: string } | null;
    deskripsi: string;
    status: string;
    status_label: string;
    catatan: string | null;
    pelapor: { id: number; name: string } | null;
    created_at: string;
};

export type AdminWali = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    phone: string | null;
    santris_count: number;
    santri_names: string[];
    team: AdminTeam;
};

export type AdminWaliForm = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    santris_count: number;
};

export type AdminWaliSantriOption = {
    id: number;
    nis: string;
    name: string;
    gender_label: string;
    kelas: { nama: string } | null;
    team: {
        name: string;
        slug: string;
    };
    linked: boolean;
};

export type AdminWaliFilters = {
    search: string;
};
