<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;

class LeadScoringService
{
    public function calculate(Lead $lead): array
    {
        $statusWeight = [
            'new' => 15,
            'contacted' => 30,
            'qualified' => 50,
            'interested' => 68,
            'converted' => 100,
            'lost' => 5,
        ];

        $sourceWeight = [
            'referral' => 20,
            'linkedin' => 16,
            'website' => 14,
            'email campaign' => 10,
            'cold call' => 6,
        ];

        $normalizedSource = strtolower(trim((string) ($lead->source ?: '')));
        $score = $statusWeight[$lead->status] ?? 20;
        $score += $sourceWeight[$normalizedSource] ?? 8;

        $followUps = $lead->relationLoaded('followUps') ? $lead->followUps : $lead->followUps()->get();
        $totalFollowUps = $followUps->count();
        $completedFollowUps = $followUps->where('status', 'completed')->count();

        $score += min(14, $totalFollowUps * 2);
        $score += min(10, $completedFollowUps * 2);

        if ($lead->follow_up_date) {
            $daysDiff = Carbon::today()->diffInDays($lead->follow_up_date, false);
            if ($daysDiff < 0 && ! in_array($lead->status, ['converted', 'lost'], true)) {
                $score -= min(20, abs($daysDiff) * 2);
            } elseif ($daysDiff <= 2) {
                $score += 8;
            } elseif ($daysDiff <= 7) {
                $score += 4;
            }
        } else {
            $score -= 5;
        }

        $ageDays = Carbon::today()->diffInDays($lead->created_at ?: now());
        if ($ageDays > 45 && ! in_array($lead->status, ['converted', 'lost'], true)) {
            $score -= min(15, (int) floor(($ageDays - 45) / 7));
        }

        $score = max(0, min(100, $score));
        $band = $score >= 75 ? 'hot' : ($score >= 45 ? 'warm' : 'cold');
        $bestFollowUp = $this->bestFollowUpAt($lead, $score);

        return [
            'score' => $score,
            'score_band' => $band,
            'conversion_probability' => $score,
            'best_follow_up_at' => $bestFollowUp,
        ];
    }

    private function bestFollowUpAt(Lead $lead, int $score): Carbon
    {
        $base = Carbon::now()->startOfHour();

        if ($lead->follow_up_date) {
            $base = $lead->follow_up_date->copy()->setTime(10, 0);
        }

        return match (true) {
            $score >= 75 => $base->copy()->setTime(9, 30),
            $score >= 45 => $base->copy()->setTime(11, 0),
            default => $base->copy()->setTime(15, 0),
        };
    }
}
