UPGRADE FROM 7.0 to 7.1
=======================

The bundle no longer requires `doctrine/common`. Doctrine marked the package abandoned
([doctrine/common@972a378](https://github.com/doctrine/common/commit/972a37830274eefaa6f7aa98096a7e413dc7662d)), and
`composer audit` of Composer 2.10 fails on an abandoned package. The bundle used only `ClassUtils::getRealClass()` from
it.

## Use native lazy objects

Doctrine recommends native lazy objects on PHP 8.4, and in ORM 4 they become the only way of lazy loading
([ORM documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/advanced-configuration.html#native-lazy-objects-optional),
[ORM 3.5 upgrade note](https://github.com/doctrine/orm/blob/3.5.0/UPGRADE.md#deprecate-not-using-native-lazy-objects-on-php-84)).
A lazy entity then has no proxy class, so `$entity::class` is the entity class and no code has to resolve a proxy name.

DoctrineBundle 3 always enables them. On DoctrineBundle 2.x, enable them yourself:

```yaml
doctrine:
    orm:
        enable_native_lazy_objects: true
```

The bundle still resolves a generated proxy class (`Doctrine\Persistence\Proxy`) to its entity, so an application with
generated proxies keeps working.

## Replace `ClassUtils` in your code

Your application loses `doctrine/common` when no other package requires it. Each use of
`Doctrine\Common\Util\ClassUtils` then fails with `Class "Doctrine\Common\Util\ClassUtils" not found`.

1. Enable native lazy objects (see above).
2. Find the calls:

   ```bash
   grep -rn 'Doctrine\\Common\\Util\\ClassUtils' src tests
   ```

3. Replace each call with the class name it receives, and remove the `use` statement:

   ```php
   // Before
   $class = ClassUtils::getRealClass($entity::class);
   $class = ClassUtils::getClass($entity);
   $class = ClassUtils::getRealClass($className);

   // After
   $class = $entity::class;
   $class = $entity::class;
   $class = $className;
   ```

   Do not use `Doctrine\ORM\Proxy\DefaultProxyClassNameResolver` instead. It triggers a deprecation on PHP 8.4
   ([doctrine/orm#12005](https://github.com/doctrine/orm/pull/12005)).

4. Remove `doctrine/common` from your `composer.json` when you require it directly.
5. Run `composer audit`. When it still reports `doctrine/common` as abandoned, `composer why doctrine/common` shows the
   package that installs it.
