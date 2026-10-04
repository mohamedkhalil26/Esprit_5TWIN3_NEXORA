{{-- Formulaire partagé entre la création et la modification d'un équipement --}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="nom" class="form-label">Nom de l'équipement <span class="text-danger">*</span></label>
        <input type="text" id="nom" name="nom" value="{{ old('nom', $equipement->nom) }}"
               class="form-control @error('nom') is-invalid @enderror" placeholder="Ex. Panneau solaire pliable 100 W">
        @error('nom')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="marque" class="form-label">Marque</label>
        <input type="text" id="marque" name="marque" value="{{ old('marque', $equipement->marque) }}"
               class="form-control @error('marque') is-invalid @enderror">
        @error('marque')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Relation : liste déroulante des catégories --}}
    <div class="col-md-6">
        <label for="categorie_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
        <select id="categorie_id" name="categorie_id" class="form-select @error('categorie_id') is-invalid @enderror">
            <option value="">— Choisir une catégorie —</option>
            @foreach ($categories as $id => $nom)
                <option value="{{ $id }}" @selected(old('categorie_id', $equipement->categorie_id) == $id)>{{ $nom }}</option>
            @endforeach
        </select>
        @error('categorie_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Relation : liste déroulante des propriétaires (users) --}}
    <div class="col-md-6">
        <label for="user_id" class="form-label">Propriétaire <span class="text-danger">*</span></label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">— Choisir un propriétaire —</option>
            @foreach ($proprietaires as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $equipement->user_id) == $user->id)>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
        <textarea id="description" name="description" rows="4"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $equipement->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Au moins 20 caractères : usage conseillé, accessoires fournis...</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="puissance_watts" class="form-label">Puissance</label>
        <div class="input-group has-validation">
            <input type="number" id="puissance_watts" name="puissance_watts" min="1"
                   value="{{ old('puissance_watts', $equipement->puissance_watts) }}"
                   class="form-control @error('puissance_watts') is-invalid @enderror">
            <span class="input-group-text">W</span>
            @error('puissance_watts')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <label for="capacite_wh" class="form-label">Capacité</label>
        <div class="input-group has-validation">
            <input type="number" id="capacite_wh" name="capacite_wh" min="1"
                   value="{{ old('capacite_wh', $equipement->capacite_wh) }}"
                   class="form-control @error('capacite_wh') is-invalid @enderror">
            <span class="input-group-text">Wh</span>
            @error('capacite_wh')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <label for="prix_jour" class="form-label">Prix par jour <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input type="number" step="0.01" min="1" id="prix_jour" name="prix_jour"
                   value="{{ old('prix_jour', $equipement->prix_jour) }}"
                   class="form-control @error('prix_jour') is-invalid @enderror">
            <span class="input-group-text">DT</span>
            @error('prix_jour')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <label for="caution" class="form-label">Caution</label>
        <div class="input-group has-validation">
            <input type="number" step="0.01" min="0" id="caution" name="caution"
                   value="{{ old('caution', $equipement->caution) }}"
                   class="form-control @error('caution') is-invalid @enderror">
            <span class="input-group-text">DT</span>
            @error('caution')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label d-block">État <span class="text-danger">*</span></label>
        @foreach (\App\Models\Equipement::ETATS as $valeur => $libelle)
            <div class="form-check form-check-inline">
                <input class="form-check-input @error('etat') is-invalid @enderror" type="radio" name="etat"
                       id="etat_{{ $valeur }}" value="{{ $valeur }}" @checked(old('etat', $equipement->etat) === $valeur)>
                <label class="form-check-label" for="etat_{{ $valeur }}">{{ $libelle }}</label>
            </div>
        @endforeach
        @error('etat')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
        <input type="text" id="ville" name="ville" value="{{ old('ville', $equipement->ville) }}"
               class="form-control @error('ville') is-invalid @enderror" list="villes">
        <datalist id="villes">
            @foreach (['Tunis', 'Ariana', 'Ben Arous', 'Sousse', 'Sfax', 'Nabeul', 'Bizerte', 'Monastir', 'Gabès'] as $ville)
                <option value="{{ $ville }}">
            @endforeach
        </datalist>
        @error('ville')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="disponible" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="disponible" name="disponible" value="1"
                   @checked(old('disponible', $equipement->disponible))>
            <label class="form-check-label" for="disponible">Disponible à la location</label>
        </div>
    </div>

    <div class="col-md-8">
        <label for="image" class="form-label">Photo</label>
        <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" data-preview="#apercu-image"
               class="form-control @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">JPG, PNG ou WEBP — 2 Mo maximum.@if ($equipement->image) Laisser vide pour conserver la photo actuelle.@endif</div>
        @enderror
    </div>
    <div class="col-md-4">
        <img id="apercu-image" src="{{ $equipement->image_url }}" alt="Aperçu"
             class="img-thumbnail {{ $equipement->image ? '' : 'd-none' }}" style="max-height: 120px">
    </div>
</div>
