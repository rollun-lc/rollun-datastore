<?php

declare(strict_types=1);

namespace rollun\datastore\DataStore\Scheme;

use InvalidArgumentException;

class Scheme
{
    /**
     * @var array
     */
    private $scheme;

    public function __construct(array $scheme)
    {
        $this->validateScheme($scheme);
        $this->scheme = $scheme;
    }

    private function validateScheme(array $scheme): void
    {
        foreach ($scheme as $fieldName => $fieldInfo) {
            if (!$fieldInfo instanceof FieldInfo) {
                throw new InvalidArgumentException("Invalid field info in scheme for field '$fieldName'");
            }
        }
    }

    public function findInfoByFieldName(string $fieldName): FieldInfo
    {
        if (empty($this->scheme[$fieldName])) {
            throw new InvalidArgumentException("Undefined field '$fieldName' in scheme");
        }
        return $this->scheme[$fieldName];
    }

    /**
     * @return FieldInfo[]
     */
    public function toArray(): array
    {
        return $this->scheme;
    }
}
