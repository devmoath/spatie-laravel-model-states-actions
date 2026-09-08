<?php

namespace Abather\SpatieLaravelModelStatesActions\Services;

use Abather\SpatieLaravelModelStatesActions\State;
use Abather\SpatieLaravelModelStatesActions\Traits\Makeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class _BaseService
{
    use Makeable;

    /** @var Model::class */
    protected string $model;

    protected string $field;

    protected ?User $user;

    protected array $excluded_states = [];

    protected array $include_states = [];

    public function __construct(string $model, string $field = 'state', ?User $user = null)
    {
        $this->model = $model;
        $this->field = $field;
        $this->user = $user ?? auth()->user();
    }

    public function excludeStates(array|string $excluded_states): self
    {
        if (is_string($excluded_states)) {
            $excluded_states = [$excluded_states];
        }

        $this->excluded_states = array_merge($this->excluded_states, $this->resolveStates($excluded_states));

        return $this;
    }

    public function includeStates(array|string $include_states): self
    {
        if (is_string($include_states)) {
            $include_states = [$include_states];
        }

        $this->include_states = array_merge($this->include_states, $this->resolveStates($include_states));

        return $this;
    }

    //States may be passed by their $name, so resolve them back to class names.

    protected function resolveStates(array $states): array
    {
        $base = State::getBaseStateClass($this->model, $this->field);

        return array_map(fn ($state) => $base::resolveStateClass($state), $states);
    }

    protected function getStates(): array
    {
        return array_diff(State::getStateClasses($this->model, $this->field), $this->excluded_states);
    }
}
