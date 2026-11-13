<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use App\Models\Pest;
use Illuminate\Http\Request;

class AdviceController extends Controller
{
    public function index(Request $request)
    {
        $pestId = filter_var($request->query('pest'), FILTER_VALIDATE_INT) ?: null;

        $pests = Pest::orderBy('name')->get();
        $items = Advice::with('pest')
            ->where('status', 'published')
            ->when($pestId, fn ($query) => $query->where('pest_id', $pestId))
            ->orderBy('pest_id')
            ->get();

        return view('advice', ['items' => $items, 'pests' => $pests, 'pestId' => $pestId]);
    }
}
