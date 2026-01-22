<?php

namespace App\Filament\Resources\IntegrationClients\Tables;

use App\Integration\Models\IntegrationClient;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IntegrationClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Key')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ownerOrganization.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('publish_status')
                    ->label('Publish status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('publish')
                    ->label('Publish')
                    ->requiresConfirmation()
                    ->modalHeading('Publish integration')
                    ->modalDescription('This will make the integration available to all eligible organizations.')
                    ->visible(fn (IntegrationClient $record): bool => $record->publish_status !== IntegrationClient::PUBLISH_STATUS_PUBLISHED)
                    ->action(function (IntegrationClient $record): void {
                        $previousStatus = $record->publish_status;

                        $record->forceFill([
                            'publish_status' => IntegrationClient::PUBLISH_STATUS_PUBLISHED,
                        ])->save();

                        activity('integration')
                            ->event('published')
                            ->performedOn($record)
                            ->causedBy(Auth::user())
                            ->withProperties([
                                'from' => $previousStatus,
                                'to' => $record->publish_status,
                                'integration_client' => [
                                    'id' => $record->id,
                                    'name' => $record->name,
                                    'key' => $record->key,
                                    'owner_organization_id' => $record->owner_organization_id,
                                ],
                            ])
                            ->log("Published integration client \"{$record->name}\"");
                    }),
                Action::make('unpublish')
                    ->label('Unpublish')
                    ->requiresConfirmation()
                    ->modalHeading('Unpublish integration')
                    ->modalDescription('This will remove the integration from public availability.')
                    ->visible(fn (IntegrationClient $record): bool => $record->publish_status === IntegrationClient::PUBLISH_STATUS_PUBLISHED)
                    ->action(function (IntegrationClient $record): void {
                        $previousStatus = $record->publish_status;

                        $record->forceFill([
                            'publish_status' => IntegrationClient::PUBLISH_STATUS_DRAFT,
                        ])->save();

                        activity('integration')
                            ->event('unpublished')
                            ->performedOn($record)
                            ->causedBy(Auth::user())
                            ->withProperties([
                                'from' => $previousStatus,
                                'to' => $record->publish_status,
                                'integration_client' => [
                                    'id' => $record->id,
                                    'name' => $record->name,
                                    'key' => $record->key,
                                    'owner_organization_id' => $record->owner_organization_id,
                                ],
                            ])
                            ->log("Unpublished integration client \"{$record->name}\"");
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
