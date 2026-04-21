<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;

class AdminLogin extends BaseLogin
{
    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            $this->getGoogleLoginAction(),
        ];
    }

    protected function getGoogleLoginAction(): Action
    {
        return Action::make('googleLogin')
            ->label('Login dengan Google')
            ->color('gray')
            ->action(fn () => $this->redirect(route('auth.social.redirect', ['provider' => 'google'])));
    }
}
