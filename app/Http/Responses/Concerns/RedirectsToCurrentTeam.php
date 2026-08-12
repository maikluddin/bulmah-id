<?php

namespace App\Http\Responses\Concerns;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $team = $this->currentTeam($request);

        URL::defaults(['current_team' => $team->slug]);

        return "/{$team->slug}{$redirect}";
    }

    protected function currentTeam(Request $request): Team
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $team = $user->currentTeam;

        if (! $team || $team->is_personal) {
            $team = $user->firstLembaga() ?? $user->personalTeam();
        }

        abort_if(! $team, 403);

        if (! $user->isCurrentTeam($team)) {
            $user->switchTeam($team);
        }

        return $team;
    }
}
