<?php

declare (strict_types=1);
namespace Doctrine\Persistence;

/**
 * Contract covering connection for a Doctrine persistence layer ManagerRegistry class to implement.
 */
interface Connection_Registry
{
    /**
     * Gets the default connection name.
     *
     * @return string The default connection name.
     */
    public function get_default_connection_name(): string;
    /**
     * Gets the named connection.
     *
     * @param string|null $name The connection name (null for the default one).
     */
    public function get_connection(string|null $name = null): object;
    /**
     * Gets an array of all registered connections.
     *
     * @return array<string, object> An array of Connection instances.
     */
    public function get_connections(): array;
    /**
     * Gets all connection names.
     *
     * @return array<string, string> An array of connection names.
     */
    public function get_connection_names(): array;
}