<?php

declare(strict_types=1);

use Keboola\AwsParameterFiller\Component;
use Keboola\Component\UserException;
use Keboola\Component\Logger;

require __DIR__ . '/../vendor/autoload.php';

$logger = new Logger();
try {
    $app = new Component($logger);
    if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4])) {
        throw new UserException(
            'Not enough arguments provided, call as: run.php templateFile targetFile namespace region'
        );
    }
    if (!file_exists($argv[1])) {
        throw new UserException(sprintf('Template file "%s" not found.', $argv[1]));
    }
    if (file_exists($argv[2])) {
        $logger->info(sprintf('Overwriting target file "%s".', $argv[2]));
    }
    $app->run($argv[1], $argv[2], $argv[3], $argv[4]);
    exit(0);
} catch (UserException $e) {
    $logger->error($e->getMessage());
    exit(1);
} catch (\Throwable $e) {
    $logger->critical(
        get_class($e) . ':' . $e->getMessage(),
        [
            'errFile' => $e->getFile(),
            'errLine' => $e->getLine(),
            'errCode' => $e->getCode(),
            'errTrace' => $e->getTraceAsString(),
            'errPrevious' => $e->getPrevious() ? get_class($e->getPrevious()) : '',
        ]
    );
    exit(2);
}
