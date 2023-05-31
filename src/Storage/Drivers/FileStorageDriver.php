<?php

namespace Whis\Storage\Drivers;

interface FileStorageDriver
{
        public function put(string $path, mixed $content, bool $returnPath): string;

        public function remove(string $path): bool;
        
}
