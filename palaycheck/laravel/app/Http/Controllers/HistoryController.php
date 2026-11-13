<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use App\Models\Detection;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $items = Detection::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return view('history.index', ['items' => $items]);
    }

    public function show(Request $request, Detection $detection)
    {
        $this->authorizeDetection($request, $detection);

        $advice = $detection->pest_id
            ? Advice::where('pest_id', $detection->pest_id)->where('status', 'published')->get()
            : collect();

        return view('history.show', [
            'detection' => $detection->load('pest'),
            'advice' => $advice,
        ]);
    }

    public function destroy(Request $request, Detection $detection)
    {
        $this->authorizeDetection($request, $detection);

        remove_detection($detection);
        flash('Scan deleted.');

        return redirect()->route('history.index');
    }

    private function authorizeDetection(Request $request, Detection $detection): void
    {
        $user = $request->user();
        abort_unless((int) $detection->user_id === (int) $user->id || $user->isAdmin(), 403);
    }
}
