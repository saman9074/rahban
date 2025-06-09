<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

        public static function infolist(Infolist $infolist): Infolist
        {
            return $infolist
                ->schema([
                    Components\TextEntry::make('user.name')->label('مسافر'),
                    Components\TextEntry::make('status')->label('وضعیت')->badge(),
                    Components\ImageEntry::make('plate_photo_path')->label('عکس پلاک')->disk('public'),
                    Components\ImageEntry::make('emergency_photo_path')->label('عکس اضطراری')->disk('public'),
                ]);
        }
}
