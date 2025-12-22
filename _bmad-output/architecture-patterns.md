# Architecture Patterns

## Part: root

- **Project Type:** Library (PHP)
- **Architecture Style:** Modular library with service-container configuration
- **Key Signals:**
  - PSR-4 autoloading across multiple modules (DataStore, Uploader, Repository)
  - Laminas ServiceManager / Diactoros / Stratigility dependencies for HTTP/middleware integration
  - Public web entry present (`public/index.php`), but core is reusable library logic
  - Multiple package-like namespaces under `src/` indicating modular components
