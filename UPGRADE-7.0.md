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

## Other changes

- Promoted properties are filled by the constructor and are not written again after it, so a `readonly` promoted
  property can be deserialized. A non-public promoted property with a `setX()` method is the exception: its setter
  still runs after the constructor, as in 6.x.
- A `readonly` property that is not promoted gets its value through a constructor parameter of the same name. Without
  such a parameter it is filled from the input, unless the constructor has already set it: then the input is ignored.
  6.x threw a `SerializerException` for `readonly`, and skipped `private(set) readonly`. In the API documentation it is
  `readOnly` when the class has a constructor without that parameter.
- A promoted parameter that is missing from the data, or `null` for a non-nullable type, gets its default value. A
  required parameter without a default still throws a `SerializerException` that names it when it is missing or `null`
  for a non-nullable type. `null` for a nullable parameter now reaches the constructor, where 6.x threw this exception.
  Constructor arguments are passed by name.
- A `private` or `protected` property without a getter method is read directly instead of throwing
  `Getter method … not found`, and written by its `setX()` method when it has one, otherwise directly. A property with
  a getter keeps using its getter and setter.
- Getters and setters are looked up as public methods of the serialized class, so a child class can add them to a
  property of its parent. A `getX()` or `setX()` that is not public is not called.
- A virtual property with only a `get` hook is ignored on input and described as `readOnly` in the API documentation.
  A virtual property with only a `set` hook is left out of the output and described as `writeOnly`.
- A promoted property is no longer described as `readOnly` in the API documentation.
- `JsonSerializer` and `JsonDeserializer` take an optional third constructor argument, `PropertyAccessor`
  (default `new PropertyAccessor()`). Code that creates them by hand needs no change.
- `Metadata::$getterSetterStrategy` is replaced by `$getterIsMethod` and `$setterIsMethod`.
- The metadata cache key prefix changed from `anzu_serialz_` to `anzu_serialz_v7_`, so metadata cached by 6.x is not
  read.
