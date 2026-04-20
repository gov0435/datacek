<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="fi-filament-info-widget-main">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Instansi
                </p>

                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                    {{ $instansi }}
                </p>
            </div>
        </div>

        <div class="fi-filament-info-widget-links">
            <x-filament::link color="gray" tag="span">
                {{ $nama }}
            </x-filament::link>

            <x-filament::link color="gray" tag="span">
                {{ $email }}
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
