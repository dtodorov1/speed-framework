<?php

namespace SpeedFramework\Core\Cache\Filesystem;



use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;

class FilesystemAdapter
{
    use LoggerAwareTrait;

    private DefaultMarshaller $marshaller;
    private static $createCacheItem;
    private static $mergeByLifetime;
    private string $directory;
    private string $namespace;
    private const NS_SEPARATOR = ':';
    private array $ids = [];
    private array $deferred = [];
    private int $defaultLifetime = 0;

    public function __construct()
    {
        $this->marshaller = new DefaultMarshaller();
        $this->namespace = \DIRECTORY_SEPARATOR.'d';

        self::$createCacheItem ??= \Closure::bind(
            static function ($key, $value, $isHit) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $v = $value;
                $item->isHit = $isHit;
                $item->unpack();

                return $item;
            },
            null,
            CacheItem::class
        );

        self::$mergeByLifetime ??= \Closure::bind(
            static function ($deferred, $namespace, &$expiredIds, $getId, $defaultLifetime) {
                $byLifetime = [];
                $now = microtime(true);
                $expiredIds = [];

                foreach ($deferred as $key => $item) {
                    $key = (string) $key;
                    if (null === $item->expiry) {
                        $ttl = 0 < $defaultLifetime ? $defaultLifetime : 0;
                    } elseif (!$item->expiry) {
                        $ttl = 0;
                    } elseif (0 >= $ttl = (int) (0.1 + $item->expiry - $now)) {
                        $expiredIds[] = $getId($key);
                        continue;
                    }
                    $byLifetime[$ttl][$getId($key)] = $item->pack();
                }
                return $byLifetime;
            },
            null,
            CacheItem::class
        );

        $this->init();
    }

    //setup directory where data will be placed and pulled
    private function init()
    {
        $directory = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'symfony-cache';

        //namespace replacement
        $directory .= \DIRECTORY_SEPARATOR.'d';

        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $directory .= \DIRECTORY_SEPARATOR;

        $this->directory = $directory;
    }

    public function getItem(mixed $key): CacheItem
    {
        $id = $this->getId($key);

        //has this key been marked as deferred?

        $isHit = false;
        $value = null;

        try {
            foreach ($this->doFetch([$id]) as $value) {
                $isHit = true;
            }

            return (self::$createCacheItem)($key, $value, $isHit);
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to fetch "{key}": '.$e->getMessage(), ['key' => $key, 'exception' => $e, 'cache-adapter' => get_debug_type($this)]);
        }

        return (self::$createCacheItem)($key, null, false);
    }

    private function doFetch(array $ids): iterable
    {
        $values = [];
        $now = time();

        foreach ($ids as $id) {
            $file = $this->getFile($id);

            //does what?
            //what would h be?
            if (!is_file($file) || !$h = @fopen($file, 'r')) {
                continue;
            }

            if (($expiresAt = (int) fgets($h) && $now >= $expiresAt)) {
                fclose($h);
                @unlink($file);
            } else {
                $i = rawurldecode(rtrim(fgets($h)));
                $value = stream_get_contents($h);
                fclose($h);
                if ($i === $id) {
                    $values[$id] = $this->marshaller->unmarshall($value);
                }
            }
        }

        return $values;
    }

    private function getFile(string $id, bool $mkdir = false)
    {
        $hash = str_replace('/', '-', base64_encode(hash('md5', static::class.$id, true)));
        $dir = $this->directory.strtoupper($hash[0].\DIRECTORY_SEPARATOR.$hash[1].\DIRECTORY_SEPARATOR);

        if ($mkdir && !is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir.substr($hash, 2, 20);
    }

    private function getId(mixed $key)
    {
        if (\is_string($key) && isset($this->ids[$key])) {
            return $this->namespace.$this->ids[$key];
        }

        \assert('' !== CacheItem::validateKey($key));
        $this->ids[$key] = $key;

        if(\count($this->ids) > 1000) {
            $this->ids = \array_slice($this->ids, 500, null, true);
        }

        return $this->namespace.$this->ids[$key];
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return $this->commit();
    }

    private function commit(): bool
    {
        $ok = true;
        $byLifetime = (self::$mergeByLifetime)($this->deferred, $this->namespace, $expiredIds, $this->getId(...), $this->defaultLifetime);
        $retry = $this->deferred = [];

        if($expiredIds) {
            try {
                $this->doDelete($expiredIds);
            } catch (\Exception $e) {
                $ok = false;
                CacheItem::log($this->logger, 'Failed to delete expired items: '.$e->getMessage(), ['exception' => $e, 'cache-adapter' => get_debug_type($this)]);
            }
        }

        //1st save attempt
        foreach ($byLifetime as $lifetime => $values) {
            try {
                $e = $this->doSave($values, $lifetime);
            } catch (\Exception $e) {
            }
            if (\is_array($e) || 1 === \count($values)) {
                foreach (\is_array($e) ? $e : array_keys($values) as $id) {
                    $ok = false;
                    $v = $values[$id];
                    $type = get_debug_type($v);
                    $message = sprintf('Failed to save key "{key}" of type %s%s', $type, $e instanceof \Exception ? ': '.$e->getMessage() : '.');
                    CacheItem::log($this->logger, $message, ['key' => substr($id, \strlen($this->namespace)), 'exception' => $e instanceof \Exception ? $e : null, 'cache-adapter' => get_debug_type($this)]);
                }
            } else {
                foreach ($values as $id => $v) {
                    $retry[$lifetime][] = $id;
                }
            }
        }

        //2nd save attempt
        foreach ($retry as $lifetime => $ids) {
            foreach ($ids as $id) {
                try {
                    $v = $byLifetime[$lifetime][$id];
                    $e = $this->doSave([$id => $v], $lifetime);
                } catch (\Exception $e) {
                }
                if (true === $e || [] === $e) {
                    continue;
                }
                $ok = false;
                $type = get_debug_type($v);
                $message = sprintf('Failed to save key "{key}" of type %s%s', $type, $e instanceof \Exception ? ': '.$e->getMessage() : '.');
                CacheItem::log($this->logger, $message, ['key' => substr($id, \strlen($this->namespace)), 'exception' => $e instanceof \Exception ? $e : null, 'cache-adapter' => get_debug_type($this)]);
            }
        }

        return $ok;
    }

    private function doDelete(array $ids): bool
    {
        $ok = true;

        foreach ($ids as $id) {
            $file = $this->getFile($id);
            $ok = (!is_file($file) || $this->doUnlink($file) || !file_exists($file) && $ok);
        }

        return $ok;
    }

    private function doUnlink(string $file)
    {
        return @unlink($file);
    }

    private function doSave(array $values, int $lifetime): array|bool
    {
        $expiresAt = $lifetime ? (time() + $lifetime) : 0;
        $values = $this->marshaller->marshall($values, $failed);

        foreach ($values as $id => $value) {
            if (!$this->write($this->getFile($id, true),
                $expiresAt."\n".rawurlencode($id)."\n".$value,
                $expiresAt)) {
                $failed[] = $id;
            }
        }

        if ($failed && !is_writeable($this->directory)) {
            throw new CacheException(sprintf('Cache directory is not writable (%s).', $this->directory));
        }

        return $failed;
    }

    private function write(string $file, string $data, int $expiresAt = null)
    {
        set_error_handler(__CLASS__.'::throwError');

        try {
            $tmp = $this->directory.$this->tmpSuffix ??= str_replace('/', '-', base64_encode(random_bytes(6)));
            try {
                $h = fopen($tmp, 'x');
            } catch (\ErrorException $e) {
                if (!str_contains($e->getMessage(), 'File exists')) {
                    throw $e;
                }

                $tmp = $this->directory.$this->tmpSuffix =str_replace('/', '-', base64_encode(random_bytes(6)));
                $h = fopen($tmp, 'x');
            }

            fwrite($h, $data);
            fclose($h);

            if (null !== $expiresAt) {
                touch($tmp, $expiresAt ?: time() + 31556952); // 1 year in seconds
            }

            return rename($tmp, $file);
        } finally {
            restore_error_handler();
        }
    }

    public static function throwError(int $type, string $message, string $file, int $line)
    {
        throw new \ErrorException($message, 0, $type, $file, $line);
    }
}