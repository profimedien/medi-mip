<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="mr-auto text-2xl font-bold">FAQ und Wissen</h1>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Frage suchen" class="input w-full sm:w-64">
    </div>

    @forelse ($categories as $category)
        <section class="card">
            <h2 class="mb-2 font-bold">{{ $category->name }}</h2>
            <div class="divide-y divide-black/5">
                @foreach ($category->faqs as $faq)
                    <details class="group py-2.5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-medium text-medi-navy">
                            {{ $faq->question }}
                            <x-heroicon-o-chevron-down class="size-4 shrink-0 transition group-open:rotate-180" />
                        </summary>
                        <div class="prose-medi mt-2 text-sm">{!! $faq->answer !!}</div>
                        @if ($faq->getMedia('images')->isNotEmpty())
                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($faq->getMedia('images') as $img)
                                    <a href="{{ route('media.show', $img) }}" target="_blank"><img src="{{ route('media.show', [$img, 'thumb']) }}" alt="" loading="lazy" class="aspect-video w-full rounded-xl object-cover"></a>
                                @endforeach
                            </div>
                        @endif
                        @if ($embed = \App\Livewire\Onboarding::embedUrl($faq->video_url))
                            <div class="mt-3 aspect-video overflow-hidden rounded-xl bg-black">
                                <iframe src="{{ $embed }}" class="size-full" allow="fullscreen; picture-in-picture" loading="lazy" title="{{ $faq->question }}"></iframe>
                            </div>
                        @endif
                    </details>
                @endforeach
            </div>
        </section>
    @empty
        <p class="card text-sm">Keine passenden Einträge gefunden.</p>
    @endforelse

    <section class="card">
        <h2 class="mb-1 font-bold">Ihre Frage fehlt?</h2>
        <p class="mb-3 text-sm">Reichen Sie sie ein. Die Redaktion beantwortet sie und nimmt sie bei Bedarf in die FAQ auf.</p>
        @if ($sent)
            <p class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800">Danke, Ihre Frage ist angekommen.</p>
        @endif
        <form wire:submit="ask" class="space-y-3">
            <textarea wire:model="question" rows="3" class="input" placeholder="Ihre Frage"></textarea>
            @error('question')<p class="text-sm text-medi-red">{{ $message }}</p>@enderror
            <button class="btn">Frage einreichen</button>
        </form>
    </section>
</div>
