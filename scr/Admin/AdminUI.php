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
            /* ESTILOS UNIFICADOS (IGUAL AL CLIENTE) */
            .gestor-container { max-width: 1000px; margin: 20px auto; font-family: sans-serif; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: #fff; border: 1px solid #ddd; }
            .gestor-header { background: #003B77; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
            .breadcrumb-gs { padding: 12px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 14px; }
            
            /* Clases de tabla y elementos del cliente */
            .cl-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .cl-table th { background: #f4f7f9; color: #333; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; font-size: 13px; }
            .cl-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; }
            
            .search-cl { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px; box-sizing: border-box; }
            
            /* Drop Zone del cliente */
            .drop-zone-cl { border: 2px dashed #ccc; padding: 25px; text-align: center; background: #fcfcfc; border-radius: 8px; margin-bottom: 20px; cursor: pointer; transition: 0.3s; }
            .drop-zone-cl:hover { border-color: #003B77; background: #f0f7ff; }
            
            .audit-tab-buttons { margin: 15px 0; display: flex; gap: 10px; }
            .audit-tab-btn { padding: 8px 16px; border: 1px solid #ddd; background: #f5f5f5; cursor: pointer; border-radius: 5px; font-size: 14px; }
            .audit-tab-btn.active { background: #003B77; color: white; }

            /* Loader */
            #loading-gs { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 9999; flex-direction: column; }
            .spinner-cl { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #003B77; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
        
        <div id="loading-gs">
            <div class="spinner-cl"></div>
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
                    echo '<table class="cl-table" style="margin-top: 20px;">';
                    echo '<thead><tr><th>Documento</th><th>Fecha</th><th style="text-align:right;">Acción</th></tr></thead>';
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
                            echo '<a href="' . home_url('/descarga.php?archivo=' . urlencode($ver_cliente . '/' . INTRANET_TRASH_FOLDER . '/' . $item)) . '" class="btn-accion-circular">📥</a>';
                        }
                        
                        echo '<form method="post" style="margin:0;" action="' . esc_url($_SERVER['REQUEST_URI']) . '" onsubmit="if(confirm(\'¿Eliminar permanentemente?\')) { document.getElementById(\'loading-gs\').style.display=\'flex\'; return true; } return false;">';
                        echo '<input type="hidden" name="ruta_archivo_papelera" value="' . esc_attr($ver_cliente . '/' . INTRANET_TRASH_FOLDER . '/' . $item) . '">';
                        echo '<button type="submit" name="borrar_archivo_papelera" class="btn-accion-circular-borrar">🗑️</button>';
                        echo '</form>';
                                                
                        echo '</div></td></tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p style="text-align:center; color:#999; padding:30px;">✅ No hay elementos en la papelera</p>';
                }
                ?>
                
            <?php else: ?>
                
                <input type="text" id="searchFileCl" class="search-cl" placeholder="🔍 Buscar por nombre..." onkeyup="filterCl()">
                
                <form method="post" enctype="multipart/form-data" id="formCl" action="<?php echo esc_url(home_url('/customer-area/dashboard/?ver_cliente=' . $ver_cliente . ($sub_actual ? '&dir=' . urlencode($sub_actual) : ''))); ?>">
                    <input type="hidden" name="rutas_relativas" id="rutas_relativas_cl">
                    <input type="hidden" name="dir" value="<?php echo esc_attr($sub_actual); ?>">
                    
                    <input type="hidden" name="dir_actual" value="<?php echo esc_attr($sub_actual); ?>">

                    <div class="drop-zone-cl" id="dropZoneCl">
                        <strong>Subir Archivos o Carpetas</strong>
                        <p style="font-size:12px; color:#666; margin:5px 0 0;">Arrastra documentos aquí</p>
                        <input type="file" name="archivo_gestor[]" id="inputCl" multiple style="display:none;">
                    </div>
                </form>
                
                <table class="cl-table" id="tablaArchivos">
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
                            <tr class="search-item-cl">
                                <td>
                                    <?php if ($file['is_dir']): ?>
                                        <a href="?ver_cliente=<?php echo $ver_cliente; ?>&dir=<?php echo urlencode($file['rel_path']); ?>" style="text-decoration:none; color:#333;">📁 <b><?php echo esc_html(str_replace('_gs_', '', $file['name'])); ?>/</b></a>
                                    <?php else: ?>
                                        <a href="<?php echo admin_url('admin-ajax.php?action=ig_descarga&view=1&archivo=' . urlencode($ver_cliente . '/' . $file['rel_path'])); ?>" target="_blank" style="text-decoration:none; color:#003B77;">
                                            📄 <?php echo esc_html(str_replace('_gs_', '', $file['name'])); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', $file['date']); ?></td>
                                
                                <td>
                                    <?php $autor_final = ($file['is_dir'] || $file['name'] === INTRANET_YEAR) ? 'Sistema' : $file['author']; ?>
                                    <span class="badge-autor badge-<?php echo strtolower($autor_final); ?>">
                                        <?php echo $autor_final; ?>
                                    </span>
                                </td>

                                <td style="text-align:right;">
                                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                                        <?php if (!$file['is_dir']): ?>
                                          <a href="<?php echo admin_url('admin-ajax.php?action=ig_descarga&archivo=' . urlencode($ver_cliente . '/' . $file['rel_path'])); ?>" download class="btn-accion-circular" title="Descargar">📥</a>                                        <?php endif; ?>
                                        
                                        <form method="post" style="margin:0;" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" onsubmit="if(confirm('¿Eliminar archivo?')) { document.getElementById('loading-gs').style.display='flex'; return true; } return false;">
                                            <input type="hidden" name="ruta_archivo" value="<?php echo esc_attr($ver_cliente . '/' . $file['rel_path']); ?>">
                                            <button type="submit" name="borrar_archivo" class='btn-accion-circular-borrar'>×</button>
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

            <?php
            // Nueva vista: Ver clientes de un trabajador específico
            $ver_trabajador = isset($_GET['ver_trabajador']) ? intval($_GET['ver_trabajador']) : null;

            if ($ver_trabajador):
                $worker_info = get_userdata($ver_trabajador);
                $assigned_clients = ig_get_clients_by_worker($ver_trabajador);
                ?>

                <div class="gestor-header">
                    <h3 style="margin:0; color:white;">👷 Clientes de <?php echo $worker_info ? esc_html($worker_info->display_name) : 'Trabajador'; ?></h3>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" style="color:#fff; font-size:12px; text-decoration:none; background:rgba(255,255,255,0.1); padding:5px 10px; border-radius:4px;">✕ Volver</a>
                </div>

                <div style="padding:20px;">
                    <input type="text" id="searchWorkerClients" class="search-cl" placeholder="🔍 Buscar cliente..." onkeyup="filterWorkerDetailClients()">

                    <?php if (empty($assigned_clients)): ?>
                        <p style="color:#999; padding:20px; background:#f5f5f5; border-radius:5px; text-align:center;">
                            Este trabajador no tiene clientes asignados.
                        </p>
                    <?php else: ?>
                        <div style="border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                            <?php foreach ($assigned_clients as $client): ?>
                                <?php
                                $folder_name = intranet_get_client_folder($client->ID, $client->display_name);
                                ?>
                                <div class="worker-detail-client" style="padding:15px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                                    <div style="flex:1;">
                                        <a href="?ver_cliente=<?php echo urlencode($folder_name); ?>" style="text-decoration:none; color:#333; font-weight:bold;">
                                            👤 <?php echo esc_html($client->display_name); ?>
                                        </a>
                                        <span style="color:#999; font-size:12px; margin-left:10px;"><?php echo $client->user_email; ?></span>
                                    </div>
                                    <form method="post" style="margin:0;" action="<?php echo esc_url(home_url('/customer-area/dashboard/')); ?>" onsubmit="return confirm('¿Desvincular este cliente del trabajador?');">
                                        <input type="hidden" name="client_id" value="<?php echo $client->ID; ?>">
                                        <button type="submit" name="desvincular_trabajador" style="background:#ffebee; color:#c62828; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; font-size:12px;">
                                            🔗 Desvincular
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>

            <div class="gestor-header"><strong>🏢 Gestión de Trabajadores y Clientes</strong></div>
            <div style="padding:20px;">
                <?php
                // Mensajes de feedback
                if (isset($_GET['msg'])) {
                    $msg = sanitize_text_field($_GET['msg']);
                    $messages = [
                        'promoted' => '✅ Usuario promovido a Trabajador correctamente.',
                        'assigned' => '✅ Cliente asignado al Trabajador correctamente.',
                        'unassigned' => '✅ Cliente desvinculado del Trabajador correctamente.',
                        'demoted' => '✅ Trabajador degradado a Cliente correctamente.'
                    ];
                    if (isset($messages[$msg])) {
                        echo '<div style="padding:12px; background:#e8f5e9; color:#2e7d32; border-left:4px solid #2e7d32; margin-bottom:15px; border-radius:4px;">' . $messages[$msg] . '</div>';
                    }
                }
                ?>

                <input type="text" id="searchClientGs" class="search-cl" placeholder="🔍 Buscar trabajador o cliente..." onkeyup="filterAll()">

                <?php
                // Obtener trabajadores y clientes sin asignar
                $workers = ig_get_all_workers();
                $unassigned_clients = ig_get_unassigned_clients();

                // Separar trabajadores con y sin clientes
                $workers_without_clients = [];
                $workers_with_clients = [];

                foreach ($workers as $worker) {
                    $assigned_clients = ig_get_clients_by_worker($worker->ID);
                    if (empty($assigned_clients)) {
                        $workers_without_clients[] = $worker;
                    } else {
                        $workers_with_clients[] = ['worker' => $worker, 'clients' => $assigned_clients];
                    }
                }
                ?>

                <!-- SECCIÓN 1: Trabajadores SIN clientes -->
                <h3 style="margin-top:0;">🔵 Trabajadores sin Clientes Asignados</h3>

                <?php if (empty($workers_without_clients)): ?>
                    <p style="color:#999; padding:20px; background:#f5f5f5; border-radius:5px; text-align:center;">
                        ✅ Todos los trabajadores tienen clientes asignados.
                    </p>
                <?php else: ?>
                    <div style="border:1px solid #ddd; border-radius:8px; overflow:hidden; margin-bottom:30px;">
                        <?php foreach ($workers_without_clients as $worker): ?>
                            <div class="worker-no-clients" style="padding:15px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                                <div style="flex:1;">
                                    <strong>👷 <?php echo esc_html($worker->display_name); ?></strong>
                                    <span style="color:#999; font-size:12px; margin-left:10px;"><?php echo $worker->user_email; ?></span>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <span style="background:#e3f2fd; color:#1976d2; padding:4px 12px; border-radius:20px; font-size:12px;">
                                        Sin clientes
                                    </span>
                                        <form method="post" style="margin:0; display:inline-block;" action="<?php echo esc_url(home_url('/customer-area/dashboard/')); ?>" onsubmit="return confirm('¿Degradar <?php echo esc_html($worker->display_name); ?> a Cliente?');">
                                        <input type="hidden" name="worker_id" value="<?php echo $worker->ID; ?>">
                                        <button type="submit" name="degradar_trabajador" style="background:#FF6B6B; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; font-size:13px; white-space:nowrap; display:block;">
                                            ⬇️ Degradar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- SECCIÓN 2: Trabajadores CON clientes (resumen) -->
                <h3 style="margin-top:30px;">🟢 Trabajadores con Clientes Asignados</h3>

                <?php if (empty($workers_with_clients)): ?>
                    <p style="color:#999; padding:20px; background:#f5f5f5; border-radius:5px; text-align:center;">
                        No hay trabajadores con clientes asignados.
                    </p>
                <?php else: ?>
                    <?php foreach ($workers_with_clients as $worker_data): ?>
                        <?php
                        $worker = $worker_data['worker'];
                        $clients = $worker_data['clients'];
                        $num_clients = count($clients);
                        ?>
                        <div class="worker-section-summary" style="margin-bottom:20px; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                            <div style="background:#2E7D32; color:white; padding:15px; display:flex; justify-content:space-between; align-items:center;">
                                <div style="flex:1;">
                                    <strong>👷 <?php echo esc_html($worker->display_name); ?></strong>
                                    <span style="background:rgba(255,255,255,0.2); padding:4px 12px; border-radius:20px; font-size:12px; margin-left:10px;">
                                        <?php echo $num_clients . ' ' . ($num_clients === 1 ? 'cliente' : 'clientes'); ?>
                                    </span>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <a href="?ver_trabajador=<?php echo $worker->ID; ?>" style="background:#fff; color:#2E7D32; text-decoration:none; padding:8px 16px; border-radius:5px; font-size:13px; font-weight:bold;">
                                        📋 Ver Todos los Clientes
                                    </a>
                                    <form method="post" style="margin:0; display:inline-block;" action="<?php echo esc_url(home_url('/customer-area/dashboard/')); ?>" onsubmit="return confirm('¿Degradar <?php echo esc_html($worker->display_name); ?> a Cliente? Se perderán sus asignaciones.');">
                                        <input type="hidden" name="worker_id" value="<?php echo $worker->ID; ?>">
                                        <button type="submit" name="degradar_trabajador" style="background:#FF6B6B; color:white; border:none; padding:8px 14px; border-radius:5px; cursor:pointer; font-size:13px; font-weight:bold; white-space:nowrap;">
                                            ⬇️ Degradar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div style="padding:15px; background:#fafafa;">
                                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                    <?php
                                    // Mostrar primeros 3 clientes como preview
                                    $preview_clients = array_slice($clients, 0, 3);
                                    foreach ($preview_clients as $client):
                                    ?>
                                        <span style="background:#fff; border:1px solid #ddd; padding:6px 12px; border-radius:20px; font-size:12px;">
                                            👤 <?php echo esc_html($client->display_name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($num_clients > 3): ?>
                                        <span style="background:#e3f2fd; border:1px solid #90caf9; padding:6px 12px; border-radius:20px; font-size:12px; color:#1976d2;">
                                            +<?php echo ($num_clients - 3); ?> más
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- SECCIÓN 3: Clientes sin Trabajador Asignado -->
                <h3 style="margin-top:30px;">📋 Clientes sin Trabajador Asignado</h3>

                <?php if (empty($unassigned_clients)): ?>
                    <p style="color:#999; padding:20px; background:#f5f5f5; border-radius:5px; text-align:center;">
                        ✅ Todos los clientes tienen un trabajador asignado.
                    </p>
                <?php else: ?>
                    <div style="border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                        <?php foreach ($unassigned_clients as $client): ?>
                            <?php
                            $folder_name = intranet_get_client_folder($client->ID, $client->display_name);
                            ?>
                            <div class="client-item-gs unassigned-client" style="padding:15px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                                <div style="flex:1;">
                                    <a href="?ver_cliente=<?php echo urlencode($folder_name); ?>" style="text-decoration:none; color:#333; font-weight:bold;">
                                        👤 <?php echo esc_html($client->display_name); ?>
                                    </a>
                                    <span style="color:#999; font-size:12px; margin-left:10px;"><?php echo $client->user_email; ?></span>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <?php if (!empty($workers)): ?>
                                        <form method="post" style="margin:0; display:flex; gap:8px; align-items:center;" action="<?php echo esc_url(home_url('/customer-area/dashboard/')); ?>">
                                            <input type="hidden" name="client_id" value="<?php echo $client->ID; ?>">
                                            <select name="worker_id" required style="padding:6px 12px; border:1px solid #ddd; border-radius:5px; font-size:13px;">
                                                <option value="">Seleccionar trabajador...</option>
                                                <?php foreach ($workers as $worker): ?>
                                                    <option value="<?php echo $worker->ID; ?>"><?php echo esc_html($worker->display_name); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="asignar_trabajador" style="background:#2E7D32; color:white; border:none; padding:6px 16px; border-radius:5px; cursor:pointer; font-size:13px;">
                                                ✓ Asignar
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!in_array(IG_ROLE_WORKER, $client->roles) && !in_array('administrator', $client->roles)): ?>
                                        <form method="post" style="margin:0;" action="<?php echo esc_url(home_url('/customer-area/dashboard/')); ?>" onsubmit="return confirm('¿Convertir a <?php echo esc_html($client->display_name); ?> en Trabajador?');">
                                            <input type="hidden" name="user_id" value="<?php echo $client->ID; ?>">
                                            <button type="submit" name="promover_trabajador" style="background:#FF9800; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; font-size:13px;">
                                                ⬆️ Hacer Trabajador
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php endif; ?>

        <?php endif; ?>
        
        </div>
        
        <script>
            // Lógica de subida idéntica a la del cliente
            const dzCl = document.getElementById('dropZoneCl');
            const inCl = document.getElementById('inputCl');
            const formCl = document.getElementById('formCl');
            const loaderCl = document.getElementById('loading-gs');

            if(dzCl) {
                dzCl.onclick = () => inCl.click();
                inCl.onchange = () => { if(inCl.files.length) { loaderCl.style.display='flex'; formCl.submit(); } };
                dzCl.ondragover = (e) => { e.preventDefault(); dzCl.style.background = "#f0f7ff"; };
                dzCl.ondragleave = () => { dzCl.style.background = "#fcfcfc"; };
                dzCl.ondrop = async (e) => {
                    e.preventDefault();
                    loaderCl.style.display = 'flex';
                    const items = e.dataTransfer.items;
                    if (items) {
                        const dt = new DataTransfer();
                        let paths = [];
                        for (let item of items) {
                            const entry = item.webkitGetAsEntry();
                            if (entry) await traverseCl(entry, "", dt, paths);
                        }
                        inCl.files = dt.files;
                        document.getElementById('rutas_relativas_cl').value = paths.join('|');
                        formCl.submit();
                    }
                };
            }

            async function traverseCl(item, path, dt, paths) {
                if (item.isFile) {
                    const file = await new Promise(res => item.file(res));
                    dt.items.add(file);
                    paths.push(path + file.name);
                } else if (item.isDirectory) {
                    const reader = item.createReader();
                    const entries = await new Promise(res => reader.readEntries(res));
                    for (let e of entries) await traverseCl(e, path + item.name + "/", dt, paths);
                }
            }

            // Buscador de archivos (estilo cliente)
            function filterCl() {
                let val = document.getElementById('searchFileCl').value.toLowerCase();
                let items = document.getElementsByClassName('search-item-cl');
                for (let i = 0; i < items.length; i++) {
                    items[i].style.display = items[i].innerText.toLowerCase().includes(val) ? "table-row" : "none";
                }
            }

            // Buscador global de trabajadores y clientes
            function filterAll() {
                let val = document.getElementById('searchClientGs').value.toLowerCase();

                // Filtrar secciones de trabajadores
                let workerSections = document.querySelectorAll('.worker-section');
                workerSections.forEach(section => {
                    let hasMatch = section.innerText.toLowerCase().includes(val);
                    section.style.display = hasMatch ? "block" : "none";
                });

                // Filtrar clientes sin asignar
                let unassignedClients = document.querySelectorAll('.unassigned-client');
                unassignedClients.forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(val) ? "flex" : "none";
                });
            }

            // Buscador local por trabajador
            function filterWorkerClients(workerId) {
                let input = document.querySelector(`.worker-client-search[data-worker="${workerId}"]`);
                let val = input.value.toLowerCase();
                let items = document.querySelectorAll(`.worker-${workerId}-client`);

                items.forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(val) ? "flex" : "none";
                });
            }

            // Buscador para vista de detalle de clientes del trabajador
            function filterWorkerDetailClients() {
                let val = document.getElementById('searchWorkerClients').value.toLowerCase();
                let items = document.querySelectorAll('.worker-detail-client');

                items.forEach(item => {
                    item.style.display = item.innerText.toLowerCase().includes(val) ? "flex" : "none";
                });
            }
            
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const u = new URLSearchParams(location.search);
                if (u.has('err')) alert('❌ Error: Duplicado');
                if (u.get('msg') === 'ok') alert('✅ Subido');
                history.replaceState({}, '', location.href.replace(/[&?](err|msg)=[^&]*/g, ''));
            });
                    
        <?php
        return ob_get_clean();
    }
}