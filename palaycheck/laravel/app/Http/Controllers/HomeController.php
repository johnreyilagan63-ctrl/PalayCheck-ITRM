<?php

namespace App\Http\Controllers;

use App\Models\Pest;

class HomeController extends Controller
{
    public function index()
    {
        $pests = Pest::where('status', 'active')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('home', ['pests' => $pests]);
    }
}
