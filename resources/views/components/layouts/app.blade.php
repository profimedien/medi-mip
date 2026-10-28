@php
    use App\Support\Navigation;
    $user = auth()->user();
    $badges = Navigation::badges($user);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    @include('partials.head', ['title' => $title ?? null])
</head>
<body class="min-h-screen" x-data="{ menu: false }">
    {{-- Kopfzeile --}}
    <header class="sticky top-0 z-30 border-b border-black/5 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                <img src="/images/medi-logo-kompakt.svg" alt="medi Gesundheitsgruppe" class="h-10 w-auto">
                <span class="border-l border-black/10 pl-3 leading-tight">
                    <span class="block text-lg font-bold tracking-tight text-medi-navy">MIP</span>
                    <span class="hidden text-[11px] font-medium text-medi-gray sm:block">Mitarbeiter-Informationsportal</span>
                </span>
            </a>
            <div class="ml-auto flex items-center gap-1">
                <a href="{{ route('notifications') }}" wire:navigate class="relative rounded-xl p-2 text-medi-gray hover:bg-medi-bg" aria-label="Mitteilungen">
                    <x-heroicon-o-bell class="size-6" />
                    @if ($badges['notifications'])
                        <span class="absolute right-1 top-1 grid min-w-4 place-items-center rounded-full bg-medi-red px-1 text-[10px] font-bold text-white">{{ $badges['notifications'] }}</span>
                    @endif
                </a>
                <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 rounded-xl p-1.5 hover:bg-medi-bg" aria-label="Mein Profil">
                    <x-avatar :user="$user" class="size-8 text-xs" />
                    <span class="hidden text-sm font-medium md:block">{{ $user->first_name }}</span>
                </a>
                @if ($user->isAdmin())
                    <a href="/admin" class="hidden rounded-xl px-3 py-2 text-sm font-medium text-medi-blue hover:bg-medi-bg md:block">Redaktion</a>
                @endif
            </div>
        </div>
    </header>

    <div class="mx-auto flex max-w-7xl gap-6 px-4 pb-28 pt-6 lg:pb-10">
        {{-- Seitenleiste Desktop --}}
        <nav class="sticky top-22 hidden h-fit w-60 shrink-0 lg:block" aria-label="Hauptnavigation">
            <ul class="space-y-0.5">
                @foreach (Navigation::ITEMS as [$route, $label, $icon, $badge])
                    @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)); @endphp
                    <li>
                        <a href="{{ route($route) }}" wire:navigate
                           @class(['flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                                   'bg-medi-blue text-white' => $active,
                                   'text-medi-gray hover:bg-white' => ! $active])>
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" />
                            <span>{{ $label }}</span>
                            @if ($badge && ($badges[$badge] ?? 0))
                                <span @class(['ml-auto rounded-full px-2 text-xs font-bold', 'bg-white text-medi-blue' => $active, 'bg-medi-red text-white' => ! $active])>{{ $badges[$badge] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-black/5 pt-4">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-medi-gray hover:bg-white">
                    <x-heroicon-o-arrow-left-on-rectangle class="size-5" /> Abmelden
                </button>
            </form>
        </nav>

        <main class="min-w-0 flex-1">
            @if (session('status'))
                <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{-- Tab-Leiste Mobil --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-black/5 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Navigation mobil">
        <ul class="grid grid-cols-5">
            @foreach (Navigation::ITEMS as [$route, $label, $icon, $badge])
                @continue(! in_array($route, Navigation::MOBILE))
                @php $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)); @endphp
                <li>
                    <a href="{{ route($route) }}" wire:navigate @class(['relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium', 'text-medi-blue' => $active, 'text-medi-gray' => ! $active])>
                        <x-dynamic-component :component="'heroicon-'.($active ? 's' : 'o').'-'.$icon" class="size-6" />
                        {{ $label }}
                        @if ($badge && ($badges[$badge] ?? 0))
                            <span class="absolute right-[22%] top-1 grid min-w-4 place-items-center rounded-full bg-medi-red px-1 text-[10px] font-bold text-white">{{ $badges[$badge] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
            <li>
                <button type="button" @click="menu = true" class="relative flex w-full flex-col items-center gap-0.5 py-2 text-[11px] font-medium text-medi-gray">
                    <x-heroicon-o-squares-2x2 class="size-6" /> Mehr
                    @if ($badges['forms'])
                        <span class="absolute right-[22%] top-1 grid min-w-4 place-items-center rounded-full bg-medi-red px-1 text-[10px] font-bold text-white">{{ $badges['forms'] }}</span>
                    @endif
                </button>
            </li>
        </ul>
    </nav>

    {{-- Menü Mobil --}}
    <div x-cloak x-show="menu" x-transition.opacity class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="menu = false"></div>
    <div x-cloak x-show="menu" x-transition class="fixed inset-x-0 bottom-0 z-50 max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[calc(env(safe-area-inset-bottom)+1.25rem)] lg:hidden">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-black/10"></div>
        <div class="grid grid-cols-3 gap-3">
            @foreach (Navigation::ITEMS as [$route, $label, $icon, $badge])
                <a href="{{ route($route) }}" wire:navigate @click="menu = false" class="relative flex flex-col items-center gap-1.5 rounded-2xl bg-medi-bg p-3 text-center text-xs font-medium text-medi-navy">
                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-7 text-medi-blue" />
                    {{ $label }}
                    @if ($badge && ($badges[$badge] ?? 0))
                        <span class="absolute right-2 top-2 rounded-full bg-medi-red px-1.5 text-[10px] font-bold text-white">{{ $badges[$badge] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
        <div class="mt-4 flex gap-3">
            @if ($user->isAdmin())
                <a href="/admin" class="btn-ghost flex-1">Redaktion</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button class="btn-ghost w-full">Abmelden</button>
            </form>
        </div>
    </div>
    {{-- Eigene Sicherheitsabfrage statt Browser-Dialog: $store.confirm.ask('Frage?', () => $wire.aktion(), 'Löschen') --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('confirm', {
                open: false, text: '', label: 'Löschen', callback: null,
                ask(text, callback, label = 'Löschen') { Object.assign(this, { text, callback, label, open: true }) },
                yes() { this.open = false; this.callback?.() },
            });
        });
    </script>
    <div x-cloak x-show="$store.confirm.open" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4"
         x-on:keydown.escape.window="$store.confirm.open = false" x-on:click.self="$store.confirm.open = false">
        <div class="card w-full max-w-xs text-center" role="alertdialog" aria-modal="true">
            <p class="font-medium text-medi-navy" x-text="$store.confirm.text"></p>
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button type="button" class="btn-ghost" x-on:click="$store.confirm.open = false">Abbrechen</button>
                <button type="button" class="btn bg-medi-red hover:bg-medi-red/90" x-on:click="$store.confirm.yes()" x-text="$store.confirm.label"></button>
            </div>
        </div>
    </div>
</body>
</html>
