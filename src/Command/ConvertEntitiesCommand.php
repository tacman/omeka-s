<?php

namespace App\Command;

use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:convert:entities',
    description: 'Dump converted entity code without writing files',
)]
class ConvertEntitiesCommand
{
    /**
     * @var string[]
     */
    private const DOCTRINE_ANNOTATIONS = [
        'Entity',
        'Table',
        'Column',
        'Id',
        'GeneratedValue',
        'SequenceGenerator',
        'Index',
        'UniqueConstraint',
        'OneToOne',
        'OneToMany',
        'ManyToMany',
        'ManyToOne',
        'JoinTable',
        'JoinColumn',
        'JoinColumns',
        'OrderBy',
        'Embeddable',
        'Embedded',
        'MappedSuperclass',
        'InheritanceType',
        'DiscriminatorColumn',
        'DiscriminatorMap',
        'Version',
        'ChangeTrackingPolicy',
        'HasLifecycleCallbacks',
        'PostLoad',
        'PostPersist',
        'PostRemove',
        'PostUpdate',
        'PreFlush',
        'PrePersist',
        'PreRemove',
        'PreUpdate',
        'Cache',
        'EntityListeners',
        'AssociationOverrides',
        'AssociationOverride',
        'AttributeOverrides',
        'AttributeOverride',
    ];

    /** @var string[] */
    private const CLASS_ARGUMENT_NAMES = [
        'targetEntity',
        'targetDocument',
        'repositoryClass',
        'entityClass',
    ];

    /** @var array<string, string> */
    private const DOCTRINE_TYPE_VALUE_MAP = [
        'date' => 'DATE_MUTABLE',
        'datetime' => 'DATETIME_MUTABLE',
        'datetimetz' => 'DATETIMETZ_MUTABLE',
        'time' => 'TIME_MUTABLE',
    ];

    /** @var string[] */
    private const DOCTRINE_TYPE_CONSTANTS = [
        'ASCII_STRING',
        'BIGINT',
        'BINARY',
        'BLOB',
        'BOOLEAN',
        'DATE_MUTABLE',
        'DATE_IMMUTABLE',
        'DATEINTERVAL',
        'DATETIME_MUTABLE',
        'DATETIME_IMMUTABLE',
        'DATETIMETZ_MUTABLE',
        'DATETIMETZ_IMMUTABLE',
        'DECIMAL',
        'NUMBER',
        'FLOAT',
        'ENUM',
        'GUID',
        'INTEGER',
        'JSON',
        'JSON_OBJECT',
        'JSONB',
        'JSONB_OBJECT',
        'SIMPLE_ARRAY',
        'SMALLFLOAT',
        'SMALLINT',
        'STRING',
        'TEXT',
        'TIME_MUTABLE',
        'TIME_IMMUTABLE',
    ];

    private ?string $currentNamespace = null;
    private ?\Nette\PhpGenerator\PhpNamespace $currentPhpNamespace = null;

    /** @var array<string, string> */
    private array $currentUseMap = [];

    /** @var array<string, string> */
    private array $currentUseAliases = [];

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Path to entity directory or a single file')]
        ?string $path = null,
        #[Option('Process a single file within the entity directory')]
        ?string $single = null,
    ): int
    {
        $projectRoot = dirname(__DIR__, 2);
        $path = $path ?: $projectRoot . '/application/src/Entity';
        $targetPath = $single ? rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $single : $path;

        if (!file_exists($targetPath)) {
            $io->error(sprintf('Path not found: %s', $targetPath));
            return Command::FAILURE;
        }

        $files = $this->collectPhpFiles($targetPath);
        if ($files === []) {
            $io->warning('No PHP files found to process.');
            return Command::SUCCESS;
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $printer = new Standard();
        $psrPrinter = new PsrPrinter();

        foreach ($files as $filePath) {
            $code = file_get_contents($filePath);
            if ($code === false) {
                $io->warning(sprintf('Skipping unreadable file: %s', $filePath));
                continue;
            }

            $ast = $parser->parse($code);
            if ($ast === null) {
                $io->warning(sprintf('Skipping unparsable file: %s', $filePath));
                continue;
            }

            $result = $this->convertFileAst($ast, $printer);
            if ($result === null) {
                $io->warning(sprintf('No class found in file: %s', $filePath));
                continue;
            }

            [$phpFile, $className] = $result;
            $io->writeln(sprintf('\n//// %s (%s)', $filePath, $className));
            $io->writeln($psrPrinter->printFile($phpFile));
        }

        $io->success('Dump complete. No files were written.');

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function collectPhpFiles(string $path): array
    {
        if (is_file($path)) {
            return str_ends_with($path, '.php') ? [$path] : [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param Node[] $ast
     * @return array{PhpFile, string}|null
     */
    private function convertFileAst(array $ast, Standard $printer): ?array
    {
        $namespace = null;
        $classNode = null;
        $useStatements = [];

        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                $namespace = $node->name?->toString();
                $useStatements = $this->collectUseStatements($node->stmts);
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Class_) {
                        $classNode = $stmt;
                        break 2;
                    }
                }
            }

            if ($node instanceof Node\Stmt\Class_) {
                $classNode = $node;
                break;
            }
        }

        if ($namespace === null) {
            $useStatements = $this->collectUseStatements($ast);
        }

        $this->setCurrentContext($namespace, $useStatements);

        if (!$classNode) {
            return null;
        }

        $phpFile = new PhpFile();
        $phpFile->setStrictTypes(true);
        $phpNamespace = $namespace ? $phpFile->addNamespace($namespace) : $phpFile->addNamespace('');
        foreach ($useStatements as $useStatement) {
            $phpNamespace->addUse($useStatement['name'], $useStatement['alias']);
        }
        $phpNamespace->addUse('Doctrine\\ORM\\Mapping', 'ORM');

        $this->currentPhpNamespace = $phpNamespace;

        $className = $classNode->name?->toString() ?? 'AnonymousClass';
        $class = $phpNamespace->addClass($className);

        if ($classNode->isAbstract()) {
            $class->setAbstract();
        }
        if ($classNode->isFinal()) {
            $class->setFinal();
        }
        if ($classNode->extends) {
            $class->setExtends($this->resolveClassName($classNode->extends->toString()));
        }
        if ($classNode->implements) {
            $class->setImplements(array_map(fn (Node\Name $name): string => $this->resolveClassName($name->toString()), $classNode->implements));
        }
        $this->applyCommentAndAttributes($class, $classNode->getDocComment());

        foreach ($classNode->getConstants() as $constNode) {
            $this->addConstants($class, $constNode, $printer);
        }

        foreach ($classNode->getProperties() as $propertyNode) {
            $this->addProperties($class, $propertyNode, $printer);
        }

        foreach ($classNode->getMethods() as $methodNode) {
            $this->addMethod($class, $methodNode, $printer);
        }

        return [$phpFile, $className];
    }

    /**
     * @param Node[] $statements
     * @return array<int, array{name: string, alias: ?string}>
     */
    private function collectUseStatements(array $statements): array
    {
        $uses = [];
        foreach ($statements as $statement) {
            if ($statement instanceof Node\Stmt\Use_) {
                foreach ($statement->uses as $use) {
                    $uses[] = [
                        'name' => $use->name->toString(),
                        'alias' => $use->alias?->toString(),
                    ];
                }
            }

            if ($statement instanceof Node\Stmt\GroupUse) {
                $prefix = $statement->prefix->toString();
                foreach ($statement->uses as $use) {
                    $uses[] = [
                        'name' => $prefix . '\\' . $use->name->toString(),
                        'alias' => $use->alias?->toString(),
                    ];
                }
            }
        }

        return $uses;
    }

    private function addConstants(\Nette\PhpGenerator\ClassType $class, ClassConst $constNode, Standard $printer): void
    {
        $visibility = 'public';
        if ($constNode->isPrivate()) {
            $visibility = 'private';
        } elseif ($constNode->isProtected()) {
            $visibility = 'protected';
        }

        foreach ($constNode->consts as $const) {
            $constant = $class->addConstant($const->name->toString(), $const->value->value);
            $constant->setVisibility($visibility);
            $constant->setValue(new Literal($printer->prettyPrintExpr($const->value)));
            $this->applyCommentAndAttributes($constant, $constNode->getDocComment());
        }
    }

    private function addProperties(\Nette\PhpGenerator\ClassType $class, Property $propertyNode, Standard $printer): void
    {
        $visibility = 'public';
        if ($propertyNode->isPrivate()) {
            $visibility = 'private';
        } elseif ($propertyNode->isProtected()) {
            $visibility = 'protected';
        }

        $docResult = $this->extractDoctrineAttributes($propertyNode->getDocComment());

        foreach ($propertyNode->props as $prop) {
            $property = $class->addProperty($prop->name->toString());
            $property->setVisibility($visibility);
            $property->setStatic($propertyNode->isStatic());

            if ($propertyNode->type instanceof Node) {
                $property->setType($this->stringifyType($propertyNode->type));
            }

            if ($prop->default) {
                $property->setValue(new Literal($printer->prettyPrintExpr($prop->default)));
            }

            if ($docResult['comment'] !== null) {
                $property->setComment($docResult['comment']);
            }
            foreach ($docResult['attributes'] as $attribute) {
                $property->addAttribute($attribute['name'], $attribute['args']);
            }
        }
    }

    private function addMethod(\Nette\PhpGenerator\ClassType $class, ClassMethod $methodNode, Standard $printer): void
    {
        $method = $class->addMethod($methodNode->name->toString());
        if ($methodNode->isPrivate()) {
            $method->setVisibility('private');
        } elseif ($methodNode->isProtected()) {
            $method->setVisibility('protected');
        } else {
            $method->setVisibility('public');
        }

        $method->setStatic($methodNode->isStatic());
        if ($methodNode->isFinal()) {
            $method->setFinal();
        }
        if ($methodNode->isAbstract()) {
            $method->setAbstract();
        }

        $this->applyCommentAndAttributes($method, $methodNode->getDocComment());

        foreach ($methodNode->params as $paramNode) {
            $param = $method->addParameter($paramNode->var->name);
            $param->setReference($paramNode->byRef);
            if ($paramNode->variadic) {
                $method->setVariadic();
            }
            if ($paramNode->type instanceof Node) {
                $param->setType($this->stringifyType($paramNode->type));
            }
            if ($paramNode->default) {
                $param->setDefaultValue(new Literal($printer->prettyPrintExpr($paramNode->default)));
            }
        }

        if ($methodNode->byRef) {
            $method->setReturnReference();
        }

        if ($methodNode->returnType instanceof Node) {
            $method->setReturnType($this->stringifyType($methodNode->returnType));
        }

        if ($methodNode->stmts === null) {
            $method->setBody('// @todo: body?');
            return;
        }

        $method->setBody($printer->prettyPrint($methodNode->stmts));
    }

    private function stringifyType(Node $type): string
    {
        if ($type instanceof Node\NullableType) {
            return '?' . $this->stringifyType($type->type);
        }

        if ($type instanceof Node\UnionType) {
            return implode('|', array_map(fn (Node $inner): string => $this->stringifyType($inner), $type->types));
        }

        if ($type instanceof Node\IntersectionType) {
            return implode('&', array_map(fn (Node $inner): string => $this->stringifyType($inner), $type->types));
        }

        if ($type instanceof Node\Identifier) {
            return $type->toString();
        }

        if ($type instanceof Node\Name) {
            return $this->resolveClassName($type->toString());
        }

        return (string) $type;
    }

    /**
     * @return array{comment: ?string, attributes: array<int, array{name: string, args: array}>}
     */
    private function extractDoctrineAttributes(?Doc $doc): array
    {
        if ($doc === null) {
            return ['comment' => null, 'attributes' => []];
        }

        $lines = preg_split('/\R/', $doc->getText()) ?: [];
        if ($lines === []) {
            return ['comment' => null, 'attributes' => []];
        }

        $lines = array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== '/**' && trim($line) !== '*/'));
        $normalized = [];
        $attributes = [];

        $total = count($lines);
        for ($i = 0; $i < $total; $i++) {
            $line = preg_replace('/^\s*\*\s?/', '', $lines[$i]);
            if ($line === null) {
                continue;
            }

            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '@')) {
                if (preg_match('/^@([A-Za-z_][A-Za-z0-9_\\\]*)/', $trimmed, $matches) === 1) {
                    $resolvedName = $this->resolveDoctrineAnnotationName($matches[1]);
                    if ($resolvedName !== null) {
                        $annotation = $trimmed;
                        $balance = substr_count($annotation, '(') - substr_count($annotation, ')');
                        while ($balance > 0 && $i + 1 < $total) {
                            $i++;
                            $nextLine = preg_replace('/^\s*\*\s?/', '', $lines[$i]);
                            if ($nextLine === null) {
                                $nextLine = '';
                            }
                            $annotation .= "\n" . ltrim($nextLine);
                            $balance += substr_count($nextLine, '(') - substr_count($nextLine, ')');
                        }

                        $attributes[] = $this->annotationToAttribute($annotation);
                        continue;
                    }
                }
            }

            $normalized[] = $line;
        }

        $comment = trim(implode("\n", $normalized));
        return [
            'comment' => $comment !== '' ? $comment : null,
            'attributes' => $attributes,
        ];
    }

    private function applyCommentAndAttributes(object $target, ?Doc $doc): void
    {
        $result = $this->extractDoctrineAttributes($doc);
        if ($result['comment'] !== null && method_exists($target, 'setComment')) {
            $target->setComment($result['comment']);
        }
        if (!empty($result['attributes']) && method_exists($target, 'addAttribute')) {
            foreach ($result['attributes'] as $attribute) {
                $target->addAttribute($attribute['name'], $attribute['args']);
            }
        }
    }

    private function resolveDoctrineAnnotationName(string $name): ?string
    {
        $name = ltrim($name, '\\');
        $short = str_contains($name, '\\') ? substr($name, strrpos($name, '\\') + 1) : $name;

        return in_array($short, self::DOCTRINE_ANNOTATIONS, true)
            ? 'Doctrine\\ORM\\Mapping\\' . $short
            : null;
    }

    /**
     * @param array<int, array{name: string, alias: ?string}> $useStatements
     */
    private function setCurrentContext(?string $namespace, array $useStatements): void
    {
        $this->currentNamespace = $namespace;
        $this->currentUseMap = [];
        $this->currentUseAliases = [];

        foreach ($useStatements as $useStatement) {
            $full = ltrim($useStatement['name'], '\\');
            $alias = $useStatement['alias'] ?? null;
            $pos = strrpos($full, '\\');
            $short = $alias ?: ($pos === false ? $full : substr($full, $pos + 1));
            $lower = strtolower($short);
            $this->currentUseMap[$lower] = $full;
            $this->currentUseAliases[$lower] = $short;
        }
    }

    private function resolveClassName(string $name): string
    {
        $trimmed = ltrim($name, '\\');
        if ($trimmed === '') {
            return $trimmed;
        }

        $lower = strtolower($trimmed);
        if (in_array($lower, ['self', 'parent', 'static'], true)) {
            return $trimmed;
        }

        $parts = explode('\\', $trimmed);
        $first = strtolower($parts[0]);
        if (isset($this->currentUseMap[$first])) {
            $base = $this->currentUseMap[$first];
            $rest = array_slice($parts, 1);
            return $rest ? $base . '\\' . implode('\\', $rest) : $base;
        }

        if (str_contains($trimmed, '\\')) {
            return $trimmed;
        }

        return $this->currentNamespace ? $this->currentNamespace . '\\' . $trimmed : $trimmed;
    }

    private function formatAnnotationArgumentValue(?string $name, string $value): Literal
    {
        $stringValue = $this->unquoteString($value);
        if ($name === 'type' && $stringValue !== null) {
            $constant = $this->mapDoctrineTypeConstant($stringValue);
            if ($constant !== null) {
                $this->ensureUseForClass('Doctrine\\DBAL\\Types\\Types');
                return new Literal('Types::' . $constant);
            }
        }
        if ($stringValue !== null && $this->shouldConvertToClassConstant($name, $stringValue)) {
            $className = $this->resolveClassName($stringValue);
            $this->ensureUseForClass($className);
            $className = $this->simplifyClassName($className);
            return new Literal($className . '::class');
        }

        return new Literal($value);
    }

    private function unquoteString(string $value): ?string
    {
        if (preg_match('/^"(.*)"$/s', $value, $matches) === 1) {
            return stripcslashes($matches[1]);
        }

        if (preg_match("/^'(.*)'$/s", $value, $matches) === 1) {
            return str_replace(["\\\\", "\\'"], ["\\", "'"], $matches[1]);
        }

        return null;
    }

    private function shouldConvertToClassConstant(?string $name, string $value): bool
    {
        if ($name !== null && in_array($name, self::CLASS_ARGUMENT_NAMES, true)) {
            return true;
        }

        return preg_match('/^[A-Z][A-Za-z0-9_\\\\]*$/', $value) === 1;
    }

    private function simplifyClassName(string $name): string
    {
        $trimmed = ltrim($name, '\\');
        if ($trimmed === '') {
            return $trimmed;
        }

        foreach ($this->currentUseMap as $alias => $full) {
            if (strcasecmp($full, $trimmed) === 0) {
                return $this->currentUseAliases[$alias] ?? $alias;
            }
        }

        if ($this->currentNamespace && str_starts_with($trimmed, $this->currentNamespace . '\\')) {
            return substr($trimmed, strlen($this->currentNamespace) + 1);
        }

        return $trimmed;
    }

    private function mapDoctrineTypeConstant(string $value): ?string
    {
        $normalized = strtolower($value);
        if (isset(self::DOCTRINE_TYPE_VALUE_MAP[$normalized])) {
            return self::DOCTRINE_TYPE_VALUE_MAP[$normalized];
        }

        $constant = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '_', $value) ?? $value);
        return in_array($constant, self::DOCTRINE_TYPE_CONSTANTS, true) ? $constant : null;
    }

    private function ensureUseForClass(string $name): void
    {
        $full = ltrim($name, '\\');
        if ($full === '' || $this->currentPhpNamespace === null) {
            return;
        }

        foreach ($this->currentUseMap as $alias => $used) {
            if (strcasecmp($used, $full) === 0) {
                return;
            }
        }

        $pos = strrpos($full, '\\');
        $short = $pos === false ? $full : substr($full, $pos + 1);
        $this->currentPhpNamespace->addUse($full);
        $lower = strtolower($short);
        $this->currentUseMap[$lower] = $full;
        $this->currentUseAliases[$lower] = $short;
    }

    /**
     * @return array{name: string, args: array}
     */
    private function annotationToAttribute(string $annotation): array
    {
        $annotation = trim($annotation);
        if (preg_match('/^@([A-Za-z_][A-Za-z0-9_\\\\]*)/s', $annotation, $matches) !== 1) {
            return ['name' => $annotation, 'args' => []];
        }

        $rawName = $matches[1];
        $resolvedName = $this->resolveDoctrineAnnotationName($rawName) ?? $rawName;
        $argsString = '';
        $parenPos = strpos($annotation, '(');
        if ($parenPos !== false) {
            $argsString = trim(substr($annotation, $parenPos + 1));
            if (str_ends_with($argsString, ')')) {
                $argsString = substr($argsString, 0, -1);
            }
        }

        return [
            'name' => $resolvedName,
            'args' => $this->parseAnnotationArguments($argsString),
        ];
    }

    /**
     * @return array<int|string, Literal>
     */
    private function parseAnnotationArguments(string $argsString): array
    {
        if ($argsString === '') {
            return [];
        }

        $converted = $this->convertAnnotationArgsToPhp($argsString);
        $parts = $this->splitArguments($converted);
        $args = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            [$name, $value] = $this->splitNamedArgument($part);
            if ($name !== null) {
                $args[$name] = $this->formatAnnotationArgumentValue($name, $value);
            } else {
                $args[] = $this->formatAnnotationArgumentValue(null, $part);
            }
        }

        return $args;
    }

    private function convertAnnotationArgsToPhp(string $args): string
    {
        $converted = preg_replace_callback('/@([A-Za-z_][A-Za-z0-9_\\\\]*)/', function (array $matches): string {
            $name = $matches[1];
            $resolved = $this->resolveDoctrineAnnotationName($name) ?? $name;
            return 'new ' . $resolved;
        }, $args);

        if ($converted === null) {
            $converted = $args;
        }

        $converted = preg_replace('/("([^"\\\\]|\\\\.)*"|\'([^\'\\\\]|\\\\.)*\')\s*=\s*/', '$1 => ', $converted) ?? $converted;
        $converted = preg_replace('/\b([A-Za-z_][A-Za-z0-9_]*)\s*=\s*/', '$1: ', $converted) ?? $converted;
        $converted = str_replace(['{', '}'], ['[', ']'], $converted);

        return $converted;
    }

    /**
     * @return string[]
     */
    private function splitArguments(string $args): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = null;
        $length = strlen($args);

        for ($i = 0; $i < $length; $i++) {
            $char = $args[$i];

            if ($inString !== null) {
                $current .= $char;
                if ($char === $inString && ($i === 0 || $args[$i - 1] !== '\\')) {
                    $inString = null;
                }
                continue;
            }

            if ($char === '"' || $char === '\'') {
                $inString = $char;
                $current .= $char;
                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private function splitNamedArgument(string $part): array
    {
        $depth = 0;
        $inString = null;
        $length = strlen($part);

        for ($i = 0; $i < $length; $i++) {
            $char = $part[$i];
            if ($inString !== null) {
                if ($char === $inString && ($i === 0 || $part[$i - 1] !== '\\')) {
                    $inString = null;
                }
                continue;
            }

            if ($char === '"' || $char === '\'') {
                $inString = $char;
                continue;
            }

            if ($char === '(' || $char === '[') {
                $depth++;
                continue;
            }

            if ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
                continue;
            }

            if ($char === ':' && $depth === 0) {
                $name = trim(substr($part, 0, $i));
                $value = trim(substr($part, $i + 1));
                if ($name !== '') {
                    return [$name, $value];
                }
                break;
            }
        }

        return [null, $part];
    }
}
