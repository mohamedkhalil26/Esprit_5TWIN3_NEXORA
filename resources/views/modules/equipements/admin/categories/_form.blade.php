{{-- Formulaire partagé entre la création et la modification d'une catégorie --}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="nom" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
        <input type="text" id="nom" name="nom" value="{{ old('nom', $categorie->nom) }}"
               class="form-control @error('nom') is-invalid @enderror" maxlength="100" autofocus>
        @error('nom')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="icone" class="form-label">Icône <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <span class="input-group-text"><i id="apercu-icone" class="bi {{ old('icone', $categorie->icone) }}"></i></span>
            <select id="icone" name="icone" class="form-select @error('icone') is-invalid @enderror"
                    onchange="document.getElementById('apercu-icone').className = 'bi ' + this.value">
                @foreach (\App\Models\Categorie::ICONES as $classe => $libelle)
                    <option value="{{ $classe }}" @selected(old('icone', $categorie->icone) === $classe)>{{ $libelle }}</option>
                @endforeach
            </select>
            @error('icone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="500"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $categorie->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">500 caractères maximum.</div>
        @enderror
    </div>
</div>
