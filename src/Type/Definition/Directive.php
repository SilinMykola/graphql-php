<?php

declare(strict_types=1);

namespace GraphQL\Type\Definition;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\DirectiveLocation;

use function array_key_exists;
use function is_array;

class Directive
{
    public const DEFAULT_DEPRECATION_REASON = 'No longer supported';

    public const INCLUDE_NAME         = 'include';
    public const IF_ARGUMENT_NAME     = 'if';
    public const SKIP_NAME            = 'skip';
    public const DEPRECATED_NAME      = 'deprecated';
    public const REASON_ARGUMENT_NAME = 'reason';

    /**
     * Lazily initialized.
     *
     * @var array<string, Directive>
     */
    protected static array $internalDirectives;

    // Schema Definitions

    public string $name;

    public ?string $description;

    /** @var array<int, FieldArgument> */
    public array $args = [];

    public bool $isRepeatable;

    /** @var array<string> */
    public array $locations;

    public ?DirectiveDefinitionNode $astNode;

    /** @var array<string, mixed> */
    public array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        if (! isset($config['name'])) {
            throw new InvariantViolation('Directive must be named.');
        }

        $this->name = $config['name'];

        $this->description = $config['description'] ?? null;

        if (isset($config['args'])) {
            foreach ($config['args'] as $name => $arg) {
                $this->args[] = is_array($arg)
                    ? new FieldArgument($arg + ['name' => $name])
                    : $arg;
            }
        }

        if (! isset($config['locations']) || ! is_array($config['locations'])) {
            throw new InvariantViolation('Must provide locations for directive.');
        }

        $this->locations = $config['locations'];

        $this->isRepeatable = $config['isRepeatable'] ?? false;
        $this->astNode      = $config['astNode'] ?? null;

        $this->config = $config;
    }

    public static function includeDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal['include'];
    }

    /**
     * @return array<string, Directive>
     */
    public static function getInternalDirectives(): array
    {
        return self::$internalDirectives ??= [
            'include'    => new self([
                'name'        => self::INCLUDE_NAME,
                'description' => 'Directs the executor to include this field or fragment only when the `if` argument is true.',
                'locations'   => [
                    DirectiveLocation::FIELD,
                    DirectiveLocation::FRAGMENT_SPREAD,
                    DirectiveLocation::INLINE_FRAGMENT,
                ],
                'args'        => [
                    new FieldArgument([
                        'name'        => self::IF_ARGUMENT_NAME,
                        'type'        => Type::nonNull(Type::boolean()),
                        'description' => 'Included when true.',
                    ]),
                ],
            ]),
            'skip'       => new self([
                'name'        => self::SKIP_NAME,
                'description' => 'Directs the executor to skip this field or fragment when the `if` argument is true.',
                'locations'   => [
                    DirectiveLocation::FIELD,
                    DirectiveLocation::FRAGMENT_SPREAD,
                    DirectiveLocation::INLINE_FRAGMENT,
                ],
                'args'        => [
                    new FieldArgument([
                        'name'        => self::IF_ARGUMENT_NAME,
                        'type'        => Type::nonNull(Type::boolean()),
                        'description' => 'Skipped when true.',
                    ]),
                ],
            ]),
            'deprecated' => new self([
                'name'        => self::DEPRECATED_NAME,
                'description' => 'Marks an element of a GraphQL schema as no longer supported.',
                'locations'   => [
                    DirectiveLocation::FIELD_DEFINITION,
                    DirectiveLocation::ENUM_VALUE,
                ],
                'args'        => [
                    new FieldArgument([
                        'name'         => self::REASON_ARGUMENT_NAME,
                        'type'         => Type::string(),
                        'description'  =>
                        'Explains why this element was deprecated, usually also including a ' .
                        'suggestion for how to access supported similar data. Formatted using ' .
                        'the Markdown syntax (as specified by [CommonMark](https://commonmark.org/).',
                        'defaultValue' => self::DEFAULT_DEPRECATION_REASON,
                    ]),
                ],
            ]),
        ];
    }

    public static function skipDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal['skip'];
    }

    public static function deprecatedDirective(): Directive
    {
        $internal = self::getInternalDirectives();

        return $internal['deprecated'];
    }

    public static function isSpecifiedDirective(Directive $directive): bool
    {
        return array_key_exists($directive->name, self::getInternalDirectives());
    }
}
