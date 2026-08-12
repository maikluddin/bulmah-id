<?php

namespace App\Http\Controllers\Website;

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StorePendaftaranRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PendaftaranController extends Controller
{
    /**
     * Show the public registration form for a lembaga.
     */
    public function create(Team $lembaga): Response
    {
        abort_if($lembaga->is_personal, 404);

        return Inertia::render('public/pendaftaran-create', [
            'lembaga' => [
                'id' => $lembaga->id,
                'name' => $lembaga->name,
                'slug' => $lembaga->slug,
                'jenjang_label' => $lembaga->jenjang_label,
            ],
            'genders' => $this->toEnumOptions(SantriGender::cases()),
        ]);
    }

    /**
     * Register a new calon santri (pendaftar) for a lembaga.
     */
    public function store(StorePendaftaranRequest $request, Team $lembaga): RedirectResponse
    {
        abort_if($lembaga->is_personal, 404);

        $lembaga->santris()->create([
            ...$request->validated(),
            'status' => SantriStatus::Pendaftar->value,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Pendaftaran berhasil dikirim. Tim kami akan segera menghubungi Anda.'),
        ]);

        return to_route('daftar.create', $lembaga);
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    protected function toEnumOptions(array $cases): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
