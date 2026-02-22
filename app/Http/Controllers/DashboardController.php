<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        $today = Carbon::today();

        $leadQuery = Lead::query();
        $opportunityQuery = Opportunity::query();

        if ($user->isSalesExecutive()) {
            $leadQuery->where('assigned_user_id', $user->id);
            $opportunityQuery->where('assigned_user_id', $user->id);
        }

        $totalLeads = (clone $leadQuery)->count();
        $qualifiedLeads = (clone $leadQuery)->where('status', 'qualified')->count();
        $convertedLeads = (clone $leadQuery)->where('status', 'converted')->count();
        $conversionRatio = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;

        $monthlyRows = (clone $leadQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyLabels = $monthlyRows->pluck('month')->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->values();
        $monthlyValues = $monthlyRows->pluck('total')->values();

        $todayFollowUps = (clone $leadQuery)->whereDate('follow_up_date', $today)->count();
        $upcomingFollowUps = (clone $leadQuery)->whereDate('follow_up_date', '>', $today)->count();
        $overdueFollowUps = (clone $leadQuery)->whereDate('follow_up_date', '<', $today)->whereNotIn('status', ['converted', 'lost'])->count();

        $leadRows = (clone $leadQuery)
            ->with('assignedUser:id,name')
            ->select(['id', 'name', 'status', 'source', 'follow_up_date', 'assigned_user_id', 'created_at', 'notes', 'conversion_probability'])
            ->orderByDesc('created_at')
            ->get();

        $predictedLeads = $leadRows->map(fn (Lead $lead) => $this->buildPredictionPayload($lead, $today));

        $highConfidenceClosures = $predictedLeads
            ->whereIn('status', ['new', 'contacted', 'qualified', 'interested'])
            ->where('prediction_score', '>=', 80)
            ->count();

        $needsAttentionCount = $predictedLeads
            ->whereIn('status', ['new', 'contacted', 'qualified', 'interested'])
            ->where('prediction_score', '<', 45)
            ->count();

        $avgPredictionScore = round((float) $predictedLeads->avg('prediction_score'), 1);

        $totalOpportunityValue = (float) (clone $opportunityQuery)->sum('estimated_value');
        $forecastRevenue = (float) (clone $opportunityQuery)
            ->get()
            ->sum(fn (Opportunity $opportunity) => ((float) $opportunity->estimated_value) * ((int) $opportunity->probability / 100));

        $stageDistribution = (clone $opportunityQuery)
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('dashboard.index', [
            'totalLeads' => $totalLeads,
            'qualifiedLeads' => $qualifiedLeads,
            'convertedLeads' => $convertedLeads,
            'conversionRatio' => $conversionRatio,
            'monthlyLabels' => $monthlyLabels,
            'monthlyValues' => $monthlyValues,
            'todayFollowUps' => $todayFollowUps,
            'upcomingFollowUps' => $upcomingFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'highConfidenceClosures' => $highConfidenceClosures,
            'needsAttentionCount' => $needsAttentionCount,
            'avgPredictionScore' => $avgPredictionScore,
            'totalOpportunityValue' => $totalOpportunityValue,
            'forecastRevenue' => $forecastRevenue,
            'stageDistribution' => $stageDistribution,
            'dashboardLeadRows' => $predictedLeads->take(150)->values(),
            'topPredictedLeads' => $predictedLeads
                ->whereIn('status', ['new', 'contacted', 'qualified', 'interested'])
                ->sortByDesc('prediction_score')
                ->take(8)
                ->values(),
        ]);
    }

    private function buildPredictionPayload(Lead $lead, Carbon $today): array
    {
        $statusWeight = [
            'new' => 22,
            'contacted' => 38,
            'qualified' => 52,
            'interested' => 68,
            'converted' => 97,
            'lost' => 6,
        ];

        $sourceWeight = [
            'referral' => 14,
            'linkedin' => 10,
            'website' => 8,
            'email campaign' => 6,
            'cold call' => 3,
        ];

        $normalizedSource = strtolower(trim((string) ($lead->source ?: '')));
        $score = $lead->conversion_probability ?: ($statusWeight[$lead->status] ?? 30);
        $score += $sourceWeight[$normalizedSource] ?? 4;

        if ($lead->follow_up_date) {
            if ($lead->follow_up_date->lt($today) && ! in_array($lead->status, ['converted', 'lost'], true)) {
                $score -= 14;
            } elseif ($lead->follow_up_date->lte($today->copy()->addDays(2))) {
                $score += 9;
            } else {
                $score += 3;
            }
        } else {
            $score -= 6;
        }

        if (strlen((string) $lead->notes) > 90) {
            $score += 4;
        }

        $score = max(2, min(99, $score));

        $band = match (true) {
            $score >= 80 => 'high',
            $score >= 70 => 'likely',
            $score >= 45 => 'moderate',
            default => 'at_risk',
        };

        $action = match ($band) {
            'high' => 'Call today and send proposal.',
            'likely' => 'Schedule product demo this week.',
            'moderate' => 'Nurture with follow-up sequence.',
            default => 'Re-engage with new value angle.',
        };

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'status' => $lead->status,
            'source' => $lead->source ?: 'Unknown',
            'follow_up_date' => optional($lead->follow_up_date)->format('Y-m-d'),
            'assigned_to' => $lead->assignedUser?->name ?: 'Unassigned',
            'created_at' => optional($lead->created_at)->format('Y-m-d'),
            'prediction_score' => $score,
            'prediction_band' => $band,
            'recommended_action' => $action,
            'url' => route('leads.show', $lead),
        ];
    }
}
