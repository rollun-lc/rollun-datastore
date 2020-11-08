<?php


namespace rollun\repository\Interfaces;


interface ModelCastingInterface
{
    public const CAST_INT = 'int';

    public const CAST_INTEGER = 'integer';

    public const CAST_FLOAT = 'float';

    public const CAST_DOUBLE = 'double';

    public const CAST_STRING = 'string';

    public const CAST_ARRAY = 'array';

    public const CAST_JSON = 'json';

    public const CAST_OBJECT = 'object';

    public const CAST_SERIALIZE = 'serialize';

    public const DIRECTION_GET = 'get';

    public const DIRECTION_SET = 'set';

    public function get($value);

    public function set($value);
}