<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Schemas\Schema;

class AppLogin extends AdminLogin
{
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
