<?php
namespace IntranetGestoria\Admin;

use IntranetGestoria\Trash\TrashManager;
use IntranetGestoria\Trash\TrashLogger;
use IntranetGestoria\File\FileSecurity;

class AdminManager {
    
    private $ver_cliente;
    private $client_base_path;
    
    public function __construct($ver_cliente) {
        $this->ver_cliente = $ver_cliente;
        $partes = explode('-', $ver_cliente);
        $user_id = $partes[1] ?? 0;
        $user = get_userdata($user_id);
        if ($user) {
            $this->client_base_path = intranet_get_client_base_path($user_id, $user->display_name);
        }
    }
    
    public function getClientInfo() {
        $partes = explode('-', $this->ver_cliente);
        $user_id = $partes[1] ?? 0;
        return get_userdata($user_id);
    }
    
    public function getFiles($sub_dir = '') {
        $full_path = $this->client_base_path . ltrim($sub_dir, '/');
        
        if (!is_dir($full_path)) return [];
        
        $items = array_diff(scandir($full_path), ['.', '..', 'index.php', INTRANET_TRASH_FOLDER]);
        
        $result = [];
        foreach ($items as $item) {
            $item_path = $full_path . '/' . $item;
            $result[] = [
                'name' => $item,
                'path' => $item_path,
                'rel_path' => ltrim($sub_dir, '/') ? rtrim($sub_dir, '/') . '/' . $item : $item,
                'is_dir' => is_dir($item_path),
                'date' => filemtime($item_path),
                'author' => $this->getAuthor($item),
            ];
        }
        return $result;
    }
    
    public function deleteFile($file_rel_path) {
        $file_path = realpath($this->client_base_path . '/' . $file_rel_path);
        
        if (!$file_path || !FileSecurity::validatePath($file_path, $this->client_base_path)) {
            return false;
        }
        
        if (is_dir($file_path)) {
            return $this->deleteDirectory($file_path);
        } else {
            return @unlink($file_path);
        }
    }
    
    public function getTrashItems() {
        return TrashManager::getTrashItems($this->client_base_path);
    }
    
    public function getTrashLog() {
        return TrashLogger::getLog($this->client_base_path);
    }
    
    public function deleteFromTrash($filename) {
        return TrashManager::permanentlyDelete($this->client_base_path, $filename);
    }
    
    private function deleteDirectory($path) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $method = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$method($fileinfo->getRealPath());
        }
        return @rmdir($path);
    }
    
    private function getAuthor($filename) {
        if (strpos($filename, '_gs_') === 0) {
            return 'Gestoría';
        } else {
            return 'Sistema';
        }
    }
}
