<?php
use ItAces\Image;

if (! function_exists('oldd')) {

    /**
     * Retrieve an old input item.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function oldd($key = null, $default = null)
    {
        if ($key) {
            $key = str_replace('.', '_', $key);
        }
        
        return app('request')->old($key, $default);
    }
}

if (! function_exists('crop')) {

    /**
     *
     * @param string $path
     * @param string $mode
     * @param int $width
     * @param int $height
     * @return string
     */
    function crop($path, string $mode, int $width, int $height = 0)
    {
        return Image::crop($path, $mode, $width, $height);
    }
}

if (! function_exists('file_mimetype')) {

    /**
     *
     * @param string $path
     * @return string
     */
    function file_mimetype($path)
    {
        return shell_exec('file -b --mime-type ' . $path . ' | tr -d "\n"');
    }
}

if (! function_exists('file_human_size')) {

    function file_human_size($path, $unit = "")
    {
        $size = filesize($path);
        
        if ((! $unit && $size >= 1 << 30) || $unit == "GB") {
            return number_format($size / (1 << 30), 2) . " GB";
        }
        
        if ((! $unit && $size >= 1 << 20) || $unit == "MB") {
            return number_format($size / (1 << 20), 2) . " MB";
        }
        
        if ((! $unit && $size >= 1 << 10) || $unit == "KB") {
            return number_format($size / (1 << 10), 2) . " KB";
        }
        
        return number_format($size) . " bytes";
    }
}
