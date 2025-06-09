<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $modelLabel = 'سفر';
    protected static ?string $pluralModelLabel = 'سفرها';

    public static function form(Form $form): Form
    {
        return $form->schema([]); // فرم غیرفعال است
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('مسافر')->searchable(),
                BadgeColumn::make('status')->label('وضعیت')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'active',
                        'danger' => 'emergency',
                    ]),
                TextColumn::make('shortUrl.short_code')
                    ->label('لینک کوتاه')
                    ->url(fn (Trip $record) => $record->shortUrl ? route('shortlink.redirect', ['short_code' => $record->shortUrl->short_code]) : null, true)
                    ->formatStateUsing(fn ($state) => $state ? "مشاهده لینک" : "ندارد"),
                ImageColumn::make('plate_photo_path')->label('عکس پلاک')->disk('public'),
                ImageColumn::make('emergency_photo_path')->label('عکس اضطراری')->disk('public'),
                TextColumn::make('created_at')->label('زمان شروع')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    // این جای صحیح متد infolist است
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\TextEntry::make('user.name')->label('مسافر'),
                Components\TextEntry::make('status')->label('وضعیت')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'completed' => 'success',
                        'emergency' => 'danger',
                        default => 'gray',
                    }),
                Components\ImageEntry::make('plate_photo_path')->label('عکس پلاک')->disk('public'),
                Components\ImageEntry::make('emergency_photo_path')->label('عکس اضطراری')->disk('public'),
                Components\TextEntry::make('created_at')->label('زمان شروع')->dateTime(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GuardiansRelationManager::class,
            RelationManagers\LocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'view' => Pages\ViewTrip::route('/{record}'), // این صفحه برای نمایش جزئیات لازم است
        ];
    }
}
