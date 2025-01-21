<?php

namespace App\Helpers;

class FileHelper
{
    public static function getFileIcon($extension)
    {
        $icons = [
            'pdf' => 'file-type-pdf',
            'doc' => 'file-type-doc',
            'docx' => 'file-type-doc',
            'xls' => 'file-type-xls',
            'xlsx' => 'file-type-xls',
            'jpg' => 'photo',
            'jpeg' => 'photo',
            'png' => 'photo',
            'gif' => 'photo'
        ];
        return $icons[strtolower($extension)] ?? 'file';
    }

    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
