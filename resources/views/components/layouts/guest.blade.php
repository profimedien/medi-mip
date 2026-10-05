<!DOCTYPE html>
<html lang="de">
<head>
    @include('partials.head', ['title' => $title ?? null])
</head>
<body class="grid min-h-screen place-items-center p-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <img src="/images/medi-logo.svg" alt="medi Gesundheitsgruppe" class="mx-auto h-14 w-auto">
            <p class="mt-3 text-sm font-medium text-medi-gray">Interaktionsportal</p>
        </div>
        <div class="card p-6">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
