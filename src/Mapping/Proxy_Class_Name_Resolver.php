<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping;

use Doctrine\Persistence\Proxy;
interface Proxy_Class_Name_Resolver
{
    /**
     * @phpstan-param class-string<Proxy<T>>|class-string<T> $className
     *
     * @phpstan-return class-string<T>
     *
     * @template T of object
     */
    public function resolve_class_name(string $class_name): string;
}