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

    // Type (or discriminator map see below) must be provided for iterables in order to determine how to deserialize its items.
    #[Serialize(type: DummyDto::class)]
    private Collection $items;

    #[Serialize(type: DummyDto::class)]
    private array $itemsArray;

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
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;

final class GeolocationHandler extends AbstractHandler
{
    /**
     * @param Geolocation $value
     */
    public function serialize(mixed $value, Metadata $metadata): array
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
