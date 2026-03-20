<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

/**
 * Basic implementation of ClassLocator that passes a list of class names.
 */
final class Class_Names implements Class_Locator
{
    /** @param list<class-string> $classNames */
    public function __construct(private readonly array $class_names)
    {
    }
    /** @return list<class-string> */
    public function get_class_names(): array
    {
        return $this->class_names;
    }
}