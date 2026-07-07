<?php

namespace App\Filament\Support;

use Illuminate\Support\Facades\Storage;

class ProductImagePath
{
    public const DIRECTORY = 'products';

    public static function normalize(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (self::isRemote($path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (! str_starts_with($path, self::DIRECTORY.'/')) {
            $path = self::DIRECTORY.'/'.basename($path);
        }

        return $path;
    }

    public static function isRemote(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    public static function exists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        if (self::isRemote($path)) {
            return true;
        }

        return Storage::disk('public')->exists(self::normalize($path));
    }

    public static function adminPreviewUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (self::isRemote($path)) {
            return $path;
        }

        $normalized = self::normalize($path);

        return url('/admin/files/'.$normalized);
    }

    public static function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (self::isRemote($path)) {
            return $path;
        }

        return asset('storage/'.self::normalize($path));
    }
}
