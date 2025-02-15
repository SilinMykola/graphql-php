<?php

declare(strict_types=1);

namespace GraphQL\Tests\Server;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use stdClass;

class ServerConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = ServerConfig::create();
        self::assertNull($config->getSchema());
        self::assertNull($config->getContext());
        self::assertNull($config->getRootValue());
        self::assertNull($config->getErrorFormatter());
        self::assertNull($config->getErrorsHandler());
        self::assertNull($config->getPromiseAdapter());
        self::assertNull($config->getValidationRules());
        self::assertNull($config->getFieldResolver());
        self::assertNull($config->getPersistedQueryLoader());
        self::assertSame(DebugFlag::NONE, $config->getDebugFlag());
        self::assertFalse($config->getQueryBatching());
    }

    public function testAllowsSettingSchema(): void
    {
        $schema = new Schema(['query' => new ObjectType(['name' => 'a', 'fields' => []])]);
        $config = ServerConfig::create()
            ->setSchema($schema);

        self::assertSame($schema, $config->getSchema());

        $schema2 = new Schema(['query' => new ObjectType(['name' => 'a', 'fields' => []])]);
        $config->setSchema($schema2);
        self::assertSame($schema2, $config->getSchema());
    }

    public function testAllowsSettingContext(): void
    {
        $config = ServerConfig::create();

        $context = [];
        $config->setContext($context);
        self::assertSame($context, $config->getContext());

        $context2 = new stdClass();
        $config->setContext($context2);
        self::assertSame($context2, $config->getContext());
    }

    public function testAllowsSettingRootValue(): void
    {
        $config = ServerConfig::create();

        $rootValue = [];
        $config->setRootValue($rootValue);
        self::assertSame($rootValue, $config->getRootValue());

        $context2 = new stdClass();
        $config->setRootValue($context2);
        self::assertSame($context2, $config->getRootValue());
    }

    public function testAllowsSettingErrorFormatter(): void
    {
        $config = ServerConfig::create();

        $formatter = static function (): void {
        };
        $config->setErrorFormatter($formatter);
        self::assertSame($formatter, $config->getErrorFormatter());

        $formatter = 'date'; // test for callable
        $config->setErrorFormatter($formatter);
        self::assertSame($formatter, $config->getErrorFormatter());
    }

    public function testAllowsSettingErrorsHandler(): void
    {
        $config = ServerConfig::create();

        $handler = static function (): void {
        };
        $config->setErrorsHandler($handler);
        self::assertSame($handler, $config->getErrorsHandler());

        $handler = 'date'; // test for callable
        $config->setErrorsHandler($handler);
        self::assertSame($handler, $config->getErrorsHandler());
    }

    public function testAllowsSettingPromiseAdapter(): void
    {
        $config = ServerConfig::create();

        $adapter1 = new SyncPromiseAdapter();
        $config->setPromiseAdapter($adapter1);
        self::assertSame($adapter1, $config->getPromiseAdapter());

        $adapter2 = new SyncPromiseAdapter();
        $config->setPromiseAdapter($adapter2);
        self::assertSame($adapter2, $config->getPromiseAdapter());
    }

    public function testAllowsSettingValidationRules(): void
    {
        $config = ServerConfig::create();

        $rules = [];
        $config->setValidationRules($rules);
        self::assertSame($rules, $config->getValidationRules());

        $rules = [
            static function (): void {
            },
        ];
        $config->setValidationRules($rules);
        self::assertSame($rules, $config->getValidationRules());

        $rules = static function (): array {
            return [
                static function (): void {
                },
            ];
        };
        $config->setValidationRules($rules);
        self::assertSame($rules, $config->getValidationRules());
    }

    public function testAllowsSettingDefaultFieldResolver(): void
    {
        $config = ServerConfig::create();

        $resolver = static function (): void {
        };
        $config->setFieldResolver($resolver);
        self::assertSame($resolver, $config->getFieldResolver());

        $resolver = 'date'; // test for callable
        $config->setFieldResolver($resolver);
        self::assertSame($resolver, $config->getFieldResolver());
    }

    public function testAllowsSettingPersistedQueryLoader(): void
    {
        $config = ServerConfig::create();

        $loader = static function (): void {
        };
        $config->setPersistedQueryLoader($loader);
        self::assertSame($loader, $config->getPersistedQueryLoader());

        $loader = 'date'; // test for callable
        $config->setPersistedQueryLoader($loader);
        self::assertSame($loader, $config->getPersistedQueryLoader());
    }

    public function testAllowsSettingCatchPhpErrors(): void
    {
        $config = ServerConfig::create();

        $config->setDebugFlag(DebugFlag::INCLUDE_DEBUG_MESSAGE);
        self::assertEquals(DebugFlag::INCLUDE_DEBUG_MESSAGE, $config->getDebugFlag());

        $config->setDebugFlag(DebugFlag::NONE);
        self::assertEquals(DebugFlag::NONE, $config->getDebugFlag());
    }

    public function testAcceptsArray(): void
    {
        $arr = [
            'schema'                => new Schema([
                'query' => new ObjectType(['name' => 't', 'fields' => ['a' => Type::string()]]),
            ]),
            'context'               => new stdClass(),
            'rootValue'             => new stdClass(),
            'errorFormatter'        => static function (): void {
            },
            'promiseAdapter'        => new SyncPromiseAdapter(),
            'validationRules'       => static function (): void {
            },
            'fieldResolver'         => static function (): void {
            },
            'persistedQueryLoader' => static function (): void {
            },
            'debugFlag'             => DebugFlag::INCLUDE_DEBUG_MESSAGE,
            'queryBatching'         => true,
        ];

        $config = ServerConfig::create($arr);

        self::assertSame($arr['schema'], $config->getSchema());
        self::assertSame($arr['context'], $config->getContext());
        self::assertSame($arr['rootValue'], $config->getRootValue());
        self::assertSame($arr['errorFormatter'], $config->getErrorFormatter());
        self::assertSame($arr['promiseAdapter'], $config->getPromiseAdapter());
        self::assertSame($arr['validationRules'], $config->getValidationRules());
        self::assertSame($arr['fieldResolver'], $config->getFieldResolver());
        self::assertSame($arr['persistedQueryLoader'], $config->getPersistedQueryLoader());
        self::assertSame(DebugFlag::INCLUDE_DEBUG_MESSAGE, $config->getDebugFlag());
        self::assertTrue($config->getQueryBatching());
    }

    public function testThrowsOnInvalidArrayKey(): void
    {
        $arr = ['missingKey' => 'value'];

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Unknown server config option "missingKey"');

        ServerConfig::create($arr);
    }

    public function testInvalidValidationRules(): void
    {
        $rules  = new stdClass();
        $config = ServerConfig::create();

        $this->expectException(InvariantViolation::class);
        $this->expectExceptionMessage('Server config expects array of validation rules or callable returning such array, but got instance of stdClass');

        $config->setValidationRules($rules);
    }
}
