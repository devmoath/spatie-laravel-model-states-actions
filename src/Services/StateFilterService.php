<?php

namespace Abather\SpatieLaravelModelStatesActions\Services;

use Filament\Tables\Filters\SelectFilter;

class StateFilterService extends _BaseService
{
    public function tableFilter(?string $field = null, bool $multiple = true, ?string $label = null): SelectFilter
    {
        $field = $field ?? $this->field;

        return SelectFilter::make($field)
            ->label($label ?? __($field))
            ->options($this->getOptions())
            ->multiple($multiple)
            ->attribute($field);
    }

    private function getOptions(): array
    {
        $result = [];

        foreach ($this->getStates() as $option) {
            if ($option::includeToFilters() || in_array($option, $this->include_states)) {
                $result[$option::getMorphClass()] = $option::title();
            }
        }

        return $result;
    }
}
