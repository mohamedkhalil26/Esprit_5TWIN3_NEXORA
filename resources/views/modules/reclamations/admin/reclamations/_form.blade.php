{{-- Formulaire partagé : création / modification d'une réclamation --}}
<div class="row g-3">

    {{-- Relation : membre auteur de la réclamation --}}
    <div class="col-md-6">
        <label for="user_id" class="form-label">
            Membre <span class="text-danger">*</span>
        </label>

        <select id="user_id"
                name="user_id"
                class="form-select @error('user_id') is-invalid @enderror">

            <option value="">— Choisir un membre —</option>

            @foreach ($membres as $membre)
                <option value="{{ $membre->id }}"
                        @selected(old('user_id', $reclamation->user_id) == $membre->id)>
                    {{ $membre->name }} ({{ $membre->email }})
                </option>
            @endforeach
        </select>

        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    {{-- Catégorie --}}
    <div class="col-md-6">
        <label for="categorie" class="form-label">
            Catégorie <span class="text-danger">*</span>
        </label>

        <select id="categorie"
                name="categorie"
                class="form-select @error('categorie') is-invalid @enderror">

            <option value="">— Type de réclamation —</option>

            @foreach (\App\Models\Reclamation::CATEGORIES as $valeur => [$libelle, $icone])
                <option value="{{ $valeur }}"
                        @selected(old('categorie', $reclamation->categorie) === $valeur)>
                    {{ $libelle }}
                </option>
            @endforeach
        </select>

        @error('categorie')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    {{-- Sujet --}}
    <div class="col-12">
        <label for="sujet" class="form-label">
            Sujet <span class="text-danger">*</span>
        </label>

        <input type="text"
               id="sujet"
               name="sujet"
               value="{{ old('sujet', $reclamation->sujet) }}"
               maxlength="150"
               class="form-control @error('sujet') is-invalid @enderror">

        @error('sujet')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    {{-- Description --}}
    <div class="col-12">
        <label for="description" class="form-label">
            Description <span class="text-danger">*</span>
        </label>

        <textarea id="description"
                  name="description"
                  rows="5"
                  maxlength="3000"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $reclamation->description) }}</textarea>

        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    {{-- Priorité --}}
    <div class="col-md-6">
        <div class="form-label">
            Priorité <span class="text-danger">*</span>
        </div>

        @foreach (\App\Models\Reclamation::PRIORITES as $valeur => [$libelle, $couleur])

            <input type="radio"
                   class="btn-check"
                   name="priorite"
                   id="priorite_{{ $valeur }}"
                   value="{{ $valeur }}"
                   @checked(old('priorite', $reclamation->priorite) === $valeur)>

            <label class="btn btn-sm btn-outline-{{ $couleur }} mb-1"
                   for="priorite_{{ $valeur }}">
                {{ $libelle }}
            </label>

        @endforeach

        @error('priorite')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>


    {{-- Statut --}}
    <div class="col-md-6">
        <label for="statut" class="form-label">
            Statut <span class="text-danger">*</span>
        </label>

        <select id="statut"
                name="statut"
                class="form-select @error('statut') is-invalid @enderror">

            @foreach (\App\Models\Reclamation::STATUTS as $valeur => [$libelle, $couleur])
                <option value="{{ $valeur }}"
                        @selected(old('statut', $reclamation->statut) === $valeur)>
                    {{ $libelle }}
                </option>
            @endforeach
        </select>

        @error('statut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    {{-- Pièce jointe --}}
    <div class="col-12">
        <label for="piece_jointe" class="form-label">
            Pièce jointe
        </label>

        <input type="file"
               id="piece_jointe"
               name="piece_jointe"
               accept=".jpg,.jpeg,.png,.pdf"
               class="form-control @error('piece_jointe') is-invalid @enderror">

        @error('piece_jointe')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">
                Image ou PDF, 4 Mo maximum.

                @if ($reclamation->piece_jointe)
                    Fichier actuel :
                    <a href="{{ $reclamation->piece_jointe_url }}"
                       target="_blank"
                       rel="noopener noreferrer">
                        voir
                    </a>
                    (laisser vide pour le conserver).
                @endif
            </div>
        @enderror
    </div>

</div>
