<?php
namespace IntranetGestoria\Worker;

class WorkerUI {

    public static function render() {
        // Verificar que el usuario actual es un trabajador
        if (!ig_is_worker(get_current_user_id())) {
            return '<p>Acceso denegado. Solo trabajadores pueden acceder a esta área.</p>';
        }

        $worker_id = get_current_user_id();
        $ver_cliente = isset($_GET['ver_cliente']) ? sanitize_text_field($_GET['ver_cliente']) : null;
        $ver_auditar = isset($_GET['auditar']) && $_GET['auditar'] === '1' ? true : false;
        $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';

        ob_start();
        ?>
        <style>
            /* ESTILOS IGUALES A ADMIN */
            .gestor-container { max-width: 1000px; margin: 20px auto; font-family: sans-serif; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: #fff; border: 1px solid #ddd; }
            .gestor-header { background: #2E7D32; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
            .breadcrumb-gs { padding: 12px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 14px; }

            .cl-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .cl-table th { background: #f4f7f9; color: #333; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; font-size: 13px; }
            .cl-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; }

            .search-cl { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px; box-sizing: border-box; }

            .drop-zone-cl { border: 2px dashed #ccc; padding: 25px; text-align: center; background: #fcfcfc; border-radius: 8px; margin-bottom: 20px; cursor: pointer; transition: 0.3s; }
            .drop-zone-cl:hover { border-color: #2E7D32; background: #f0fff0; }

            .audit-tab-buttons { margin: 15px 0; display: flex; gap: 10px; }
            .audit-tab-btn { padding: 8px 16px; border: 1px solid #ddd; background: #f5f5f5; cursor: pointer; border-radius: 5px; font-size: 14px; }
            .audit-tab-btn.active { background: #2E7D32; color: white; }

            #loading-gs { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 9999; flex-direction: column; }
            .spinner-cl { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2E7D32; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

            .badge-autor { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
            .badge-trabajador { background: #e8f5e9; color: #2e7d32; }
            .badge-cliente { background: #e3f2fd; color: #1976d2; }
            .badge-papelera { background: #ffebee; color: #c62828; }

            .btn-accion-circular { display: inline-block; width: 32px; height: 32px; line-height: 30px; text-align: center; border-radius: 50%; background: #e3f2fd; color: #1976d2; text-decoration: none; font-size: 14px; transition: 0.3s; }
            .btn-accion-circular:hover { background: #1976d2; color: white; }
            .btn-accion-circular-borrar { width: 32px; height: 32px; border-radius: 50%; background: #ffebee; color: #c62828; border: none; cursor: pointer; font-size: 18px; transition: 0.3s; }
            .btn-accion-circular-borrar:hover { background: #c62828; color: white; }

            .client-item-gs { padding: 12px; border-bottom: 1px solid #eee; }
            .client-item-gs:hover { background: #f5f5f5; }
        </style>

        <div id="loading-gs">
            <div class="spinner-cl"></div>
            <div style="font-weight:bold; color:#2E7D32;">🚀 Procesando...</div>
        </div>

        <div class="gestor-container">

        <?php if ($ver_cliente): ?>

            <?php
            $manager = new WorkerManager($worker_id, $ver_cliente);
            $client = $manager->getClientInfo();

            // Verificar acceso
            if (!$client) {
                echo '<div class="gestor-header"><h3 style="margin:0; color:white;">⛔ Acceso denegado</h3></div>';
                echo '<div style="padding:20px;"><p>No tienes permiso para acceder a este cliente.</p></div>';
                echo '</div>';
                return ob_get_clean();
            }

            $files = $manager->getFiles($sub_actual);
            ?>

            <div class="gestor-header">
                <h3 style="margin:0; color:white;">📂 Gestión: <?php echo esc_html($client->display_name); ?></h3>
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

                        echo '<form method="post" style="margin:0;" onsubmit="if(confirm(\'¿Eliminar permanentemente?\')) { document.getElementById(\'loading-gs\').style.display=\'flex\'; return true; } return false;">';
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

                <form method="post" enctype="multipart/form-data" id="formCl">
                    <input type="hidden" name="rutas_relativas" id="rutas_relativas_cl">
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
                            <td><span class="badge-autor badge-<?php echo strtolower($file['author']); ?>"><?php echo $file['author']; ?></span></td>
                            <td style="text-align:right;">
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <?php if (!$file['is_dir']): ?>
                                        <a href="<?php echo admin_url('admin-ajax.php?action=ig_descarga&archivo=' . urlencode($ver_cliente . '/' . $file['rel_path'])); ?>" class="btn-accion-circular">📥</a>
                                    <?php endif; ?>

                                    <form method="post" style="margin:0;" onsubmit="if(confirm('¿Eliminar?')) { document.getElementById('loading-gs').style.display='flex'; return true; } return false;">
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

            <div class="gestor-header"><strong>👥 Mis Clientes Asignados</strong></div>
            <div style="padding:20px;">
                <input type="text" id="searchClientGs" class="search-cl" placeholder="🔍 Buscar cliente..." onkeyup="filterClientes()">
                <div class="file-list">
                    <?php
                    $manager = new WorkerManager($worker_id);
                    $clients = $manager->getAssignedClients();

                    if (empty($clients)) {
                        echo '<p style="text-align:center; color:#999; padding:30px;">No tienes clientes asignados todavía.</p>';
                    } else {
                        foreach ($clients as $client) {
                            $folder_name = intranet_get_client_folder($client->ID, $client->display_name);
                            echo "<div class='client-item-gs'>
                                    <a href='?ver_cliente=".urlencode($folder_name)."' style='text-decoration:none; color:#333;'>👤 <b>".esc_html($client->display_name)."</b></a>
                                  </div>";
                        }
                    }
                    ?>
                </div>
            </div>

        <?php endif; ?>

        </div>

        <script>
            const dzCl = document.getElementById('dropZoneCl');
            const inCl = document.getElementById('inputCl');
            const formCl = document.getElementById('formCl');
            const loaderCl = document.getElementById('loading-gs');

            if(dzCl) {
                dzCl.onclick = () => inCl.click();
                inCl.onchange = () => { if(inCl.files.length) { loaderCl.style.display='flex'; formCl.submit(); } };
                dzCl.ondragover = (e) => { e.preventDefault(); dzCl.style.background = "#f0fff0"; };
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

            function filterCl() {
                let val = document.getElementById('searchFileCl').value.toLowerCase();
                let items = document.getElementsByClassName('search-item-cl');
                for (let i = 0; i < items.length; i++) {
                    items[i].style.display = items[i].innerText.toLowerCase().includes(val) ? "table-row" : "none";
                }
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
