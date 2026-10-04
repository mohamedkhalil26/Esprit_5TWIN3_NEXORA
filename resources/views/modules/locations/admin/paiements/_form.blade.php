{{-- Formulaire partagé : création / modification d'un paiement --}}
<div class="row g-3">
    {{-- Relation : réservation concernée --}}
    <div class="col-12">
        <label for="reservation_id" class="form-label">Réservation <span class="text-danger">*</span></label>
        <select id="reservation_id" name="reservation_id" class="form-select @error('reservation_id') is-invalid @enderror">
            <option value="">— Choisir une réservation —</option>
            @foreach ($reservations as $reservation)
                <option value="{{ $reservation->id }}" @selected(old('reservation_id', $paiement->reservation_id) == $reservation->id)>
                    N°{{ $reservation->id }} — {{ $reservation->equipement->nom }} — {{ $reservation->locataire->name }}
                    ({{ $reservation->periode }}) · reste {{ number_format($reservation->reste_a_payer, 2, ',', ' ') }} DT
                </option>
            @endforeach
        </select>
        @error('reservation_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="montant" class="form-label">Montant <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input type="number" step="0.01" min="0.01" id="montant" name="montant"
                   value="{{ old('montant', $paiement->montant) }}"
                   class="form-control @error('montant') is-invalid @enderror">
            <span class="input-group-text">DT</span>
            @error('montant')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label for="date_paiement" class="form-label">Date du paiement <span class="text-danger">*</span></label>
        <input type="datetime-local" id="date_paiement" name="date_paiement"
               value="{{ old('date_paiement', $paiement->date_paiement?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('date_paiement') is-invalid @enderror">
        @error('date_paiement')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="reference" class="form-label">Référence</label>
        <input type="text" id="reference" name="reference" value="{{ old('reference', $paiement->reference) }}"
               class="form-control text-uppercase @error('reference') is-invalid @enderror" placeholder="Générée automatiquement">
        @error('reference')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label class="form-label d-block">Méthode de paiement <span class="text-danger">*</span></label>
        <div class="btn-group flex-wrap" role="group">
            @foreach (\App\Models\Paiement::METHODES as $valeur => [$libelle, $icone])
                <input type="radio" class="btn-check" name="methode" id="methode_{{ $valeur }}" value="{{ $valeur }}"
                       @checked(old('methode', $paiement->methode) === $valeur)>
                <label class="btn btn-outline-primary" for="methode_{{ $valeur }}"><i class="bi bi-{{ $icone }} me-1"></i>{{ $libelle }}</label>
            @endforeach
        </div>
        @error('methode')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="statut" class="form-label">Statut <span class="text-danger">*</span></label>
        <select id="statut" name="statut" class="form-select @error('statut') is-invalid @enderror">
            @foreach (\App\Models\Paiement::STATUTS as $valeur => [$libelle, $couleur])
                <option value="{{ $valeur }}" @selected(old('statut', $paiement->statut) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('statut')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
