<?php
use Whis\Storage\Storage;

function remove(string $path): bool {
    return Storage::remove($path);
}
function put(string $path, mixed $content): string {
    return Storage::put($path, $content);
}
function download(string $filename, string $folder = null)
{
    if (!is_null($folder)) {
        Storage::download($folder, $filename);
        return;
    }
    Storage::downloadUploaded($filename);
    return;
}
function file(string $filename, string $folder = null)
{
    if (!is_null($folder)) {
        Storage::assets($folder, $filename);
        return;
    }
    Storage::uploaded($filename);
    return;
}