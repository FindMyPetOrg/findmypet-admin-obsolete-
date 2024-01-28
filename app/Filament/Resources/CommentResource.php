<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Filament\Resources\CommentResource\RelationManagers;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
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

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?string $navigationGroup = 'Administrative Area';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('comment_details_section')
                    ->schema([
                    Forms\Components\Select::make('user_id')
                        ->required()
                        ->label('Associate comment to user')
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
                        ->getOptionLabelUsing(function (string $value) {
                            $user = User::find($value);

                            return "{$user->name} {$user->email}";
                        })
                        ->preload()
                        ->exists('users', 'id'),
                        Forms\Components\Select::make('post_id')
                            ->required()
                            ->label('Associate comment to post')
                            ->searchable(true)
                            ->getSearchResultsUsing(function (string $search): array {
                                return Post::query()
                                    ->where(function (Builder $builder) use ($search) {
                                        $searchString = "%$search%";
                                        $builder->where('title', 'like', $searchString)
                                            ->orWhere('description', 'like', $searchString)
                                            ->orWhere('id', 'like', $searchString);
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (Post $post) {
                                        return [$post->id => "{$post->title} - {$post->user->name}"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function (string $value) {
                                return Post::find($value)?->title;
                            })
                            ->preload()
                            ->exists('posts', 'id'),

                        Forms\Components\Textarea::make('description')
                            ->placeholder('Enter a description for the post')
                            ->required()
                            ->minLength(3)
                            ->maxLength(256)
                            ->columnSpanFull()
                        ])

                    ->columns(2)
                    ->description('Here you can insert comment details such as description, associated post etc.')
                    ->heading('Comment details')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                Tables\Columns\TextColumn::make('attached_to_post')
                    ->getStateUsing(function (Model $record): string {
                        return $record->post->title;
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
