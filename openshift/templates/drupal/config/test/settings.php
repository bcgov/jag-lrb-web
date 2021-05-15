<?php
$databases['default']['default'] = array (
   'database' => getenv('MYSQL_DATABASE'),
   'username' => getenv('MYSQL_USER'),
   'password' => getenv('MYSQL_PASSWORD'),
   'host' => getenv('MYSQL_HOST'),
   'port' => getenv('MYSQL_PORT'),
   'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
   'driver' => 'mysql',
   'prefix' => '',
   'collation' => 'utf8mb4_general_ci',
);
$settings['hash_salt'] = json_encode($databases);
$settings['file_public_path'] = 'files';
$settings['file_private_path'] = 'files/private';
$settings['config_sync_directory'] = 'files';
$settings['trusted_host_patterns'] = [
   '^jag-lrb-web(-dev|-test)?\.apps\.silver\.devops\.gov\.bc\.ca$',
   '^(www\.(dev|test)|(dev|test))\.?lrb\.bc\.ca$',
   '^(www\.)?lrb\.bc\.ca$'
 ];

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
$config['system.logging']['error_level'] = 'verbose';