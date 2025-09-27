<?php  declare(strict_types=1);
namespace Careminate\Tests\Unit;

class DependencyClass
{
    public function __construct(private SubDependencyClass $subDependency)
    {
    }

    public function getSubDependency(): SubDependencyClass
    {
        return $this->subDependency;
    }
}