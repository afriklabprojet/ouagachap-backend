<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $hour = now()->hour;

        $greeting = match (true) {
            $hour < 12 => '🌅 Bonjour',
            $hour < 18 => '☀️ Bon après-midi',
            default    => '🌙 Bonsoir',
        };

        $name = $user ? explode(' ', $user->name)[0] : '';

        return $greeting . ($name !== '' ? ', ' . $name : '') . ' ! — ' . now()->translatedFormat('l j F Y');
    }

    public function getSubheading(): ?string
    {
        return 'Voici un aperçu de votre activité en temps réel.';
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'xl'      => 3,
        ];
    }
}
