<?php

namespace Symfony\HttpClientRecorderBundle\Store;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\HttpClientRecorderBundle\Har\HarFile;

/**
 * @psalm-import-type HarData from HarFile
 */
final class FilesystemStore implements StoreInterface
{
    public function __construct(private string $directory, private Filesystem $filesystem)
    {
        $this->filesystem = new Filesystem();
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (!$this->filesystem->exists($this->directory)) {
            $this->filesystem->mkdir($this->directory);
        }
    }

    private function path(string $name): string
    {
        // TODO: throw if not absolute
        // TODO create directory not in construct
        // TODO create directory only if test mode ?
        if ($this->filesystem->isAbsolutePath($name)) {
            return $name;
        }

        return $this->directory.DIRECTORY_SEPARATOR.$name;
    }

    public function load(string $name): HarFile
    {
        $path = $this->path($name);

        if (!is_file($path)) {
            return HarFile::create();
        }

        /** @psalm-var HarData $har */
        $har = json_decode(file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);

        return new HarFile($har);
    }

    public function save(string $name, HarFile $har): void
    {
        $this->filesystem->dumpFile(
            $this->path($name),
            json_encode($har->toArray(), \JSON_PRETTY_PRINT)
        );
    }
}
