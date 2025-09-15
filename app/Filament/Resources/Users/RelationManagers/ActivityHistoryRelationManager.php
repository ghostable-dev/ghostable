<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\RelationManagers\ChangeHistoryRelationManager;
use App\Filament\Resources\Core\Activity\ActivityResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActivityHistoryRelationManager extends ChangeHistoryRelationManager
{
    protected static ?string $title = 'Activity';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    public function table(Table $table): Table
    {
        $modified = ActivityResource::table($table);

        $modified->getColumn('subject.id')->hidden(false);

        $modified->getFilter('subject_type')->hidden(false);

        return $modified;
    }
}
