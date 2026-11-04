<div class="flash-stack">
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @foreach (['success' => 'check-circle-fill', 'error' => 'exclamation-triangle-fill', 'warning' => 'exclamation-circle-fill', 'info' => 'info-circle-fill'] as $type => $icone)
        @if (session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show shadow" role="alert" data-autohide>
                <i class="bi bi-{{ $icone }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
            </div>
        @endif
    @endforeach
</div>
