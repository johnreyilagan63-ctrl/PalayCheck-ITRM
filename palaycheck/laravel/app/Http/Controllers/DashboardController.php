<?php

namespace App\Http\Controllers;

use App\Models\Detection;
use App\Models\LearningMaterial;
use App\Models\Pest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $total = Detection::where('user_id', $user->id)->count();
        $pestCount = Pest::where('status', 'active')->count();
        $guideCount = LearningMaterial::where('status', 'published')->count();
        $recent = Detection::where('user_id', $user->id)->orderByDesc('created_at')->limit(5)->get();

        return view('dashboard', [
            'user' => $user,
            'total' => $total,
            'pestCount' => $pestCount,
            'guideCount' => $guideCount,
            'recent' => $recent,
        ]);
    }
}
