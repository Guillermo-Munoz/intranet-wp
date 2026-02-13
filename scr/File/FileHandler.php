<?php
namespace IntranetGestoria\File;

class FileHandler {
    
    public static function deleteRecursive($path) {
        if (!file_exists($path)) return false;
        
        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $fileinfo) {
                $method = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                @$method($fileinfo->getRealPath());
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }
        
        return !file_exists($path);
    }
    
    public static function copyRecursive($source, $destination) {
        if (!file_exists($source)) return false;
        
        if (is_dir($source)) {
            if (!file_exists($destination)) {
                wp_mkdir_p($destination);
            }
            
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $fileinfo) {
                $rel_path = str_replace($source . '/', '', $fileinfo->getRealPath());
                
                if ($fileinfo->isDir()) {
                    wp_mkdir_p($destination . '/' . dirname($rel_path));
                } else {
                    @copy($fileinfo->getRealPath(), $destination . '/' . $rel_path);
                }
            }
            return true;
        } else {
            return @copy($source, $destination);
        }
    }
}
