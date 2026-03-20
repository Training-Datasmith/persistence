# Architecture: persistence (doctrine/persistence)

## Purpose

A low-level abstraction layer shared between Doctrine ORM and Doctrine MongoDB ODM. Defines common interfaces (`ObjectManager`, `ObjectRepository`, `ClassMetadata`) so userland code can be written against Doctrine without depending on a specific persistence backend.

## Directory Structure

```
src/
  Object_Manager.php             — Core interface: persist, remove, flush, find, getRepository
  Object_Repository.php          — Interface for entity/document repositories
  Manager_Registry.php           — Interface for registering and retrieving managers/connections
  Abstract_Manager_Registry.php  — Base implementation of ManagerRegistry
  Object_Manager_Decorator.php   — Forwards all calls to an inner ObjectManager (decorator pattern)
  Connection_Registry.php        — Interface for connection registries
  Proxy.php                      — Marker interface for Doctrine proxy objects
  Notify_Property_Changed.php    — Interface for entities that notify listeners on change
  Property_Changed_Listener.php  — Receives property change notifications
  Mapping/
    Class_Metadata.php            — Interface describing how a class maps to storage
    Class_Metadata_Factory.php    — Interface for loading and caching ClassMetadata
    Abstract_Class_Metadata_Factory.php — Base metadata factory with caching logic
    Mapping_Exception.php
    Reflection_Service.php        — Interface for reflection operations (allows non-PHP-reflection)
    Runtime_Reflection_Service.php
    Driver/
      Mapping_Driver.php          — Interface: loads metadata for a class
      Mapping_Driver_Chain.php    — Tries multiple drivers in order (by namespace prefix)
      File_Driver.php             — Base for XML/YAML/JSON file-based drivers
      PHP_Driver.php              — Loads metadata from PHP return files
      Default_File_Locator.php
      Symfony_File_Locator.php
  Event/
    Lifecycle_Event_Args.php      — Event data for persist/update/remove lifecycle events
    Manager_Event_Args.php
    ...
  Reflection/
    Runtime_Reflection_Property.php
    Enum_Reflection_Property.php
    Typed_No_Default_Reflection_Property.php
```

## Key Design Decisions

- **Interface-only public API**: The public API is entirely interfaces; implementations live in ORM/ODM packages
- **Decorator pattern**: `Object_Manager_Decorator` forwards all calls, allowing middleware-style wrappers (e.g., logging, transaction management) without subclassing
- **Driver chain**: `Mapping_Driver_Chain` routes metadata loading to the correct driver based on entity namespace prefix, enabling mixed-format projects
- **Reflection abstraction**: `Reflection_Service` can be swapped for a proxy-aware or cached version to avoid PHP reflection overhead in production

## Extension Points

- Implement `Object_Manager` to support a new persistence backend
- Implement `Mapping_Driver` to add a new metadata format (e.g., TOML, attribute-only)
- Extend `Abstract_Manager_Registry` to integrate with any DI container

## Dependency Flow

```
ObjectManager (interface)
  ← Doctrine ORM EntityManager
  ← Doctrine MongoDB ODM DocumentManager

ObjectRepository (interface)
  ← EntityRepository (ORM)

ClassMetadata (interface)
  ← ORM ClassMetadata (with column/relation mappings)
```
