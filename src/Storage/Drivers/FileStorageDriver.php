<?php

namespace Whis\Storage\Drivers;

interface FileStorageDriver
{
        public function put(string $path, mixed $content): string;

        public function remove(string $path): bool;
        
}
