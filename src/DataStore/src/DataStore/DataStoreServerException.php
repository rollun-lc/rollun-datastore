<?php

namespace rollun\datastore\DataStore;

/**
 * Some server-side error, like HTTP 5xx response
 * TODO: implement recognizing this exception in DbTable data store
 */
class DataStoreServerException extends DataStoreException
{
}