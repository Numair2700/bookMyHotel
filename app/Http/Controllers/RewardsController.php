<?php

namespace App\Http\Controllers;

use App\Models\RewardPointsLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardsController extends Controller
{
    /** FR13 — the guest's reward points balance and ledger. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $ledger = $user->rewardPointsLedger()
            ->latest()
            ->get()
            ->map(fn (RewardPointsLedger $entry): array => [
                'id' => $entry->id,
                'points' => $entry->points,
                'reason' => $entry->reason,
                'created_at' => $entry->created_at?->toDateString(),
            ]);

        return Inertia::render('rewards/index', [
            'balance' => (int) $user->reward_points_balance,
            'ledger' => $ledger->all(),
        ]);
    }
}
