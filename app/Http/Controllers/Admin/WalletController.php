<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveWalletTransactionRequest;
use App\Models\Santri;
use App\Models\Team;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    use ScopesSantri;

    /**
     * Display the wallet and its transactions for a santri.
     */
    public function index(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $transactions = $santri->walletTransactions()
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (WalletTransaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'type_label' => $transaction->type->label(),
                'amount' => (float) $transaction->amount,
                'tanggal' => $transaction->tanggal->format('Y-m-d'),
                'description' => $transaction->description,
            ]);

        return Inertia::render('admin/wallet/index', [
            'santri' => $this->toSantri($santri),
            'team' => $this->toTeamData($current_team),
            'transactions' => $transactions,
            'balance' => (float) ($santri->wallet?->balance ?? 0),
            'types' => $this->toEnumOptions(WalletTransactionType::cases()),
        ]);
    }

    /**
     * Record a wallet transaction for a santri.
     */
    public function store(SaveWalletTransactionRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $data = $request->validated();
        $data['type'] = WalletTransactionType::from($data['type']);
        $data['team_id'] = $current_team->id;

        if ($data['type'] === WalletTransactionType::Penarikan) {
            $balance = (float) ($santri->wallet?->balance ?? 0);

            if ($data['amount'] > $balance) {
                throw ValidationException::withMessages([
                    'amount' => __('Saldo tidak mencukupi untuk penarikan tersebut.'),
                ]);
            }
        }

        $transaction = $santri->walletTransactions()->create($data);

        $this->adjustBalance($santri, $transaction->type, $transaction->amount);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaksi dompet berhasil dicatat.')]);

        return back();
    }

    /**
     * Remove the specified wallet transaction.
     */
    public function destroy(Team $current_team, Santri $santri, WalletTransaction $transaction): RedirectResponse
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($transaction->santri_id === $santri->id, 404);

        $this->adjustBalance($santri, $transaction->type, -$transaction->amount);

        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaksi dompet berhasil dihapus.')]);

        return back();
    }

    /**
     * Apply a transaction effect to the santri's wallet balance.
     */
    protected function adjustBalance(Santri $santri, WalletTransactionType $type, float $amount): void
    {
        $sign = $type === WalletTransactionType::Penarikan ? -1 : 1;

        $wallet = $santri->wallet()->firstOrCreate([]);

        $wallet->increment('balance', $sign * $amount);
    }
}
