<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Safemood\Discountify\Models\Condition;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MakeConditionCommand extends Command
{
    protected $signature = 'discountify:condition
                            {name : Condition name or class name}
                            {--class : Generate a condition class instead of a database-backed condition}
                            {--slug= : Slug identifier (defaults to snake_case of name)}
                            {--field=count : Condition field (count|total|subtotal|item key)}
                            {--operator=gte : Condition operator}
                            {--value=0 : Condition value}
                            {--discount=0 : Discount amount}
                            {--type=percentage : percentage|fixed}
                            {--priority=0 : Evaluation priority}
                            {--active=1 : Set the condition active}
                            {--description= : Optional condition description}
                            {--force : Overwrite existing condition or class}';

    protected $description = 'Create a new Discountify condition';

    public function handle(): int
    {
        $name = $this->argument('name');
        if (! is_string($name)) {
            $name = '';
        }

        $slug = $this->option('slug');
        if (! is_string($slug) || $slug === '') {
            $slug = Str::snake($name);
        }

        $field = $this->option('field');
        if (! is_string($field) || $field === '') {
            $field = 'count';
        }

        $operator = $this->option('operator');
        if (! is_string($operator) || $operator === '') {
            $operator = 'gte';
        }

        $value = $this->parseValue($this->option('value'));

        $discount = $this->option('discount');
        if (! is_numeric($discount)) {
            $discount = 0;
        }

        $type = $this->option('type');
        if (! is_string($type) || $type === '') {
            $type = 'percentage';
        }

        $priority = $this->option('priority');
        if (! is_numeric($priority)) {
            $priority = 0;
        }

        $isActive = $this->parseBoolean($this->option('active'));
        $description = $this->option('description');
        if (! is_string($description)) {
            $description = null;
        }

        $name = (string) $name;
        $slug = (string) $slug;
        $field = (string) $field;
        $operator = (string) $operator;
        $discount = (float) $discount;
        $type = (string) $type;
        $priority = (int) $priority;
        $description = $description === '' ? null : $description;

        if ($this->option('class')) {
            return $this->createConditionClass($name, $slug, $discount, $type, $priority);
        }

        return $this->createDbCondition($name, $slug, $field, $operator, $value, $discount, $type, $priority, $isActive, $description);
    }

    private function createConditionClass(string $name, string $slug, float $discount, string $type, int $priority): int
    {
        $namespace = config('discountify.condition_namespace', 'App\\Conditions');
        $path = config('discountify.condition_path', app_path('Conditions'));

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file = "{$path}/{$name}.php";

        if (file_exists($file) && ! $this->option('force')) {
            $this->error("Condition class [{$name}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        file_put_contents($file, $this->stub($name, $namespace, $slug, $discount, $type, $priority));

        $this->info("Condition class [{$name}] created at [{$file}].");

        return self::SUCCESS;
    }

    private function createDbCondition(
        string $name,
        string $slug,
        string $field,
        string $operator,
        mixed $value,
        float $discount,
        string $type,
        int $priority,
        bool $isActive,
        ?string $description,
    ): int {
        $condition = Condition::query()->where('slug', $slug)->first();

        if ($condition && ! $this->option('force')) {
            $this->error("Condition [{$slug}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'slug' => $slug,
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'discount' => $discount,
            'discount_type' => $type,
            'priority' => $priority,
            'is_active' => $isActive,
            'description' => $description,
        ];

        if ($condition) {
            $condition->update($attributes);
        } else {
            $condition = Condition::create($attributes);
        }

        $this->info("Condition [{$condition->slug}] saved to the database.");

        return self::SUCCESS;
    }

    private function parseValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }
        }

        return $value;
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private function stub(
        string $name,
        string $namespace,
        string $slug,
        float $discount,
        string $type,
        int $priority,
    ): string {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use Safemood\\Discountify\\Contracts\\ConditionInterface;

        class {$name} implements ConditionInterface
        {
            public bool   \$skip     = false;
            public string \$slug     = '{$slug}';
            public float  \$discount = {$discount};
            public string \$type     = '{$type}';
            public int    \$priority = {$priority};

            /**
             * Return true to apply the discount.
             */
            public function __invoke(array \$items): bool
            {
                return false; // TODO: implement your logic
            }
        }
        PHP;
    }
}
