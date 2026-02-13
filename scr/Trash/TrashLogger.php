<?php
namespace IntranetGestoria\Trash;

class TrashLogger {
    
    public static function registerDeletion($base_path, $filename, $type, $username) {
        $trash_path = intranet_ensure_trash_directory($base_path);
        $log_file = $trash_path . 'log_borrados.txt';
        
        $timestamp = current_time('mysql');
        $entry = "[$timestamp] Usuario: $username | Tipo: $type | Archivo/Carpeta: $filename\n";
        
        file_put_contents($log_file, $entry, FILE_APPEND);
    }
    
    public static function getLog($base_path) {
        $trash_path = $base_path . INTRANET_TRASH_FOLDER . '/';
        $log_file = $trash_path . 'log_borrados.txt';
        
        if (!file_exists($log_file)) return '';
        
        return file_get_contents($log_file);
    }
    
    public static function getTrashPath($base_path) {
        return intranet_ensure_trash_directory($base_path);
    }
}
