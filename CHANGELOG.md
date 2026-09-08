# Changelog

## v2.1.0
- support states that define `$name`, states are now resolved back to their class names before they are used, and the options of `formSelect()`, `formSelectWithAuth()` and `tableFilter()` are keyed by the stored value.
- pass the field to the actions, so `StateActionsService::make(Order::class, 'importance')` will authorize and change the right field without overriding `$state_key`.
- `tableFilter()` uses the field that passed to the service instead of always using `state`.
- fix `includeToFilters()` to depend on `excludeFromFilters()` instead of `excludeFromActions()`.
- add the missing `$label` parameter to `formSelect()`.

## v2.0.1
- support Laravel 13 and Filament v5.
- allow newer `laravel/pint` and `nunomaduro/collision` versions.

## v2.0.0
- support Filament v4, the table actions and the page actions are unified, so `tableAction()` returns `Filament\Actions\Action` as well.
- use `formatStateUsing()` instead of `state()` in `textColumn()` and `textEntry()`.
- require php `^8.1` and add `filament/filament` to the required packages.

## v1.0.9
- support Laravel 12.

## v1.0.8
- add `formSelectWithAuth`.

## v1.0.7:
- add icons to `TextEntry` and `TextColumn`.
- add the ability to display the state without icon by passing `true` with functions `textEntry(without_icon: true)`, `textColumn(without_icon: true)`, and `display(without_icon: true)`
- add action group so you can group the actions and display current state as label.

### v1.0.6:
- include table filters `StateFilterService::make(static::getModel())->tableFilter()`.
- prevent State from shown in actions even if the authorization disabled based on `canTransitionTo()` method.