<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms\Form;
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
        // Form is disabled because admins only view trips, not edit them.
        return $form->schema([]);
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
                    ->formatStateUsing(fn ($state) => $state ? url("/r/{$state}") : 'ندارد')
                    ->url(fn ($state) => $state ? url("/r/{$state}") : null, true), // Opens in a new tab
                TextColumn::make('created_at')->label('زمان شروع')->dateTime('Y-m-d H:i')->sortable(),
                ImageColumn::make('plate_photo_path')->label('عکس پلاک')->disk('public'),
                ImageColumn::make('emergency_photo_path')->label('عکس اضطراری')->disk('public'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        // CORRECTED: The 'view' page route has been removed to fix the error.
        // The ViewAction in the table uses a modal by default.
        return [
            'index' => Pages\ListTrips::route('/'),
        ];
    }
}