<?php

namespace App\Console\Commands;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('lembaga:admin {team : Slug lembaga, mis. smpi-bustanul-hikmah} {--email= : Email akun admin (default: admin-{awalan-slug}@bulmah.id)} {--name= : Nama akun admin} {--password= : Password akun (default: password)}')]
#[Description('Buat (atau pastikan) akun admin lembaga dengan role Admin Lembaga, lengkap dengan personal team.')]
class CreateLembagaAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateTeam $createTeam): int
    {
        $team = Team::query()
            ->where('slug', $this->argument('team'))
            ->first();

        if (! $team) {
            $this->error("Lembaga dengan slug '{$this->argument('team')}' tidak ditemukan.");

            return self::FAILURE;
        }

        if ($team->is_personal) {
            $this->error("'{$team->name}' adalah personal team, bukan lembaga.");

            return self::FAILURE;
        }

        $email = $this->option('email') ?? 'admin-'.Str::before($team->slug, '-').'@bulmah.id';
        $name = $this->option('name') ?? "Admin {$team->name}";
        $password = $this->option('password') ?? 'password';

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        if ($user->personalTeam() === null) {
            $createTeam->handle($user, "{$user->name}'s Team", isPersonal: true);
        }

        if (! $user->belongsToTeam($team)) {
            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::AdminLembaga,
            ]);
        }

        $user->switchTeam($team);

        $this->info("Akun admin lembaga '{$team->name}' siap:");
        $this->line("  Email    : {$user->email}");
        $this->line("  Nama     : {$user->name}");
        $this->line("  Password : {$password}");
        $this->line('  Role     : '.TeamRole::AdminLembaga->label());
        $this->line('  Login    : '.url("/{$team->slug}/dashboard"));

        return self::SUCCESS;
    }
}
