<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold">Onboarding</h1>
        <p class="text-sm">Wie funktioniert was bei medi? Abläufe, Formulare und kurze Erklärvideos.</p>
    </div>
    <div class="space-y-3">
        @foreach ($sections as $section)
            <details class="card group" @if ($loop->first) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-medi-blue/10 text-medi-blue">
                        <x-dynamic-component :component="'heroicon-o-'.($section->icon ?: 'document-text')" class="size-5" />
                    </span>
                    <span class="font-bold text-medi-navy">{{ $section->title }}</span>
                    <x-heroicon-o-chevron-down class="ml-auto size-5 transition group-open:rotate-180" />
                </summary>
                <div class="mt-4 space-y-4">
                    <div class="prose-medi text-sm">{!! $section->body !!}</div>
                    @if ($embed = \App\Livewire\Onboarding::embedUrl($section->video_url))
                        <div class="aspect-video overflow-hidden rounded-xl bg-black">
                            <iframe src="{{ $embed }}" class="size-full" allow="fullscreen; picture-in-picture" loading="lazy" title="{{ $section->title }}"></iframe>
                        </div>
                    @endif
                    @if ($section->getMedia('documents')->isNotEmpty())
                        <ul class="space-y-1.5">
                            @foreach ($section->getMedia('documents') as $doc)
                                <li><a href="{{ route('media.show', $doc) }}" target="_blank" class="flex items-center gap-2 text-sm text-medi-sky hover:underline"><x-heroicon-o-document-arrow-down class="size-4" /> {{ $doc->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
</div>
