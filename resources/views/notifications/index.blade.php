@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.front')

@section('title', 'Notifications')

@section('content')
<section class="py-5 mt-5"><div class="container"><h1 class="h3">Mes notifications</h1><div class="list-group mt-3">
@forelse($notifications as $notification)<div class="list-group-item {{ $notification->read_at ? '' : 'list-group-item-primary' }}"><div class="d-flex justify-content-between gap-3"><div><span class="badge text-bg-secondary">{{ $notification->category }}</span><h2 class="h6 mt-2">{{ $notification->title }}</h2><p class="mb-1">{{ $notification->content }}</p></div>@if(!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">Marquer comme lue</button></form>@endif</div></div>@empty<p class="text-muted">Aucune notification.</p>@endforelse
</div>{{ $notifications->links() }}</div></section>
@endsection