<?php

declare(strict_types=1);

namespace Keboola\AwsParameterFiller;

use Keboola\Component\UserException;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Yaml;

class ComponentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        var_dump(getenv('AWS_ACCESS_KEY_ID'));
        var_dump(getenv('AWS_SECRET_ACCESS_KEY'));
        if (empty(getenv('TEST_NAMESPACE')) || empty(getenv('TEST_REGION'))) {
            throw new \Exception('Environment variable TEST_NAMESPACE and TEST_REGION must be set.');
        }
    }

    public function testRun(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $data = [
            'parameters' => [
                'first' => 'someValue',
                'second' => null,
            ],
        ];
        $templateFile = $temp->getTmpFolder() . '/parameters.yml';
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        file_put_contents($templateFile, Yaml::dump($data));
        $component = new Component(new NullLogger());
        $component->run($templateFile, $targetFile, (string) getenv('TEST_NAMESPACE'), (string) getenv('TEST_REGION'));
        $data = Yaml::parse((string) file_get_contents($targetFile));
        self::assertEquals(
            [
                'parameters' => [
                    'first' => 'someValue',
                    'second' => 'Fantomas was here',
                ],
            ],
            $data
        );
    }

    public function testInvalidParameter(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $data = [
            'parameters' => [
                'first' => 'someValue',
                'non-existent' => null,
            ],
        ];
        $templateFile = $temp->getTmpFolder() . '/parameters.yml';
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        file_put_contents($templateFile, Yaml::dump($data));
        $component = new Component(new NullLogger());
        self::expectException(UserException::class);
        self::expectExceptionMessage('Parameter "' . getenv('TEST_NAMESPACE') . '/non-existent" was not found.');
        $component->run($templateFile, $targetFile, (string) getenv('TEST_NAMESPACE'), (string) getenv('TEST_REGION'));
    }

    public function testInvalidNamespace(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $data = [
            'parameters' => [
                'first' => 'someValue',
                'second' => null,
            ],
        ];
        $templateFile = $temp->getTmpFolder() . '/parameters.yml';
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        file_put_contents($templateFile, Yaml::dump($data));
        $component = new Component(new NullLogger());
        self::expectException(UserException::class);
        self::expectExceptionMessage(
            'Parameter name "second" or namespace "invalid/string" is invalid: Parameter name: can\'t be prefixed with'
        );
        $component->run($templateFile, $targetFile, 'invalid/string', (string) getenv('TEST_REGION'));
    }
}
