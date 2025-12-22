# Project Documentation Index

## Project Overview

- **Type:** monolith with 1 part
- **Primary Language:** PHP
- **Architecture:** Modular library with DI container (Laminas ServiceManager)

## Quick Reference

- **Tech Stack:** PHP 8.0, Laminas components, Composer
- **Entry Point:** public/index.php (optional HTTP entry)
- **Architecture Pattern:** Modular library + ConfigAggregator

## Generated Documentation

- [Project Overview](./project-overview.md)
- [Architecture](./architecture.md)
- [Source Tree Analysis](./source-tree-analysis.md)
- [Component Inventory](./component-inventory.md)
- [Development Guide](./development-guide.md)
- [Deployment Guide](./deployment-guide.md)

## Existing Documentation

- [Docs Index](../docs/index.md) - Existing project documentation index
- [Datastore Schema](../docs/datastore-schema.md)
- [Datastore Methods](../docs/datastore_methods.md)
- [Request Logs](../docs/request-logs.md)
- [Typecasting](../docs/typecasting.md)
- [RQL](../docs/rql.md)
- [Scheme README](../src/DataStore/src/DataStore/Scheme/README.md)

## Getting Started

1. `make init` (or `make init-8.0`) to build containers and install dependencies.
2. `make up` to start the stack, then open http://localhost:8080.
3. Run tests with `make test`.
