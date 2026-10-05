<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Event;
use App\Models\Form;
use App\Models\Gallery;
use App\Models\Post;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liefert hochgeladene Dateien nur an angemeldete Personen aus,
 * die den zugehörigen Inhalt sehen dürfen. Dateien liegen nicht öffentlich.
 */
class MediaController extends Controller
{
    public function show(Request $request, Media $media, ?string $conversion = null): StreamedResponse
    {
        $user = $request->user();
        $model = $media->model;

        $allowed = match (true) {
            $model === null => false,
            $user->isAdmin() => true,
            $model instanceof Post, $model instanceof Gallery, $model instanceof Form, $model instanceof Event => $model->isVisibleTo($user),
            $model instanceof ChatMessage => $model->group->members()->where('user_id', $user->id)->exists(),
            default => true, // Profile, Kleinanzeigen, Onboarding: für alle Angemeldeten
        };
        abort_unless($allowed, 403);

        $path = $conversion && $media->hasGeneratedConversion($conversion)
            ? $media->getPathRelativeToRoot($conversion)
            : $media->getPathRelativeToRoot();

        $disk = $media->disk;
        $inline = str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf';

        return \Storage::disk($disk)->response($path, $media->file_name, [
            'Cache-Control' => 'private, max-age=86400',
        ], $inline ? 'inline' : 'attachment');
    }
}
