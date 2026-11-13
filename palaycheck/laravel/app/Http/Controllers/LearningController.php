<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;

class LearningController extends Controller
{
    public function index()
    {
        $items = LearningMaterial::where('status', 'published')->orderBy('id')->get();

        return view('learning.index', ['items' => $items]);
    }

    public function show(LearningMaterial $learning)
    {
        abort_unless($learning->status === 'published', 404);

        return view('learning.show', ['item' => $learning]);
    }
}
