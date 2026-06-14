<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;

class JadwalPPGWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    /**
     * @var string|int|array<string, string|int|null>
     */
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.app.widgets.jadwal-ppg-widget';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'title' => 'Linimasa Proses Penjaringan PPG',
            'jadwal' => [
                [
                    'no' => 1,
                    'aktivitas' => 'Rilis Program Penjaringan Guru Tertentu Belum Bersertifikat Pendidik',
                    'tanggal' => '1 April 2026',
                    'icon' => 'heroicon-o-rocket-launch',
                    'iconBg' => 'bg-sky-600',
                    'textColor' => 'text-sky-700 dark:text-sky-400',
                    'badgeBg' => 'bg-sky-50 dark:bg-sky-500/10',
                    'badgeText' => 'text-sky-700 dark:text-sky-400',
                    'badgeBorder' => 'border-sky-100 dark:border-sky-500/20',
                    'cardBorder' => 'hover:border-sky-200 dark:hover:border-sky-800',
                ],
                [
                    'no' => 2,
                    'aktivitas' => 'Konfirmasi Keikutsertaan Program PPG bagi Guru Tertentu secara mandiri oleh guru',
                    'tanggal' => '1 - 30 April 2026',
                    'icon' => 'heroicon-o-check-circle',
                    'iconBg' => 'bg-emerald-600',
                    'textColor' => 'text-emerald-700 dark:text-emerald-400',
                    'badgeBg' => 'bg-emerald-50 dark:bg-emerald-500/10',
                    'badgeText' => 'text-emerald-700 dark:text-emerald-400',
                    'badgeBorder' => 'border-emerald-100 dark:border-emerald-500/20',
                    'cardBorder' => 'hover:border-emerald-200 dark:hover:border-emerald-800',
                ],
                [
                    'no' => 3,
                    'aktivitas' => 'Pendaftaran PPG bagi Guru Tertentu Tahun 2026',
                    'tanggal' => '1 April - 30 Mei 2026 [Perpanjangan 15 Juli]',
                    'icon' => 'heroicon-o-pencil-square',
                    'iconBg' => 'bg-blue-600',
                    'textColor' => 'text-blue-700 dark:text-blue-400',
                    'badgeBg' => 'bg-blue-50 dark:bg-blue-500/10',
                    'badgeText' => 'text-blue-700 dark:text-blue-400',
                    'badgeBorder' => 'border-blue-100 dark:border-blue-500/20',
                    'cardBorder' => 'hover:border-blue-200 dark:hover:border-blue-800',
                ],
                [
                    'no' => 4,
                    'aktivitas' => 'Verval Lanjutan oleh Dinas Pendidikan (Perpanjangan ke-3)',
                    'tanggal' => '1 Mei - 10 Juli 2026',
                    'icon' => 'heroicon-o-shield-check',
                    'iconBg' => 'bg-amber-500',
                    'textColor' => 'text-amber-700 dark:text-amber-400',
                    'badgeBg' => 'bg-amber-50 dark:bg-amber-500/10',
                    'badgeText' => 'text-amber-700 dark:text-amber-400',
                    'badgeBorder' => 'border-amber-100 dark:border-amber-500/20',
                    'cardBorder' => 'hover:border-amber-200 dark:hover:border-amber-800',
                ],
                [
                    'no' => 5,
                    'aktivitas' => 'Pengumuman hasil seleksi administrasi PPG bagi Guru Tertentu tahun 2026 (Periode 1)',
                    'tanggal' => '10 Juni 2026',
                    'icon' => 'heroicon-o-megaphone',
                    'iconBg' => 'bg-rose-600',
                    'textColor' => 'text-rose-700 dark:text-rose-400',
                    'badgeBg' => 'bg-rose-50 dark:bg-rose-500/10',
                    'badgeText' => 'text-rose-700 dark:text-rose-400',
                    'badgeBorder' => 'border-rose-100 dark:border-rose-500/20',
                    'cardBorder' => 'hover:border-rose-200 dark:hover:border-rose-800',
                ],
                [
                    'no' => 6,
                    'aktivitas' => 'Pemanggilan peserta (konfirmasi kesediaan)',
                    'tanggal' => '15 Juni 2026',
                    'icon' => 'heroicon-o-envelope',
                    'iconBg' => 'bg-violet-600',
                    'textColor' => 'text-violet-700 dark:text-violet-400',
                    'badgeBg' => 'bg-violet-50 dark:bg-violet-500/10',
                    'badgeText' => 'text-violet-700 dark:text-violet-400',
                    'badgeBorder' => 'border-violet-100 dark:border-violet-500/20',
                    'cardBorder' => 'hover:border-violet-200 dark:hover:border-violet-800',
                ],
                [
                    'no' => 7,
                    'aktivitas' => 'Pelaksanaan PPG bagi Guru Tertentu Tahun 2026 Tahap 2',
                    'tanggal' => '22 Juni 2026',
                    'icon' => 'heroicon-o-academic-cap',
                    'iconBg' => 'bg-teal-600',
                    'textColor' => 'text-teal-700 dark:text-teal-400',
                    'badgeBg' => 'bg-teal-50 dark:bg-teal-500/10',
                    'badgeText' => 'text-teal-700 dark:text-teal-400',
                    'badgeBorder' => 'border-teal-100 dark:border-teal-500/20',
                    'cardBorder' => 'hover:border-teal-200 dark:hover:border-teal-800',
                ],
            ],
            'link' => 'https://ppg.kemendikdasmen.go.id/',
        ];
    }
}
