<?php

declare(strict_types=1);

use AnzuSystems\SerializerBundle\Tests\AnzuTestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new AnzuTestKernel('test', false);
$kernel->boot();

$app = new Application($kernel);
$app->setAutoExit(false);

$output = new ConsoleOutput();

// Clear cache
$input = new ArrayInput([
    'command' => 'cache:clear',
    '--no-warmup' => true,
    '--env' => getenv('APP_ENV'),
]);
$app->run($input, $output);

// Database drop
$input = new ArrayInput([
    'command' => 'doctrine:database:drop',
    '--force' => true,
    '--if-exists' => true,
]);
$app->run($input, $output);

# Database create
$input = new ArrayInput([
    'command' => 'doctrine:database:create',
]);
$app->run($input, $output);

# Update schema
$input = new ArrayInput([
    'command' => 'doctrine:schema:update',
    '--force' => true,
    '--complete' => true,
]);
$app->run($input, $output);
