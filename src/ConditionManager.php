<?php

namespace Safemood\Discountify;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Safemood\Discountify\Contracts\ConditionManagerInterface;
use Safemood\Discountify\Exceptions\DuplicateSlugException;
use Symfony\Component\Finder\Finder;

/**
 * Class ConditionManager
 */
class ConditionManager implements ConditionManagerInterface
{
    protected array $conditions = [];

    /**
     * Define multiple conditions at once.
     *
     * @return $this
     */
    public function add(array $conditions): self
    {
        $this->conditions = array_merge(
            $this->conditions,
            collect($conditions)
                ->reject(function ($condition) {
                    if (empty($condition['slug'])) {
                        throw new InvalidArgumentException('Slug must be provided.');
                    }

                    $this->checkDuplicateSlug($condition['slug']);

                    return isset($condition['skip']) && $condition['skip'];
                })
                ->map(fn ($condition) => Arr::only($condition, ['slug', 'condition', 'discount']))
                ->toArray()
        );

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     *
     * @return $this
     */
    public function define(string $slug, callable $condition, float $discount, bool $skip = false): self
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('Slug must be provided.');
        }

        $this->checkDuplicateSlug($slug);

        if (! $skip) {
            $this->conditions[] = compact('slug', 'condition', 'discount');
        }

        return $this;
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     *
     * @return $this
     */
    public function defineIf(string $slug, bool $isAcceptable, float $discount): self
    {
        return $this->define($slug, fn () => $isAcceptable, $discount);
    }

    /**
     * Get all defined conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Discover condition classes in a given namespace and path.
     *
     * @param  string  $namespace
     * @param  string|null  $path
     * @return $this
     */
    public function discover($namespace = 'App\\Conditions', $path = null): self
    {
        $namespace = str()->finish($namespace, '\\');

        $directory = $path ?? base_path('app/Conditions');

        if (! is_dir($directory)) {
            return $this;
        }

        Collection::make((new Finder)
            ->files()
            ->name('*.php')
            ->depth(0)
            ->in($directory))
            ->each(function ($file) use ($namespace) {
                $class = $namespace.$file->getBasename('.php');

                if (class_exists($class) && is_a($class, $namespace, true)) {
                    $conditionInstance = new $class();
                    $skipping = property_exists($conditionInstance, 'skip') && $conditionInstance->skip;

                    if (method_exists($conditionInstance, '__invoke') && ! $skipping) {
                        $slug = property_exists($conditionInstance, 'slug') ?
                            $conditionInstance->slug : strtolower(str_replace($namespace, '', $class));

                        $conditionCallback = fn ($items) => $conditionInstance->__invoke($items);

                        $discount = property_exists($conditionInstance, 'discount') ?
                            $conditionInstance->discount : 0;

                        $this->define($slug, $conditionCallback, $discount);
                    }
                }
            });

        return $this;
    }

    /**
     * Check if a slug is already defined and throw an exception if it is.
     *
     * @throws \InvalidArgumentException
     */
    private function checkDuplicateSlug(string $slug): void
    {
        if (in_array($slug, array_column($this->conditions, 'slug'), true)) {
            throw new DuplicateSlugException($slug);
        }
    }
}
