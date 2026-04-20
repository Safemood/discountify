<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'discountify:condition')]
class ConditionMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'discountify:condition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new condition class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Condition'; // ✅ fixed typo

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/condition.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/condition.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return "{$rootNamespace}\\Conditions";
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        $customPath = config('discountify.condition_path');

        return $customPath.'/'.class_basename($name).'.php';
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['discount', 'd', InputOption::VALUE_OPTIONAL, 'The discount for the condition', 0],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the Condition already exists'],
            ['slug', 's', InputOption::VALUE_OPTIONAL, 'The slug for the condition', null],
        ];
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        return $this->customizeStub($stub);
    }

    /**
     * Customize stub replacements.
     */
    protected function customizeStub(string $stub): string
    {
        $slugOption = $this->option('slug');
        $discountOption = $this->option('discount');

        $slug = ! is_array($slugOption) && $slugOption !== null
            ? (string) $slugOption
            : $this->getNameInput();

        $discount = ! is_array($discountOption) && $discountOption !== null
            ? (string) $discountOption
            : '0';

        return str_replace(
            ['{{ slug }}', '{{ discount }}'],
            [
                str()->snake($slug, '_'),
                $discount,
            ],
            $stub
        );
    }
}
