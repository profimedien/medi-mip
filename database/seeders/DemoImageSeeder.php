<?php

namespace Database\Seeders;

use App\Models\Gallery;
use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Hängt echte medi-Fotos (Neubau Fürth, Quelle: Kundenordner auf dem NAS) an die Demo-Inhalte.
 * Kann auf bestehenden Daten erneut laufen: php artisan db:seed --class=DemoImageSeeder
 */
class DemoImageSeeder extends Seeder
{
    private const POSTS = [
        'Willkommen im MIP' => 'herzlich_willkommen2.jpg',
        'Neue Hygieneregeln' => 'medi-fuerth-9.jpg',
        'Teambesprechung Physiotherapie' => 'medi-fuerth-12.jpg',
        'Fürth: Neue Geräte' => 'medi-fuerth-1.jpg',
        'Dienstrad-Leasing' => 'medi-fuerth-5.jpg',
        'Empfang: neue Telefonzeiten' => 'medi-fuerth-11.jpg',
    ];

    public function run(): void
    {
        $dir = database_path('seeders/demo-images');

        foreach (self::POSTS as $title => $file) {
            $post = Post::where('title', 'like', $title.'%')->first();
            if ($post && ! $post->hasMedia('image')) {
                $post->addMedia($dir.'/'.$file)->preservingOriginal()->toMediaCollection('image');
            }
        }

        $gallery = Gallery::firstOrCreate(['title' => 'Eröffnung Fürth'], [
            'description' => 'Einblicke in unser neues Reha- und Gesundheitszentrum in der Dr.-Mack-Straße.',
            'event_date' => now()->subMonths(2), 'audience_all' => true,
        ]);
        $gallery->update(['description' => 'Einblicke in unser neues Reha- und Gesundheitszentrum in der Dr.-Mack-Straße.']);
        $gallery->clearMediaCollection('photos');
        foreach ([1, 11, 5, 9, 12, 2, 8, 6, 7, 3, 10, 4] as $n) {
            $gallery->addMedia($dir."/medi-fuerth-$n.jpg")->preservingOriginal()->toMediaCollection('photos');
        }
    }
}
