@if (session('status') || $errors->any())
<div data-feedback tabindex="-1">
    @if (session('status')) <p class="notice" role="status">{{ session('status') }}</p> @endif
    @if ($errors->any()) <div class="notice error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
</div>
@endif
