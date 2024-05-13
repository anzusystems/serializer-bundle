UPGRADE FROM 3.x to 4.0
=======================

Add parameter `SerializationContext $context` to method `serialize()` in all your serializer handlers.

Before:

```php
public function serialize(mixed $value, Metadata $metadata): mixed;
```

After:

```php
public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): mixed;
```
