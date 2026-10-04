{{-- Formulaire partagé : création / modification d'une réponse --}}
<div class="row g-3">
    {{-- Relation : réclamation concernée --}}
    <div class="col-12">
        <label for="reclamation_id" class="form-label">Réclamation <span class="text-danger">*</span></label>
        <select id="reclamation_id" name="reclamation_id" class="form-select @error('reclamation_id') is-invalid @enderror">
            <option value="">— Choisir une réclamation —</option>
            @foreach ($reclamations as $reclamation)
                <option value="{{ $reclamation->id }}" @selected(old('reclamation_id', $reponse->reclamation_id) == $reclamation->id)>
                    N°{{ $reclamation->id }} — {{ $reclamation->sujet }} ({{ $reclamation->auteur->name }}, {{ $reclamation->statut_label }})
                </option>
            @endforeach
        </select>
        @error('reclamation_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="message" class="form-label">Réponse <span class="text-danger">*</span></label>
        <textarea id="message" name="message" rows="6" maxlength="2000"
                  class="form-control @error('message') is-invalid @enderror">{{ old('message', $reponse->message) }}</textarea>
        @error('message')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Entre 5 et 2000 caractères.</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="nouveau_statut" class="form-label">Mettre à jour le statut de la réclamation</label>
        <select id="nouveau_statut" name="nouveau_statut" class="form-select @error('nouveau_statut') is-invalid @enderror">
            <option value="">Ne pas modifier</option>
            @foreach (\App\Models\Reclamation::STATUTS as $valeur => [$libelle, $couleur])
                <option value="{{ $valeur }}" @selected(old('nouveau_statut') === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('nouveau_statut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
