{{-- Logo untuk HTML print & DomPDF: data URI (bukan public_path di src — browser tidak bisa baca path disk). --}}
@php
    $__logoFile = public_path('images/logo.png');
    $__logoSrc = is_file($__logoFile)
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($__logoFile))
        : asset('images/logo.png');
@endphp
<img src="{{ $__logoSrc }}" alt="77racing">
