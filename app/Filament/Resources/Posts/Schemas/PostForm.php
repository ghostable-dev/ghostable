<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('category')
                    ->options(PostCategory::selectOptions())
                    ->required(),
                Select::make('status')
                    ->options(PostStatus::selectOptions())
                    ->required(),
                Toggle::make('is_featured'),
                DateTimePicker::make('posted_at'),
                MarkdownEditor::make('content'),
                Textarea::make('description'),
                TextInput::make('hero'),
                TextInput::make('social'),
                TextInput::make('meta_title'),
                Textarea::make('meta_description'),
                TagsInput::make('meta_keywords'),
            ]);
    }
}
