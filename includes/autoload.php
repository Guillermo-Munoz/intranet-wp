<?php
if (!defined('ABSPATH')) exit;

class IntranetGestoriaFactory {
    private static $instances = [];
    
    public static function make($class) {
        $full_class = "IntranetGestoria\\" . $class;
        
        if (!isset(self::$instances[$full_class])) {
            if (class_exists($full_class)) {
                self::$instances[$full_class] = new $full_class();
            }
        }
        return self::$instances[$full_class] ?? null;
    }
}

// Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'IntranetGestoria\\';
    
    if (strpos($class, $prefix) !== 0) return;
    
    $relative_class = substr($class, strlen($prefix));
    $file = INTRANET_GESTORIA_PATH . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Alias para compatibilidad
class Factory extends IntranetGestoriaFactory {}
