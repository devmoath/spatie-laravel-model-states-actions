<?php

namespace Abather\SpatieLaravelModelStatesActions;

use Abather\SpatieLaravelModelStatesActions\Services\ChangStateService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Lang;
use Spatie\ModelStates\State as base;

abstract class State extends base
{
    public static int $order = 1;

    public static string $state_key = 'state';

    public static bool $exclude_from_actions = false;

    public static bool $exclude_from_filters = false;

    protected static ?string $ability = null;

    protected static ?string $notification_ability = null;

    protected static ?string $icon = 'heroicon-o-cursor-arrow-rays';

    protected static ?string $color = 'primary';

    protected static ?bool $skip_authorization = false;

    protected static ?bool $requires_confirmation = true;

    //Title will be viewed in tables and resources view/edit pages.

    public static function title(): string
    {
        return static::translate('title', class_basename(static::class));
    }

    public static function getStateKeyName(?string $name = null): string
    {
        return $name ?? static::$state_key;
    }

    //Label will be viewed for buttons and actions.

    protected static function translate(string $key, string $default): string
    {
        if (Lang::has(static::getTranslationPath().'.'.$key)) {
            return __(static::getTranslationPath().'.'.$key);
        }

        return $default;
    }

    protected static function getTranslationPath(): string
    {
        return 'states.'.class_basename(static::class);
    }

    public static function label(): string
    {
        return static::translate('label', class_basename(static::class));
    }

    //ability name will be used to determine policy name.

    public static function color(): string
    {
        return static::$color;
    }

    //The order that should be used when viewing actions/buttons for more than one state.

    public static function icon(): string
    {
        return static::$icon;
    }

    public static function abilityName(): string
    {
        return static::$ability ?? str(static::class)->classBasename()->camel();
    }

    public static function notificationAbility(): string
    {
        return static::$notification_ability ?? str(static::class)->classBasename()->camel();
    }

    public static function getActionForm(?Model $record = null): array
    {
        return [];
    }

    public static function order(): int
    {
        return static::$order ?? 0;
    }

    public static function includeToActions(): bool
    {
        return ! static::excludeFromActions();
    }

    public static function excludeFromActions(): bool
    {
        return static::$exclude_from_actions;
    }

    public static function includeToFilters(): bool
    {
        return ! static::excludeFromActions();
    }

    public static function excludeFromFilters(): bool
    {
        return static::$exclude_from_filters;
    }

    public static function tableAction($user): TableAction
    {
        return TableAction::make(class_basename(static::class))
            ->label(static::label())
            ->color(static::color())
            ->icon(static::icon())
            ->form(fn (Model $record) => static::getActionForm($record))
            ->authorize(fn (Model $record) => static::isAuthorized($user, $record))
            ->action(fn (Model $record, ?array $data) => static::transferToMe($record, $user, $data))
            ->requiresConfirmation(static::requiresConfirmation());
    }

    public static function action($user): Action
    {
        return Action::make(class_basename(static::class))
            ->label(static::label())
            ->form(fn (Model $record) => static::getActionForm($record))
            ->color(static::color())
            ->icon(static::icon())
            ->authorize(fn (Model $record) => static::isAuthorized($user, $record))
            ->action(fn (Model $record, ?array $data) => static::transferToMe($record, $user, $data))
            ->requiresConfirmation(static::requiresConfirmation());
    }

    public static function textColumn(?string $field = null, ?string $label = null, bool $without_icon = false): TextColumn
    {
        $field = static::getStateKeyName($field);

        return TextColumn::make($field)
            ->label($label ?? __($field))
            ->formatStateUsing(fn (?Model $record) => $record->{$field}->title())
            ->badge()
            ->when(! $without_icon, fn (TextColumn $component) => $component->icon(fn (?Model $record) => $record->{$field}->icon()))
            ->color(fn (?Model $record) => $record->{$field}->color());
    }

    public static function textEntry(?string $field = null, ?string $label = null, bool $without_icon = false): TextEntry
    {
        $field = static::getStateKeyName($field);

        return TextEntry::make($field)
            ->label($label ?? __($field))
            ->formatStateUsing(fn (?Model $record) => $record->{$field}->title())
            ->badge()
            ->when(! $without_icon, fn (TextEntry $component) => $component->icon(fn (?Model $record) => $record->{$field}->icon()))
            ->color(fn (?Model $record) => $record->{$field}->color());
    }

    public static function formSelect(string $model, ?string $field = null): Select
    {
        $field = static::getStateKeyName($field);

        return Select::make($field)
            ->label($label ?? __($field))
            ->options(static::getStatesForSelect($model, $field));
    }

    public static function formSelectWithAuth(Model $record, User $user, ?string $field = null, ?string $label = null): Select
    {
        $field = static::getStateKeyName($field);

        return Select::make($field)
            ->label($label ?? __($field))
            ->options(static::getStatesForSelectWithAuthorization($record, $user, $field));
    }

    public static function getStatesForSelect(string $model, ?string $field = null): array
    {
        $field = static::getStateKeyName($field);

        $status = [];

        foreach ($model::getStatesFor($field)->toArray() as $state) {
            $status[$state] = $state::title();
        }

        return $status;
    }

    public static function getStatesForSelectWithAuthorization(Model $record, User $user, ?string $field = null): array
    {
        $field = static::getStateKeyName($field);

        $status = [];

        foreach (get_class($record)::getStatesFor($field)->toArray() as $state) {
            if (static::isAuthorized($user, $record, $state)) {
                $status[$state] = $state::label();
            }
        }

        return $status;
    }

    public function display(bool $without_icon = false): Action
    {
        return Action::make(class_basename($this))
            ->label($this->title())
            ->color($this->color())
            ->when(! $without_icon, fn (Action $component) => $component->icon($this->icon()))
            ->disabled();
    }

    public static function transferToMe(Model $record, User $user, ?array $data = [])
    {
        ChangStateService::make($record, $user)
            ->attribute(static::getStateKeyName())
            ->skipAuthorization(static::skipAuthorization())
            ->to(static::class);
    }

    public static function skipAuthorization(): bool
    {
        return static::$skip_authorization;
    }

    public static function requiresConfirmation(): bool
    {
        return static::$requires_confirmation;
    }

    public static function isAuthorized($user, $record, ?string $finalState = null): bool
    {
        $finalState = $finalState ?? static::class;

        if (!$record->{$finalState::getStateKeyName()}->canTransitionTo($finalState)) {
            return false;
        }

        if ($finalState::skipAuthorization()) {
            return true;
        }

        return $user->can($finalState::abilityName(), $record);
    }
}
