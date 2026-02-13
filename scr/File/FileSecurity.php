<?php
namespace IntranetGestoria\File;

class FileSecurity {
    
    public static function validateExtension($filename) {
        return intranet_validate_extension($filename);
    }
    
    public static function validatePath($path, $base_dir) {
        return intranet_validate_path($path, $base_dir);
    }
    
    public static function isInTrash($path) {
        return intranet_is_in_trash($path);
    }
    
    public static function sanitizeFilename($filename) {
        return sanitize_file_name(basename($filename));
    }
}
