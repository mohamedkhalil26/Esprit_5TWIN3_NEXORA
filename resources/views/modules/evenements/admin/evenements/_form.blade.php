{{-- Formulaire partagé : création / modification d'un événement --}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
        <input type="text" id="titre" name="titre" value="{{ old('titre', $evenement->titre) }}" maxlength="150"
               class="form-control @error('titre') is-invalid @enderror">
        @error('titre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
            <option value="">— Choisir —</option>
            @foreach (\App\Models\Evenement::TYPES as $valeur => [$libelle, $icone])
                <option value="{{ $valeur }}" @selected(old('type', $evenement->type) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
        <textarea id="description" name="description" rows="5"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $evenement->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Programme, public visé, matériel à apporter... (30 caractères minimum)</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="lieu" class="form-label">Lieu <span class="text-danger">*</span></label>
        <input type="text" id="lieu" name="lieu" value="{{ old('lieu', $evenement->lieu) }}"
               class="form-control @error('lieu') is-invalid @enderror">
        @error('lieu')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
        <input type="text" id="ville" name="ville" value="{{ old('ville', $evenement->ville) }}"
               class="form-control @error('ville') is-invalid @enderror">
        @error('ville')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="date_debut" class="form-label">Début <span class="text-danger">*</span></label>
        <input type="datetime-local" id="date_debut" name="date_debut"
               value="{{ old('date_debut', $evenement->date_debut?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('date_debut') is-invalid @enderror">
        @error('date_debut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="date_fin" class="form-label">Fin <span class="text-danger">*</span></label>
        <input type="datetime-local" id="date_fin" name="date_fin"
               value="{{ old('date_fin', $evenement->date_fin?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('date_fin') is-invalid @enderror">
        @error('date_fin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="capacite" class="form-label">Capacité <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input type="number" id="capacite" name="capacite" min="1" max="500"
                   value="{{ old('capacite', $evenement->capacite) }}"
                   class="form-control @error('capacite') is-invalid @enderror">
            <span class="input-group-text">places</span>
            @error('capacite')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label for="prix" class="form-label">Prix <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input type="number" id="prix" name="prix" min="0" step="0.01"
                   value="{{ old('prix', $evenement->prix) }}"
                   class="form-control @error('prix') is-invalid @enderror">
            <span class="input-group-text">DT</span>
            @error('prix')
                <div class="invalid-feedback">{{ $message }}</div>
            @else
                <div class="form-text">0 = gratuit</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
        <select id="statut" name="statut" class="form-select @error('statut') is-invalid @enderror">
            @foreach (\App\Models\Evenement::STATUTS as $valeur => [$libelle, $couleur])
                <option value="{{ $valeur }}" @selected(old('statut', $evenement->statut) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('statut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="image" class="form-label">Affiche</label>
        <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" data-preview="#apercu-affiche"
               class="form-control @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <img id="apercu-affiche" src="{{ $evenement->image_url }}" alt="Aperçu"
             class="img-thumbnail {{ $evenement->image ? '' : 'd-none' }}" style="max-height: 120px">
    </div>
</div>
