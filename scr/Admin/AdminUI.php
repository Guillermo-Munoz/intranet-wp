<?php
namespace IntranetGestoria\Admin;

class AdminUI {
    
    public static function render() {
        if (!current_user_can('edit_published_posts')) {
            return '<p>Acceso denegado.</p>';
        }
        
        $ver_cliente = isset($_GET['ver_cliente']) ? sanitize_text_field($_GET['ver_cliente']) : null;
        $ver_auditar = isset($_GET['auditar']) && $_GET['auditar'] === '1' ? true : false;
        $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
        
        ob_start();
        ?>
        <style>
            .gestor-container { max-width: 1000px; margin: 20px auto; font-family: sans-serif; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: #fff; border: 1px solid #ddd; }
            .gestor-header { background: #003B77; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
            .breadcrumb-gs { padding: 12px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 14px; }
            .gs-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .gs-table th { background: #f4f7f9; color: #333; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; font-size: 13px; }
            .gs-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; }
            .audit-tab-buttons { margin: 15px 0; display: flex; gap: 10px; }
            .audit-tab-btn { padding: 8px 16px; border: 1px solid #ddd; background: #f5f5f5; cursor: pointer; border-radius: 5px; font-size: 14px; }
            .audit-tab-btn.active { background: #003B77; color: white; }
        </style>
        
        <div id="loading-gs">
            <div class="spinner-gs"></div>
            <div style="font-weight:bold; color:#003B77;">🚀 Procesando...</div>
        </div>
        
        <div class="gestor-container">
        
        <?php if ($ver_cliente): ?>
            
            <?php
            $manager = new AdminManager($ver_cliente);
            $client = $manager->getClientInfo();
            $files = $manager->getFiles($sub_actual);
            ?>
            
            <div class="gestor-header">
                <h3 style="margin:0; color:white;">📂 Gestión: <?php echo $client ? esc_html($client->display_name) : 'Expediente'; ?></h3>
                <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" style="color:#fff; font-size:12px; text-decoration:none; background:rgba(255,255,255,0.1); padding:5px 10px; border-radius:4px;">✕ Volver</a>
            </div>
            
            <div style="padding: 0 20px;">
                <div class="audit-tab-buttons">
                    <button class="audit-tab-btn <?php echo !$ver_auditar ? 'active' : ''; ?>" onclick="window.location='?ver_cliente=<?php echo urlencode($ver_cliente); ?>'">
                        📁 Documentos Activos
                    </button>
                    <button class="audit-tab-btn <?php echo $ver_auditar ? 'active' : ''; ?>" onclick="window.location='?ver_cliente=<?php echo urlencode($ver_cliente); ?>&auditar=1'">
                        🗑️ Papelera de Auditoría
                    </button>
                </div>
            </div>
            
            <div class="breadcrumb-gs">
                📍 <a href="?ver_cliente=<?php echo $ver_cliente; echo $ver_auditar ? '&auditar=1' : ''; ?>">Inicio</a> 
                <?php 
                if ($sub_actual) {
                    $dirs = explode('/', trim($sub_actual, '/'));
                    $acc = '';
                    foreach ($dirs as $d) {
                        $acc .= $d . '/';
                        $d_limpio = str_replace('_gs_', '', $d);
                        echo " / <a href='?ver_cliente=$ver_cliente&dir=".urlencode(rtrim($acc, '/'))."" . ($ver_auditar ? "&auditar=1" : "") . "'>$d_limpio</a>";
                    }
                }
                ?>
            </div>
            
            <div style="padding:20px;">
            
            <?php if ($ver_auditar): ?>
                
                <h4 style="color: #c62828; margin-top: 0;">🗑️ Archivos Eliminados</h4>
                
                <?php
                $log = $manager->getTrashLog();
                $trash_items = $manager->getTrashItems();
                
                if ($log) {
                    echo '<details style="cursor: pointer; border: 1px solid #ffc107; border-radius: 5px; padding: 12px; background: #fff9e6;">';
                    echo '<summary style="font-weight: bold; color: #cc8800;"><span style="margin-right: 8px;">📋</span>Registro de Eliminaciones</summary>';
                    echo '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ffc107; font-family: monospace; font-size: 12px; color: #333; max-height: 300px; overflow-y: auto;">';
                    echo nl2br(esc_html($log));
                    echo '</div></details>';
                }
                
                if (!empty($trash_items)) {
                    echo '<table class="gs-table" style="margin-top: 20px;">';
                    echo '<thead><tr><th>Archivo Eliminado</th><th>Fecha</th><th>Acción</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($trash_items as $item) {
                        $trash_path = INTRANET_CLIENTS_DIR . $ver_cliente . '/' . INTRANET_TRASH_FOLDER . '/';
                        $item_full = $trash_path . $item;
                        $es_carpeta = is_dir($item_full);
                        
                        preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})_(.*)/', $item, $matches);
                        $nombre = isset($matches[3]) ? $matches[3] : $item;
                        $fecha = isset($matches[1]) ? $matches[1] . ' ' . str_replace('-', ':', $matches[2]) : date('d/m/Y H:i', filemtime($item_full));
                        
                        echo '<tr>';
                        echo '<td>'. ($es_carpeta ? '📁' : '📄') . ' <span class="badge-autor badge-papelera">'. esc_html($nombre) . '</span></td>';
                        echo '<td>' . $fecha . '</td>';
                        echo '<td style="text-align:right;"><div style="display:flex; gap:8px; justify-content:flex-end;">';
                        
                        if (!$es_carpeta) {
                            echo '<a href="' . home_url('/descarga.php?archivo=' . urlencode($ver_cliente . '/' . INTRANET_TRASH_FOLDER . '/' . $item)) . '" download style="background:#003B77; color:white; border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; text-decoration:none;">📥</a>';
                        }
                        
                        echo '<form method="post" style="margin:0;" onsubmit="if(confirm(\'¿Eliminar permanentemente?\')) { document.getElementById(\'loading-gs\').style.display=\'flex\'; return true; } return false;">';
                        echo '<input type="hidden" name="ruta_archivo_papelera" value="' . esc_attr($ver_cliente . '/' . INTRANET_TRASH_FOLDER . '/' . $item) . '">';
                        echo '<button type="submit" name="borrar_archivo_papelera" style="border:none; background:#ff5252; color:white; border-radius:50%; width:32px; height:32px; cursor:pointer;">🗑️</button>';
                        echo '</form>';
                        
                        echo '</div></td></tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p style="text-align:center; color:#999; padding:30px;">✅ No hay elementos en la papelera</p>';
                }
                ?>
                
            <?php else: ?>
                
                <input type="text" id="searchFileGs" class="search-gs" placeholder="🔍 Buscar..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px;" onkeyup="filterGs()">
                
                <table class="gs-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Fecha</th>
                            <th>Autor</th>
                            <th style="text-align:right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr class="search-item-gs">
                            <td>
                                <?php if ($file['is_dir']): ?>
                                    <a href="?ver_cliente=<?php echo $ver_cliente; ?>&dir=<?php echo urlencode($file['rel_path']); ?>" style="text-decoration:none; color:#333;">📁 <b><?php echo esc_html(str_replace('_gs_', '', $file['name'])); ?>/</b></a>
                                <?php else: ?>
                                    <a href="<?php echo home_url('/descarga.php?archivo=' . urlencode($ver_cliente . '/' . $file['rel_path'])); ?>" target="_blank" style="text-decoration:none; color:#003B77;">📄 <?php echo esc_html($file['name']); ?></a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', $file['date']); ?></td>
                            <td><span class="badge-autor badge-<?php echo strtolower($file['author']); ?>"><?php echo $file['author']; ?></span></td>
                            <td style="text-align:right;">
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <?php if (!$file['is_dir']): ?>
                                        <a href="<?php echo home_url('/descarga.php?archivo=' . urlencode($ver_cliente . '/' . $file['rel_path'])); ?>" download style="background:#003B77; color:white; border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; text-decoration:none;">📥</a>
                                    <?php endif; ?>
                                    
                                    <form method="post" style="margin:0;" onsubmit="if(confirm('¿Eliminar?')) { document.getElementById(\'loading-gs\').style.display=\'flex\'; return true; } return false;">
                                        <input type="hidden" name="ruta_archivo" value="<?php echo esc_attr($ver_cliente . '/' . $file['rel_path']); ?>">
                                        <button type="submit" name="borrar_archivo" style="border:none; background:#ff5252; color:white; border-radius:50%; width:32px; height:32px; cursor:pointer;">×</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php endif; ?>
            
            </div>
        
        <?php else: ?>
            
            <div class="gestor-header"><strong>🏢 Listado de Clientes</strong></div>
            <div style="padding:20px;">
                <input type="text" id="searchClientGs" class="search-gs" placeholder="🔍 Buscar cliente..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px;" onkeyup="filterClientes()">
                <div class="file-list">
                    <?php
                    if (file_exists(INTRANET_CLIENTS_DIR)) {
                        foreach (array_diff(scandir(INTRANET_CLIENTS_DIR), ['.', '..']) as $f) {
                            $p = explode('-', $f);
                            if (count($p) < 2) continue;
                            $u = get_userdata($p[1]);
                            if ($u && !in_array('administrator', $u->roles)) {
                                echo "<div class='client-item-gs' style='padding:12px; border-bottom:1px solid #eee;'>
                                        <a href='?ver_cliente=".urlencode($f)."' style='text-decoration:none; color:#333;'>👤 <b>".esc_html($u->display_name)."</b></a>
                                      </div>";
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        
        <?php endif; ?>
        
        </div>
        
        <script>
            function filterGs() {
                let val = document.getElementById('searchFileGs').value.toLowerCase();
                let items = document.querySelectorAll('.search-item-gs');
                items.forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(val) ? "table-row" : "none";
                });
            }
            
            function filterClientes() {
                let val = document.getElementById('searchClientGs').value.toLowerCase();
                let items = document.querySelectorAll('.client-item-gs');
                items.forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(val) ? "block" : "none";
                });
            }
        </script>
        
        <?php
        return ob_get_clean();
    }
}
