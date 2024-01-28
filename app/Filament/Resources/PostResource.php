<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use App\Models\User;
use Faker\Provider\Text;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administrative Area';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('post_details_section')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->columnSpanFull()
                            ->getUploadedFileUsing(function (array $files) {
                                return $files;
                            }),
                        Forms\Components\TextInput::make('title')
                            ->placeholder('Enter a title for the post')
                            ->required()
                            ->minLength(3)
                            ->maxLength(128)
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->placeholder('Enter a description for the post')
                            ->required()
                            ->minLength(3)
                            ->maxLength(256)
                            ->columnSpan(1),
                        Forms\Components\Select::make('user_id')
                            ->required()
                            ->label('Associate post to user')
                            ->searchable(true)
                            ->getSearchResultsUsing(function (string $search): array {
                                return User::query()
                                    ->where(function (Builder $builder) use ($search) {
                                        $searchString = "%$search%";
                                        $builder->where('name', 'like', $searchString)
                                            ->orWhere('email', 'like', $searchString)
                                            ->orWhere('id', 'like', $searchString);
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (User $user) {
                                        return [$user->id => "{$user->name} - {$user->email}"];
                                    })
                                    ->toArray();
                            })
                            ->exists('users', 'id'),
                        Forms\Components\Select::make('type')
                            ->options(['REQUEST', 'FOUND'])
                            ->in(['REQUEST', 'FOUND']),
                        Forms\Components\TextInput::make('rewrad')
                            ->required()
                            ->numeric(true)
                            ->suffix('RON')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->splitKeys(['Tab', ' '])
                            ->columnSpanFull()
                    ])
                    ->columns(2)
                    ->description('Here you can insert post details such as title, description')
                    ->heading('Post details')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UserRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
