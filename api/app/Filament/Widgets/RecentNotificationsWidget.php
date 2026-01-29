<?php

namespace App\Filament\Widgets;

use App\Models\InAppNotification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentNotificationsWidget extends BaseWidget
{
    protected static ?string $heading = '🔔 Notifications Récentes';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InAppNotification::query()
                    ->with('user')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Heure')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Destinataire')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'order_status' => '📦',
                        'payment' => '💳',
                        'promo' => '🎁',
                        'system' => '🔔',
                        'wallet' => '💰',
                        default => '📌',
                    })
                    ->color(fn ($state) => match($state) {
                        'order_status' => 'info',
                        'payment' => 'success',
                        'promo' => 'purple',
                        'wallet' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Notification')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Lu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.notifications.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false)
            ->poll('30s');
    }
}
