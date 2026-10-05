@php($derniersArticles = \App\Models\Article::publies()->with('auteur')->latest('publie_le')->take(3)->get())

@if ($derniersArticles->isNotEmpty())
    <section class="page-section bg-cream" id="blog">
        <div class="container px-4 px-lg-5">
            <h2 class="text-center mt-0">Conseils & actualités</h2>
            <hr class="divider">
            <div class="row g-4 mt-2">
                @foreach ($derniersArticles as $article)
                    <div class="col-md-4">
                        @include('modules.blog.front._card')
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                <a href="{{ route('front.blog.index') }}" class="btn btn-primary btn-xl">Lire le blog</a>
            </div>
        </div>
    </section>
@endif
