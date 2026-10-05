<?php

namespace App\Livewire;

use App\Models\OnboardingSection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Onboarding')]
class Onboarding extends Component
{
    public function render()
    {
        return view('livewire.onboarding', ['sections' => OnboardingSection::with('media')->orderBy('sort')->get()]);
    }

    /** YouTube- oder Vimeo-Link in Einbettungsadresse umwandeln */
    public static function embedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?dnt=1';
        }

        return null;
    }
}
