@php($derniersEquipements = \App\Models\Equipement::with('categorie')->disponibles()->latest()->take(3)->get())

@if ($derniersEquipements->isNotEmpty())
    <section class="page-section bg-cream" id="equipements">
        <div class="container px-4 px-lg-5">
            <h2 class="text-center mt-0">Derniers équipements proposés</h2>
            <hr class="divider">
            <div class="row g-4 mt-2">
                @foreach ($derniersEquipements as $equipement)
                    <div class="col-md-4">
                        @include('modules.equipements.front._card')
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('front.equipements.index') }}" class="btn btn-primary btn-xl">Voir tout le catalogue</a>
            </div>
        </div>
    </section>
@endif
