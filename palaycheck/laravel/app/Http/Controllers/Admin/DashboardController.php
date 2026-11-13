<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advice;
use App\Models\Detection;
use App\Models\LearningMaterial;
use App\Models\Pest;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $detections = Detection::orderByDesc('created_at')->get();
        $matched = $detections->whereNotNull('pest_id')->count();
        $total = $detections->count();

        $byMonth = $detections
            ->filter(fn (Detection $detection) => $detection->created_at !== null)
            ->groupBy(fn (Detection $detection) => $detection->created_at->format('Y-m'))
            ->map(fn ($rows, string $label) => ['label' => $label, 'value' => $rows->count()])
            ->sortKeys()
            ->values()
            ->all();

        $byPest = $detections
            ->groupBy(fn (Detection $detection) => $detection->label ?? 'Unknown')
            ->map(fn ($rows, string $label) => ['label' => $label, 'value' => $rows->count()])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        $bySeverity = $detections
            ->groupBy(fn (Detection $detection) => $detection->severity ?? 'low')
            ->map(fn ($rows, string $label) => ['label' => $label, 'value' => $rows->count()])
            ->sortByDesc('value')
            ->values()
            ->all();

        $months = [];
        $cursor = now()->startOfMonth();
        for ($index = 0; $index < 6; $index++) {
            $months[] = $cursor->format('Y-m');
            $cursor = $cursor->subMonth();
        }

        $registrations = User::whereNotNull('created_at')
            ->get()
            ->groupBy(fn (User $user) => $user->created_at->format('Y-m'));

        $reg = collect(array_reverse($months))
            ->map(fn (string $month) => [
                'label' => $month,
                'value' => $registrations->get($month, collect())->count(),
            ])
            ->all();

        return view('admin.dashboard', [
            'users' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'pests' => Pest::count(),
            'activePests' => Pest::where('status', 'active')->count(),
            'total' => $total,
            'matched' => $matched,
            'learning' => LearningMaterial::count(),
            'publishedLearning' => LearningMaterial::where('status', 'published')->count(),
            'adviceCount' => Advice::count(),
            'publishedAdvice' => Advice::where('status', 'published')->count(),
            'byMonth' => $byMonth,
            'byPest' => $byPest,
            'bySeverity' => $bySeverity,
            'reg' => $reg,
            'recent' => $detections->take(5),
            'recentUsers' => User::orderByDesc('created_at')->limit(5)->get(),
            'status' => model_status(),
            'matchRate' => $total > 0 ? $matched / $total * 100 : 0,
        ]);
    }
}
