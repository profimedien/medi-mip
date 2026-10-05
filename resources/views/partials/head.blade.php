<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0032ba">
<title>{{ $title ? $title.' · ' : '' }}MIP · Mitarbeiter-Informationsportal</title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/images/icon-192.png" type="image/png">
<link rel="apple-touch-icon" href="/images/icon-180.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="MIP">
@vite(['resources/css/app.css', 'resources/js/app.js'])
