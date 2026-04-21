<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AppLogin extends AdminLogin
{
    public function getSubHeading(): string|Htmlable
    {
        return new HtmlString(
            '<img src="'.asset('logo-kgtk.png').'" alt="Logo" style="height: 4rem; width: auto; margin: 0 auto 1rem;">'
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getGoogleLoginAction(),
        ];
    }
}
