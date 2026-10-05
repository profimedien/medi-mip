<?php

namespace Tests\Feature;

use App\Models\Post;
use Tests\TestCase;

class AudienceTest extends TestCase
{
    private function makePost(array $audience): Post
    {
        return Post::create(['title' => 'Test', 'published_at' => now()->subMinute()] + $audience);
    }

    public function test_beitrag_fuer_alle_sehen_alle(): void
    {
        $post = $this->makePost(['audience_all' => true]);
        $this->assertTrue($post->isVisibleTo($this->person('Roth', ['Rezeption'])));
    }

    public function test_standort_und_abteilung_muessen_beide_passen(): void
    {
        $post = $this->makePost(['audience_all' => false,
            'audience_locations' => [$this->location('Fürth')->id],
            'audience_departments' => [$this->department('Physiotherapie')->id]]);

        $this->assertTrue($post->isVisibleTo($this->person('Fürth', ['Physiotherapie'])));
        $this->assertFalse($post->isVisibleTo($this->person('Fürth', ['Rezeption'])));
        $this->assertFalse($post->isVisibleTo($this->person('Roth', ['Physiotherapie'])));
    }

    public function test_nur_abteilung_gilt_standortuebergreifend(): void
    {
        $post = $this->makePost(['audience_all' => false, 'audience_departments' => [$this->department('Logopädie')->id]]);

        $this->assertTrue($post->isVisibleTo($this->person('Fürth', ['Logopädie'])));
        $this->assertTrue($post->isVisibleTo($this->person('Neuendettelsau', ['Logopädie', 'Ergotherapie'])));
        $this->assertFalse($post->isVisibleTo($this->person('Fürth', ['Ergotherapie'])));
    }

    public function test_einzelperson_sieht_inhalt_immer(): void
    {
        $person = $this->person('Roth', ['Rezeption']);
        $post = $this->makePost(['audience_all' => false, 'audience_locations' => [$this->location('Fürth')->id], 'audience_users' => [(string) $person->id]]);

        $this->assertTrue($post->isVisibleTo($person));
        $this->assertSame([$person->id], $post->fresh()->audience_users, 'IDs werden als Integer gespeichert');
    }

    public function test_zielgruppe_fuer_benachrichtigungen_stimmt_mit_sichtbarkeit_ueberein(): void
    {
        $a = $this->person('Fürth', ['Physiotherapie']);
        $b = $this->person('Fürth', ['Rezeption']);
        $c = $this->person('Roth', ['Physiotherapie']);
        $post = $this->makePost(['audience_all' => false,
            'audience_locations' => [$this->location('Fürth')->id],
            'audience_departments' => [$this->department('Physiotherapie')->id]]);

        $ids = $post->audienceMembers()->pluck('id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertNotContains($b->id, $ids);
        $this->assertNotContains($c->id, $ids);
    }
}
