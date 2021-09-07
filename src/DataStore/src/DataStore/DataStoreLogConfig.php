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

    private $operations = [];
    private $types = [];

    public function needLog(string $operation, string $type): bool
    {
        $needLogOperation = in_array(self::ALL_OPERATIONS, $this->operations)
            || in_array($operation, $this->operations);

        $needLogType = in_array(self::ALL_TYPES, $this->types)
            || in_array($type, $this->types);

        return $needLogOperation && $needLogType;
    }

    public function initFromConfig(array $config)
    {
        if (empty($config[self::OPERATIONS]) || !is_array($config[self::OPERATIONS])) {
            throw new \InvalidArgumentException("Config key '" . self::OPERATIONS . "' is missing or is not array");
        }

        if (empty($config[self::TYPES]) || !is_array($config[self::TYPES])) {
            throw new \InvalidArgumentException("Config key '" . self::TYPES . "' is missing or is not array");
        }

        $operations = $config[self::OPERATIONS];

        foreach ($operations as $operation) {
            if (!in_array($operation, self::ALLOWED_OPERATIONS)) {
                throw new \InvalidArgumentException("Operation '$operation' is not allowed");
            }
        }

        $types = $config[self::TYPES];

        foreach ($types as $type) {
            if (!in_array($type, self::ALLOWED_TYPES)) {
                throw new \InvalidArgumentException("Type '$type' is not allowed");
            }
        }

        $this->operations = $operations;
        $this->types = $types;
    }

}