<?php

namespace Whis\Storage;
use PHLAK\StrGen\Generator as StrGenerator;

class File
{

    /**
     * Instantiate new file.
     *
     * @param string $path
     * @param mixed $content
     * @param string $type
     */
    public function __construct(
        private mixed $content,
        private string $type,
        private string $originalName,
        private int $size = 0,
    ) {
        $this->content = $content;
        $this->type = $type;
        $this->originalName = $originalName;
        $this->size = $size;
    }

    /**
     * Check if the current file is an image.
     *
     * @return boolean
     */
    public function isImage(): bool {
        return str_starts_with($this->type, "image");
    }

    public function originalName(): string {
        return $this->originalName;
    }

    public function size(): int {
        return $this->size;
    }

    public function getAttribute(string $attribute): mixed {
        return match ($attribute) {
            "content" => $this->content,
            "type" => $this->type,
            "originalName" => $this->originalName,
            "size" => $this->size,
            default => null,
        };
    }

    /**
     * Type of the image.
     *
     * @return string|null
     */
    public function extension(bool $getFromName = false): ?string {
        if($getFromName) return pathinfo($this->originalName, PATHINFO_EXTENSION);
        return match ($this->type) {
            "image/jpeg" => "jpeg",
            "image/png" => "png",
            "application/pdf" => "pdf",
            "video/mp4" => "mp4",
            default => null,
        };
    }

    /**
     * Store the file.
     *
     * @return string URL.
     */
    public function store(?string $directory = null, string $alternativeDirectory=null, bool $getRealExtension=false, string $customUrl=null): string {
        $file = (new StrGenerator())->alphaNumeric(20) . $this->extension($getRealExtension);
        $path = is_null($directory) ? $file : "$directory/$file";
        return Storage::put($path, $this->content, $alternativeDirectory, $customUrl);
    }

    public static function remove(string $path): bool {
        return Storage::remove($path);
    }

    public static function download(string $filename, ?bool $asset=false, string $alternativeDirectory=null ,string $folder="") {
        return Storage::download($filename, $asset, $alternativeDirectory, $folder);
    }

    public static function get(string $filename, ?bool $asset=false, string $alternativeDirectory=null ,string $folder="") {
        return Storage::get($filename, $asset, $alternativeDirectory, $folder);
    }
}
