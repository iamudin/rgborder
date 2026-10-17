<?php

use App\Filament\Resources\ContentPages\ContentPageResource;
use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Models\ContentPage;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'ContentPageSeeder']);
});

test('public can view seeded about page', function () {
    $response = $this->get('/info/about');

    $response->assertStatus(200);
    $response->assertSee('Tentang RGB Arena');
    $response->assertSee('Teknologi Sensor Presisi Tinggi');
});

test('public can view rules and safety page', function () {
    $response = $this->get('/info/rules');

    $response->assertStatus(200);
    $response->assertSee('Aturan & Protokol Keselamatan');
    $response->assertSee('DILARANG KONTAK FISIK');
});

test('shortcut aliases redirect to full informational routes', function () {
    $this->get('/about')->assertRedirect('/info/about');
    $this->get('/rules')->assertRedirect('/info/rules');
});

test('unpublished page returns 404 for public visitors', function () {
    ContentPage::create([
        'title' => 'Draf Rahasia',
        'slug' => 'draf-rahasia',
        'content' => 'Konten belum siap dipublikasi',
        'is_published' => false,
    ]);

    $this->get('/info/draf-rahasia')->assertStatus(404);
});

test('admin can access content pages filament resource and list records', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(ContentPageResource::getUrl('index'))
        ->assertStatus(200);

    Livewire::actingAs($admin)
        ->test(ListContentPages::class)
        ->assertCanSeeTableRecords(ContentPage::all());
});

test('admin can create a new content page through filament', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(CreateContentPage::class)
        ->fillForm([
            'title' => 'Panduan Turnamen RGB 2026',
            'slug' => 'panduan-turnamen-2026',
            'category' => 'rules',
            'excerpt' => 'Aturan lengkap babak penyisihan turnamen RGB.',
            'content' => '<h2>Babak Penyisihan</h2><p>Semua tim wajib mendaftar 4 pemain lengkap.</p>',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('content_pages', [
        'slug' => 'panduan-turnamen-2026',
        'title' => 'Panduan Turnamen RGB 2026',
    ]);
});

test('admin can edit existing content page through filament', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $page = ContentPage::where('slug', 'about')->first();

    Livewire::actingAs($admin)
        ->test(EditContentPage::class, ['record' => $page->getKey()])
        ->fillForm([
            'title' => 'Tentang RGB Arena Terkini',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();
    expect($page->title)->toBe('Tentang RGB Arena Terkini');
});
