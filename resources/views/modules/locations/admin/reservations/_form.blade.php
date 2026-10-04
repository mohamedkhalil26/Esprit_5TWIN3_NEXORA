{{-- Formulaire partagé : création / modification d'une réservation --}}
<div class="row g-3">
    {{-- Relation : équipement loué --}}
    <div class="col-md-6">
        <label for="equipement_id" class="form-label">Équipement <span class="text-danger">*</span></label>
        <select id="equipement_id" name="equipement_id" class="form-select @error('equipement_id') is-invalid @enderror">
            <option value="" data-prix="0">— Choisir un équipement —</option>
            @foreach ($equipements->groupBy('categorie.nom') as $categorie => $liste)
                <optgroup label="{{ $categorie }}">
                    @foreach ($liste as $equipement)
                        <option value="{{ $equipement->id }}" data-prix="{{ $equipement->prix_jour }}"
                                @selected(old('equipement_id', $reservation->equipement_id) == $equipement->id)>
                            {{ $equipement->nom }} — {{ $equipement->ville }} ({{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT/j){{ $equipement->disponible ? '' : ' — indisponible' }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('equipement_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Relation : locataire --}}
    <div class="col-md-6">
        <label for="user_id" class="form-label">Locataire <span class="text-danger">*</span></label>
        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
            <option value="">— Choisir un locataire —</option>
            @foreach ($locataires as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $reservation->user_id) == $user->id)>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
        @error('user_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
        <input type="date" id="date_debut" name="date_debut"
               value="{{ old('date_debut', $reservation->date_debut?->format('Y-m-d')) }}"
               class="form-control @error('date_debut') is-invalid @enderror">
        @error('date_debut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="date_fin" class="form-label">Date de fin <span class="text-danger">*</span></label>
        <input type="date" id="date_fin" name="date_fin"
               value="{{ old('date_fin', $reservation->date_fin?->format('Y-m-d')) }}"
               class="form-control @error('date_fin') is-invalid @enderror">
        @error('date_fin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
        <select id="statut" name="statut" class="form-select @error('statut') is-invalid @enderror">
            @foreach (\App\Models\Reservation::STATUTS as $valeur => [$libelle, $couleur])
                <option value="{{ $valeur }}" @selected(old('statut', $reservation->statut) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('statut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="message" class="form-label">Message du locataire</label>
        <textarea id="message" name="message" rows="3" maxlength="1000"
                  class="form-control @error('message') is-invalid @enderror">{{ old('message', $reservation->message) }}</textarea>
        @error('message')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <div class="callout callout-info h-100 mb-0">
            <h6 class="mb-2"><i class="bi bi-calculator me-1"></i>Estimation</h6>
            <div><span id="estimation-jours">0</span> jour(s)</div>
            <div class="fs-4 fw-bold"><span id="estimation-montant">0,00</span> DT</div>
            <small class="text-muted">Calculé automatiquement à l'enregistrement.</small>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const equipement = document.getElementById('equipement_id');
            const debut = document.getElementById('date_debut');
            const fin = document.getElementById('date_fin');

            const calculer = () => {
                const prix = parseFloat(equipement.selectedOptions[0]?.dataset.prix || 0);
                let jours = 0;
                if (debut.value && fin.value) {
                    jours = Math.round((new Date(fin.value) - new Date(debut.value)) / 86400000) + 1;
                }
                jours = Math.max(jours, 0);
                document.getElementById('estimation-jours').textContent = jours;
                document.getElementById('estimation-montant').textContent =
                    (jours * prix).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            };

            [equipement, debut, fin].forEach((champ) => champ.addEventListener('change', calculer));
            calculer();
        })();
    </script>
@endpush
