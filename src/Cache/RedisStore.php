<?php

namespace TusPhp\Cache;

use TusPhp\Datum;
use TusPhp\Config;

class RedisStore extends AbstractCache
{
    /** @var \Predis\Client|\Redis */
    protected $redis;

    /**
     * RedisStore constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options = empty($options) ? Config::get('redis') : $options;

        if (class_exists('\Redis')) {
            $this->redis = new \Redis([
                'host' => $options['host'],
                'port' => (int)$options['port'],
            ]);
        } elseif (class_exists('\Predis\Client')) {
            $this->redis = new \Predis\Client($options);
        } else {
            throw new \LogicException('Redis extension not installed');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, bool $withExpired = false)
    {
        $prefix = $this->getPrefix();

        if (false === strpos($key, $prefix)) {
            $key = $prefix . $key;
        }

        $contents = $this->redis->get($key);
        if (null !== $contents && false !== $contents) {
            $contents = json_decode($contents, true);
        }

        if ($withExpired) {
            return $contents;
        }

        if ( ! $contents) {
            return null;
        }

        $isExpired = Datum::fromRfc7231($contents['expires_at'])->isExpired();

        return $isExpired ? null : $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value)
    {
        $contents = $this->get($key) ?? [];

        if (\is_array($value)) {
            $contents = $value + $contents;
        } else {
            $contents[] = $value;
        }

        $status = $this->redis->setex($this->getPrefix() . $key, $this->getTtl(), json_encode($contents));

        return ($status !== false);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $prefix = $this->getPrefix();

        if (false === strpos($key, $prefix)) {
            $key = $prefix . $key;
        }

        return $this->redis->del([$key]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function keys(): array
    {
        return $this->redis->keys($this->getPrefix() . '*');
    }
}
