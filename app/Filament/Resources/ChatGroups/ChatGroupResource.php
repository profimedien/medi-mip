<?php

namespace App\Filament\Resources\ChatGroups;

use App\Filament\Resources\ChatGroups\Pages\ManageChatGroups;
use App\Models\ChatGroup;
use App\Models\ChatMember;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Medigram: automatische Gruppen ansehen, eigene Gruppen anlegen. Direktchats bleiben privat. */
class ChatGroupResource extends Resource
{
    protected static ?string $model = ChatGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Chatgruppe';

    protected static ?string $pluralModelLabel = 'Medigram-Gruppen';

    public static function canAccess(): bool
    {
        return auth()->user()?->isGesamtAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', '!=', 'direct');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Name')->required()
                ->disabled(fn (?ChatGroup $record) => $record && $record->type !== 'custom'),
            Toggle::make('read_only')->label('Nur Moderation darf schreiben (Ankündigungskanal)'),
            Select::make('member_ids')->label('Mitglieder')->multiple()->searchable()
                ->visible(fn (?ChatGroup $record) => ! $record || $record->type === 'custom')
                ->getSearchResultsUsing(fn (string $s) => User::where('status', '!=', 'disabled')
                    ->where(fn ($q) => $q->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%"))
                    ->limit(30)->get()->mapWithKeys(fn ($u) => [$u->id => $u->name]))
                ->getOptionLabelsUsing(fn (array $v) => User::whereIn('id', $v)->get()->mapWithKeys(fn ($u) => [$u->id => $u->name])),
            Select::make('moderator_ids')->label('Moderation')->multiple()->searchable()
                ->getSearchResultsUsing(fn (string $s) => User::where('status', '!=', 'disabled')
                    ->where(fn ($q) => $q->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%"))
                    ->limit(30)->get()->mapWithKeys(fn ($u) => [$u->id => $u->name]))
                ->getOptionLabelsUsing(fn (array $v) => User::whereIn('id', $v)->get()->mapWithKeys(fn ($u) => [$u->id => $u->name]))
                ->helperText('Bei automatischen Gruppen kommen die zuständigen Admins automatisch dazu.'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('type')
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->weight('medium'),
                TextColumn::make('type')->label('Art')->badge()->formatStateUsing(fn ($state) => ChatGroup::TYPES[$state] ?? $state),
                TextColumn::make('members_count')->counts('members')->label('Mitglieder'),
                TextColumn::make('messages_count')->counts('messages')->label('Nachrichten'),
                IconColumn::make('read_only')->label('Nur Moderation')->boolean(),
                TextColumn::make('last_message_at')->label('Letzte Nachricht')->since(),
            ])
            ->filters([SelectFilter::make('type')->label('Art')->options(array_diff_key(ChatGroup::TYPES, ['direct' => 1]))])
            ->recordActions([
                Action::make('edit')->label('Bearbeiten')->icon('heroicon-o-pencil-square')
                    ->schema(fn (Schema $schema) => static::form($schema))
                    ->fillForm(fn (ChatGroup $r) => [
                        'name' => $r->name, 'read_only' => $r->read_only,
                        'member_ids' => $r->members()->pluck('user_id')->all(),
                        'moderator_ids' => $r->members()->where('is_moderator', true)->pluck('user_id')->all(),
                    ])
                    ->action(fn (ChatGroup $r, array $data) => static::saveGroup($r, $data)),
                Action::make('open')->label('Im Portal öffnen')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (ChatGroup $r) => route('chat', $r), shouldOpenInNewTab: true)
                    ->visible(fn (ChatGroup $r) => $r->members()->where('user_id', auth()->id())->exists()),
                DeleteAction::make()->visible(fn (ChatGroup $r) => $r->type === 'custom'),
            ]);
    }

    public static function saveGroup(ChatGroup $group, array $data): void
    {
        $group->fill(['read_only' => (bool) ($data['read_only'] ?? false)]);
        if ($group->type === 'custom' || ! $group->exists) {
            $group->name = $data['name'];
        }
        $group->save();

        $moderators = array_map('intval', $data['moderator_ids'] ?? []);
        if ($group->type === 'custom') {
            $members = array_unique(array_merge(array_map('intval', $data['member_ids'] ?? []), $moderators, [auth()->id()]));
            ChatMember::where('chat_group_id', $group->id)->whereNotIn('user_id', $members)->delete();
            foreach ($members as $id) {
                ChatMember::firstOrCreate(['chat_group_id' => $group->id, 'user_id' => $id]);
            }
        } else {
            foreach ($moderators as $id) {
                ChatMember::firstOrCreate(['chat_group_id' => $group->id, 'user_id' => $id]);
            }
        }
        ChatMember::where('chat_group_id', $group->id)->update(['is_moderator' => false]);
        ChatMember::where('chat_group_id', $group->id)->whereIn('user_id', $moderators)->update(['is_moderator' => true]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageChatGroups::route('/')];
    }
}
