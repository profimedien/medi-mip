<?php

namespace App\Filament\Pages;

use App\Models\OrgUnit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/** Organigramm per Drag & Drop pflegen */
class OrgChartEditor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|UnitEnum|null $navigationGroup = 'Wissen';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Organigramm';

    protected static ?string $title = 'Organigramm';

    protected static ?string $slug = 'organigramm';

    protected string $view = 'filament.pages.org-chart-editor';

    public static function canAccess(): bool
    {
        return auth()->user()?->isGesamtAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Bereiche mit der Maus ziehen: auf einen Bereich ablegen macht ihn zur Untereinheit, auf die Linie darüber ablegen sortiert ihn davor ein.';
    }

    protected function getViewData(): array
    {
        return ['roots' => OrgUnit::whereNull('parent_id')->with('head', 'children.head', 'children.children.head', 'children.children.children.head', 'children.children.children.children')->orderBy('sort')->get()];
    }

    /** Verschieben: $parentId = neue Oberebene (null = oberste Ebene), $beforeId = davor einsortieren */
    public function move(int $id, ?int $parentId = null, ?int $beforeId = null): void
    {
        $unit = OrgUnit::findOrFail($id);
        if ($parentId !== null && ($parentId === $id || in_array($parentId, $this->descendantIds($unit), true))) {
            Notification::make()->title('Ein Bereich kann nicht unter sich selbst einsortiert werden.')->warning()->send();

            return;
        }

        $unit->update(['parent_id' => $parentId]);
        $siblings = OrgUnit::where('parent_id', $parentId)->where('id', '!=', $id)->orderBy('sort')->pluck('id')->all();
        $pos = $beforeId !== null ? array_search($beforeId, $siblings, true) : false;
        array_splice($siblings, $pos === false ? count($siblings) : $pos, 0, [$id]);
        foreach ($siblings as $i => $sid) {
            OrgUnit::whereKey($sid)->update(['sort' => $i]);
        }
    }

    private function descendantIds(OrgUnit $unit): array
    {
        $ids = [];
        foreach (OrgUnit::where('parent_id', $unit->id)->get() as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->descendantIds($child));
        }

        return $ids;
    }

    private function unitForm(): array
    {
        return [
            TextInput::make('name')->label('Bezeichnung')->required()->maxLength(120),
            Select::make('head_user_id')->label('Leitung (Person im Portal)')->searchable()
                ->getSearchResultsUsing(fn (string $s) => User::where('status', '!=', 'disabled')
                    ->where(fn ($q) => $q->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%"))
                    ->limit(20)->get()->mapWithKeys(fn ($u) => [$u->id => $u->name]))
                ->getOptionLabelUsing(fn ($v) => User::find($v)?->name),
            TextInput::make('head_name')->label('oder Leitung als Text')->helperText('Wenn die Person nicht im Portal ist.'),
            Textarea::make('description')->label('Beschreibung')->rows(2),
        ];
    }

    public function addAction(): Action
    {
        return Action::make('add')->label('Bereich anlegen')->icon('heroicon-o-plus')
            ->modalHeading(fn (array $arguments) => empty($arguments['parent']) ? 'Neuer Bereich auf oberster Ebene' : 'Neuer Unterbereich in „'.OrgUnit::find($arguments['parent'])?->name.'“')
            ->schema($this->unitForm())
            ->action(function (array $data, array $arguments) {
                $parent = $arguments['parent'] ?? null;
                OrgUnit::create($data + ['parent_id' => $parent, 'sort' => OrgUnit::where('parent_id', $parent)->max('sort') + 1]);
            });
    }

    public function editAction(): Action
    {
        return Action::make('edit')->label('Bearbeiten')->icon('heroicon-o-pencil-square')
            ->modalHeading('Bereich bearbeiten')
            ->schema($this->unitForm())
            ->fillForm(fn (array $arguments) => OrgUnit::findOrFail($arguments['id'])->only(['name', 'head_user_id', 'head_name', 'description']))
            ->action(fn (array $data, array $arguments) => OrgUnit::findOrFail($arguments['id'])->update($data));
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')->label('Löschen')->color('danger')->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalDescription('Untereinheiten rutschen eine Ebene nach oben und bleiben erhalten.')
            ->action(function (array $arguments) {
                $unit = OrgUnit::findOrFail($arguments['id']);
                OrgUnit::where('parent_id', $unit->id)->update(['parent_id' => $unit->parent_id]);
                $unit->delete();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->addAction(),
            Action::make('preview')->label('Im Portal ansehen')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(route('orgchart'), shouldOpenInNewTab: true),
        ];
    }
}
