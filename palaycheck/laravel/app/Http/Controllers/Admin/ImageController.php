<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Detection;

class ImageController extends Controller
{
    public function index()
    {
        $images = Detection::with('user')
            ->whereNotNull('image_file')
            ->where('image_file', '<>', '')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.images', ['images' => $images]);
    }
}
