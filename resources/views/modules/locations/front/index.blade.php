@extends('layouts.front')

@section('title', 'Mes réservations')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Mes réservations',
        'subtitle' => 'Suivez vos locations en cours et passées',
        'breadcrumb' => ['Mes réservations' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            @if ($reservations->isEmpty())
                <x-empty-state icon="calendar" message="Vous n'avez encore effectué aucune réservation.">
                    @if (Route::has('front.equipements.index'))
                        <a href="{{ route('front.equipements.index') }}" class="btn btn-primary">Parcourir le catalogue</a>
                    @endif
                </x-empty-state>
            @else
                <div class="card form-card">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Équipement</th>
                                <th>Période</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th class="text-end"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($reservations as $reservation)
                                <tr>
                                    <td>
                                        <i class="bi {{ $reservation->equipement->categorie->icone }} text-sun me-2"></i>
                                        {{ $reservation->equipement->nom }}
                                    </td>
                                    <td>{{ $reservation->periode }} <span class="text-muted small">({{ $reservation->nb_jours }} j)</span></td>
                                    <td class="fw-semibold">{{ number_format($reservation->montant_total, 2, ',', ' ') }} DT</td>
                                    <td><span class="badge text-bg-{{ $reservation->statut_couleur }}">{{ $reservation->statut_label }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('front.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary">Détail</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $reservations->links() }}</div>
            @endif
        </div>
    </section>
@endsection
