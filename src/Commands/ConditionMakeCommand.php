<?php

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
    protected $type = 'Condtion';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/condition.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/condition.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {

        $customPath = config('discountify.condition_path');

        return $customPath.'/'.class_basename($name).'.php';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['discount', 'd', InputOption::VALUE_OPTIONAL, 'The discount for the condition', 0, []],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the Condition already exists'],
            ['slug', 's', InputOption::VALUE_OPTIONAL, 'The slug for the condition', null, []],
        ];
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->customizeStub($stub);
    }

    protected function customizeStub($stub)
    {
        $slug = $this->option('slug') ?? $this->getNameInput();

        return str_replace(['{{ slug }}', '{{ discount }}'], [
            str()->snake($slug, '_'),
            $this->option('discount'),
        ], $stub);
    }
}
