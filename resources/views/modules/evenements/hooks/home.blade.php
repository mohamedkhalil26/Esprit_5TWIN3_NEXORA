@php($prochainsEvenements = \App\Models\Evenement::avecNbInscrits()->aVenir()->orderBy('date_debut')->take(4)->get())

@if ($prochainsEvenements->isNotEmpty())
    <section class="page-section" id="evenements">
        <div class="container px-4 px-lg-5">
            <h2 class="text-center mt-0">Prochains ateliers & événements</h2>
            <hr class="divider">
            <div class="row g-4 mt-2">
                @foreach ($prochainsEvenements as $evenement)
                    <div class="col-md-6">
                        @include('modules.evenements.front._card')
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('front.evenements.index') }}" class="btn btn-outline-primary btn-xl">Tous les événements</a>
            </div>
        </div>
    </section>
@endif
