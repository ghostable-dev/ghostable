<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\RelationManagers\ChangeHistoryRelationManager as RelationManager;

class ChangeHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';
}
