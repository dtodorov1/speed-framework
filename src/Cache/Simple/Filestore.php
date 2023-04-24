<?php

namespace App\Filestore;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;

class Filestore
{
    private $directory = '';
    private DefaultMarshaller $marshaller;
    private $deferred = [];
    private int $defaultLifetime = 0;

    public function __construct()
    {
        $this->initDir();
        $this->marshaller = new DefaultMarshaller();
    }

    /**
     * @return void
     *
     * setup directory in Temp in which files will be placed
     */
    private function initDir(): void
    {
        $directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'f-store-cache';

        $directory .= \DIRECTORY_SEPARATOR . '@d';

        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $directory .= \DIRECTORY_SEPARATOR;

        $this->directory = $directory;
    }

    public function save(SimpleCacheItem $item)
    {
        if (!$item instanceof SimpleCacheItem)
        {
            return false;
        }

        $this->deferred[$item->getKey()] = $item;

        return $this->commit();
    }

    private function commit()
    {
        $ok = true;

        $values = $this->deferred;
        $this->deferred = [];

        try {
            $e = $this->doSave($values);
        } catch (\Exception $e) {
        }

        if (\is_array($e)) {
            $ok = false;
        }

        return $ok;
    }

    private function doSave(array $values)
    {
        $values = $this->marshaller->marshall($values, $failed);

        foreach ($values as $id => $value) {
            if (!$this->write($this->getFile($id, true), rawurlencode($id)."\n".$value))
            {
                $failed[] = $id;
            }
        }

        if ($failed && !is_writeable($this->directory)) {
            throw new CacheException(sprinf('Cache directory is not writable (%s).', $this->directory));
        }

        return $failed;
    }

    private function getFile(string $id, bool $mkdir = false)
    {
        $hash = str_replace('/', '-', base64_encode(hash('md5', 'file-cache'.$id, true)));
        $dir = $this->directory.strtoupper($hash[0].\DIRECTORY_SEPARATOR.$hash[1].\DIRECTORY_SEPARATOR);

        if ($mkdir && !is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir.substr($hash, 2, 20);
    }

    private function write(string $file, string $data)
    {
        set_error_handler(__CLASS__.'::throwError');

        try {
            $temp = $file .'_' . str_replace('/', '-', base64_encode(random_bytes(6)));
            try {
                $h = fopen($temp, 'x');
            } catch (\ErrorException $e) {
                if (!str_contains($e->getMessage(), 'File exists')) {
                    throw $e;
                }

                $temp = $this->directory . $this->tmpSuffix = str_replace('/', '-', base64_encode(random_bytes(6)));
                $h = fopen($temp, 'x');
            }
            fwrite($h, $data);
            fclose($h);

            return rename($temp, $file);
        } finally {
            restore_error_handler();
        }
    }

    public function getItem(mixed $key): SimpleCacheItem
    {
        if (isset($this->deferred[$key])) {
            $this->commit();
        }

        $item = null;

        try {
            foreach ($this->doFetch([$key]) as $item) {
            }

            if (isset($item)) {
                $item->setIsHit(true);
                return $item;
            }
        } catch (\Exception $e) {
        }

        return new SimpleCacheItem($key);
    }

    private function doFetch(array $ids): iterable
    {
        $values = [];

        foreach ($ids as $id) {
            $file = $this->getFile($id);
            if (!is_file($file) || !$h = @fopen($file, 'r')) {
                continue;
            }

            $i = rawurldecode(rtrim(fgets($h)));
            $value = stream_get_contents($h);
            fclose($h);
            if ($i === $id) {
                $values[$id] = $this->marshaller->unmarshall($value);
            }
        }

        return $values;
    }

    public static function throwError(int $type, string $message, string $file, int $line)
    {
        throw new \ErrorException($message, 0, $type, $file, $line);
    }

}