<?php

namespace App\Support;

use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\Form;
use App\Models\User;

class Navigation
{
    /** Hauptbereiche: [Route, Titel, Heroicon, Badge-Schlüssel] */
    public const ITEMS = [
        ['home', 'Start', 'home', null],
        ['news.index', 'News', 'newspaper', null],
        ['chat', 'Medigram', 'chat-bubble-left-right', 'chat'],
        ['calendar', 'Kalender', 'calendar-days', null],
        ['forms.index', 'Rückmeldungen', 'clipboard-document-check', 'forms'],
        ['directory.index', 'Verzeichnis', 'users', null],
        ['contacts', 'Ansprechpartner', 'phone', null],
        ['faq', 'FAQ und Wissen', 'question-mark-circle', null],
        ['onboarding', 'Onboarding', 'academic-cap', null],
        ['participation', 'Mitmachen', 'light-bulb', null],
        ['classifieds', 'Kleinanzeigen', 'tag', null],
        ['galleries.index', 'Galerien', 'photo', null],
        ['orgchart', 'Organigramm', 'rectangle-group', null],
    ];

    public const MOBILE = ['home', 'news.index', 'chat', 'calendar'];

    public static function badges(User $user): array
    {
        return once(function () use ($user) {
            $memberships = ChatMember::where('user_id', $user->id)->get(['chat_group_id', 'last_read_message_id']);
            $chat = 0;
            foreach ($memberships as $m) {
                $chat += \App\Models\ChatMessage::where('chat_group_id', $m->chat_group_id)
                    ->where('id', '>', $m->last_read_message_id)
                    ->whereNull('deleted_at')
                    ->where('user_id', '!=', $user->id)
                    ->count();
            }

            $forms = Form::published()->visibleTo($user)
                ->where(fn ($q) => $q->whereNull('deadline')->orWhere('deadline', '>', now()))
                ->whereDoesntHave('responses', fn ($q) => $q->where('user_id', $user->id))
                ->count();

            return [
                'chat' => $chat,
                'forms' => $forms,
                'notifications' => $user->unreadNotifications()->count(),
            ];
        });
    }
}
