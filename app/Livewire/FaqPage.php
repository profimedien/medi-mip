<?php

namespace App\Livewire;

use App\Models\FaqCategory;
use App\Models\FaqQuestion;
use App\Notifications\InboxNotification;
use App\Models\Setting;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('FAQ und Wissen')]
class FaqPage extends Component
{
    public string $search = '';

    public string $question = '';

    public bool $sent = false;

    public function ask(): void
    {
        $this->validate(['question' => ['required', 'string', 'min:10', 'max:2000']], [], ['question' => 'Frage']);
        $q = FaqQuestion::create(['user_id' => auth()->id(), 'question' => $this->question]);
        Notification::route('mail', Setting::emails('faq_recipients'))
            ->notify(new InboxNotification('Neue Frage für den FAQ-Bereich', auth()->user()->name.' fragt:', $this->question, url('/admin/faq-questions')));
        $this->reset('question');
        $this->sent = true;
    }

    public function render()
    {
        $term = trim($this->search);
        $categories = FaqCategory::orderBy('sort')->with(['faqs' => fn ($q) => $q->where('published', true)
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('question', 'like', "%$term%")->orWhere('answer', 'like', "%$term%")))])
            ->get()->filter(fn ($c) => $c->faqs->isNotEmpty());

        return view('livewire.faq', ['categories' => $categories]);
    }
}
