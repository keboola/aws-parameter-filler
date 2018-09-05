<?php

declare(strict_types=1);

use Keboola\AwsParameterFiller\Component;
use Keboola\Component\UserException;
use Keboola\Component\Logger;

$logger = new Logger();
try {
    $app = new Component($logger);
    if (empty($argv[1]) || empty($argv[2]) || empty($argv[3])) {
        throw new UserException(
            'Not enough arguments provided, call as: run.php fileName namespace region'
        );
    }
    $app->run($argv[1], $argv[2], $argv[3]);
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
