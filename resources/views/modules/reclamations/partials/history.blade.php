<div class="card p-4 my-4"><h2 class="h5">Historique du traitement</h2>
<ul class="mb-0">@forelse($reclamation->historique as $entry)<li class="mb-2">
{{ $entry->created_at->format('d/m/Y H:i') }} — {{ $entry->auteur?->name ?? 'Compte supprimé / système' }}
@foreach($entry->changes as $field => $change)<div>{{ ['statut'=>'Statut', 'priorite'=>'Priorité', 'categorie'=>'Catégorie'][$field] ?? $field }} : {{ $change['avant'] }} → {{ $change['apres'] }}</div>@endforeach
</li>@empty<li>Aucun changement de statut, catégorie ou priorité enregistré depuis l’activation du suivi.</li>@endforelse</ul></div>
