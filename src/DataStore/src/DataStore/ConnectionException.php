<?php

namespace rollun\datastore\DataStore;

/**
 * Error establishing TCP (or any other, specific for datastore) connection
 * Also can be thrown if the connection is lost during operation
 */
class ConnectionException extends DataStoreException
{

}