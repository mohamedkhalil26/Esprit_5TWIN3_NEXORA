{{-- Formulaire partagé : création / modification d'une inscription --}}
<div class="row g-3">
    {{-- Relation : événement --}}
    <div class="col-md-6">
        <label for="evenement_id" class="form-label">Événement <span class="text-danger">*</span></label>
        <select id="evenement_id" name="evenement_id" class="form-select @error('evenement_id') is-invalid @enderror">
            <option value="">— Choisir un événement —</option>
            @foreach ($evenements as $evenement)
                <option value="{{ $evenement->id }}" @selected(old('evenement_id', $participation->evenement_id) == $evenement->id)>
                    {{ $evenement->date_debut->format('d/m/Y') }} — {{ $evenement->titre }}
                    ({{ $evenement->nb_inscrits }}/{{ $evenement->capacite }}){{ $evenement->statut === 'annule' ? ' — annulé' : '' }}
                </option>
            @endforeach
        </select>
        @error('evenement_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Relation : membre --}}
    <div class="col-md-6">
        <label for="user_id" class="form-label">Membre <span class="text-danger">*</span></label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">— Choisir un membre —</option>
            @foreach ($membres as $membre)
                <option value="{{ $membre->id }}" @selected(old('user_id', $participation->user_id) == $membre->id)>
                    {{ $membre->name }} ({{ $membre->email }})
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label d-block">Statut <span class="text-danger">*</span></label>
        @foreach (\App\Models\Participation::STATUTS as $valeur => [$libelle, $couleur])
            <input type="radio" class="btn-check" name="statut" id="statut_{{ $valeur }}" value="{{ $valeur }}"
                   @checked(old('statut', $participation->statut) === $valeur)>
            <label class="btn btn-sm btn-outline-{{ $couleur }} mb-1" for="statut_{{ $valeur }}">{{ $libelle }}</label>
        @endforeach
        @error('statut')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="commentaire" class="form-label">Commentaire</label>
        <textarea id="commentaire" name="commentaire" rows="2" maxlength="500"
                  class="form-control @error('commentaire') is-invalid @enderror">{{ old('commentaire', $participation->commentaire) }}</textarea>
        @error('commentaire')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
