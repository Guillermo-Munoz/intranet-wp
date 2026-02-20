<?php
namespace IntranetGestoria\Utils;

class SendEmail {
    private static $remitente_email = 'no-reply@sgasesores.es';
    private static $remitente_nombre = 'S&G ASESORES';

    public static function enviar_notificacion($client_id, $quien_envia = 'asesoria') {
        $client       = get_userdata($client_id);
        $current_uid  = get_current_user_id();
        $current_user = get_userdata($current_uid);

        if (!$client || empty($client->user_email)) return false;

        $headers = [
            'From: S&G ASESORES <no-reply@sgasesores.es>',
            'Reply-To: no-reply@sgasesores.es',
            'Content-Type: text/html; charset=UTF-8',
        ];

        // --- FLUJO 1: CLIENTE sube → avisa a Admin (author) + Trabajador asignado ---
        if ($quien_envia === 'cliente') {
            $destinatarios = [];
            $admins = get_users(['role' => 'author']);
            foreach ($admins as $admin) { $destinatarios[] = $admin->user_email; }
            $worker = \ig_get_worker_by_client($client_id);
            if ($worker) { $destinatarios[] = $worker->user_email; }

            $subject = "📩 Documentación subida por cliente: {$client->display_name}";
            $texto   = "El cliente <strong>{$client->display_name}</strong> ha subido nuevos archivos a su expediente.";

            return wp_mail(
                array_unique(array_filter($destinatarios)),
                $subject,
                self::get_plantilla("Equipo de Gestión", $texto),
                $headers
            );
        }

        // --- FLUJO 2: TRABAJADOR sube → avisa al cliente ---
        if ($quien_envia === 'trabajador') {
            $nombre_trabajador = $current_user ? $current_user->display_name : 'Tu asesor';
            $subject = '📄 Nuevos documentos en tu expediente - S&G ASESORES';
            $texto   = "Tu asesor asignado, <span style='font-weight:600;color:#1a365d;'>{$nombre_trabajador}</span>, acaba de subir nuevos documentos a tu expediente personal.";

            return wp_mail(
                $client->user_email,
                $subject,
                self::get_plantilla($client->display_name, $texto),
                $headers
            );
        }

        // --- FLUJO 3: ADMIN sube → avisa al cliente ---
        if ($quien_envia === 'admin') {
            $subject = '📄 Nuevos documentos en tu expediente - S&G ASESORES';
            $texto   = "El equipo de <span style='font-weight:600;color:#1a365d;'>S&G ASESORES</span> acaba de subir nuevos documentos a tu expediente personal.";

            return wp_mail(
                $client->user_email,
                $subject,
                self::get_plantilla($client->display_name, $texto),
                $headers
            );
        }

        return false;
    }

    private static function get_plantilla($nombre_dest, $texto_cuerpo) {
        $year = date('Y');
        return "
        <div style='background-color:#f6f9fc;padding:40px 10px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;'>
            <div style='max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);border:1px solid #e2e8f0;'>
                
                <div style='background-color:#1a365d;padding:30px;text-align:center;'>
                    <h1 style='color:#ffffff;margin:0;font-size:24px;letter-spacing:1px;'>S&G ASESORES</h1>
                </div>

                <div style='padding:40px 30px;'>
                    <h2 style='color:#2d3748;margin-bottom:15px;font-size:18px;'>Estimado/a {$nombre_dest}:</h2>
                    <p style='color:#4a5568;font-size:16px;line-height:1.6;'>{$texto_cuerpo}</p>
                    <div style='text-align:center;margin:35px 0;'>
                        <a href='https://sgasesores.es/' style='background-color:#2b6cb0;color:#ffffff;padding:14px 28px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;'>
                            ACCEDER AL ÁREA DE CLIENTE
                        </a>
                    </div>
                    <p style='color: #718096; font-size: 14px; margin-top: 40px;'>
                        Saludos cordiales,<br>
                        <strong>El equipo de S&G ASESORES</strong>
                    </p>
                </div>

                <div style='background-color: #f8fafc; padding: 30px; border-top: 1px solid #edf2f7;'>
                    <p style='color: #a0aec0; font-size: 11px; line-height: 1.5; text-align: justify; margin: 0;'>
                        <strong>AVISO DE CONFIDENCIALIDAD:</strong> Este mensaje y, en su caso, los archivos anexos se dirigen exclusivamente a su destinatario y puede contener información privilegiada o confidencial. Si no es usted el destinatario indicado, queda notificado de que la utilización, divulgación y/o copia sin autorización está prohibida en virtud de la legislación vigente. Si ha recibido este mensaje por error, le rogamos que nos lo comunique inmediatamente por esta misma vía y proceda a su destrucción.<br><br>
                        <strong>PROTECCIÓN DE DATOS:</strong> De conformidad con el RGPD y la LOPDGDD, le informamos que sus datos personales son tratados por S&G ASESORES para la gestión de servicios profesionales. Puede ejercer sus derechos de acceso, rectificación, supresión y otros derechos enviando un email a info@sgasesores.es.
                    </p>
                </div>
            </div>
            <div style='text-align:center;margin-top:20px;color:#a0aec0;font-size:12px;'>
                © {$year} S&G ASESORES.
            </div>
        </div>";
    }
}