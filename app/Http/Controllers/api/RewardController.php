<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        $goal = (int) config('rewards.goal_points', 1000);
        $reward = config('rewards.default_reward');

        $points = (int) $user->points;
        $eligibleRewardsCount = intdiv($points, $goal);
        $remainingPoints = $goal > 0 ? max($goal - ($points % $goal), 0) : 0;

        if ($eligibleRewardsCount > 0 && $points % $goal === 0) {
            $remainingPoints = 0;
        }

        $progressBase = $goal > 0 ? ($points % $goal) : 0;
        $progress = $goal > 0
            ? round(($progressBase / $goal) * 100, 2)
            : 0;

        if ($eligibleRewardsCount > 0 && $points % $goal === 0) {
            $progress = 100;
        }

        $redemptions = $user->rewardRedemptions()
            ->latest()
            ->get([
                'id',
                'reward_code',
                'reward_name',
                'points_cost',
                'redeemed_at',
                'created_at',
            ]);

        return response()->json([
            'current_points' => $points,
            'goal' => $goal,
            'progress' => $progress,
            'remaining_points' => $remainingPoints,
            'eligible_rewards_count' => $eligibleRewardsCount,
            'has_reward' => $eligibleRewardsCount > 0,
            'reward' => [
                'code' => $reward['code'],
                'name' => $reward['name'],
                'points_cost' => (int) $reward['points_cost'],
            ],
            'redemptions' => $redemptions,
        ]);
    }

    public function redeem(Request $request)
    {
        $user = $request->user();
        $reward = config('rewards.default_reward');
        $goal = (int) $reward['points_cost'];

        $redemption = DB::transaction(function () use ($user, $reward, $goal) {
            $lockedUser = \App\Models\User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedUser) {
                return null;
            }

            if ((int) $lockedUser->points < $goal) {
                return false;
            }

            $lockedUser->decrement('points', $goal);

            return RewardRedemption::create([
                'user_id' => $lockedUser->id,
                'reward_code' => $reward['code'],
                'reward_name' => $reward['name'],
                'points_cost' => $goal,
                'redeemed_at' => now(),
                'redeemed_by' => $lockedUser->id,
            ]);
        });

        if ($redemption === false) {
            return response()->json([
                'message' => 'Points insuffisants pour réclamer cette récompense.',
            ], 422);
        }

        if (!$redemption) {
            return response()->json([
                'message' => 'Impossible de traiter la récompense.',
            ], 500);
        }

        return response()->json([
            'message' => 'Récompense réclamée avec succès.',
            'redemption' => $redemption,
        ], 201);
    }
}