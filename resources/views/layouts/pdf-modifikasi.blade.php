<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('pdf_title', 'Dokumen')</title>
    @include('layouts.partials.pdf-modifikasi-styles')
    @stack('pdf_head')
</head>
<body class="@yield('body_class')">
@hasSection('print_bar')
    @yield('print_bar')
@endif

<div class="doc">
    <div class="doc-accent"></div>
    <div class="doc-body">
        @yield('content')
    </div>
</div>
@stack('pdf_scripts')
</body>
</html>
