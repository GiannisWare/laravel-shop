<?php

use App\Filament\Pages\ScanLookup;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('renders the scanner page for an admin under the scanning navigation group', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/scan-lookup')
        ->assertOk()
        ->assertSee('Scan products')
        ->assertSee('Scanner input');

    expect(ScanLookup::getNavigationGroup())->toBe('Scanning');
});

it('creates and displays an unpublished draft after a valid scan', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ScanLookup::class)
        ->set('data.barcode', '4006381333931')
        ->call('scan')
        ->assertHasNoErrors()
        ->assertSet('createdDraft', true)
        ->assertDispatched('scan-finished')
        ->assertSee('New unpublished draft')
        ->assertSee('4006381333931');

    $product = Product::query()->where('barcode', '4006381333931')->firstOrFail();

    expect($product->is_published)->toBeFalse()
        ->and($product->name)->toBeNull()
        ->and($product->slug)->toBeNull();
});

it('shows an existing product without creating another row', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create([
        'barcode' => '4006381333931',
        'name' => 'Existing paint',
    ]);

    Livewire::actingAs($admin)
        ->test(ScanLookup::class)
        ->set('data.barcode', '4006381333931')
        ->call('scan')
        ->assertHasNoErrors()
        ->assertSet('createdDraft', false)
        ->assertSee('Existing paint')
        ->assertSee('This barcode already exists in the catalog.');

    expect(Product::query()->count())->toBe(1)
        ->and(Product::query()->firstOrFail()->is($product))->toBeTrue();
});

it('rejects an invalid barcode without creating a product', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ScanLookup::class)
        ->set('data.barcode', '1234567890123')
        ->call('scan')
        ->assertHasErrors(['data.barcode']);

    expect(Product::query()->count())->toBe(0);
});
