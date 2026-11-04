@extends('layouts.admin')

@section('title', 'Feedback IA')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <x-admin.card title="Synthèse des retours IA" icon="thumbs-up">
            <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Fonctionnalité</th><th>Type</th><th>Total</th></tr></thead><tbody>
            @forelse($summary as $item)<tr><td>{{ $item->feature }}</td><td>{{ $item->feedback_type }}</td><td>{{ $item->total }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Aucun feedback enregistré.</td></tr>@endforelse
            </tbody></table></div>
        </x-admin.card>
    </div>
    <div class="col-12">
        <x-admin.card title="Derniers retours" icon="chat-square-text">
            <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Utilisateur</th><th>Fonctionnalité</th><th>Type</th><th>Note</th><th>Commentaire</th></tr></thead><tbody>
            @forelse($feedback as $item)<tr><td>{{ $item->created_at->format('d/m/Y H:i') }}</td><td>{{ $item->user?->name ?? 'Compte supprimé' }}</td><td>{{ $item->feature }}</td><td>{{ $item->feedback_type }}</td><td>{{ $item->rating ?? 'N/D' }}</td><td>{{ $item->comment ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="text-muted">Aucun feedback enregistré.</td></tr>@endforelse
            </tbody></table></div>
        </x-admin.card>
    </div>
</div>
@endsection