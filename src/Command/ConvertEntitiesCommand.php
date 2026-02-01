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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:convert:entities',
    description: 'Dump converted entity code without writing files',
)]
class ConvertEntitiesCommand extends Command
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

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to entity directory or a single file')
            ->addOption('single', null, InputOption::VALUE_OPTIONAL, 'Process a single file within the entity directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = dirname(__DIR__, 2);
        $path = $input->getArgument('path') ?: $projectRoot . '/application/src/Entity';
        $single = $input->getOption('single');
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

        $className = $classNode->name?->toString() ?? 'AnonymousClass';
        $class = $phpNamespace->addClass($className);

        if ($classNode->isAbstract()) {
            $class->setAbstract();
        }
        if ($classNode->isFinal()) {
            $class->setFinal();
        }
        if ($classNode->extends) {
            $class->setExtends($classNode->extends->toString());
        }
        if ($classNode->implements) {
            $class->setImplements(array_map(static fn (Node\Name $name): string => $name->toString(), $classNode->implements));
        }
        $classDoc = $this->normalizeDocComment($classNode->getDocComment());
        if ($classDoc !== null) {
            $class->setComment($classDoc);
        }

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
            $constant = $class->addConstant($const->name->toString());
            $constant->setVisibility($visibility);
            $constant->setValue(new Literal($printer->prettyPrintExpr($const->value)));
            $comment = $this->normalizeDocComment($constNode->getDocComment());
            if ($comment !== null) {
                $constant->setComment($comment);
            }
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

        $comment = $this->normalizeDocComment($propertyNode->getDocComment());

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

            if ($comment !== null) {
                $property->setComment($comment);
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

        $comment = $this->normalizeDocComment($methodNode->getDocComment());
        if ($comment !== null) {
            $method->setComment($comment);
        }

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
            $method->setBody(null);
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
            return $type->toString();
        }

        return (string) $type;
    }

    private function normalizeDocComment(?Doc $doc): ?string
    {
        if ($doc === null) {
            return null;
        }

        $lines = preg_split('/\R/', $doc->getText()) ?: [];
        if ($lines === []) {
            return null;
        }

        $lines = array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== '/**' && trim($line) !== '*/'));
        $normalized = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            if ($line === null) {
                continue;
            }
            $line = preg_replace_callback('/@([A-Za-z_][A-Za-z0-9_\\]*)/', function (array $matches): string {
                $name = $matches[1];
                if (str_contains($name, '\\')) {
                    return '@' . $name;
                }
                if (in_array($name, self::DOCTRINE_ANNOTATIONS, true)) {
                    return '@ORM\\' . $name;
                }
                return '@' . $name;
            }, $line);

            $normalized[] = $line;
        }

        $comment = trim(implode("\n", $normalized));
        return $comment !== '' ? $comment : null;
    }
}
