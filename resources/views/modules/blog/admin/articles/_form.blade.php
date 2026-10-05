{{-- Formulaire partagé : création / modification d'un article --}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
        <input type="text" id="titre" name="titre" value="{{ old('titre', $article->titre) }}" maxlength="180"
               class="form-control form-control-lg @error('titre') is-invalid @enderror">
        @error('titre')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            @if ($article->slug)<div class="form-text">Adresse : /blog/{{ $article->slug }}</div>@endif
        @enderror
    </div>

    {{-- Relation : auteur --}}
    <div class="col-md-4">
        <label for="user_id" class="form-label">Auteur <span class="text-danger">*</span></label>
        <select id="user_id" name="user_id" class="form-select form-select-lg @error('user_id') is-invalid @enderror">
            @foreach ($auteurs as $auteur)
                <option value="{{ $auteur->id }}" @selected(old('user_id', $article->user_id) == $auteur->id)>
                    {{ $auteur->name }}{{ $auteur->role === 'admin' ? ' (admin)' : '' }}
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="resume" class="form-label">Résumé <span class="text-danger">*</span></label>
        <textarea id="resume" name="resume" rows="2" maxlength="300"
                  class="form-control @error('resume') is-invalid @enderror">{{ old('resume', $article->resume) }}</textarea>
        @error('resume')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text"><span id="compteur-resume">{{ mb_strlen(old('resume', $article->resume ?? '')) }}</span> / 300 caractères</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="contenu" class="form-label">Contenu <span class="text-danger">*</span></label>
        <textarea id="contenu" name="contenu" rows="12"
                  class="form-control @error('contenu') is-invalid @enderror">{{ old('contenu', $article->contenu) }}</textarea>
        @error('contenu')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Séparez les paragraphes par une ligne vide. <span id="compteur-mots">0</span> mot(s).</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
        <select id="categorie" name="categorie" class="form-select @error('categorie') is-invalid @enderror">
            <option value="">— Choisir —</option>
            @foreach (\App\Models\Article::CATEGORIES as $valeur => [$libelle, $icone])
                <option value="{{ $valeur }}" @selected(old('categorie', $article->categorie) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('categorie')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label d-block">Statut <span class="text-danger">*</span></label>
        @foreach (\App\Models\Article::STATUTS as $valeur => [$libelle, $couleur])
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="statut" id="statut_{{ $valeur }}" value="{{ $valeur }}"
                       @checked(old('statut', $article->statut) === $valeur)>
                <label class="form-check-label" for="statut_{{ $valeur }}">{{ $libelle }}</label>
            </div>
        @endforeach
        @error('statut')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="publie_le" class="form-label">Date de publication</label>
        <input type="datetime-local" id="publie_le" name="publie_le"
               value="{{ old('publie_le', $article->publie_le?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('publie_le') is-invalid @enderror">
        @error('publie_le')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">Vide = maintenant (à la publication).</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="image" class="form-label">Image de couverture</label>
        <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp" data-preview="#apercu-couverture"
               class="form-control @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <img id="apercu-couverture" src="{{ $article->image_url }}" alt="Aperçu"
             class="img-thumbnail {{ $article->image ? '' : 'd-none' }}" style="max-height: 120px">
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const resume = document.getElementById('resume');
            const contenu = document.getElementById('contenu');
            const majCompteurs = () => {
                const cr = document.getElementById('compteur-resume');
                const cm = document.getElementById('compteur-mots');
                if (cr) cr.textContent = resume.value.length;
                if (cm) cm.textContent = contenu.value.trim() ? contenu.value.trim().split(/\s+/).length : 0;
            };
            resume.addEventListener('input', majCompteurs);
            contenu.addEventListener('input', majCompteurs);
            majCompteurs();
        })();
    </script>
@endpush
