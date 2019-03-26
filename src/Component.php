<?php

declare(strict_types=1);

namespace Keboola\AwsParameterFiller;

use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;
use Keboola\Component\UserException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class Component
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $region;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run(string $filePath, string $namespace, string $region): void
    {
        $this->namespace = $namespace;
        if (empty($namespace) || strlen($namespace) < 3 ||
            $namespace[0] !== '/' || $namespace[strlen($namespace)-1] !== '/'
        ) {
            throw new UserException(
                sprintf(
                    'Invalid namespace "%s". Namespace argument must start end and with a slash (/) and be non-empty.',
                    $namespace
                )
            );
        }
        if (!file_exists($filePath)) {
            touch($filePath);
        }
        if (!is_writable($filePath)) {
            throw new UserException(sprintf('File "%s" is not writable.', $filePath));
        }
        $this->region = $region;
        $data = $this->getParameters($namespace);
        file_put_contents($filePath, $this->format($data, $namespace), FILE_APPEND);
    }

    private function format(array $data, string $namespace): string
    {
        $result = sprintf("\n" . '# Added by aws-parameter-filler from namespace: "%s".', $namespace);
        foreach ($data as $key => $value) {
            $this->logger->info(sprintf('Got parameter "%s".', $key));
            // quote values with no var expansion https://github.com/bkeepers/dotenv/issues/267
            $result .= sprintf("\n%s='%s'", $key, $value);
        }
        return $result . "\n";
    }

    private function getParametersFromSsm(string $namespace): array
    {
        $client = new SsmClient(['region' => $this->region, 'version' => '2014-11-06']);
        $this->logger->info(sprintf('Getting parameters from SSM namespace "%s".', $namespace));
        try {
            $result = $client->getParametersByPath([
                'Path' => $namespace,
                'Recursive' => false,
                'WithDecryption' => true,
            ]);
            $parameters = $result->get('Parameters');
            $nextToken = $result->get('NextToken');
            while ($nextToken) {
                $result = $client->getParametersByPath([
                    'Path' => $namespace,
                    'Recursive' => false,
                    'NextToken' => $nextToken,
                    'WithDecryption' => true,
                ]);
                $nextToken = $result->get('NextToken');
                $parameters = array_merge($parameters, $result->get('Parameters'));
            }
        } catch (SsmException $e) {
            if ($e->getAwsErrorCode() === 'AccessDeniedException') {
                throw new UserException(sprintf('Access denied to namespace "%s".', $namespace));
            }
            throw $e;
        }
        return $parameters;
    }

    private function getParamName(string $namespace, string $key): string
    {
        return substr($key, strlen($namespace));
    }

    private function getParameters(string $namespace): array
    {
        $data = [];
        foreach ($this->getParametersFromSsm($namespace) as $row) {
            $data[$this->getParamName($namespace, $row['Name'])] = $row['Value'];
        }
        return $data;
    }
}
