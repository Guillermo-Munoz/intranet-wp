<?php
namespace IntranetGestoria\Client;

class ClientUI {
    
    public static function render() {
        if (!is_user_logged_in()) {
            return '<p>🔒 Por favor, <a href="' . wp_login_url() . '">inicia sesión</a>.</p>';
        }
        
        $user = wp_get_current_user();
        $manager = new ClientManager($user);
        $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
        $files = $manager->getFiles($sub_actual);
        
        ob_start();
        ?>
        <div id="loading-cl">
            <div class="spinner-cl"></div>
            <div style="font-weight:bold; color:#003B77;">Trabajando...</div>
        </div>
        
        <div class="cliente-container">
            <div class="cliente-header">
                <h3 style="margin:0; color:white;">📂 Mis Documentos</h3>
                <span style="opacity:0.8; font-size:12px;"><?php echo esc_html($user->display_name); ?></span>
            </div>
            
            <div class="breadcrumb-cl">
                📍 <a href="?">Inicio</a> 
                <?php 
                if ($sub_actual) {
                    $dirs = explode('/', trim($sub_actual, '/'));
                    $acc = '';
                    foreach ($dirs as $d) {
                        $acc .= $d . '/';
                        $d_limpio = str_replace('_gs_', '', $d);
                        echo " / <a href='?dir=".urlencode(rtrim($acc, '/'))."'>$d_limpio</a>";
                    }
                }
                ?>
            </div>
            
            <div style="padding:20px;">
                <input type="text" id="searchFileCl" class="search-cl" placeholder="🔍 Buscar por nombre..." onkeyup="filterCl()">
                
                <form method="post" enctype="multipart/form-data" id="formCl">
                    <input type="hidden" name="rutas_relativas" id="rutas_relativas_cl">
                    <div class="drop-zone-cl" id="dropZoneCl">
                        <strong>Subir Archivos o Carpetas</strong>
                        <p style="font-size:12px; color:#666; margin:5px 0 0;">Arrastra documentos aquí</p>
                        <input type="file" name="archivo_cliente[]" id="inputCl" multiple style="display:none;">
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
                                    <a href="?dir=<?php echo urlencode($file['rel_path']); ?>" style="text-decoration:none; color:#333;">📁 <b><?php echo esc_html(str_replace('_gs_', '', $file['name'])); ?>/</b></a>
                                <?php else: ?>
                                    <a href="<?php echo home_url('/descarga.php?archivo=' . urlencode($manager->getIdCarpeta() . '/' . $file['rel_path'])); ?>" target="_blank" style="text-decoration:none; color:#003B77;">📄 <?php echo esc_html($file['name']); ?></a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', $file['date']); ?></td>
                            <td><span class="badge-autor badge-<?php echo strtolower($file['author']); ?>"><?php echo $file['author']; ?></span></td>
                            <td style="text-align:right;">
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <?php if (!$file['is_dir'] && strpos($file['name'], '_gs_') !== 0): ?>
                                        <a href="<?php echo home_url('/descarga.php?archivo=' . urlencode($manager->getIdCarpeta() . '/' . $file['rel_path'])); ?>" download class="btn-accion-circular" title="Descargar">📥</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($file['name'] !== INTRANET_YEAR && strpos($file['name'], '_gs_') !== 0): ?>
                                        <form method="post" style="margin:0;" onsubmit="if(confirm('¿Eliminar?')) { document.getElementById(\'loading-cl\').style.display=\'flex\'; return true; } return false;">
                                            <input type="hidden" name="ruta_archivo" value="<?php echo esc_attr($file['rel_path']); ?>">
                                            <button type="submit" name="borrar_archivo_cliente" class='btn-accion-circular-borrar'>×</button>
                                        </form>
                                    <?php elseif (strpos($file['name'], '_gs_') === 0 || $file['name'] === INTRANET_YEAR): ?>
                                        <div style="opacity:0.3; cursor:help;">🔒</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
            const dzCl = document.getElementById('dropZoneCl');
            const inCl = document.getElementById('inputCl');
            const formCl = document.getElementById('formCl');
            const loaderCl = document.getElementById('loading-cl');
            
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
        </script>
        <?php
        return ob_get_clean();
    }
}
