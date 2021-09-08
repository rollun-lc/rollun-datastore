<?php


namespace rollun\datastore\DataStore;


class DataStoreLogConfig
{
    const KEY_LOG_CONFIG = 'logConfig';

    const OPERATIONS = 'operations';
    const TYPES = 'types';

    const ALL_OPERATIONS = '*';
    const CREATE = 'create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    const ALLOWED_OPERATIONS = [
        self::ALL_OPERATIONS,
        self::CREATE,
        self::READ,
        self::UPDATE,
        self::DELETE,
    ];

    const ALL_TYPES = '*';
    const REQUEST = 'request';
    const RESPONSE = 'response';

    const ALLOWED_TYPES = [
        self::ALL_TYPES,
        self::REQUEST,
        self::RESPONSE,
    ];

    private $operations;
    private $types;

    public function __construct(array $operations = [], array $types = [])
    {
        $this->operations = $operations;
        $this->types = $types;
    }

    public function needLog(string $operation, string $type): bool
    {
        $needLogOperation = in_array(self::ALL_OPERATIONS, $this->operations)
            || in_array($operation, $this->operations);

        $needLogType = in_array(self::ALL_TYPES, $this->types)
            || in_array($type, $this->types);

        return $needLogOperation && $needLogType;
    }

}