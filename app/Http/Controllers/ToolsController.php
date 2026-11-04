<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Reclamation;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ToolsController extends Controller
{
    public function attachment(Request $request, Reclamation $reclamation)
    {
        abort_unless($request->user()->isAdmin() || (int) $reclamation->user_id === $request->user()->id, 403);
        $path = $reclamation->piece_jointe;
        abort_unless($path && ! str_contains($path, '..'), 404);
        if (str_starts_with($path, 'reclamations/')) {
            abort_unless(Storage::disk('local')->exists($path), 404);

            return Storage::disk('local')->download($path);
        }
        abort_unless(str_starts_with($path, 'uploads/reclamations/') && is_file(public_path($path)), 404);

        return response()->download(public_path($path));
    }

    public function compare(Request $request)
    {
        $data = $request->validate(['ids' => 'nullable|array|min:2|max:4', 'ids.*' => 'required|integer|distinct|exists:equipements,id']);

        return view('tools.compare', [
            'catalogue' => Equipement::disponibles()->orderBy('nom')->get(['id', 'nom']),
            'items' => isset($data['ids']) ? Equipement::with('categorie')->whereIn('id', $data['ids'])->get() : collect(),
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'equipement' => 'nullable|integer|exists:equipements,id',
            'date_debut' => 'nullable|required_with:date_fin|date_format:Y-m-d|after_or_equal:today',
            'date_fin' => 'nullable|required_with:date_debut|date_format:Y-m-d|after_or_equal:date_debut',
        ]);
        $item = isset($data['equipement']) ? Equipement::findOrFail($data['equipement']) : null;
        $periods = $item ? Reservation::where('equipement_id', $item->id)->whereIn('statut', Reservation::STATUTS_ACTIFS)
            ->whereDate('date_fin', '>=', today())->orderBy('date_debut')->get(['date_debut', 'date_fin']) : collect();
        $estimate = null;
        if ($item && ! empty($data['date_debut']) && ! empty($data['date_fin'])) {
            $days = Reservation::calculerJours($data['date_debut'], $data['date_fin']);
            $available = $days <= 30 && $item->disponible && $item->user_id !== $request->user()->id
                && ! Reservation::chevauchant($item->id, $data['date_debut'], $data['date_fin'])->exists();
            $estimate = ['days' => $days, 'total' => round($days * $item->prix_jour, 2), 'available' => $available];
        }

        return view('tools.availability', [
            'catalogue' => Equipement::orderBy('nom')->get(['id', 'nom']),
            'item' => $item, 'periods' => $periods, 'estimate' => $estimate,
        ]);
    }

    public function favorites(Request $request)
    {
        return view('tools.favorites', ['articles' => $request->user()->favoris()->publies()->latest('article_user.created_at')->paginate(9)]);
    }

    public function favorite(Request $request, Article $article)
    {
        abort_unless(Article::publies()->whereKey($article->id)->exists(), 404);
        $request->user()->favoris()->syncWithoutDetaching([$article->id]);

        return back()->with('success', 'Article enregistré dans vos favoris.');
    }

    public function unfavorite(Request $request, Article $article)
    {
        $request->user()->favoris()->detach($article->id);

        return back()->with('success', 'Article retiré de vos favoris.');
    }
}
