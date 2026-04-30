<?php

declare(strict_types=1);

namespace Safemood\Discountify\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Models\Condition as ConditionModel;

/**
 * ConditionEngine — merges and evaluates all discount conditions.
 *
 * Two sources:
 *   1. Code classes  — PHP classes in App\Conditions\ (auto-discovered)
 *   2. DB conditions — rows in discountify_conditions (UI-managed)
 *
 * PHP 8.4: typed properties, readonly where applicable, named arguments.
 */
final class ConditionEngine
{
    /** @var array<int, array{slug:string,condition:callable,discount:float,type:DiscountType,skip:bool,priority:int}> */
    private array $conditions = [];

    // ── Registration ──────────────────────────────────────────────────────────

    /**
     * Register one or more inline conditions.
     *
     * Each item must be an associative array with keys:
     *   slug (string), condition (callable), discount (float),
     *   type (string|DiscountType), skip (bool), priority (int)
     */
    public function add(array $conditions): static
    {
        foreach ($conditions as $c) {
            $this->conditions[] = $this->normalise($c);
        }

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     */
    public function define(string $slug, callable $condition, float $discount, bool $skip = false): static
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('Slug must be provided.');
        }

        return $this->add([compact('slug', 'condition', 'discount', 'skip')]);
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     */
    public function defineIf(string $slug, bool $isAcceptable, float $discount): static
    {
        return $this->define($slug, fn () => $isAcceptable, $discount);
    }

    /**
     * Add a condition class instance.
     */
    public function addClassCondition(object $instance): static
    {
        $this->conditions[] = $this->normalise([
            'slug' => $instance->slug ?? class_basename($instance),
            'condition' => $instance,
            'discount' => $instance->discount ?? 0,
            'type' => $instance->type ?? 'percentage',
            'skip' => $instance->skip ?? false,
            'priority' => $instance->priority ?? 0,
        ]);

        return $this;
    }

    /** Mark a registered condition as skipped by its slug. */
    public function skip(string $slug): static
    {
        foreach ($this->conditions as &$c) {
            if ($c['slug'] === $slug) {
                $c['skip'] = true;
            }
        }

        return $this;
    }

    /** Remove all registered conditions. */
    public function flush(): static
    {
        $this->conditions = [];

        return $this;
    }

    // ── Auto-discovery ────────────────────────────────────────────────────────

    public function discover(): static
    {
        $namespace = config('discountify.condition_namespace', 'App\\Conditions');
        $path = config('discountify.condition_path', app_path('Conditions'));

        if (! is_dir($path)) {
            return $this;
        }

        foreach ((array) glob("{$path}/*.php") as $file) {
            $class = $namespace.'\\'.basename(path: (string) $file, suffix: '.php');

            if (class_exists($class)) {
                $this->addClassCondition(new $class);
            }
        }

        return $this;
    }

    // ── DB loading ────────────────────────────────────────────────────────────

    public function loadFromDatabase(): static
    {
        ConditionModel::all()
            ->where('is_active', true)
            ->sortByDesc('priority')
            ->each(function (ConditionModel $model): void {
                $this->conditions[] = $this->normalise([
                    'slug' => $model->slug,
                    'condition' => fn (array $items): bool => $model->evaluate($items),
                    'discount' => $model->discount,
                    'type' => $model->discount_type,
                    'skip' => false,
                    'priority' => $model->priority,
                ]);
            });

        return $this;
    }

    // ── Evaluation ────────────────────────────────────────────────────────────

    /**
     * Return all passing (non-skipped) conditions sorted by priority.
     *
     * @return Collection<int, array{slug:string,discount:float,type:DiscountType}>
     */
    public function evaluate(array $items): Collection
    {
        return collect($this->conditions)
            ->filter(fn (array $c): bool => ! $c['skip'])
            ->sortByDesc('priority')
            ->filter(fn (array $c): bool => (bool) ($c['condition'])($items))
            ->values();
    }

    /**
     * Sum of all passing discounts converted to a percentage of the subtotal.
     */
    public function totalDiscount(array $items, float $subtotal): float
    {
        return $this->evaluate($items)->sum(
            fn (array $c): float => $c['type']->toPercentage($c['discount'], $subtotal)
        );
    }

    public function all(): array
    {
        return $this->conditions;
    }

    /**
     * Get all conditions (alias for all()).
     */
    public function getConditions(): array
    {
        return $this->all();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * @return array{slug:string,condition:callable,discount:float,type:DiscountType,skip:bool,priority:int}
     */
    private function normalise(array $c): array
    {
        $type = $c['type'] ?? 'percentage';

        return [
            'slug' => $c['slug'] ?? 'unnamed',
            'condition' => $c['condition'],
            'discount' => (float) ($c['discount'] ?? 0),
            'type' => $type instanceof DiscountType ? $type : DiscountType::from($type),
            'skip' => (bool) ($c['skip'] ?? false),
            'priority' => (int) ($c['priority'] ?? 0),
        ];
    }
}
