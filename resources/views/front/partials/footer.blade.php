<footer class="footer-ss pt-5 pb-4">
    <div class="container px-4 px-lg-5">
        <div class="row gy-4">
            <div class="col-lg-5">
                <a href="{{ route('home') }}" class="d-flex align-items-center mb-3">
                    <img src="{{ asset('images/logo.svg') }}" alt="Logo SolarShare" height="40" class="me-2">
                    <span class="h5 mb-0 text-white">SolarShare</span>
                </a>
                <p class="small mb-0">
                    La plateforme de location et de partage entre particuliers de petits équipements
                    d'énergie renouvelable : panneaux solaires portables, batteries, petites éoliennes...
                    Consommons l'énergie de façon plus responsable, ensemble.
                </p>
            </div>
            <div class="col-6 col-lg-3 offset-lg-1">
                <h6 class="mb-3">Navigation</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="{{ route('home') }}">Accueil</a></li>
                    @guest
                        <li class="mb-2"><a href="{{ route('login') }}">Connexion</a></li>
                        <li class="mb-2"><a href="{{ route('register') }}">Créer un compte</a></li>
                    @endguest
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="mb-3">Contact</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Tunis, Tunisie</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2"></i>contact@solarshare.tn</li>
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i>+216 71 000 000</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between small">
            <span>&copy; {{ date('Y') }} SolarShare — Projet Applications Web Avancées</span>
            <span>Template : <a href="https://startbootstrap.com/theme/creative" target="_blank" rel="noopener">Creative by Start Bootstrap</a></span>
        </div>
    </div>
</footer>
