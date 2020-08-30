<?php

namespace VVK;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use VVK\Utility\Str;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class Uploader
{

    /**
     * 
     * @param \Illuminate\Http\UploadedFile
     * @param string $input
     * @param array $options
     * @return string|false
     */
    static public function storeImage($file, string $input, array $options = [])
    {
        $path = config('itaces.upload.img');

        return self::storeFile($file, $path, $input, $options);
    }
    
    /**
     *
     * @param \Illuminate\Http\UploadedFile $files
     * @param string $input
     * @param array $options
     * @return string|false
     */
    static public function storeDocument($file, string $input, array $options = [])
    {
        $path = config('itaces.upload.doc');
        
        return self::storeFile($file, $path, $input, $options);
    }

    /**
     * 
     * @param \Illuminate\Http\UploadedFile
     * @param string $path
     * @param string $input
     * @param array $options
     * @return string|false
     */
    static public function storeFile(UploadedFile $file, string $path, string $input, array $options = [])
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw ValidationException::withMessages([
                $input => [$file->getErrorMessage()],
            ]);
        }
        
        $hash = md5(Str::random(40));

        for ($i = 0; $i < 6; $i += 2) {
            $path .= '/' . substr($hash, $i, $i + 2);
        }

        $extension = $file->extension();
        $name = $hash . '.' . $extension;
        
        return $file->storePubliclyAs($path, $name, $options);
    }

}
