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
 ];