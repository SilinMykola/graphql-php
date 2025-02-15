<?php

declare(strict_types=1);

namespace GraphQL\Server;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use GraphQL\Utils\Utils;
use GraphQL\Validator\Rules\ValidationRule;

use function is_array;
use function is_callable;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * Server configuration class.
 * Could be passed directly to server constructor. List of options accepted by **create** method is
 * [described in docs](executing-queries.md#server-configuration-options).
 *
 * Usage example:
 *
 *     $config = GraphQL\Server\ServerConfig::create()
 *         ->setSchema($mySchema)
 *         ->setContext($myContext);
 *
 *     $server = new GraphQL\Server\StandardServer($config);
 *
 * @phpstan-type PersistedQueryLoader callable(string $queryId, OperationParams $operation): (string|DocumentNode)
 * @phpstan-type RootValueResolver callable(OperationParams $operation, DocumentNode $doc, string $operationType): mixed
 * @phpstan-type ValidationRulesOption array<ValidationRule>|(callable(OperationParams $operation, DocumentNode $doc, string $operationType): array<ValidationRule>)|null
 */
class ServerConfig
{
    /**
     * Converts an array of options to instance of ServerConfig
     * (or just returns empty config when array is not passed).
     *
     * @param array<string, mixed> $config
     *
     * @api
     */
    public static function create(array $config = []): self
    {
        $instance = new static();
        foreach ($config as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (! method_exists($instance, $method)) {
                throw new InvariantViolation(sprintf('Unknown server config option "%s"', $key));
            }

            $instance->$method($value);
        }

        return $instance;
    }

    private ?Schema $schema = null;

    /** @var mixed|callable(self $config, OperationParams $params, DocumentNode $doc): mixed|null */
    private $context = null;

    /**
     * @var mixed|callable
     * @phpstan-var mixed|RootValueResolver
     */
    private $rootValue = null;

    /** @var callable|null */
    private $errorFormatter = null;

    /** @var callable|null */
    private $errorsHandler = null;

    private int $debugFlag = DebugFlag::NONE;

    private bool $queryBatching = false;

    /**
     * @var array<ValidationRule>|callable|null
     * @phpstan-var ValidationRulesOption
     */
    private $validationRules = null;

    /** @var callable|null */
    private $fieldResolver = null;

    private ?PromiseAdapter $promiseAdapter = null;

    /**
     * @var callable|null
     * @phpstan-var PersistedQueryLoader|null
     */
    private $persistedQueryLoader = null;

    /**
     * @api
     */
    public function setSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @param mixed|callable $context
     *
     * @api
     */
    public function setContext($context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param mixed|callable $rootValue
     * @phpstan-param mixed|RootValueResolver $rootValue
     *
     * @api
     */
    public function setRootValue($rootValue): self
    {
        $this->rootValue = $rootValue;

        return $this;
    }

    /**
     * @param callable(Error): array<string, mixed> $errorFormatter
     *
     * @api
     */
    public function setErrorFormatter(callable $errorFormatter): self
    {
        $this->errorFormatter = $errorFormatter;

        return $this;
    }

    /**
     * @param callable(array<int, Error> $errors, callable(Error): array<string, mixed> $formatter): array<int, array<string, mixed>> $handler
     *
     * @api
     */
    public function setErrorsHandler(callable $handler): self
    {
        $this->errorsHandler = $handler;

        return $this;
    }

    /**
     * Set validation rules for this server.
     *
     * @param array<ValidationRule>|callable|null $validationRules
     * @phpstan-param ValidationRulesOption $validationRules
     *
     * @api
     */
    public function setValidationRules($validationRules): self
    {
        // @phpstan-ignore-next-line necessary until we can use proper union types
        if (! is_array($validationRules) && ! is_callable($validationRules) && $validationRules !== null) {
            $invalidValidationRules = Utils::printSafe($validationRules);

            throw new InvariantViolation("Server config expects array of validation rules or callable returning such array, but got {$invalidValidationRules}");
        }

        $this->validationRules = $validationRules;

        return $this;
    }

    /**
     * @api
     */
    public function setFieldResolver(callable $fieldResolver): self
    {
        $this->fieldResolver = $fieldResolver;

        return $this;
    }

    /**
     * @phpstan-param PersistedQueryLoader|null $persistedQueryLoader
     *
     * @api
     */
    public function setPersistedQueryLoader(?callable $persistedQueryLoader): self
    {
        $this->persistedQueryLoader = $persistedQueryLoader;

        return $this;
    }

    /**
     * Set response debug flags.
     *
     * @see \GraphQL\Error\DebugFlag class for a list of all available flags
     *
     * @api
     */
    public function setDebugFlag(int $debugFlag = DebugFlag::INCLUDE_DEBUG_MESSAGE): self
    {
        $this->debugFlag = $debugFlag;

        return $this;
    }

    /**
     * Allow batching queries (disabled by default).
     *
     * @api
     */
    public function setQueryBatching(bool $enableBatching): self
    {
        $this->queryBatching = $enableBatching;

        return $this;
    }

    /**
     * @api
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter): self
    {
        $this->promiseAdapter = $promiseAdapter;

        return $this;
    }

    /**
     * @return mixed|callable
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return mixed|callable
     * @phpstan-return mixed|RootValueResolver
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    public function getSchema(): ?Schema
    {
        return $this->schema;
    }

    public function getErrorFormatter(): ?callable
    {
        return $this->errorFormatter;
    }

    public function getErrorsHandler(): ?callable
    {
        return $this->errorsHandler;
    }

    public function getPromiseAdapter(): ?PromiseAdapter
    {
        return $this->promiseAdapter;
    }

    /**
     * @return array<ValidationRule>|callable|null
     * @phpstan-return ValidationRulesOption
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    public function getFieldResolver(): ?callable
    {
        return $this->fieldResolver;
    }

    /**
     * @phpstan-return PersistedQueryLoader|null
     */
    public function getPersistedQueryLoader(): ?callable
    {
        return $this->persistedQueryLoader;
    }

    public function getDebugFlag(): int
    {
        return $this->debugFlag;
    }

    public function getQueryBatching(): bool
    {
        return $this->queryBatching;
    }
}
