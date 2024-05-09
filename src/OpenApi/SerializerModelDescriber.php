<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\OpenApi;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\Annotations\SymfonyConstraintAnnotationReader;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

final class SerializerModelDescriber implements ModelDescriberInterface
{
    public const NESTED_CLASS = 'nested_object';

    private ?SymfonyConstraintAnnotationReader $symfonyConstraintAnnotationReader = null;

    public function __construct(
        private readonly MetadataRegistry $metadataRegistry,
        private readonly HandlerResolver $handlerResolver,
    ) {
    }

    public function supports(Model $model): bool
    {
        return false === empty($this->getMetadata($model));
    }

    /**
     * @throws SerializerException|ReflectionException
     */
    public function describe(Model $model, Schema $schema): void
    {
        $schema->type = Type::BUILTIN_TYPE_OBJECT;
        $properties = [];
        foreach ($this->getMetadata($model) as $propertyName => $metadata) {
            /** @var Metadata $metadata */
            $handler = $this->handlerResolver->getDescriptionHandler($propertyName, $metadata);
            $description = $handler->describe($propertyName, $metadata);

            // Describe nested object.
            if (isset($description[self::NESTED_CLASS])) {
                $nestedSchema = $this->describeNested($propertyName, $description[self::NESTED_CLASS]);
                if ($nestedSchema) {
                    $properties[] = $nestedSchema;

                    continue;
                }
            }
            $this->describeNestedItems($description);
            $property = new Property($description);

            // Describe symfony constraints and property docBlock description.
            if (null !== $metadata->property && null !== $model->getType()->getClassName()) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $propertyReflection = new ReflectionProperty($model->getType()->getClassName(), $metadata->property);
                $this->getSymfonyConstraintAnnotationReader()->updateProperty($propertyReflection, $property);
                $this->addDocBlockDescription($propertyReflection, $property);
            }

            // Method docBlock description.
            if (null === $metadata->setter && null !== $model->getType()->getClassName()) {
                /** @psalm-suppress ArgumentTypeCoercion */
                $methodReflection = new ReflectionMethod($model->getType()->getClassName(), $metadata->getter);
                $this->addDocBlockDescription($methodReflection, $property);
            }

            $properties[] = $property;
        }
        $schema->properties = $properties;
    }

    private function getSymfonyConstraintAnnotationReader(): SymfonyConstraintAnnotationReader
    {
        if (null === $this->symfonyConstraintAnnotationReader) {
            /** @psalm-suppress NullArgument */
            $this->symfonyConstraintAnnotationReader = new SymfonyConstraintAnnotationReader(null);
        }

        return $this->symfonyConstraintAnnotationReader;
    }

    private function addDocBlockDescription(ReflectionProperty|ReflectionMethod $reflection, Property $property): void
    {
        $docComment = $reflection->getDocComment();
        if (false !== $docComment) {
            $docComment = explode(PHP_EOL, $docComment);

            $firstKey = array_key_first($docComment);
            $lastKey = array_key_last($docComment);
            if ($firstKey && $lastKey) {
                unset($docComment[$firstKey], $docComment[$lastKey]);
            }

            $description = '';
            foreach ($docComment as $line) {
                $line = trim(ltrim($line, '* '));
                if ($line) {
                    if (str_starts_with($line, '@')) {
                        continue;
                    }
                    $description .= $line;

                    continue;
                }

                break;
            }
            if ($description) {
                $property->description = $description;
            }
        }
    }

    /**
     * @throws ReflectionException | SerializerException
     */
    private function describeNested(string $property, string $className): ?Property
    {
        $nestedModel = new Model(new Type(Type::BUILTIN_TYPE_OBJECT, class: $className));
        $nestedSchema = new Property([
            'property' => $property,
            'title' => SerializerHelper::getClassBaseName($className),
        ]);
        if ($this->supports($nestedModel)) {
            $this->describe($nestedModel, $nestedSchema);

            return $nestedSchema;
        }

        return null;
    }

    /**
     * @throws ReflectionException | SerializerException
     */
    private function describeNestedItems(array &$description): void
    {
        if (isset($description['items'][self::NESTED_CLASS])) {
            $nestedItems = new Items(['title' => SerializerHelper::getClassBaseName($description['items'][self::NESTED_CLASS])]);
            $nestedItemsModel = new Model(new Type(Type::BUILTIN_TYPE_OBJECT, class: $description['items'][self::NESTED_CLASS]));

            if ($this->supports($nestedItemsModel)) {
                $this->describe($nestedItemsModel, $nestedItems);
            }

            $description['items'] = $nestedItems;
        }
    }

    /**
     * @return array<string, Metadata>
     */
    private function getMetadata(Model $model): array
    {
        $className = $model->getType()->getClassName();
        if (null !== $className && '' !== $className && class_exists($className)) {
            try {
                return $this->metadataRegistry->get($className)->getAll();
            } catch (SerializerException) {
                return [];
            }
        }

        return [];
    }
}
