<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuardianResource\Pages;
use App\Models\Guardian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $modelLabel = 'دفترچه تلفن نگهبان';
    protected static ?string $pluralModelLabel = 'دفترچه تلفن نگهبانان';

    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('مالک (کاربر)')->searchable(),
                TextColumn::make('name')->label('نام نگهبان')->searchable(),
                TextColumn::make('phone_number')->label('شماره تلفن'),
                IconColumn::make('is_default')->label('پیش‌فرض؟')->boolean(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuardians::route('/'),
        ];
    }
}