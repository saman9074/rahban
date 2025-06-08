<?php
namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';
    protected static ?string $modelLabel = 'موقعیت';
    protected static ?string $pluralModelLabel = 'موقعیت‌های ثبت شده';

    public function form(Form $form): Form { return $form->schema([]); }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('latitude')
            ->columns([
                Tables\Columns\TextColumn::make('latitude')->label('عرض جغرافیایی'),
                Tables\Columns\TextColumn::make('longitude')->label('طول جغرافیایی'),
                Tables\Columns\TextColumn::make('created_at')->label('زمان ثبت')->dateTime('H:i:s'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}