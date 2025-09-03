<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Blog\Enums\PostCategory;
use App\Blog\Enums\PostStatus;
use App\Blog\Models\Post;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category')
                    ->required()
                    ->options(PostCategory::selectOptions())
                    ->columnSpanFull(),
                self::getHeroEditor(),
                self::getSocialEditor(),
                self::getSlugEditor(),
                TextInput::make('title')->required()->maxLength(191)->columnSpanFull(),
                Textarea::make('description')->maxLength(65535)->columnSpanFull(),
                self::getMarkdownEditor(),
                self::getVisibilitySection(),
                self::getSEOSection(),
            ]);
    }
    
    public static function getHeroEditor(): FileUpload
    {
        return FileUpload::make('hero')
            ->image()
            ->directory(fn (Post $record) => $record->directory)
            ->downloadable(true)
            ->previewable(true)
            ->preserveFilenames()
            ->imageEditor(true);
    }
    
    public static function getSocialEditor(): FileUpload
    {
        return FileUpload::make('social')
            ->image()
            ->directory(fn (Post $record) => $record->directory)
            ->downloadable(true)
            ->previewable(true)
            ->preserveFilenames()
            ->imageEditor(true);
    }
    
    public static function getSlugEditor(): TextInput
    {
        return TextInput::make('slug')
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(191)
            ->columnSpanFull();
    }
    
    public static function getMarkdownEditor(): MarkdownEditor
    {
        return MarkdownEditor::make('content')
            ->nullable()
            ->maxLength(65535)
            ->columnSpanFull()
            ->saveUploadedFileAttachmentUsing(function(Post $record, TemporaryUploadedFile $file) {
                return $file->storeAs($record->directory, str(
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                )->slug('_')->append('.', $file->getClientOriginalExtension()));
            });
    }
    
    public static function getVisibilitySection(): Section
    {
        return Section::make('Visibility')
            ->collapsible(true)
            ->collapsed(false)
            ->schema([
                Select::make('status')->required()
                    ->options(PostStatus::selectOptions())
                    ->default(PostStatus::DRAFT),
                Toggle::make('is_featured'),
                DatePicker::make('posted_at')
                    ->default(now()),
            ]);
    }
    
    public static function getSEOSection(): Section
    {
        return Section::make('SEO')
            ->collapsible(true)
            ->collapsed(true)
            ->schema([
                TextInput::make('meta_title')
                    ->label('Title')
                    ->maxLength(191),
                Textarea::make('meta_description')
                    ->label('Description')
                    ->maxLength(191),
                TagsInput::make('meta_keywords')
                    ->label('Keywords'),
            ]);
    }
}
