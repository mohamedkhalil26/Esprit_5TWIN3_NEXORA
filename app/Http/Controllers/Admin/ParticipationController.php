<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipationRequest;
use App\Models\Evenement;
use App\Models\Participation;
use App\Models\User;
use App\Services\ParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipationController extends Controller
{
    /**
     * Toutes les inscriptions avec l'événement et le membre (jointures).
     */
    public function index(Request $request): View
    {
        $participations = Participation::query()
            ->with(['evenement', 'participant'])
            ->when($request->input('evenement'), fn ($q, $id) => $q->where('evenement_id', $id))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->input('q'), fn ($q, $nom) => $q->whereHas('participant', fn ($u) => $u->where('name', 'like', "%{$nom}%")))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('modules.evenements.admin.participations.index', [
            'participations' => $participations,
            'evenements' => Evenement::orderByDesc('date_debut')->get(['id', 'titre', 'date_debut']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('modules.evenements.admin.participations.create', [
            'participation' => new Participation([
                'evenement_id' => $request->integer('evenement') ?: null,
                'statut' => 'inscrit',
            ]),
            ...$this->listesFormulaire(),
        ]);
    }

    public function store(ParticipationRequest $request): RedirectResponse
    {
        $participation = app(ParticipationService::class)->save($request->validated());

        return redirect()
            ->route('admin.evenements.show', $participation->evenement_id)
            ->with('success', "{$participation->participant->name} a été inscrit(e) à l'événement.");
    }

    public function show(Participation $participation): View
    {
        $participation->load(['evenement', 'participant']);

        return view('modules.evenements.admin.participations.show', compact('participation'));
    }

    public function edit(Participation $participation): View
    {
        return view('modules.evenements.admin.participations.edit', [
            'participation' => $participation,
            ...$this->listesFormulaire(),
        ]);
    }

    public function update(ParticipationRequest $request, Participation $participation): RedirectResponse
    {
        app(ParticipationService::class)->save($request->validated(), $participation);

        return redirect()
            ->route('admin.participations.show', $participation)
            ->with('success', 'L\'inscription a été modifiée.');
    }

    public function destroy(Participation $participation): RedirectResponse
    {
        $evenementId = $participation->evenement_id;
        $participation->delete();

        return redirect()
            ->route('admin.evenements.show', $evenementId)
            ->with('success', 'L\'inscription a été supprimée.');
    }

    private function listesFormulaire(): array
    {
        return [
            'evenements' => Evenement::avecNbInscrits()->orderByDesc('date_debut')->get(),
            'membres' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
