<x-filament-widgets::widget>
    <div class="mip-hero">
        <h2>{{ $greeting }}, {{ $name }}</h2>
        <p>{{ now()->translatedFormat('l, j. F Y') }} · {{ $scope }}</p>
        <div class="mip-actions">
            @foreach ($actions as $a)
                <a href="{{ $a['url'] }}" class="mip-action" @if (! empty($a['blank'])) target="_blank" @endif>
                    <x-dynamic-component :component="'heroicon-o-'.$a['icon']" />
                    {{ $a['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
