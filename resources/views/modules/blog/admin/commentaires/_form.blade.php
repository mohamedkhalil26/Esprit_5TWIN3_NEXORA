{{-- Formulaire partagé : création / modification d'un commentaire --}}
<div class="row g-3">
    {{-- Relation : article commenté --}}
    <div class="col-md-7">
        <label for="article_id" class="form-label">Article <span class="text-danger">*</span></label>
        <select id="article_id" name="article_id" class="form-select @error('article_id') is-invalid @enderror">
            <option value="">— Choisir un article —</option>
            @foreach ($articles as $id => $titre)
                <option value="{{ $id }}" @selected(old('article_id', $commentaire->article_id) == $id)>{{ $titre }}</option>
            @endforeach
        </select>
        @error('article_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Relation : auteur du commentaire --}}
    <div class="col-md-5">
        <label for="user_id" class="form-label">Auteur <span class="text-danger">*</span></label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            @foreach ($auteurs as $auteur)
                <option value="{{ $auteur->id }}" @selected(old('user_id', $commentaire->user_id) == $auteur->id)>{{ $auteur->name }}</option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="contenu" class="form-label">Commentaire <span class="text-danger">*</span></label>
        <textarea id="contenu" name="contenu" rows="4" maxlength="1000"
                  class="form-control @error('contenu') is-invalid @enderror">{{ old('contenu', $commentaire->contenu) }}</textarea>
        @error('contenu')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="hidden" name="approuve" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="approuve" name="approuve" value="1"
                   @checked(old('approuve', $commentaire->approuve))>
            <label class="form-check-label" for="approuve">Approuvé (visible sur le blog)</label>
        </div>
    </div>
</div>
