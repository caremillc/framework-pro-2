<?php  declare(strict_types=1);
namespace Careminate\Tests\Unit;

class DependantClass
{
    public function __construct(private DependencyClass $dependency)
    {
    }

    public function getDependency(): DependencyClass
    {
        return $this->dependency;
    }
}