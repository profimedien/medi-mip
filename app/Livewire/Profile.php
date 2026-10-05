<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Mein Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public array $profile = [];

    public array $notify = [];

    public $avatar = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $u = auth()->user();
        $this->profile = $u->only(['profile_visible', 'show_birthday', 'show_anniversary', 'phone_extension', 'work_email', 'qualifications', 'about']);
        foreach (['mail_news', 'mail_forms', 'mail_chat_digest'] as $key) {
            $this->notify[$key] = (bool) $u->notificationSetting($key);
        }
    }

    public function saveProfile(): void
    {
        $data = $this->validate([
            'profile.profile_visible' => ['boolean'],
            'profile.show_birthday' => ['boolean'],
            'profile.show_anniversary' => ['boolean'],
            'profile.phone_extension' => ['nullable', 'string', 'max:30'],
            'profile.work_email' => ['nullable', 'email', 'max:255'],
            'profile.qualifications' => ['nullable', 'string', 'max:1000'],
            'profile.about' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:8192'],
        ], [], ['profile.work_email' => 'E-Mail', 'avatar' => 'Foto'])['profile'];

        $user = auth()->user();
        $user->update($data);
        if ($this->avatar) {
            $user->addMedia($this->avatar->getRealPath())->usingFileName('avatar.'.$this->avatar->extension())->toMediaCollection('avatar');
            $this->avatar = null;
        }
        session()->flash('profile', 'Profil gespeichert.');
    }

    public function removeAvatar(): void
    {
        auth()->user()->clearMediaCollection('avatar');
    }

    public function saveNotifications(): void
    {
        $user = auth()->user();
        $user->update(['notification_settings' => array_merge($user->notification_settings ?? [], array_map('boolval', $this->notify))]);
        session()->flash('notify', 'Einstellungen gespeichert.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['current_password' => 'aktuelles Passwort', 'password' => 'neues Passwort']);
        auth()->user()->update(['password' => $this->password]);
        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('password', 'Passwort geändert.');
    }

    public function render()
    {
        return view('livewire.profile', [
            'user' => auth()->user()->load('location', 'departments'),
            'vapid' => config('webpush.vapid.public_key'),
        ]);
    }
}
