<?php

namespace Whis\Storage;

use PHLAK\StrGen\Generator as StrGenerator;
use RuntimeException;

class File
{
    /**
     * Instantiate new file.
     *
     * @param mixed  $content
     * @param string $type
     * @param string $originalName
     * @param int    $size
     * @param int    $error Código de error de subida de PHP: UPLOAD_ERR_*
     */
    public function __construct(
        private mixed $content,
        private string $type,
        private string $originalName,
        private int $size = 0,
        private int $error = UPLOAD_ERR_OK,
    ) {
    }

    /**
     * Check if the current file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->type, "image");
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function content(): mixed
    {
        return $this->content;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function hasUploadError(): bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }

    public function uploadErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'No upload error.',
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.',
        };
    }

    public function getAttribute(string $attribute): mixed
    {
        return match ($attribute) {
            "content"      => $this->content,
            "type"         => $this->type,
            "originalName" => $this->originalName,
            "size"         => $this->size,
            "error"        => $this->error,
            default        => null,
        };
    }

    /**
     * Extension of the file.
     */
    public function extension(bool $getFromName = false): ?string
    {
        if ($getFromName) {
            return $this->extensionFromOriginalName();
        }

        $extension = match ($this->type) {
            "image/jpeg"      => "jpeg",
            "image/jpg"       => "jpg",
            "image/png"       => "png",
            "image/webp"      => "webp",
            "image/gif"       => "gif",
            "application/pdf" => "pdf",
            "video/mp4"       => "mp4",
            default           => null,
        };

        return $extension ?: $this->extensionFromOriginalName();
    }

    private function extensionFromOriginalName(): ?string
    {
        $extension = pathinfo($this->originalName, PATHINFO_EXTENSION);

        $extension = strtolower(trim((string) $extension));

        return $extension !== '' ? $extension : null;
    }

    /**
     * Store the file.
     *
     * @return string URL or physical path, depending on $getPath.
     */
    public function store(
        ?string $directory = null,
        bool $getPath = false,
        ?string $alternativeDirectory = null,
        bool $getRealExtension = false,
        ?string $customUrl = null
    ): string {
        if ($this->hasUploadError()) {
            throw new RuntimeException(
                "Cannot store uploaded file: " . $this->uploadErrorMessage()
            );
        }

        $extension = $this->extension($getRealExtension);

        if ($extension === null || $extension === '') {
            throw new RuntimeException(
                "Cannot store uploaded file: file extension could not be detected."
            );
        }

        $filename = (new StrGenerator())->alphaNumeric(20) . "." . $extension;

        $directory = $directory !== null
            ? trim(str_replace('\\', '/', $directory), '/')
            : null;

        $path = $directory === null || $directory === ''
            ? $filename
            : $directory . "/" . $filename;

        return Storage::put(
            $path,
            $this->content,
            $getPath,
            $alternativeDirectory,
            $customUrl
        );
    }

    public static function remove(string $path): bool
    {
        return Storage::remove($path);
    }

    public static function download(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null,
        string $folder = ""
    ) {
        return Storage::download($filename, $asset, $alternativeDirectory, $folder);
    }

    public static function get(
        string $filename,
        bool $asset = false,
        ?string $alternativeDirectory = null,
        string $folder = ""
    ) {
        return Storage::get($filename, $asset, $alternativeDirectory, $folder);
    }
}