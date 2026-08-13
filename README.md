AnzuSerializer
============

---

A fast & light serializer bundle for symfony.

--- 
### Install

```
composer require anzusystems/serializer-bundle
```

### Usage

Simply inject `AnzuSystems\SerializerBundle\Serializer` via constructor, and then:

  ```php
// Serialize object or iterable to json:
  $this->serializer->serialize($dto);

// Deserialize json into object:
  $this->serializer->deserialize($json, SerializerTestDto::class);

// Deserialize json into array of objects:
  $this->serializer->deserialize($json, SerializerTestDto::class, []);

// Deserialize json into collection of objects:
  $this->serializer->deserialize($json, SerializerTestDto::class, new ArrayCollection());

```
Default format for `DateTimeInterface` objects (de)serialization can be changed:

```yaml
# config/packages/anzu_systems_serializer.yaml
anzu_systems_serializer:
  date_format: 'Y-m-d\TH:i:s.u\Z'
```

### Attributes

To be able to (de)serialize objects, the property (or method) of that object must have `AnzuSystems\SerializerBundle\Attributes\Serialize` attribute.

```php
    #[Serialize]
    private string $name;

    #[Serialize]
    private int $position;

    #[Serialize]
    private DummyDto $dummyDto;

    #[Serialize]
    private DateTimeImmutable $createdAt;

    // Custom date format used by `DateTime`. 
    #[Serialize(type: 'd.m.Y H:i:s')]
    private DateTimeImmutable $createdAtCustomFormat;

    // The valueObject must be an instance of `ValueObjectInterface`, to automatically (de)serialize.
    #[Serialize]
    private DummyValueObject $dummyValueObject;
    
    // The enum must be an instance of `EnumInterface`, to automatically (de)serialize.
    #[Serialize]
    private DummyEnum $dummyEnum;
    
    // Must be an instance of Symfony\Component\Uid\Uuid, to automatically (de)serialize.
    #[Serialize]
    private Uuid $docId;

    // Type (or discriminator map see below) must be provided for iterables in order to determine how to deserialize its items.
    #[Serialize(type: DummyDto::class)]
    private Collection $items;

    #[Serialize(type: DummyDto::class)]
    private array $itemsArray;

    // Serialize collection of entities as IDs ordered by position.
    #[Serialize(handler: EntityIdHandler::class, type: Author::class, orderBy: ['position' => Criteria::ASC])]
    protected Collection $authors;

    // Override type for deserialization based on provided "discriminator" field in json.
    #[Serialize(discriminatorMap: ['person' => Person::class, 'machine' => Machine::class])]
    private Collection $items;

    // Provide type via container parameter name. Example yaml config:
    // anzu_systems_serializer:
    //   parameter_bag:
    //     AnzuSystems\Contracts\Entity\AbstractUser: App\Entity\User
    #[Serialize(handler: EntityIdHandler::class, type: new ContainerParam(AbstractUser::class))]
    protected Collection $users;

    // (De)serialize a doctrine entity into/from IDs instead of (de)serializing whole object.
    #[Serialize(handler: EntityIdHandler::class)]
    private User $user;

    // Override the name of this property in json.
    #[Serialize(serializedName: 'stats')]
    private UserStats $decorated;

    // Serialize a virtual property (only serialization).
    #[Serialize]
    public function getViolations(): Collection
```

### Built-in handlers

- Auto-resolved handlers based on type:
    - `BasicHandler` (scalar values and null)
    - `DateTimeHandler` (date format configurable via settings)
    - `EnumHandler` (conversion between string and `EnumInterface`)
    - `ObjectHandler` (conversion of whole objects, i.e. embeds)
    - `UuidHandler` (conversion of Symfony Uuids)
- Custom handlers:
    - `EntityIdHandler` (conversion of IDs into entities and back)
    - `ArrayStringHandler` (CSV into array: `'1,2,3'` or `'a, b,c'` to `[1, 2, 3]` or `['a', 'b', 'c']`)

To force a specific handler (override the auto-resolved handler), just specify the handler in the `AnzuSerialize` attribute.

```php
#[Serialize(handler: ArrayStringHandler::class)]
private array $ids;
```

### Custom handler.

To create a custom handler, simply extend the `AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler`.

For instance in the following example a Geolocation class is converted to/from array:

```php
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;

final class GeolocationHandler extends AbstractHandler
{
    /**
     * @param Geolocation $value
     */
    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): string): array
    {
        return [
            'lat' => $value->getLatitude(),
            'lon' => $value->getLongitude(),
        ];
    }

    /**
     * @param array $value
     */
    public function deserialize(mixed $value, Metadata $metadata): Geolocation
    {
        return new Geolocation(
            (float) $value['lat'],
            (float) $value['lon'],
        );
    }
}
```

Then just force the handler to be used for the property via attribute:

```php
#[Serialize(handler: GeolocationHandler::class)]
private Geolocation $location;
```

In case you want always automatically all properties of the before-mentioned type `Geolocation` to be handled by the `GeolocationHandler` without forcing it via attribute, add following methods to the handler:
```php
    public static function supportsSerialize(mixed $value): bool
    {
        return $value instanceof Geolocation;
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return is_a($type, Geolocation::class, true) && is_array($value);
    }
```

In case you want multiple automatic handlers that can both support the same thing, you can set priority with which the handler will be chosen. 
In that case, add the following method (higher priority will be chosen first):
```php
public static function getPriority(): int
{
    return 3;
}
```
By default, all handlers have priority 0. Except:
`BasicHandler` has highest priority (10) - this handles simple scalar values, so generally you want it to be first.
`ObjectHandler` has lowest priority (-1) - this handles nested iterables/objects that no other handler supports.

### Batch handlers

A handler that resolves its value through I/O (a database lookup, an API call) would do it once per item of
a serialized collection, and once per property of a deserialized object. Two interfaces let it do the I/O once
for the whole batch instead: `BatchSerializeHandlerInterface` for the way out, `BatchDeserializeHandlerInterface`
for the way in.

#### Serialization

Implement `BatchSerializeHandlerInterface` and the serializer will hand you every value of the collection as a
`BatchItem` before it asks you to serialize the first one, so you can resolve them all at once:

```php
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchSerializeHandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

final class AuthorHandler extends AbstractHandler implements BatchSerializeHandlerInterface
{
    /** @var array<int, Author> */
    private array $authors = [];

    public function prepareSerializeBatch(SerializationContext $context, BatchItem ...$items): void
    {
        $ids = array_filter(array_map(static fn (BatchItem $item): mixed => $item->value, $items), 'is_int');
        foreach ($this->authorRepository->findByIds(array_diff($ids, array_keys($this->authors))) as $author) {
            $this->authors[$author->getId()] = $author;
        }
    }

    /**
     * @param int|null $value
     */
    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): ?array
    {
        // one query for the whole collection instead of one per item
    }
}
```

The handler is still forced on the property the usual way, `#[Serialize(handler: AuthorHandler::class)]`.
Serialization output is not affected in any way - preparing a batch only changes how many times the handler
has to go and fetch something.

Worth knowing before you rely on it:

- `prepareSerializeBatch()` is called **once per serialized collection**, with every value the handler owns across
  every item, each carrying the `Metadata` of the property it came from - so a handler parametrized by metadata
  (`customType`, `strategy`, `orderBy`) can group the items itself instead of being handed them pre-split.
- The values it reads are reused when serializing, so a getter behind a batched property is called once per item.
- A collection nested inside every item of another collection is prepared once per parent item.
- It may be called **several times per request** (a response can contain more than one collection), so it has
  to be idempotent - keep what you already resolved. Keep it in a store that is reset between runs, though:
  handlers are container singletons, so an unbounded map on the handler itself outlives the response in a
  worker.
- Only `array` and `Doctrine\Common\Collections\Collection` are prepared. A generator or a plain iterator is
  skipped, because traversing it twice would consume the data that is about to be serialized; such handlers
  fall back to resolving value by value.
- Only handlers forced via `#[Serialize(handler: ...)]` are prepared, not the automatically resolved ones.
- Values arrive in the order of the collection, duplicates and nulls included. Filtering is up to the handler.

#### Deserialization

Implement `BatchDeserializeHandlerInterface` and you get every property of the object about to be deserialized
that this handler is responsible for, each as a `BatchItem` carrying the raw value and its `Metadata`, before the
first `deserialize()` call:

```php
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchDeserializeHandlerInterface;

final class AuthorHandler extends AbstractHandler implements BatchDeserializeHandlerInterface
{
    public function prepareDeserializeBatch(BatchItem ...$items): void
    {
        // group by whatever the handler keys its lookups on, then one fetch per group
    }
}
```

The point is the grouping the handler itself controls: three properties pointing at the same target type share
one lookup, and a property holding a single value joins the same batch instead of costing a lookup of its own.

Worth knowing:

- The pass covers **one object at a time**, the properties present in the incoming data that this handler owns.
  A list payload (`deserialize($json, Foo::class, [])`) is prepared for the whole list first, so N items cost one
  batch, not N. Nested objects are prepared when their own turn comes, not together with their parent.
- Properties without a setter are skipped unless they are constructor arguments, matching what deserialization
  itself does.
- It is guaranteed to run **before the first `deserialize()` call** of that object, constructor arguments
  included, so a handler may treat it as the one place where it fetches - `EntityIdHandler` does exactly that
  and afterwards only reads Doctrine's identity map, which is what keeps an id that does not exist from costing
  a query of its own.

### Automatically generated API documentation via NelmioApiDocBundle 

Model describer will be automatically registered if [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) is present.
Symfony annotations are also supported/reflected in documentation. DocBlock titles are also added automatically as description for properties and methods.

In case you create a custom handler, you can override the generated description by adding the following method to the handler:
```php
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use OpenApi\Annotations\Property;

public function describe(string $property, Metadata $metadata): array
{
    $description = parent::describe($property, $metadata);
    $description['type'] = 'object';
    $description['title'] = 'Geolocation';
    $description['properties'] = [
        new Property([
            'property' => 'lon',
            'title' => 'Longitude',
            'type' => 'float',
            'minimum' => -180,
            'maximum' => 180,
        ]),
        new Property([
            'property' => 'lat',
            'title' => 'Latitude',
            'type' => 'float',
            'minimum' => -90,
            'maximum' => 90,
        ]),
    ];

    return $description;
}
```

Check out [Property](https://github.com/zircote/swagger-php/blob/master/src/Attributes/Property.php) attribute for a list of supported description configuration options.  
On top of that, you may want to add the `NESTED_CLASS` key to replace the description with a whole another classes' description:
```php
$description[SerializerModelDescriber::NESTED_CLASS] = 'App\Entity\User';
```
In case you want to define an array of particular objects, then:
```php
$description['items'][SerializerModelDescriber::NESTED_CLASS] = 'App\Entity\User';
```
It's best to have a look at the `AnzuSystems\SerializerBundle\Handler\Handlers` namespace for inspiration on how other handlers work.

### Caveats/requirements/features

- Iterables with keys will be automatically (de)serialized into an associative array or indexed collection.
- Currently, only json format is supported.
- Every property that you want to (de)serialize, must have a public getter and setter.
    - Setter name example for property $email: `setEmail`
    - Getter name example for property $email: `getEmail`
    - Getter name example for boolean properties: `isEnabled`
- Constructor of an object that you want to (de)serialize cannot have required parameters.
    - You can also use public static functions to instantiate an object if you want required parameters. For instance:

```php
public static function getInstance(Post $decorated): self
{
    return (new self())
        ->setDecorated($decorated)
    ;
}
```

- Use `SerializeParam` to convert request body into desired object. Example:
```php
#[Route('/topic', name: 'create', methods: [Request::METHOD_POST])]
public function create(#[SerializeParam] Topic $topic): JsonResponse
{
    return $this->createdResponse(
        $this->topicFacade->create($topic)
    );
}
```
