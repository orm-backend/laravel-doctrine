<?php
namespace ItAces;

use Illuminate\Support\Facades\Storage;

/**
 *
 * @author Vitaliy Kovalenko vvk@kola.cloud
 *        
 */
class Image
{

    const MODE_ZOOM = 'zoom';
    
    const MODE_CENTER = 'center';
    
    const MODE_SIMPLE = 'simple';
    
    const MODE_FEEL = 'feel';
    
    static public function crop($path, $mode, int $width, int $height = 0)
    {
        if (!$path) {
            return null;
        }
        
        $cached = self::cachePath($path, $mode, $width, $height);
        
        if (substr( $cached, 0, 1 ) == '/') {
            $cached = substr( $cached, 1 );
        }
        
        if (Storage::exists($cached)) {
            return '/' . $cached;
        }
        
        return route('image.resize', [
            $mode,
            $width,
            $height
        ]) . '?src=' . urlencode($path);
    }

    static public function cachePath(string $path, $mode, int $width, int $height)
    {
        return config('itaces.upload.cache') . "/{$mode}/{$width}x{$height}" . self::hashed($path);
    }

    static public function hashed($path)
    {
        $hashed = '';
        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        $hash = md5($path);
        
        for ($i = 0; $i < 6; $i += 2) {
            $hashed .= '/' . substr($hash, $i, $i + 2);
        }
        
        $hashed .= '/' . $hash;
        
        return $hashed . '.' . $ext;
    }
    
    static public function resizeImage($src, $dest, $width, $height, $mode)
    {
        @mkdir(dirname($dest), 0777, true);
        
        if ($mode == self::MODE_ZOOM) {
            return self::resizeImageZoom($src, $dest, $width, $height);
        } else if ($mode == self::MODE_CENTER) {
            return self::resizeImageZoomCenter($src, $dest, $width, $height);
        } else if ($mode == self::MODE_SIMPLE) {
            return self::resizeImageSimple($src, $dest, $width, $height);
        }
        
        return self::resizeImageFeel($src, $dest, $width, $height);
    }

    static protected function beforeResize($src, $dest, $width, $height, $rgb = 0xFFFFFF)
    {
        $is_remote = substr($src, 0, 7) == 'http://' || substr($src, 0, 8) == 'https://';
        
        if (! $is_remote && ! file_exists($src)) {
            return false;
        }
        
        $size = getimagesize($src);
        
        if ($size === false || ! $size[0] || ! $size[1]) {
            return false;
        }

        $format = $is_remote ? "string" : strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
        $icfunc = "imagecreatefrom" . $format;
        
        if (! function_exists($icfunc)) {
            return false;
        }
        
        $orig_width = $size[0];
        $orig_height = $size[1];
        
        if ($is_remote) {
            $src = file_get_contents($src);
            
            if (! $src) {
                return false;
            }
        }
        
        $isrc = $icfunc($src);
        
        if (! $isrc) {
            return false;
        }
        
        $idest = imagecreatetruecolor($width, $height);
        
        if ($format == "png") {
            imagealphablending($idest, true);
            $rgb = imagecolorallocatealpha($idest, 0, 0, 0, 127);
        }
        
        imagefill($idest, 0, 0, $rgb);
        
        return array(
            $isrc,
            $idest,
            $orig_width,
            $orig_height,
            $format
        );
    }

    static protected function afterResize($isrc, $idest, $dest, $format, $quality = 90)
    {
        if ($format == "png") {
            imagealphablending($idest, false);
            imagesavealpha($idest, true);
            imagepng($idest, $dest);
        } else {
            imagejpeg($idest, $dest, $quality);
        }
        
        imagedestroy($isrc);
        imagedestroy($idest);
        
        return true;
    }

    /**
     * Resizing the image
     * Все размеры полотна получаемого в результате изображения равны заданным.
     * Для портретных изображений отрисовывается верхняя часть, для альбомных серединка.
     * При необходимости изображение масштабируется.
     *
     * @param string $src
     *            path to source image file
     * @param string $dest
     *            path to dectination image file
     * @param int $width
     * @param int $height
     * @param int $rgb
     * @param int $quality
     * @return bool
     */
    static protected function resizeImageZoom($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 90)
    {
        list ($isrc, $idest, $orig_width, $orig_height, $format) = self::beforeResize($src, $dest, $width, $height, $rgb);
        
        if (! $isrc || ! $idest) {
            return false;
        }
        
        if ($orig_width > $width && $orig_height > $height) {
            // если обе стороны исходного изображения больше заданных
            $x_ratio = $orig_width / $width;
            $y_ratio = $orig_height / $height;
            
            if ($x_ratio > $y_ratio) {
                // вырезаем серединку по x
                $new_width = floor($width * $y_ratio);
                $left = round(($orig_width - $new_width) / 2);
                imagecopyresampled($idest, $isrc, 0, 0, $left, 0, $width, $height, $new_width, $orig_height);
            } else {
                // вырезаем верхушку по y
                $new_height = floor($height * $x_ratio);
                imagecopyresampled($idest, $isrc, 0, 0, 0, 0, $width, $height, $orig_width, $new_height);
            }
        } else {
            // одна из сторон или обе меньше
            $x_ratio = $orig_width < $width ? $width / $orig_width : 1;
            $y_ratio = $orig_height < $height ? $height / $orig_height : 1;
            $ratio = $x_ratio > $y_ratio ? $x_ratio : $y_ratio;
            $new_width = ceil($orig_width * $ratio);
            $new_height = ceil($orig_height * $ratio);
            // $top = round ( ($height - $new_height) / 2 );
            $left = round(($width - $new_width) / 2); // отрицательное
            
            imagecopyresampled($idest, $isrc, $left, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        }
        
        return self::afterResize($isrc, $idest, $dest, $format, $quality);
    }

    static protected function resizeImageSimple($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 90)
    {
        $is_remote = substr($src, 0, 7) == 'http://' || substr($src, 0, 8) == 'https://';
        
        if (! $is_remote && ! file_exists($src)) {
            return false;
        }
        
        $size = getimagesize($src);
        
        if ($size === false || ! $size[0] || ! $size[1]) {
            return false;
        }
        
        $format = $is_remote ? "string" : strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
        $icfunc = "imagecreatefrom" . $format;
        
        if (! function_exists($icfunc)) {
            return false;
        }
        
        $orig_width = $size[0];
        $orig_height = $size[1];
        
        if ($is_remote) {
            $src = file_get_contents($src);
            
            if (! $src) {
                return false;
            }
        }
        
        $isrc = $icfunc($src);
        
        if (! $isrc) {
            return false;
        }
        
        $ratio = $orig_width / $width;
        
        if ($ratio > 1) {
            $new_width = round($orig_width / $ratio);
            $new_height = round($orig_height / $ratio);
        } else {
            $new_width = $orig_width;
            $new_height = $orig_height;
        }
        
        $idest = imagecreatetruecolor($new_width, $new_height);
        
        if (! $idest) {
            return false;
        }
        
        if ($format == "png") {
            imagealphablending($idest, true);
            $rgb = imagecolorallocatealpha($idest, 0, 0, 0, 127);
        }
        
        imagefill($idest, 0, 0, $rgb);
        imagecopyresampled($idest, $isrc, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        
        return self::afterResize($isrc, $idest, $dest, $format, $quality);
    }

    static protected function resizeImageZoomCenter($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 90)
    {
        list ($isrc, $idest, $orig_width, $orig_height, $format) = self::beforeResize($src, $dest, $width, $height, $rgb);
        
        if (! $isrc || ! $idest) {
            return false;
        }
        
        if ($orig_width > $width && $orig_height > $height) {
            // если обе стороны исходного изображения больше заданных
            $x_ratio = $orig_width / $width;
            $y_ratio = $orig_height / $height;
            
            if ($x_ratio > $y_ratio) {
                // вырезаем серединку по x
                $new_width = floor($width * $y_ratio);
                $left = round(($orig_width - $new_width) / 2);
                imagecopyresampled($idest, $isrc, 0, 0, $left, 0, $width, $height, $new_width, $orig_height);
            } else {
                // вырезаем серединку по y
                $new_height = floor($height * $x_ratio);
                $top = round(($orig_height - $new_height) / 2);
                imagecopyresampled($idest, $isrc, 0, 0, 0, $top, $width, $height, $orig_width, $new_height);
            }
        } else {
            // одна из сторон или обе меньше
            $x_ratio = $orig_width < $width ? $width / $orig_width : 1;
            $y_ratio = $orig_height < $height ? $height / $orig_height : 1;
            $ratio = $x_ratio > $y_ratio ? $x_ratio : $y_ratio;
            $new_width = ceil($orig_width * $ratio);
            $new_height = ceil($orig_height * $ratio);
            $top = round(($height - $new_height) / 2);
            $left = round(($width - $new_width) / 2); // отрицательное
            
            imagecopyresampled($idest, $isrc, $left, $top, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        }
        
        return self::afterResize($isrc, $idest, $dest, $format, $quality);
    }

    /**
     * Resizing the image
     * Во всех случаях размеры полотна (получаемого в результате изображения) равен заданным.
     * 1. Ширина и высота исходного изображения больше заданных размеров.
     * Если соотношение сторон исходного изображения к заданному по X больше соотношения по Y,
     * коэффициент трансформации выбираем по Y и вырезаем середину по X. В противном случае, выбираем
     * коэффициент трансформации по X и, в случае горизонтального исходного изображения (ширина больше
     * высоты), выбираем середину по X, иначе (вертикальное изобажение) обрезаем нижнюю часть.
     * 2. Одна из сторон исходного изображения больше заданного размера, а другая меньше.
     * Ту сторону, размер исходного изображения которой меньше заданного, выравниваем по-середине полотна,
     * а из другой вырезаем срединку. (Без масштабирования).
     * 3. Обе стороны исходного изображения меньше заданных размеров.
     * Выравниваем изображение и по вертикали, и по горизонтали в центре полотна. (Без масштабирования).
     *
     * @param string $src
     *            path to source image file
     * @param string $dest
     *            path to dectination image file
     * @param int $width
     * @param int $height
     * @param int $rgb
     * @param int $quality
     * @return bool
     */
    static protected function resizeImageFeel($src, $dest, $width, $height, $rgb = 0x282828, $quality = 90)
    {
        list ($isrc, $idest, $orig_width, $orig_height, $format) = self::beforeResize($src, $dest, $width, $height, $rgb);
        
        if (! $isrc || ! $idest) {
            return false;
        }
        
        // для слишком длинных или высоких
        $zoom = $orig_width / $orig_height > 1.9 ? 1.75 : 1;
        $zoom = $orig_height / $orig_width > 1.4 ? 1.6 : $zoom;
        // $zoom = 1;
        // соотношение
        $x_ratio = $width / $orig_width;
        $y_ratio = $height / $orig_height;
        $ratio = 1;
        
        if ($orig_width < $orig_height || $orig_height < $height) {
            // для портретных изображений выбираем минимальное соотнощение (вписываем фото в полотно)
            $ratio = $x_ratio < $y_ratio ? $x_ratio : $y_ratio;
        } else {
            // для альбомных выбираем максимальное соотношение (вырезаем часть фото)
            $ratio = $x_ratio > $y_ratio ? $x_ratio : $y_ratio;
        }
        
        $new_width = ceil($orig_width * $ratio * $zoom);
        $new_height = ceil($orig_height * $ratio * $zoom);
        
        if ($width > $height && ($orig_width < $orig_height || $orig_height < $height)) {
            // вписываем
            $left = round(($width - $new_width) / 2);
            $top = round(($height - $new_height) / ($orig_width < $orig_height ? 4 : 2));
            imagecopyresampled($idest, $isrc, $left, $top, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
        } else {
            // вырезаем
            $left = round(($new_width - $width) / 2);
            $top = round(($new_height - $height) / ($width > $height ? 4 : 2));
            imagecopyresampled($idest, $isrc, 0, 0, $left, $top, $new_width, $new_height, $orig_width, $orig_height);
        }
        
        return self::afterResize($isrc, $idest, $dest, $format, $quality);
    }

}
