<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use App\Blog\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (PostStatus $state) => $state->label())
                    ->color(fn ($state): string => match ($state->value) {
                        PostStatus::DRAFT->value => 'gray',
                        PostStatus::PUBLISHED->value => 'success',
                        PostStatus::ARCHIVED->value => 'danger',
                    })
                    ->badge(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('category')
                    ->formatStateUsing(fn (PostCategory $state) => $state->label())
                    ->badge(),
                TextColumn::make('posted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make(PostStatus::ARCHIVED->label())
                    ->placeholder('Without archived posts')
                    ->trueLabel('Without archived posts')
                    ->falseLabel('Only archived posts')
                    ->queries(
                        true: fn ($query) => $query,
                        false: fn ($query) => $query->archived(),
                        blank: fn ($query) => $query->published()->orWhere->draft(),
                    )
            ])
            ->recordActions([
                Action::make('preview')
                    ->iconButton()
                    ->icon('heroicon-s-eye')
                    ->url(fn (Post $post): string => route('blog.preview', $post))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
