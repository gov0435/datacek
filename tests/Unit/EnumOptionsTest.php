<?php

use App\Enums\Jenjang;
use App\Enums\KabKota;
use App\Enums\StatusDaftar;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('jenjang enum implements has label', function () {
    expect(Jenjang::tryFrom('PAUD'))->toBeInstanceOf(HasLabel::class);
    expect(Jenjang::tryFrom('PAUD')?->getLabel())->toBe('PAUD');
});

test('jenjang enum implements has color', function () {
    expect(Jenjang::tryFrom('PAUD'))->toBeInstanceOf(HasColor::class);
    expect(Jenjang::tryFrom('PAUD')?->getColor())->toBe('success');
    expect(Jenjang::tryFrom('SD')?->getColor())->toBe('info');
});

test('kabkota enum implements has label', function () {
    expect(KabKota::tryFrom('Kab. Gorontalo'))->toBeInstanceOf(HasLabel::class);
    expect(KabKota::tryFrom('Kab. Gorontalo')?->getLabel())->toBe('Kab. Gorontalo');
});

test('status daftar enum implements has label and color', function () {
    expect(StatusDaftar::tryFrom('Sudah Daftar'))->toBeInstanceOf(HasLabel::class);
    expect(StatusDaftar::tryFrom('Sudah Daftar')?->getLabel())->toBe('Sudah Daftar');
    expect(StatusDaftar::tryFrom('Sudah Daftar')?->getColor())->toBe('success');
    expect(StatusDaftar::tryFrom('Belum Daftar')?->getColor())->toBe('warning');
});
