UPGRADE FROM 6.x to 7.0
=======================

7.0 supports every PHP 8.4 property: `private`, `protected`, asymmetric visibility and property hooks. See
*Properties* in `README.md`.

## Check your `private(set)` and `protected(set)` properties

A `#[Serialize]` property that is `private(set)` or `protected(set)` and not promoted is now filled from the input
when the class is deserialized. 6.x skipped a `private(set)` property without an error and kept the value the object
already had. For a `protected(set)` property with its key in the input, 6.x threw a `SerializerException`.

Check each such property in a class that you deserialize from outside input (a request body, a message, a file). When
the input must not change it, serialize it through a getter method or a virtual property instead.

Before (6.x keeps `1` whatever the input says, 7.0 takes the value from the input):

```php
#[Serialize]
public private(set) int $schemaVersion = 1;
```

After (serialized, never written from the input):

```php
#[Serialize]
public int $schemaVersion {
    get => self::SCHEMA_VERSION;
}
```

## Check classes that inherit a `private` property

A `private` `#[Serialize]` property of a parent class is now in the output of the child class and is filled from the
input. 6.x dropped it without an error. Check every class that extends a class with such a property: its JSON gets new
keys. When the child must not expose the property, declare it again in the child without `#[Serialize]`, or stop
inheriting from that class.

## Check what your classes receive from outside

Invalid input now throws a `DeserializationException` (HTTP 422 through `SerializerExceptionHandler` in `common-bundle`)
that names the class and the property. 6.x turned it into another value without an error, or ended in a raw `Error`.

| Target type | Input | 6.x | 7.0 |
|---|---|---|---|
| `int`, `float` | text such as `"abc"` or `"12abc"` | `0` or `12` | exception |
| `int`, `float` | array | `0` or `1` | exception |
| `bool` | array | `false` | exception |
| `string` | array, or an object without `__toString()` | `"Array"` or `Error` | exception |
| `bool` | text such as `"maybe"`, a number other than 0 and 1 | `false` | exception |
| `array`, a class, a date, a UUID, an enum | `null` when the property is not nullable | `UnhandledMatchError` | exception |
| a date, a UUID, an enum | a value of the wrong type, `5` into a string backed enum | `TypeError` | exception |
| a UUID | text that is not a UUID | `InvalidArgumentException` | exception |
| a list with `ArrayStringHandler` of `int` or `float` | `"1,abc"` | `[1, 0]` | exception |

These stay valid and give the same result as in 6.x:

- `int` and `float` take a number, a numeric text, a `bool`, an empty text and `null` (`0`). A `float` into an `int` is
  still cut, so `12.7` gives `12`.
- `string` takes any scalar, `null` (`''`) and an object with `__toString()`.
- `bool` takes `true`, `false`, `0`, `1`, `"true"`, `"false"`, `"on"`, `"off"`, `"yes"`, `"no"`, an empty text and `null`
  (`false`).
- A UUID takes every form `Uuid::fromString()` reads, also outside the RFC variant and in base58 or base32. An empty text
  gives the nil UUID.

Check each class that reads data you do not produce yourself: other services, imports, AI output, old stored JSON. In
6.x a wrong value passed without notice. In 7.0 the whole deserialization of that object fails. Decide per place whether
to catch `DeserializationException`, or to widen the type of the property.

## Other fixes that change a result

- A union of scalars and `array`, for example `int|string`, keeps the type of the input. 6.x gave `"5"` for `5`. A union
  with a class, a custom `handler` or a custom `type` still goes through the handler of its first type.
- A `set` hook decides what the property takes: `set(string|array $value)` receives both, and `set(?string $value)`
  receives `null`. 6.x used the type of the property.
- A nullable constructor parameter that is not promoted, `?string $name`, accepts a value. 6.x threw
  `Unable to determine deserialization handler`.
- An int backed enum takes a numeric text, so it can be filled from a query string. 6.x threw a `TypeError`.
- The name taken from a method drops only a `get` or `is` prefix. 6.x removed any leading letters from `g`, `e`, `t`, `i`
  and `s`, so `size()` gave `ze` and `title()` gave `le`. A class that relied on such a name must set `serializedName`.
- `Unable to deserialize "x". Check type.` from a failed write is now `Unable to deserialize "Class::$x": <reason>`.

## Other changes

- Promoted properties are filled by the constructor and are not written again after it, so a `readonly` promoted
  property can be deserialized. A non-public promoted property with a `setX()` method is the exception: its setter
  still runs after the constructor, as in 6.x.
- A `readonly` property that is not promoted gets its value through a constructor parameter of the same name and type,
  where the parameter may also accept `null`, and the constructor must assign it. Without such a parameter it is filled from the input, unless the constructor has already set it or it is `private` or
  `protected` with a getter and no setter: then the input is ignored.
  6.x threw a `SerializerException` for `readonly`, and skipped `private(set) readonly`. In the API documentation it is
  `readOnly` when the class has a constructor without that parameter.
- A promoted parameter that is missing from the data, or `null` for a non-nullable type, gets its default value. A
  required parameter without a default still throws a `SerializerException` that names it when it is missing or `null`
  for a non-nullable type. `null` for a nullable parameter now reaches the constructor, where 6.x threw this exception.
  Constructor arguments are passed by name, so a variadic `...$rest` gets `['rest' => 'x']` where 6.x gave `['x']`.
- A `private` or `protected` property without a getter method is read directly instead of throwing
  `Getter method … not found`, and written by its `setX()` method when it has one, otherwise directly. A property with
  a getter keeps using its getter and setter.
- Getters and setters are looked up as public methods of the serialized class, so a child class can add them to a
  property of its parent. A `getX()` or `setX()` that is not public is not called.
- A virtual property with only a `get` hook is ignored on input and described as `readOnly` in the API documentation.
  A virtual property with only a `set` hook is left out of the output and described as `writeOnly`.
- A property promoted by a public constructor is no longer described as `readOnly` in the API documentation.
- A static `#[Serialize]` property throws a `SerializerException` when the metadata is built.
- `JsonSerializer` and `JsonDeserializer` take an optional third constructor argument, `PropertyAccessor`
  (default `new PropertyAccessor()`). Code that creates them by hand needs no change.
- `Metadata::$getterSetterStrategy` is replaced by `$getterIsMethod` and `$setterIsMethod`. New fields: `$declaringClass`,
  `$readable`, `$filledByConstructor`, `$readonlyNotFilledByConstructor`, `$readInClassScope` and `$writeInClassScope`, which
  say whether a direct read or write needs the scope of the declaring class, and `$scalarUnionTypes` with the types of a
  scalar union.
- Two properties with the same data name still end with the last one read, as in 6.x, so an inherited `protected` or
  `public` property wins over a property of the child class. Only a `private` property of a parent gives way to the child.
- The metadata cache key prefix changed from `anzu_serialz_` to `anzu_serialz_v7_`, so metadata cached by 6.x is not
  read.
