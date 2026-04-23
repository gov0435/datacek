<?php

namespace App\Filament\App\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InfoInstansi extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.app.widgets.info-instansi';

    protected function getViewData(): array
    {
        $user = Auth::user();

        $instansi = $user instanceof User
            ? ($user->instansi ?: '-')
            : '-';

        $nama = $user instanceof User
            ? $user->name
            : '-';

        $email = $user instanceof User
            ? $user->email
            : '-';

        $role = $user instanceof User
            ? strtoupper($user->role)
            : '-';

        return compact('instansi', 'nama', 'email', 'role');
    }
}
