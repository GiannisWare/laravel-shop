<x-filament-panels::page>
    <div
        class="grid gap-6"
        x-data
        x-on:scan-finished.window="$nextTick(() => $refs.barcode?.focus())"
    >
        <x-filament::section
            heading="Scanner input"
            description="Keep this field focused, scan the product, and let the scanner send Enter."
            icon="phosphor-barcode"
        >
            <form wire:submit="scan" class="grid gap-4">
                {{ $this->getSchema('form') }}

                <div>
                    <x-filament::button
                        type="submit"
                        icon="phosphor-magnifying-glass"
                        wire:target="scan"
                    >
                        Scan barcode
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        @if ($product = $this->getScannedProduct())
            @php
                $statusLabel = match (true) {
                    $product->trashed() => 'Archived product',
                    $createdDraft => 'New unpublished draft',
                    $product->is_published => 'Published product',
                    default => 'Unpublished draft',
                };

                $statusColor = match (true) {
                    $product->trashed() => 'warning',
                    $createdDraft => 'info',
                    $product->is_published => 'success',
                    default => 'gray',
                };
            @endphp

            <x-filament::section
                :heading="$product->name ?: 'Uncatalogued product'"
                :description="$createdDraft ? 'A draft was created from this scan.' : 'This barcode already exists in the catalog.'"
                icon="phosphor-package"
            >
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Barcode</div>
                        <div class="mt-1 font-mono text-base text-gray-950 dark:text-white">{{ $product->barcode }}</div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</div>
                        <div class="mt-2">
                            <x-filament::badge :color="$statusColor">
                                {{ $statusLabel }}
                            </x-filament::badge>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Category</div>
                        <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $product->category?->name ?? 'Not assigned' }}</div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Brand</div>
                        <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $product->brand?->name ?? 'Not assigned' }}</div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-filament::button
                        tag="a"
                        :href="$this->getScannedProductEditUrl()"
                        icon="phosphor-pencil-simple"
                    >
                        {{ $createdDraft ? 'Complete product' : 'Open product' }}
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        icon="phosphor-barcode"
                        x-on:click="$refs.barcode?.focus()"
                    >
                        Scan another
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
