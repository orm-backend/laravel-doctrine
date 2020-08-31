<?php

namespace OrmBackend\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OrmBackend\Image;

/**
 * 
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *
 */
class ImageController
{
    
    /**
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $mode
     * @param int $width
     * @param int $height
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function resize(Request $request, string $mode, int $width, int $height) {
        $disk = config('filesystems.default');
        $rootPath = config("filesystems.disks.{$disk}.root");
        $rootUrl = config("filesystems.disks.{$disk}.url");
        $src = $request->get('src');
        
        if (substr( $src, 0, 1 ) == '/') {
            $src = substr( $src, 1 );
        }
        
        if (!Storage::exists($src)) {
            abort(404);
        }

        $cached = Image::cachePath($src, $mode, $width, $height);
        
        if (!Storage::exists($cached)) {
            Image::resizeImage($rootPath . '/' . $src, $rootPath . '/' . $cached, $width, $height, $mode);
        }

        if ($request->hasHeader('HTTP_X_FRONTEND')) {
            $response = response();
            $response->header('X-Accel-Redirect', $rootUrl . '/' . $cached);
            $response->header('X-Cache-Hash', md5( $cached ));
            
            return $response;
        }
        
        return response()->redirectTo($rootUrl . '/' . $cached);
    }
    
}
