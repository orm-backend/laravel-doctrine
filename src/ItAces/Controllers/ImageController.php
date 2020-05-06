<?php

namespace ItAces\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ItAces\Image;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ImageController
{
    
    public function resize(Request $request, string $mode, int $width, int $height) {
        $src = $request->get('src');
        
        if (substr( $src, 0, 1 ) == '/') {
            $src = substr( $src, 1 );
        }
        
        $dest = Image::cachePath($src, $mode, $width, $height);
        
        if (!Storage::exists($dest)) {
            //dd(public_path($src), public_path($dest));
            Image::resizeImage(public_path($src), public_path($dest), $width, $height, $mode);
        }

        if ($request->hasHeader('HTTP_X_FRONTEND')) {
            $response = response();
            $response->header('X-Accel-Redirect', '/' . $dest);
            $response->header('X-Cache-Hash', md5( $dest ));
            
            return $response;
        }
        
        return response()->redirectTo('/' . $dest);
    }
    
}
