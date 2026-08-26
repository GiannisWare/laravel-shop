<?php

use App\Enums\NavigationLocation;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\NavigationItems\Pages\EditNavigationItem;
use App\Models\NavigationItem;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('rejects hero slide paths containing backslashes', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(CreateHeroSlide::class)
        ->fillForm([
            'key' => 'unsafe-path',
            'title' => 'Unsafe path',
            'image_alt' => 'Paint supplies',
            'cta_url' => '/products\\hidden',
            'is_active' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['cta_url']);
});

it('clears a stale navigation parent when the location changes', function () {
    $parent = NavigationItem::factory()->create([
        'location' => NavigationLocation::Header,
    ]);
    $child = NavigationItem::factory()->for($parent, 'parent')->create([
        'location' => NavigationLocation::Header,
    ]);

    $component = Livewire::actingAs(User::factory()->admin()->create())
        ->test(EditNavigationItem::class, ['record' => $child->getKey()])
        ->assertSchemaStateSet([
            'location' => NavigationLocation::Header->value,
            'parent_id' => $parent->getKey(),
        ])
        ->set('data.location', NavigationLocation::Footer->value)
        ->assertSchemaStateSet([
            'location' => NavigationLocation::Footer->value,
            'parent_id' => null,
        ]);

    $parentSelect = $component->instance()->form->getComponent('parent_id');

    expect($parentSelect)
        ->toBeInstanceOf(Select::class)
        ->and($parentSelect->isLive())->toBeTrue();
});
