<?php
namespace IntranetGestoria\Trash;

use IntranetGestoria\File\FileHandler;
use IntranetGestoria\File\FileSecurity;

class TrashManager {
    
    public static function moveToTrash($base_path, $file_path, $username) {
        $trash_path = TrashLogger::getTrashPath($base_path);
        $timestamp = current_time('timestamp');
        $date_format = date('Y-m-d_H-i-s', $timestamp);
        $filename = basename($file_path);
        
        if (is_dir($file_path)) {
            $dest = $trash_path . $date_format . '_' . $filename . '/';
        } else {
            $dest = $trash_path . $date_format . '_' . $filename;
        }
        
        // Copiar a papelera
        FileHandler::copyRecursive($file_path, $dest);
        
        // Registrar en log
        TrashLogger::registerDeletion($base_path, $file_path, is_dir($file_path) ? 'CARPETA' : 'ARCHIVO', $username);
        
        // Eliminar original
        FileHandler::deleteRecursive($file_path);
        
        return true;
    }
    
    public static function permanentlyDelete($base_path, $filename) {
        $trash_path = $base_path . INTRANET_TRASH_FOLDER . '/';
        $file_path = $trash_path . $filename;
        
        // Validar que está en papelera
        if (!FileSecurity::isInTrash($file_path)) {
            return false;
        }
        
        return FileHandler::deleteRecursive($file_path);
    }
    
    public static function getTrashItems($base_path) {
        $trash_path = $base_path . INTRANET_TRASH_FOLDER . '/';
        
        if (!is_dir($trash_path)) return [];
        
        return array_diff(scandir($trash_path), ['.', '..', 'index.php', 'log_borrados.txt']);
    }
}
