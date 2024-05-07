<?php

declare(strict_types=1);

namespace App\Tests;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

use function dirname;

final class CoverageRuntime extends SymfonyRuntime
{
    public static function getProjectRoot(): string
    {
        return dirname(__DIR__);
    }

    public function getRunner(?object $application): RunnerInterface
    {
        return $this->wrapRunner(parent::getRunner($application), $this->createFilter());
    }

    private function createFilter(): Filter
    {
        $filter = new Filter();
        $filter->includeFiles($this->findFiles());

        return $filter;
    }

    /**
     * @return string[]
     */
    private function findFiles(): array
    {
        $finder = new Finder();
        $finder->files()->name('*.php')->in(self::getProjectRoot().'/src');

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    private function wrapRunner(RunnerInterface $runner, Filter $filter): RunnerInterface
    {
        return new readonly class($runner, $filter) implements RunnerInterface {
            public function __construct(private RunnerInterface $runner, private Filter $filter)
            {
            }

            public function run(): int
            {
                $driver = (new Selector())->forLineCoverage($this->filter);
                $coverage = new CodeCoverage($driver, $this->filter);

                $id = uniqid(more_entropy: true);

                $coverage->start($id);
                $result = $this->runner->run();
                $coverage->stop();

                $target = CoverageRuntime::getProjectRoot().'/var/coverage/'.$id.'.cov';
                (new PHP())->process($coverage, $target);

                return $result;
            }
        };
    }
}
