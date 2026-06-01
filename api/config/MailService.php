<?php
/**
 * MailService — Cliente SMTP nativo (sin Composer)
 *
 * Soporta:
 *   - STARTTLS (puerto 587)  → encryption: 'tls'
 *   - SSL directo (puerto 465) → encryption: 'ssl'
 *   - Sin cifrado (desarrollo) → encryption: ''
 *   - AUTH LOGIN
 *   - Emails HTML + texto plano
 */
class MailService
{
    private array  $config;
    private        $socket = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (require __DIR__ . '/smtp.php');
    }

    // ── Métodos de alto nivel ────────────────────────────────────────────────

    /**
     * Envía un email de recuperación de contraseña.
     */
    public function sendPasswordReset(string $to, string $nombre, string $token): bool
    {
        $url     = rtrim($this->config['app_url'], '/') . "/forgot-password.php?token=" . urlencode($token);
        $subject = 'Restablecer contraseña — Trazabilidad Café';
        $html    = $this->tplPasswordReset($nombre, $url);
        return $this->send($to, $nombre, $subject, $html);
    }

    /**
     * Notifica al comprador que su contrato fue confirmado.
     */
    public function sendVentaConfirmada(string $to, string $compradorNombre, array $venta): bool
    {
        $subject = "Contrato {$venta['numero_contrato']} confirmado — Trazabilidad Café";
        $html    = $this->tplVentaConfirmada($compradorNombre, $venta);
        return $this->send($to, $compradorNombre, $subject, $html);
    }

    /**
     * Notifica al administrador sobre alerta de stock bajo.
     */
    public function sendAlertaStock(string $to, string $nombre, array $lote): bool
    {
        $subject = "Alerta de stock — Acopio {$lote['codigo']}";
        $html    = $this->tplAlertaStock($nombre, $lote);
        return $this->send($to, $nombre, $subject, $html);
    }

    /**
     * Notifica resultado de análisis de laboratorio.
     */
    public function sendAnalisisListo(string $to, string $nombre, array $analisis): bool
    {
        $subject = "Análisis de calidad disponible — Acopio {$analisis['acopio_codigo']}";
        $html    = $this->tplAnalisisListo($nombre, $analisis);
        return $this->send($to, $nombre, $subject, $html);
    }

    /**
     * Método genérico: envía un email HTML a un destinatario.
     */
    public function send(string $to, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            $this->connect();
            $this->sendMessage($to, $toName, $subject, $htmlBody, $textBody ?: strip_tags($htmlBody));
            $this->disconnect();
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] Error al enviar email a ' . $to . ': ' . $e->getMessage());
            $this->forceDisconnect();
            return false;
        }
    }

    // ── SMTP Core ────────────────────────────────────────────────────────────

    private function connect(): void
    {
        $host = $this->config['host'];
        $port = (int)($this->config['port'] ?? 587);
        $enc  = $this->config['encryption'] ?? 'tls';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        if ($enc === 'ssl') {
            $this->socket = stream_socket_client(
                "ssl://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
        } else {
            $this->socket = stream_socket_client(
                "tcp://{$host}:{$port}", $errno, $errstr, 30
            );
        }

        if (!$this->socket) {
            throw new \RuntimeException("No se puede conectar a {$host}:{$port} — {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, 30);

        // Saludo del servidor
        $this->expect(220, 'saludo del servidor');

        // EHLO
        $this->command('EHLO ' . $this->getLocalHostname(), 250);

        // STARTTLS (cuando encryption = 'tls')
        if ($enc === 'tls') {
            $this->command('STARTTLS', 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                // Intentar TLS 1.1 como fallback
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Falló el handshake TLS con el servidor SMTP');
                }
            }
            // EHLO de nuevo tras upgrade TLS
            $this->command('EHLO ' . $this->getLocalHostname(), 250);
        }

        // Autenticación AUTH LOGIN
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($this->config['username']), 334);
        $this->command(base64_encode($this->config['password']), 235);
    }

    private function sendMessage(string $to, string $toName, string $subject, string $html, string $text): void
    {
        $from     = $this->config['from_email'];
        $fromName = $this->config['from_name'];

        $this->command("MAIL FROM:<{$from}>", 250);
        $this->command("RCPT TO:<{$to}>", 250);
        $this->command('DATA', 354);

        $boundary = 'bound_' . bin2hex(random_bytes(8));

        $msg  = "From: " . $this->encodeHeader($fromName) . " <{$from}>\r\n";
        $msg .= "To: " . $this->encodeHeader($toName) . " <{$to}>\r\n";
        $msg .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $msg .= "Date: " . date('r') . "\r\n";
        $msg .= "X-Mailer: TrazabilidadCafe-MailService/1.0\r\n";
        $msg .= "\r\n";

        // Parte texto plano
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $msg .= chunk_split(base64_encode($text)) . "\r\n";

        // Parte HTML
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $msg .= chunk_split(base64_encode($html)) . "\r\n";

        $msg .= "--{$boundary}--\r\n";
        $msg .= "\r\n.\r\n";

        fwrite($this->socket, $msg);
        $this->expect(250, 'aceptación del mensaje');
    }

    private function disconnect(): void
    {
        if ($this->socket) {
            fwrite($this->socket, "QUIT\r\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function forceDisconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function command(string $cmd, int $expectedCode): string
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->expect($expectedCode, $cmd);
    }

    private function expect(int $code, string $context = ''): string
    {
        $response = '';
        $timeout  = time() + 30;

        while (time() < $timeout) {
            $line = fgets($this->socket, 1024);
            if ($line === false) break;
            $response .= $line;
            // La última línea del bloque tiene un espacio en posición 3
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }

        $actual = (int)substr($response, 0, 3);
        if ($actual !== $code) {
            throw new \RuntimeException(
                "SMTP: esperado {$code}, recibido {$actual}" .
                ($context ? " (en: {$context})" : '') .
                " — Respuesta: " . trim($response)
            );
        }

        return $response;
    }

    private function encodeHeader(string $text): string
    {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }

    private function getLocalHostname(): string
    {
        $h = gethostname();
        return $h !== false ? $h : 'localhost';
    }

    // ── Templates HTML ───────────────────────────────────────────────────────

    private function tplPasswordReset(string $nombre, string $url): string
    {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);">
      <!-- Cabecera -->
      <tr><td style="background:#1E3932;padding:32px 40px;text-align:center;">
        <div style="font-size:2rem;">☕</div>
        <div style="color:#fff;font-size:1.3rem;font-weight:700;margin-top:8px;">Trazabilidad Café</div>
        <div style="color:#7fb8a4;font-size:.85rem;margin-top:4px;">Sistema de Gestión</div>
      </td></tr>
      <!-- Cuerpo -->
      <tr><td style="padding:36px 40px;">
        <h2 style="color:#1E3932;margin:0 0 12px;font-size:1.2rem;">Restablecer contraseña</h2>
        <p style="color:#4a6b5e;font-size:.95rem;line-height:1.6;margin:0 0 24px;">
          Hola <strong>{$nombre}</strong>,<br><br>
          Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón de abajo para crear una nueva contraseña.
        </p>
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr><td align="center" style="padding:8px 0 28px;">
            <a href="{$url}" style="display:inline-block;background:#00704A;color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:1rem;font-weight:700;letter-spacing:.3px;">
              Restablecer contraseña
            </a>
          </td></tr>
        </table>
        <p style="color:#7B9E94;font-size:.82rem;line-height:1.5;margin:0 0 8px;">
          Este enlace expira en <strong>1 hora</strong>. Si no solicitaste el cambio, puedes ignorar este correo — tu contraseña permanecerá sin cambios.
        </p>
        <p style="color:#aaa;font-size:.78rem;margin:16px 0 0;word-break:break-all;">
          Si el botón no funciona, copia este enlace en tu navegador:<br>
          <a href="{$url}" style="color:#00704A;">{$url}</a>
        </p>
      </td></tr>
      <!-- Pie -->
      <tr><td style="background:#f4f6f5;padding:20px 40px;text-align:center;border-top:1px solid #e8eeec;">
        <p style="color:#aaa;font-size:.78rem;margin:0;">
          &copy; {$year} Trazabilidad Café &mdash; Sistema de Gestión de Café de Especialidad
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function tplVentaConfirmada(string $compradorNombre, array $v): string
    {
        $year        = date('Y');
        $total       = number_format((float)($v['total_usd'] ?? 0), 2);
        $cantidad    = number_format((float)($v['cantidad_kg'] ?? 0), 2);
        $precio      = number_format((float)($v['precio_usd_kg'] ?? 0), 4);
        $entrega     = $v['fecha_entrega'] ?? 'Por confirmar';
        $contrato    = htmlspecialchars($v['numero_contrato'] ?? '');
        $lote        = htmlspecialchars($v['lote_codigo'] ?? '');
        $variedad    = htmlspecialchars($v['variedad'] ?? 'N/A');
        $incoterm    = htmlspecialchars($v['incoterm'] ?? 'FOB');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:40px 0;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);">
      <tr><td style="background:#1E3932;padding:32px 40px;text-align:center;">
        <div style="font-size:2rem;">☕</div>
        <div style="color:#fff;font-size:1.3rem;font-weight:700;margin-top:8px;">Trazabilidad Café</div>
        <div style="color:#7fb8a4;font-size:.85rem;margin-top:4px;">Confirmación de Contrato</div>
      </td></tr>
      <tr><td style="padding:36px 40px;">
        <div style="background:#e8f5ee;border-left:4px solid #00704A;border-radius:0 8px 8px 0;padding:14px 18px;margin-bottom:24px;">
          <span style="color:#00704A;font-weight:700;font-size:1rem;">✓ Contrato confirmado</span>
        </div>
        <p style="color:#4a6b5e;font-size:.95rem;line-height:1.6;margin:0 0 24px;">
          Estimado/a <strong>{$compradorNombre}</strong>,<br><br>
          Nos complace informarle que el contrato de compra <strong>{$contrato}</strong> ha sido confirmado y el proceso de despacho está en marcha.
        </p>
        <!-- Tabla de detalles -->
        <table width="100%" cellpadding="10" cellspacing="0" style="border:1px solid #e0ebe7;border-radius:8px;font-size:.88rem;margin-bottom:24px;">
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Nº Contrato</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">{$contrato}</td>
          </tr>
          <tr>
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Acopio</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">{$lote}</td>
          </tr>
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Variedad</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">{$variedad}</td>
          </tr>
          <tr>
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Cantidad</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">{$cantidad} kg</td>
          </tr>
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Precio unitario</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">USD {$precio}/kg</td>
          </tr>
          <tr>
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Total</td>
            <td style="color:#00704A;font-weight:700;border-bottom:1px solid #e0ebe7;">USD {$total}</td>
          </tr>
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;border-bottom:1px solid #e0ebe7;">Incoterm</td>
            <td style="color:#4a6b5e;border-bottom:1px solid #e0ebe7;">{$incoterm}</td>
          </tr>
          <tr>
            <td style="color:#1E3932;font-weight:700;">Fecha de entrega</td>
            <td style="color:#4a6b5e;">{$entrega}</td>
          </tr>
        </table>
        <p style="color:#7B9E94;font-size:.82rem;line-height:1.5;margin:0;">
          Para consultas sobre su pedido, responda a este correo o contacte al equipo de ventas.
        </p>
      </td></tr>
      <tr><td style="background:#f4f6f5;padding:20px 40px;text-align:center;border-top:1px solid #e8eeec;">
        <p style="color:#aaa;font-size:.78rem;margin:0;">&copy; {$year} Trazabilidad Café</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function tplAlertaStock(string $nombre, array $lote): string
    {
        $year   = date('Y');
        $codigo = htmlspecialchars($lote['codigo'] ?? '');
        $stock  = number_format((float)($lote['peso_actual_kg'] ?? 0), 2);
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:40px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);">
      <tr><td style="background:#1E3932;padding:28px 36px;text-align:center;">
        <div style="color:#fff;font-size:1.2rem;font-weight:700;">⚠ Alerta de Stock — Trazabilidad Café</div>
      </td></tr>
      <tr><td style="padding:32px 36px;">
        <p style="color:#4a6b5e;font-size:.95rem;line-height:1.6;margin:0 0 20px;">
          Hola <strong>{$nombre}</strong>,<br><br>
          El acopio <strong>{$codigo}</strong> tiene un stock bajo de <strong>{$stock} kg</strong> disponibles.
        </p>
        <p style="color:#7B9E94;font-size:.82rem;">Revise el inventario e inicie el proceso de abastecimiento si es necesario.</p>
      </td></tr>
      <tr><td style="background:#f4f6f5;padding:18px 36px;text-align:center;border-top:1px solid #e8eeec;">
        <p style="color:#aaa;font-size:.78rem;margin:0;">&copy; {$year} Trazabilidad Café</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function tplAnalisisListo(string $nombre, array $a): string
    {
        $year    = date('Y');
        $lote    = htmlspecialchars($a['acopio_codigo'] ?? '');
        $score   = number_format((float)($a['score_taza'] ?? 0), 2);
        $clasif  = htmlspecialchars($a['clasificacion'] ?? '');
        $humedad = number_format((float)($a['humedad_pct'] ?? 0), 1);
        $color   = match($a['clasificacion'] ?? '') {
            'specialty' => '#00704A',
            'premium'   => '#2980b9',
            'comercial' => '#e67e22',
            default     => '#c0392b',
        };
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:40px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);">
      <tr><td style="background:#1E3932;padding:28px 36px;text-align:center;">
        <div style="font-size:1.8rem;">🔬</div>
        <div style="color:#fff;font-size:1.2rem;font-weight:700;margin-top:6px;">Análisis de Calidad Disponible</div>
      </td></tr>
      <tr><td style="padding:32px 36px;">
        <p style="color:#4a6b5e;font-size:.95rem;line-height:1.6;margin:0 0 20px;">
          Hola <strong>{$nombre}</strong>, el análisis de calidad del acopio <strong>{$lote}</strong> está listo.
        </p>
        <table width="100%" cellpadding="10" cellspacing="0" style="border:1px solid #e0ebe7;border-radius:8px;font-size:.88rem;margin-bottom:20px;">
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;">Score taza</td>
            <td style="font-weight:700;color:{$color};">{$score} / 100</td>
          </tr>
          <tr>
            <td style="color:#1E3932;font-weight:700;border-top:1px solid #e0ebe7;">Clasificación</td>
            <td style="color:{$color};font-weight:700;text-transform:uppercase;border-top:1px solid #e0ebe7;">{$clasif}</td>
          </tr>
          <tr style="background:#f4f9f6;">
            <td style="color:#1E3932;font-weight:700;border-top:1px solid #e0ebe7;">Humedad</td>
            <td style="color:#4a6b5e;border-top:1px solid #e0ebe7;">{$humedad}%</td>
          </tr>
        </table>
        <p style="color:#7B9E94;font-size:.82rem;">Ingrese al sistema para ver el reporte completo.</p>
      </td></tr>
      <tr><td style="background:#f4f6f5;padding:18px 36px;text-align:center;border-top:1px solid #e8eeec;">
        <p style="color:#aaa;font-size:.78rem;margin:0;">&copy; {$year} Trazabilidad Café</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}
