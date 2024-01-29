<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'User\'s Administrative Area';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            ImageEntry::make('current_avatar')
                ->getStateUsing(function (Model $record) {
                    if ($record->avatar === null)
                        return filament()->getUserAvatarUrl($record);
                    return $record->avatar;
                })
                ->circular(),
            Section::make('Description & details')
                ->description(function (Model $record) {
                    if ($record->description === null)
                        return 'Description was not yet been introduced.';
                    return $record->description;
                })
                ->schema([
                    TextEntry::make('name')
                        ->weight('bold'),
                    TextEntry::make('email')
                        ->weight('bold'),
                    TextEntry::make('address')
                        ->weight('bold')
                        ->placeholder('Address was not yet been introduced.'),
                    TextEntry::make('phone_number')
                        ->weight('bold')
                        ->placeholder('Phone number was not yet been introduced.'),
                    TextEntry::make('email_verified_at')
                        ->weight('bold')
                        ->placeholder('Email address was not verified yet.'),
                    TextEntry::make('date_of_birth')
                        ->dateTime('d-m-Y')
                        ->weight('bold')
                        ->placeholder('Date of birth was not yet been introduced.'),
                    TextEntry::make('user_tags')
                        ->getStateUsing(function (Model $record): string {
                            $admin_string = $record->is_admin ? 'admin' : 'user';
                            $verified_string = $record->is_verified ? 'verified' : 'not verified';

                            return "{$admin_string},{$verified_string}";
                        })
                        ->badge()
                        ->separator(',')
                        ->icon(function (string $state): string | null {
                            if (Str::contains($state, 'not', true))
                                return 'heroicon-o-shield-exclamation';
                            else if (Str::contains($state, 'verified', true))
                                return 'heroicon-o-shield-check';
                            else if (Str::contains($state, 'admin', true))
                                return 'heroicon-o-cog';
                            return null;
                        })
                        ->color(function (string $state): string | null {
                            Log::info($state);
                            if (Str::contains($state, 'not', true))
                                return 'gray';
                            else if (Str::contains($state, 'verified', true))
                                return 'info';
                            else if (Str::contains($state, 'admin', true))
                                return 'danger';
                            return null;
                        }),
                    TextEntry::make('ip_address')
                        ->weight('bold')
                        ->label('IP Address')
                        ->placeholder('IP Address on this account can\'t be found.'),
                ])->columns(3)
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('avatar_url')
                    ->label('Create Avatar')
                    ->avatar()
                    ->columnSpanFull()
                    ->getUploadedFileNameForStorageUsing(
                        fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                            ->prepend(bin2hex(random_bytes(16))),
                    )
                    ->maxSize(4096)
                    ->storeFiles(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->nullable()
                    ->regex('/^[a-zA-Z\d\s\-\,\#\.\+]+$/')
                    ->minLength(10)
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->nullable()
                    ->regex('/^$|^\(?\d{2,4}\)?[\d\s-]+$/')
                    ->minLength(3)
                    ->maxLength(16),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->nullable()
                    ->date()
                    ->beforeOrEqual(strtotime('18 years ago'))
                    ->validationMessages(['before_or_equal' => 'Date of birth can\'t be for setting an under aged user']),
                Forms\Components\Checkbox::make('is_admin')
                    ->nullable()
                    ->inline()
                    ->label('Is this user an admin?'),
                Forms\Components\Checkbox::make('is_verified')
                    ->nullable()
                    ->inline()
                    ->label('Is this user verified?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('#')
                    ->getStateUsing(function (Model $model) {
                        if ($model->avatar === null)
                            return filament()->getUserAvatarUrl($model);
                        return $model->avatar;
                    })
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->description(function (Model $record) {
                        $description = substr($record->description, 0, 16);

                        return "{$description}...";
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->placeholder('Not verified')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('deleted_at')
                    ->getStateUsing(function (Model $record): bool {
                        return $record->deleted_at === null;
                    })
                    ->boolean()
                    ->label('Is active?')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user_tags')
                    ->getStateUsing(function (Model $record): string {
                        $admin_string = $record->is_admin ? 'admin' : 'user';
                        $verified_string = $record->is_verified ? 'verified' : 'not verified';

                        return "{$admin_string},{$verified_string}";
                    })
                    ->badge()
                    ->separator(',')
                    ->icon(function (string $state): string | null {
                        if (Str::contains($state, 'not', true))
                            return 'heroicon-o-shield-exclamation';
                        else if (Str::contains($state, 'verified', true))
                            return 'heroicon-o-shield-check';
                        else if (Str::contains($state, 'admin', true))
                            return 'heroicon-o-cog';
                        return null;
                    })
                    ->color(function (string $state): string | null {
                        Log::info($state);
                        if (Str::contains($state, 'not', true))
                            return 'gray';
                        else if (Str::contains($state, 'verified', true))
                            return 'info';
                        else if (Str::contains($state, 'admin', true))
                            return 'danger';
                        return null;
                    })
                    ->label('User tags')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
