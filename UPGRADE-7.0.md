UPGRADE FROM 6.x to 7.0
=======================

7.0 supports every PHP 8.4 property: `private`, `protected`, asymmetric visibility and property hooks. See
*Properties* in `README.md`.

## Check your `private(set)` and `protected(set)` properties

A `#[Serialize]` property that is `private(set)` or `protected(set)` and not promoted is now filled from the input
when the class is deserialized. 6.x skipped it without an error and kept the value the object already had.

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

## Other changes

- Promoted properties are filled by the constructor only and are not written again after it, so a `readonly` promoted
  property can be deserialized.
- A promoted parameter that is missing from the data, or `null` for a non-nullable type, gets its default value. A
  required parameter without a default still throws a `SerializerException` that names it. Constructor arguments are
  passed by name.
- A `private` or `protected` property without a getter method is read and written directly instead of throwing
  `Getter method … not found`. A property with a getter keeps using its getter and setter.
- A virtual property with only a `get` hook is ignored on input and described as `readOnly` in the API documentation.
  A virtual property with only a `set` hook is left out of the output and described as `writeOnly`.
- A promoted property is no longer described as `readOnly` in the API documentation.
- `JsonSerializer` and `JsonDeserializer` take an optional third constructor argument, `PropertyAccessor`
  (default `new PropertyAccessor()`). Code that creates them by hand needs no change.
- The metadata cache key prefix changed from `anzu_serialz_` to `anzu_serialz_v7_`, so metadata cached by 6.x is not
  read.
