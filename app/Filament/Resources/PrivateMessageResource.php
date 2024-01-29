<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivateMessageResource\Pages;
use App\Filament\Resources\PrivateMessageResource\RelationManagers;
use App\Models\PrivateMessage;
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

class PrivateMessageResource extends Resource
{
    protected static ?string $model = PrivateMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User\'s Administrative Area';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sender_id')
                    ->required()
                    ->label('Associate sender')
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
                Forms\Components\Select::make('receiver_id')
                    ->required()
                    ->label('Associate receiver')
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
                    ->exists('users', 'id')
                    ->different('sender_id'),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(512)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('seen')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender')
                    ->getStateUsing(function (Model $record): string {
                        return $record->sender->name;
                    })
                    ->badge()
                    ->color(function (Model $record) {
                        if ($record->sender->is_verified)
                            return Color::Blue;
                        return Color::Gray;
                    })
                    ->icon(function (Model $record) {
                        if ($record->sender->is_verified)
                            return 'heroicon-o-shield-check';
                        return null;
                    }),
                Tables\Columns\TextColumn::make('receiver')
                    ->getStateUsing(function (Model $record): string {
                        return $record->receiver->name;
                    })
                    ->badge()
                    ->color(function (Model $record) {
                        if ($record->receiver->is_verified)
                            return Color::Blue;
                        return Color::Gray;
                    })
                    ->icon(function (Model $record) {
                        if ($record->receiver->is_verified)
                            return 'heroicon-o-shield-check';
                        return null;
                    }),
                Tables\Columns\TextColumn::make('receiver_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('seen')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrivateMessages::route('/'),
            'create' => Pages\CreatePrivateMessage::route('/create'),
            'edit' => Pages\EditPrivateMessage::route('/{record}/edit'),
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
