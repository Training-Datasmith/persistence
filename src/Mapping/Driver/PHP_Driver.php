<?php

declare (strict_types=1);
namespace Doctrine\Persistence\Mapping\Driver;

use Doctrine\Persistence\Mapping\Class_Metadata;
/**
 * The PHPDriver includes php files which just populate ClassMetadataInfo
 * instances with plain PHP code.
 *
 * @template-extends FileDriver<ClassMetadata<object>>
 */
class Php_Driver extends File_Driver
{
    /** @phpstan-var ClassMetadata<object> */
    protected Class_Metadata $metadata;
    /** @param string|array<int, string>|FileLocator $locator */
    public function __construct(string|array|File_Locator $locator)
    {
        parent::__construct($locator, '.php');
    }
    public function load_metadata_for_class(string $class_name, Class_Metadata $metadata): void
    {
        $this->metadata = $metadata;
        $this->load_mapping_file($this->locator->find_mapping_file($class_name));
    }
    /**
     * {@inheritDoc}
     */
    protected function load_mapping_file(string $file): array
    {
        $metadata = $this->metadata;
        include $file;
        return [$metadata->get_name() => $metadata];
    }
}