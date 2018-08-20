<?php

declare(strict_types=1);

namespace Keboola\AwsParameterFiller;

use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;
use Keboola\Component\UserException;
use Psr\Log\LoggerInterface;
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

    public function run(string $templateFile, string $targetFile, string $namespace, string $region): void
    {
        $this->namespace = $namespace;
        $this->region = $region;
        $data = Yaml::parse((string) file_get_contents($templateFile));
        $data = $this->processData($data);
        file_put_contents($targetFile, Yaml::dump($data));
    }

    private function processData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $data[$key] = $this->getParameterValue($key);
            } elseif (is_array($data[$key])) {
                $data[$key] = $this->processData($data[$key]);
            } //else keep it as it is
        }
        return $data;
    }

    public function getParameterValue(string $key): string
    {
        $client = new SsmClient(['region' => $this->region, 'version' => '2014-11-06']);
        $name = $this->namespace . '/' . $key;
        try {
            $this->logger->info(sprintf('Getting parameter "%s" from SSM.', $name));
            $result = $client->getParameter([
                'Name' => $name,
                'WithDecryption' => true,
            ]);
            return (string) $result->get('Parameter')['Value'];
        } catch (SsmException $e) {
            if ($e->getAwsErrorCode() === 'ParameterNotFound') {
                throw new UserException(sprintf('Parameter "%s" was not found.', $name));
            }
            if ($e->getAwsErrorCode() === 'ValidationException') {
                throw new UserException(
                    sprintf(
                        'Parameter name "%s" or namespace "%s" is invalid: %s',
                        $key,
                        $this->namespace,
                        $e->getAwsErrorMessage()
                    )
                );
            }
            throw $e;
        }
    }
}
