<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;
use App\Core\Session;
use App\Core\Url;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

// PSR-4 style autoloader for the App\ namespace.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

date_default_timezone_set('Europe/London');
mb_internal_encoding('UTF-8');

$debug = (bool) config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
error_reporting(E_ALL);

Url::boot();
View::setPath(BASE_PATH . '/app/Views');

if (PHP_SAPI !== 'cli') {
    Session::start();
}
