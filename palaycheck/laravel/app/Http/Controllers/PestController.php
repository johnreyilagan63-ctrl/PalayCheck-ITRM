<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use App\Models\Pest;
use Illuminate\Http\Request;

class PestController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $pests = Pest::query()
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('scientific_name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('pests.index', ['pests' => $pests, 'search' => $search]);
    }

    public function show(Pest $pest)
    {
        $advice = Advice::where('pest_id', $pest->id)
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        return view('pests.show', ['pest' => $pest, 'advice' => $advice]);
    }
}
