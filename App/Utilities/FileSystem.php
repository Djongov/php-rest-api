<?php declare(strict_types=1);

namespace App\Utilities;

class FileSystem
{
    // Returns the size of a folder in bytes. /1024 /1024 to get MB. echo App\Utilities\FileSystem::folderSize(dirname($_SERVER['DOCUMENT_ROOT'])) / 1024 / 1024 . ' MB'; for project root size
    public static function folderSize($folderPath) : float
    {
        $totalSize = 0;
        $files = scandir($folderPath);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $folderPath . '/' . $file;
                if (is_dir($filePath)) {
                    $totalSize += self::folderSize($filePath);
                } else {
                    $totalSize += filesize($filePath);
                }
            }
        }
        return $totalSize; // float in bytes
    }
}
