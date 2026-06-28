<div class="min-h-screen">
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">LPTK PPG Guru Tertentu Tahap 2</h1>
                <p class="text-sm text-gray-500 mt-0.5">Update: 29 Juni 2026 06:58</p>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="relative flex flex-col justify-between p-5 bg-white border border-gray-200 rounded-2xl shadow-sm">
                    <div>
                        <label for="lptk-search" class="block text-sm font-semibold text-gray-500">Pencarian LPTK</label>
                        <div class="relative mt-2">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input
                                wire:model.live.debounce.300ms="search"
                                type="search"
                                id="lptk-search"
                                class="block w-full py-2 pl-10 pr-4 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 placeholder-gray-400 transition-all"
                                placeholder="Cari nama LPTK, website, grup..."
                            />
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden p-5 bg-gradient-to-br from-sky-500/10 to-indigo-500/10 border border-sky-500/20 rounded-2xl shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500">Total LPTK</p>
                            <h3 class="mt-1 text-3xl font-extrabold text-sky-700">{{ $totalLptk }}</h3>
                        </div>
                        <div class="p-3 text-sky-600 bg-sky-500/20 rounded-xl">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14v7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden p-5 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 rounded-2xl shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500">Total Calon Peserta</p>
                            <h3 class="mt-1 text-3xl font-extrabold text-emerald-700">{{ $totalCalon }}</h3>
                        </div>
                        <div class="p-3 text-emerald-600 bg-emerald-500/20 rounded-xl">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50/75">
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase text-center w-16">No</th>
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase">Nama LPTK</th>
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase text-center w-36">Calon Peserta</th>
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase w-44">Website</th>
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase w-44">Lapor Diri</th>
                                <th class="px-6 py-4 text-xs font-bold tracking-wider text-gray-500 uppercase w-44">Grup WA/TG</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($lptkList as $lptk)
                                <tr class="transition-colors hover:bg-gray-50/50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-400 text-center">{{ $lptk['no'] }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 p-2 bg-gray-100/50 rounded-lg text-gray-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-800">{{ $lptk['nama_lptk'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700">{{ $lptk['jumlah_calon_peserta'] }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($lptk['website_lptk'])
                                            <a href="{{ $lptk['website_lptk'] }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-sky-600 hover:text-sky-500">
                                                <span>Kunjungi</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($lptk['link_lapor_diri'])
                                            <a href="{{ $lptk['link_lapor_diri'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 text-amber-700 hover:bg-amber-500/20 text-xs font-semibold rounded-lg transition-colors">
                                                <span>Lapor Diri</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($lptk['link_grup'])
                                            @if (is_array($lptk['link_grup']))
                                                <div class="flex flex-col gap-1.5">
                                                    @foreach ($lptk['link_grup'] as $grup)
                                                        @php
                                                            $isTelegram = str_contains($grup['url'], 't.me');
                                                            $badgeClass = $isTelegram
                                                                ? 'bg-blue-500/10 text-blue-700 hover:bg-blue-500/20'
                                                                : 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/20';
                                                        @endphp
                                                        <a href="{{ $grup['url'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 {{ $badgeClass }} text-xs font-semibold rounded-lg transition-colors w-max">
                                                            <span>{{ $grup['nama'] }}</span>
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                            </svg>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                @php
                                                    $isTelegram = str_contains($lptk['link_grup'], 't.me');
                                                    $badgeClass = $isTelegram
                                                        ? 'bg-blue-500/10 text-blue-700 hover:bg-blue-500/20'
                                                        : 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/20';
                                                @endphp
                                                <a href="{{ $lptk['link_grup'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 {{ $badgeClass }} text-xs font-semibold rounded-lg transition-colors">
                                                    <span>{{ $isTelegram ? 'Telegram' : 'WhatsApp' }}</span>
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        @elseif (!empty($lptk['catatan']))
                                            <span class="text-xs text-gray-500 italic">{{ $lptk['catatan'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-500">Tidak ada LPTK yang cocok dengan pencarian Anda</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="text-center text-xs text-gray-400 py-4">
                Data LPTK PPG Guru Tertentu Tahap 2 Tahun 2026
            </footer>
        </div>
    </main>
</div>
