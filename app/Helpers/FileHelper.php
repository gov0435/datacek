<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Clean and sanitize file name
     * - Removes whitespace from start/end
     * - Replaces spaces with dashes
     * - Removes special characters (keeps only alphanumeric, dash, underscore)
     * - Preserves file extension
     *
     * @param  string  $fileName  Original file name
     * @return string Cleaned file name
     *
     * @example
     * FileHelper::cleanFileName('My Document @#$.pdf') // Returns: My-Document.pdf
     */
    public static function cleanFileName(string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $nameWithoutExt = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        $cleanedName = str($nameWithoutExt)
            ->trim()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-zA-Z0-9\-_]/', '');

        return $cleanedName.($extension ? '.'.$extension : '');
    }

    /**
     * Generate unique file name with timestamp prefix
     *
     * @param  string  $fileName  Original file name
     * @return string Prefixed with timestamp
     *
     * @example
     * FileHelper::generateUniqueFileName('document.pdf') // Returns: 1704067200-document.pdf
     */
    public static function generateUniqueFileName(string $fileName): string
    {
        return time().'-'.self::cleanFileName($fileName);
    }
}
