<?php

namespace Spatie\BetterTypes;

use Closure;
use Illuminate\Support\Collection;
use ReflectionClass;

class Handlers
{
    /** @var \Spatie\BetterTypes\Method[] */
    private array $methods = [];

    private array $visibilityFilter = [];

    /** @var Closure[] */
    private array $filters = [];

    public function __construct(
        private ReflectionClass $class
    ) {
        foreach ($class->getMethods() as $reflectionMethod) {
            $this->methods[$reflectionMethod->getName()] = new Method($reflectionMethod);
        }
    }

    public static function new(object|string $object): self
    {
        return new self(
            $object instanceof ReflectionClass
                ? $object
                : new ReflectionClass($object)
        );
    }

    public function all(): Collection
    {
        $allMethods = [];

        foreach ($this->methods as $name => $method) {
            if (! $this->filterAllows($method)) {
                continue;
            }

            $allMethods[$name] = $method;
        }

        return collect($allMethods);
    }

    public function first(): ?Method
    {
        return $this->all()->first();
    }

    public function filter(Closure $filter): self
    {
        $clone = clone $this;

        $clone->filters[] = $filter;

        return $clone;
    }

    public function reject(Closure $reject): self
    {
        return $this->filter(fn (Method $method) => ! $reject($method));
    }

    public function accepts(mixed ...$input): self
    {
        return $this->filter(fn (Method $method) => $method->accepts(...$input));
    }

    public function public(): self
    {
        $clone = clone $this;

        $clone->visibilityFilter[] = Method::PUBLIC;

        return $clone;
    }

    public function protected(): self
    {
        $clone = clone $this;

        $clone->visibilityFilter[] = Method::PROTECTED;

        return $clone;
    }

    public function private(): self
    {
        $clone = clone $this;

        $clone->visibilityFilter[] = Method::PRIVATE;

        return $clone;
    }

    private function filterAllows(Method $method): bool
    {
        if (
            $this->visibilityFilter !== []
            && ! in_array($method->visibility(), $this->visibilityFilter)
        ) {
            return false;
        }

        foreach ($this->filters as $filter) {
            if ($filter($method) === false) {
                return false;
            }
        }

        return true;
    }
}
