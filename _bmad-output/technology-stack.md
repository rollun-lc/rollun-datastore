# Technology Stack

| Category | Technology | Version | Justification |
| --- | --- | --- | --- |
| Language | PHP | ^8.0 | composer.json requires php ^8.0 |
| Dependency Manager | Composer | N/A | composer.json / composer.lock present |
| Framework / Components | Laminas (laminas-http, laminas-servicemanager, laminas-db, laminas-diactoros, laminas-stratigility) | ^2.x - ^3.x | composer.json dependencies |
| Framework / Components | Mezzio (dev) | ^3.9 | composer.json require-dev uses mezzio |
| Utilities | rollun-utils | ^9.0.0 | composer.json dependencies |
| Logging | rollun-logger | ^7.6.4 | composer.json dependencies |
| RQL Parser | rollun-com/xiag-rql-parser | ^1.0.0 | composer.json dependencies |
| Filesystem | symfony/filesystem | ^6.0 | composer.json dependencies |
| Testing | PHPUnit | ^9.5.10 | composer.json require-dev |
| Code Quality | PHP_CodeSniffer | N/A | phpcs.xml present |
| Code Quality | PHP-CS-Fixer | N/A | .php-cs-fixer.dist.php present |
| Refactoring | Rector | ^2.0 | rector.php + composer.json require-dev |
| Containerization | Docker / Docker Compose | N/A | docker/ and docker-compose*.yml present |
| Documentation | MkDocs | N/A | mkdocs.yml present |
