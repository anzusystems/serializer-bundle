<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\Proxy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class MetadataRegistry
{
    private const CACHE_PREFIX = 'anzu_ser_';

    /**
     * @var array<class-string, array<string, Metadata>>
     */
    private array $metadata = [];

    public function __construct(
        private readonly CacheItemPoolInterface $appCache,
        private readonly LoggerInterface $appLogger,
        private readonly MetadataFactory $metadataFactory,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @return array<string, Metadata>
     *
     * @throws SerializerException
     */
    public function get(string $className): array
    {
        if (is_a($className, Proxy::class, true)) {
            $className = ClassUtils::getRealClass($className);
        }
        if (false === array_key_exists($className, $this->metadata)) {
            try {
                $cachedItem = $this->appCache->getItem(self::CACHE_PREFIX . $className);
                if ($cachedItem->isHit()) {
                    $this->metadata[$className] = $cachedItem->get();

                    return $this->metadata[$className];
                }
                $this->metadata[$className] = $this->metadataFactory->buildMetadata($className);
                $cachedItem->set($this->metadata[$className]);
                $this->appCache->save($cachedItem);
            } catch (InvalidArgumentException) {
                $this->appLogger->warning('Unable to cache Serializer metadata.');
            }
        }

        return $this->metadata[$className];
    }
}
