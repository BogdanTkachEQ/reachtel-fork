<?php
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/api_db.php');
require_once(__DIR__ . '/api_constants.php');

\Morpheus\Schema\CustomTypesRegistrationManager::registerTypes();

return [
    'dbname'   => DB_MYSQL_DATABASE,
    'user'     => DB_MYSQL_MIGRATION_USERNAME,
    'password' => api_db_get_db_password(DB_MYSQL_MIGRATION_PASSWORD),
    'host'     => DB_MYSQL_WRITE_HOST,
    'driver'   => 'pdo_mysql'
];
