<?php

namespace App\Http\Controllers;

use App\Services\Ai\GlobalSearch\GlobalSearchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiGlobalSearchController extends Controller
{
    public function index(Request $request, GlobalSearchService $search): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:200', 'type' => ['nullable', Rule::in(['equipements', 'evenements', 'articles', 'reservations', 'reclamations'])]]);
        $results = ! empty($data['q']) ? $search->search($request->user(), $data['q'], $data['type'] ?? null) : collect();

        return view('ai.search', ['query' => $data['q'] ?? '', 'type' => $data['type'] ?? '', 'results' => $results]);
    }
}
