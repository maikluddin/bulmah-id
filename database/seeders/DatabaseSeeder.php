<?php

namespace Database\Seeders;

use App\Actions\Teams\CreateTeam;
use App\Enums\LembagaType;
use App\Enums\PostStatus;
use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Enums\TeamRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use App\Models\Yayasan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    protected array $lembaga = [];

    protected array $users = [];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedYayasanAndLembaga();
        $this->seedAccounts();
        $this->seedContent();
    }

    /**
     * Create (or reuse) the yayasan and its lembaga teams.
     */
    protected function seedYayasanAndLembaga(): void
    {
        $yayasan = Yayasan::firstOrCreate(
            ['slug' => 'yayasan-ponpes-bustanul-hikmah'],
            [
                'name' => 'Yayasan Ponpes Bustanul Hikmah',
                'description' => 'Yayasan pendidikan Islam yang menaungi lembaga pendidikan di lingkungan Ponpes Bustanul Hikmah, Cianjur.',
                'address' => 'Jl. Pesantren No. 1, Cianjur, Jawa Barat',
                'phone' => '0263-123456',
                'email' => 'info@bulmah.id',
                'website' => 'https://bulmah.id',
            ],
        );

        $lembaga = [
            'pondok' => ['name' => 'Pondok Pesantren Bustanul Hikmah', 'type' => LembagaType::PondokPesantren->value, 'email' => 'pondok@bulmah.id', 'description' => 'Pesantren yang menaungi santri mukim putra dan putri dengan pembinaan akhlak dan keilmuan Islam.'],
            'smpi' => ['name' => 'SMPI Bustanul Hikmah', 'type' => LembagaType::Sekolah->value, 'email' => 'smpi@bulmah.id', 'description' => 'Sekolah Menengah Pertama Islam yang memadukan kurikulum nasional dengan pendidikan pesantren.'],
            'smki' => ['name' => 'SMKI Bustanul Hikmah', 'type' => LembagaType::Sekolah->value, 'email' => 'smki@bulmah.id', 'description' => 'Sekolah Menengah Kejuruan Islam dengan program keahlian teknologi dan keterampilan.'],
            'mau' => ['name' => 'MAU Bustanul Hikmah', 'type' => LembagaType::Sekolah->value, 'email' => 'mau@bulmah.id', 'description' => 'Madrasah Aliyah Unggulan dengan program tahfidz dan pendalaman ilmu agama.'],
        ];

        foreach ($lembaga as $key => $data) {
            $this->lembaga[$key] = Team::firstOrCreate(
                ['slug' => $key.'-bustanul-hikmah'],
                [
                    'name' => $data['name'],
                    'yayasan_id' => $yayasan->id,
                    'type' => $data['type'],
                    'description' => $data['description'],
                    'address' => 'Jl. Pesantren No. 1, Cianjur, Jawa Barat',
                    'phone' => '0263-123456',
                    'email' => $data['email'],
                ],
            );
        }
    }

    /**
     * Create (or reuse) the application accounts and their team memberships.
     */
    protected function seedAccounts(): void
    {
        $accounts = [
            ['name' => 'Administrator Bulmah', 'email' => 'admin@bulmah.id'],
            ['name' => 'Admin Pondok Putra', 'email' => 'admin-putra@bulmah.id'],
            ['name' => 'Admin Pondok Putri', 'email' => 'admin-putri@bulmah.id'],
            ['name' => 'Admin SMPI Bustanul Hikmah', 'email' => 'admin-smpi@bulmah.id'],
            ['name' => 'Admin SMKI Bustanul Hikmah', 'email' => 'admin-smki@bulmah.id'],
            ['name' => 'Admin MAU Bustanul Hikmah', 'email' => 'admin-mau@bulmah.id'],
            ['name' => 'Bendahara Pondok Bustanul Hikmah', 'email' => 'bendahara-putra@bulmah.id'],
            ['name' => 'Bendahara SMPI Bustanul Hikmah', 'email' => 'bendahara-smpi@bulmah.id'],
            ['name' => 'Bendahara SMKI Bustanul Hikmah', 'email' => 'bendahara-smki@bulmah.id'],
            ['name' => 'Bendahara MAU Bustanul Hikmah', 'email' => 'bendahara-mau@bulmah.id'],
            ['name' => 'Ustaz Ahmad Fauzi', 'email' => 'ustaz@bulmah.id'],
            ['name' => 'H. Muhammad Ridwan', 'email' => 'wali@bulmah.id'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $this->users[$account['email']] = $user;

            if ($user->personalTeam() === null) {
                (new CreateTeam)->handle($user, "{$user->name}'s Team", isPersonal: true);
            }
        }

        $this->assignMemberships();
    }

    /**
     * Assign team memberships and current teams for the seeded accounts.
     */
    protected function assignMemberships(): void
    {
        $assignments = [
            'admin@bulmah.id' => [
                'memberships' => [
                    'pondok' => ['role' => TeamRole::Owner],
                    'smpi' => ['role' => TeamRole::Owner],
                    'smki' => ['role' => TeamRole::Owner],
                    'mau' => ['role' => TeamRole::Owner],
                ],
                'current' => 'pondok',
            ],
            'admin-putra@bulmah.id' => [
                'memberships' => [
                    'pondok' => ['role' => TeamRole::AdminLembaga, 'santri_gender' => SantriGender::LakiLaki],
                ],
                'current' => 'pondok',
            ],
            'admin-putri@bulmah.id' => [
                'memberships' => [
                    'pondok' => ['role' => TeamRole::AdminLembaga, 'santri_gender' => SantriGender::Perempuan],
                ],
                'current' => 'pondok',
            ],
            'admin-smpi@bulmah.id' => [
                'memberships' => [
                    'smpi' => ['role' => TeamRole::AdminLembaga],
                ],
                'current' => 'smpi',
            ],
            'admin-smki@bulmah.id' => [
                'memberships' => [
                    'smki' => ['role' => TeamRole::AdminLembaga],
                ],
                'current' => 'smki',
            ],
            'admin-mau@bulmah.id' => [
                'memberships' => [
                    'mau' => ['role' => TeamRole::AdminLembaga],
                ],
                'current' => 'mau',
            ],
            'bendahara-putra@bulmah.id' => [
                'memberships' => [
                    'pondok' => ['role' => TeamRole::AdminPembayaran, 'santri_gender' => SantriGender::LakiLaki],
                ],
                'current' => 'pondok',
            ],
            'bendahara-smpi@bulmah.id' => [
                'memberships' => [
                    'smpi' => ['role' => TeamRole::AdminPembayaran],
                ],
                'current' => 'smpi',
            ],
            'bendahara-smki@bulmah.id' => [
                'memberships' => [
                    'smki' => ['role' => TeamRole::AdminPembayaran],
                ],
                'current' => 'smki',
            ],
            'bendahara-mau@bulmah.id' => [
                'memberships' => [
                    'mau' => ['role' => TeamRole::AdminPembayaran],
                ],
                'current' => 'mau',
            ],
            'ustaz@bulmah.id' => [
                'memberships' => [
                    'smpi' => ['role' => TeamRole::Ustaz],
                ],
                'current' => 'personal',
            ],
            'wali@bulmah.id' => [
                'memberships' => [
                    'smpi' => ['role' => TeamRole::Member],
                ],
                'current' => 'personal',
            ],
        ];

        foreach ($assignments as $email => $assignment) {
            $user = $this->users[$email];

            foreach ($assignment['memberships'] as $key => $membership) {
                $this->lembaga[$key]->memberships()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'role' => $membership['role'],
                        'santri_gender' => $membership['santri_gender'] ?? null,
                    ],
                );
            }

            $current = $assignment['current'] === 'personal'
                ? $user->personalTeam()
                : $this->lembaga[$assignment['current']];

            $user->switchTeam($current);
        }
    }

    /**
     * Seed categories, posts, and santri for every lembaga.
     */
    protected function seedContent(): void
    {
        foreach ($this->lembaga as $team) {
            $this->seedCategories($team);
            $this->seedPosts($team);
            $this->seedSantri($team);
        }
    }

    /**
     * Seed the default post categories for a lembaga.
     */
    protected function seedCategories(Team $team): void
    {
        $categories = [
            ['name' => 'Berita', 'description' => 'Berita terkini seputar lembaga.'],
            ['name' => 'Pengumuman', 'description' => 'Pengumuman resmi dari lembaga.'],
            ['name' => 'Kegiatan', 'description' => 'Dokumentasi kegiatan dan program lembaga.'],
        ];

        foreach ($categories as $category) {
            PostCategory::firstOrCreate(
                ['team_id' => $team->id, 'slug' => Str::slug($category['name'])],
                ['name' => $category['name'], 'description' => $category['description']],
            );
        }
    }

    /**
     * Seed representative published posts for a lembaga.
     */
    protected function seedPosts(Team $team): void
    {
        foreach ($this->postData($team) as $post) {
            $slug = Str::slug($post['title']);

            if (Post::withTrashed()->where('team_id', $team->id)->where('slug', $slug)->exists()) {
                continue;
            }

            $category = PostCategory::where('team_id', $team->id)
                ->where('slug', $post['category'])
                ->first();

            Post::create([
                'team_id' => $team->id,
                'category_id' => $category?->id,
                'author_id' => $this->authorFor($team)->id,
                'title' => $post['title'],
                'excerpt' => $post['excerpt'],
                'body' => $post['body'],
                'status' => PostStatus::Published,
                'published_at' => $post['published_at'],
            ]);
        }
    }

    /**
     * Get the author (admin lembaga or admin yayasan) for a lembaga.
     */
    protected function authorFor(Team $team): User
    {
        $email = match ($team->slug) {
            'smpi-bustanul-hikmah' => 'admin-smpi@bulmah.id',
            'smki-bustanul-hikmah' => 'admin-smki@bulmah.id',
            'mau-bustanul-hikmah' => 'admin-mau@bulmah.id',
            'pondok-bustanul-hikmah' => 'admin-putra@bulmah.id',
            default => 'admin@bulmah.id',
        };

        return $this->users[$email] ?? $this->users['admin@bulmah.id'];
    }

    /**
     * Seed a few santri for a lembaga.
     */
    protected function seedSantri(Team $team): void
    {
        $prefix = match ($team->slug) {
            'pondok-bustanul-hikmah' => '2025.4',
            'smpi-bustanul-hikmah' => '2025.1',
            'smki-bustanul-hikmah' => '2025.2',
            'mau-bustanul-hikmah' => '2025.3',
            default => '2025.0',
        };

        $data = [
            ['nis' => $prefix.'.001', 'name' => 'Abdurrahman Hakim', 'gender' => SantriGender::LakiLaki, 'birth_place' => 'Cianjur', 'birth_date' => '2012-03-14', 'address' => 'Jl. Pesantren No. 10, Cianjur', 'wali_name' => 'H. Muhammad Ridwan', 'wali_phone' => '0812-3456-7890'],
            ['nis' => $prefix.'.002', 'name' => 'Muhammad Ilham', 'gender' => SantriGender::LakiLaki, 'birth_place' => 'Bandung', 'birth_date' => '2013-07-02', 'address' => 'Jl. Merdeka No. 5, Bandung', 'wali_name' => 'H. Ahmad Sobirin', 'wali_phone' => '0813-2233-4455'],
            ['nis' => $prefix.'.003', 'name' => 'Fatimah Az-Zahra', 'gender' => SantriGender::Perempuan, 'birth_place' => 'Sukabumi', 'birth_date' => '2012-01-25', 'address' => 'Jl. Raya Sukabumi No. 8, Sukabumi', 'wali_name' => 'Hj. Siti Aminah', 'wali_phone' => '0857-9988-7766'],
        ];

        foreach ($data as $santri) {
            $santri = Santri::firstOrCreate(
                ['team_id' => $team->id, 'nis' => $santri['nis']],
                [
                    'name' => $santri['name'],
                    'gender' => $santri['gender'],
                    'birth_place' => $santri['birth_place'],
                    'birth_date' => $santri['birth_date'],
                    'address' => $santri['address'],
                    'status' => SantriStatus::Aktif,
                    'wali_name' => $santri['wali_name'],
                    'wali_phone' => $santri['wali_phone'],
                    'entry_date' => now()->subYears(2)->format('Y-m-d'),
                ],
            );

            $this->linkWaliAccount($santri);
        }
    }

    /**
     * Link the wali account (wali@bulmah.id) to a santri when the wali name matches.
     */
    protected function linkWaliAccount(Santri $santri): void
    {
        $wali = User::where('email', 'wali@bulmah.id')->first();

        if ($wali === null || $santri->wali_user_id !== null) {
            return;
        }

        if (str_contains($santri->wali_name ?? '', $wali->name)) {
            $santri->update(['wali_user_id' => $wali->id]);
        }
    }

    /**
     * Get the representative post data for a lembaga.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function postData(Team $team): array
    {
        return [
            [
                'category' => 'berita',
                'title' => 'Kabar Terbaru '.$team->name,
                'excerpt' => 'Update kegiatan dan capaian terbaru dari '.$team->name.'.',
                'body' => 'Alhamdulillah, '.$team->name.' terus berkembang dan berprestasi. Simak kabar terbaru seputar kegiatan, prestasi santri, dan program lembaga di sini.',
                'published_at' => now()->subDays(3),
            ],
            [
                'category' => 'pengumuman',
                'title' => 'Penerimaan Santri Baru Tahun Ajaran 2026/2027',
                'excerpt' => 'Pendaftaran santri baru '.$team->name.' tahun ajaran 2026/2027 telah dibuka.',
                'body' => $team->name.' membuka pendaftaran santri baru untuk tahun ajaran 2026/2027. Pendaftaran dapat dilakukan secara online melalui website resmi atau langsung ke kantor lembaga.',
                'published_at' => now()->subWeeks(1),
            ],
            [
                'category' => 'kegiatan',
                'title' => 'Kegiatan '.$team->name.' Berjalan Khidmat',
                'excerpt' => 'Rangkaian kegiatan '.$team->name.' berlangsung dengan penuh khidmat dan antusiasme.',
                'body' => 'Berbagai kegiatan pembiasaan ibadah, pembelajaran, dan ekstrakurikuler '.$team->name.' berjalan dengan baik. Semoga melahirkan generasi yang berakhlak dan berprestasi.',
                'published_at' => now()->subMonths(1),
            ],
        ];
    }
}
