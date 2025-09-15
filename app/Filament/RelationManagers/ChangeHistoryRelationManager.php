<?php

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Core\Activity\ActivityResource;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChangeHistoryRelationManager extends RelationManager
{
    protected static ?string $title = 'Change History';

    protected static string $relationship = 'activities';

    protected static ?string $recordTitleAttribute = 'description';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public function table(Table $table): Table
    {
        $modified = ActivityResource::table($table);

        $modified->getColumn('subject.id')->hidden(true);

        $modified->getFilter('subject_type')->hidden(true);

        return $modified;
    }
}
