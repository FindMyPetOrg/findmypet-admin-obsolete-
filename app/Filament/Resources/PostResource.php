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
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
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
                            ->required()
                            ->multiple()
                            ->columnSpanFull()
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(bin2hex(random_bytes(16))),
                            )
                            ->storeFiles(),
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
                        Forms\Components\TextInput::make('lat')
                            ->required()
                            ->numeric()
                            ->label('Latitude')
                            ->placeholder('Enter the latitude of the post')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('lng')
                            ->required()
                            ->numeric()
                            ->label('Longitude')
                            ->placeholder('Enter the longitude of the post')
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
                            ->options(['REQUEST' => 'Request', 'FOUND' => 'Found'])
                            ->in(['REQUEST', 'FOUND']),
                        Forms\Components\TextInput::make('reward')
                            ->required()
                            ->numeric()
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->description(function (Model $record) {
                        $description = substr($record->description, 0, 16);

                        return "{$description}...";
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_by')
                    ->getStateUsing(function (Model $record): string {
                        return $record->user->name;
                    })
                    ->badge()
                    ->color(function (Model $record) {
                        if ($record->user->is_verified)
                            return Color::Blue;
                        return Color::Gray;
                    })
                    ->icon(function (Model $record) {
                        if ($record->user->is_verified)
                            return 'heroicon-o-shield-check';
                        return null;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('deleted_at')
                    ->getStateUsing(function (Model $record): bool {
                        return $record->deleted_at !== null;
                    })
                    ->boolean()
                    ->label('Is hidden?')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('post_tags')
                    ->getStateUsing(function (Model $record): array {
                        return $record->tags;
                    })
                    ->badge()
                    ->color(Color::Green)
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
