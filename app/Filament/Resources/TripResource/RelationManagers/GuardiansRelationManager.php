<?php
namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';
    protected static ?string $modelLabel = 'نگهبان';
    protected static ?string $pluralModelLabel = 'نگهبانان';

    public function form(Form $form): Form { return $form->schema([]); }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام'),
                Tables\Columns\TextColumn::make('phone_number')->label('شماره تماس'),
            ]);
    }
}