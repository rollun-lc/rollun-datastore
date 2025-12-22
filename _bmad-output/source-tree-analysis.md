# Source Tree Analysis

```
rollun-datastore/
├── bin/                         # Utility scripts (if any)
├── config/                      # Application configuration (Laminas ConfigAggregator)
│   └── autoload/                # Env-specific and module configs
├── data/                        # Runtime/test data (cache, fixtures)
├── docker/                      # Docker runtime configs (nginx, php-fpm)
├── docs/                        # Project documentation
├── public/                      # Optional web entry (built-in PHP server)
├── src/                         # Core source code
│   ├── DataStore/               # Datastore module
│   │   └── src/                 # Implementation
│   ├── Repository/              # Repository module
│   │   └── src/                 # Implementation
│   └── Uploader/                # Uploader module
│       └── src/                 # Implementation
├── test/                        # Unit/functional/integration tests
└── vendor/                      # Composer dependencies (excluded)
```

## Critical Folders Summary

- **src/DataStore/src**: Datastore core (types, schema, RQL, middleware, table gateways).
- **src/Repository/src**: Repository abstractions and casting.
- **src/Uploader/src**: Upload logic and iterators.
- **config/**: Service container and module configuration.
- **public/**: Optional HTTP entry point for middleware usage.
- **docs/**: Existing documentation set.
- **docker/**: Local runtime and environment provisioning.
- **test/**: Unit, functional, and integration tests.
