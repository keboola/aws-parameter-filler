<?php

declare(strict_types=1);

namespace Keboola\AwsParameterFiller;

use Keboola\Component\UserException;
use Keboola\Temp\Temp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class ComponentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (empty(getenv('TEST_NAMESPACE')) || empty(getenv('TEST_REGION'))) {
            throw new \Exception('Environment variable TEST_NAMESPACE and TEST_REGION must be set.');
        }
    }

    public function testRun(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        $component = new Component(new NullLogger());
        $component->run($targetFile, (string) getenv('TEST_NAMESPACE'), (string) getenv('TEST_REGION'));
        $dotEnv = new Dotenv();
        $dotEnv->load($targetFile);
        self::assertEquals($_ENV['pin'], 'First shall thou take out the Holy Pin');
        self::assertEquals($_ENV['then'], 'Then shall thou count to three, no more, no less');
        self::assertEquals($_ENV['five'], 'Five is right out!');
        self::assertEquals(
            $_ENV['number'],
            'Three shall be number thou shalt count, and the number of the counting shall be three'
        );
        self::assertEquals('Five is right out.', $_ENV['numberfive']);
        self::assertEquals('Four shalt thou not count.', $_ENV['numberfour']);
        self::assertEquals('Neither count thou two, excepting that thou then proceed to three.', $_ENV['numbertwo']);
        self::assertEquals('One', $_ENV['one']);
        self::assertEquals('Two', $_ENV['two']);
        self::assertEquals('Five', $_ENV['three']);
        self::assertEquals('Three, Sir', $_ENV['threesir']);
        self::assertEquals('SuperSecretValue', $_ENV['six']);
        $vars = explode(',', $_ENV['SYMFONY_DOTENV_VARS']);
        sort($vars);
        self::assertEquals(
            ['five', 'number', 'numberfive', 'numberfour',
                'numbertwo', 'one', 'pin', 'six', 'then', 'three', 'threesir', 'two'],
            $vars
        );
    }

    public function testRunAppend(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        file_put_contents($targetFile, "a=b\nc=d\n");
        $component = new Component(new NullLogger());
        $component->run($targetFile, (string) getenv('TEST_NAMESPACE'), (string) getenv('TEST_REGION'));
        $dotEnv = new Dotenv();
        $dotEnv->load($targetFile);
        self::assertEquals($_ENV['pin'], 'First shall thou take out the Holy Pin');
        self::assertEquals($_ENV['then'], 'Then shall thou count to three, no more, no less');
        self::assertEquals($_ENV['five'], 'Five is right out!');
        self::assertEquals(
            $_ENV['number'],
            'Three shall be number thou shalt count, and the number of the counting shall be three'
        );
        self::assertEquals('Five is right out.', $_ENV['numberfive']);
        self::assertEquals('Four shalt thou not count.', $_ENV['numberfour']);
        self::assertEquals('Neither count thou two, excepting that thou then proceed to three.', $_ENV['numbertwo']);
        self::assertEquals('One', $_ENV['one']);
        self::assertEquals('Two', $_ENV['two']);
        self::assertEquals('Five', $_ENV['three']);
        self::assertEquals('Three, Sir', $_ENV['threesir']);
        self::assertEquals('SuperSecretValue', $_ENV['six']);
        self::assertEquals('b', $_ENV['a']);
        self::assertEquals('d', $_ENV['c']);
        $vars = explode(',', $_ENV['SYMFONY_DOTENV_VARS']);
        sort($vars);
        self::assertEquals(
            ['a', 'c', 'five', 'number', 'numberfive', 'numberfour',
                'numbertwo', 'one', 'pin', 'six', 'then', 'three', 'threesir', 'two'],
            $vars
        );
    }

    public function testAccessDenied(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        $component = new Component(new NullLogger());
        self::expectException(UserException::class);
        self::expectExceptionMessage('Access denied to namespace "/keboola/non-existent/".');
        $component->run($targetFile, '/keboola/non-existent/', (string) getenv('TEST_REGION'));
    }

    public function testInvalidNamespace(): void
    {
        $temp = new Temp('filler-test');
        $temp->initRunFolder();
        $component = new Component(new NullLogger());
        self::expectException(UserException::class);
        self::expectExceptionMessage(
            'Invalid namespace "invalid/string". Namespace argument must start end and with a slash'
        );
        $targetFile = $temp->getTmpFolder() . '/' . uniqid('target');
        $component->run($targetFile, 'invalid/string', (string) getenv('TEST_REGION'));
    }

    public function testInvalidFile(): void
    {
        $component = new Component(new NullLogger());
        self::expectException(UserException::class);
        self::expectExceptionMessage(
            'File "php://stdin" is not writable.'
        );
        $component->run('php://stdin', 'File "php://stdin" is not writable.', (string) getenv('TEST_REGION'));
    }
}
