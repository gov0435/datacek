<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-calendar-days" icon-color="primary">
        <x-slot name="heading">
            <div class="flex flex-col gap-0.5">
                <span class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $title }}
                </span>
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    Jadwal Resmi Pelaksanaan PPG 2026
                </span>
            </div>
        </x-slot>

        <x-slot name="afterHeader">
            <x-filament::button
                :href="route('filament.app.resources.data-potensis.index')"
                tag="a"
                color="primary"
                icon="heroicon-m-document-text"
                icon-position="after"
                size="sm"
            >
                Lihat Data Potensi
            </x-filament::button>
        </x-slot>

        <div class="flex flex-col divide-y divide-gray-100 dark:divide-gray-800 -mx-6 px-6">
            @foreach($jadwal as $item)
                <div class="flex items-center gap-4 py-3 group">
                    {{-- Icon Badge --}}
                    <div class="relative flex-shrink-0">
                        <div class="w-10 h-10 rounded-xl {{ $item['iconBg'] }} flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
                            <x-filament::icon
                                :icon="$item['icon']"
                                class="w-5 h-5 text-white"
                            />
                        </div>
                        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[9px] font-black flex items-center justify-center leading-none border border-white dark:border-gray-800">
                            {{ $item['no'] }}
                        </span>
                    </div>

                    {{-- Aktivitas --}}
                    <p class="flex-1 text-sm font-semibold {{ $item['textColor'] }} leading-snug">
                        {{ $item['aktivitas'] }}
                    </p>

                    {{-- Tanggal Badge --}}
                    <div class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                        {{ $item['badgeBg'] }} {{ $item['badgeText'] }} border {{ $item['badgeBorder'] }}">
                        <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5" />
                        <span class="whitespace-nowrap">{{ $item['tanggal'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <x-slot name="footer">
            <div class="flex items-start gap-3 text-xs text-gray-500 dark:text-gray-400 italic">
                <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" />
                <p>
                    *) jika terdapat perubahan jadwal akan disampaikan pada laman
                    <x-filament::link :href="$link" target="_blank" rel="noopener noreferrer" class="font-semibold not-italic">
                        {{ $link }}
                    </x-filament::link>
                </p>
            </div>
        </x-slot>
    </x-filament::section>
</x-filament-widgets::widget>
