<?php

namespace rollun\datastore\DataStore;

/**
 * Error when connection is established and request was sent, but response is not received
 */
class OperationTimedOutException extends DataStoreException {}
