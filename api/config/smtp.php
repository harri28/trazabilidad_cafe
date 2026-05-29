<?php
/**
 * Configuración SMTP — Trazabilidad Café
 *
 * Proveedores comunes:
 *   Gmail  → host: smtp.gmail.com, port: 587, encryption: tls
 *            (requiere "Contraseña de aplicación" en Cuenta Google)
 *   Outlook→ host: smtp-mail.outlook.com, port: 587, encryption: tls
 *   IONOS  → host: smtp.ionos.com, port: 587, encryption: tls
 *   Custom → usar datos del proveedor de hosting
 */
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',                       // 'tls' (STARTTLS), 'ssl' o ''
    'username'   => 'harristr045@gmail.com',       // ← tu cuenta Gmail
    'password'   => 'xxxx xxxx xxxx xxxx',        // ← App Password (16 caracteres de Google)
    'from_email' => 'harristr045@gmail.com',
    'from_name'  => 'Trazabilidad Café',
    'app_url'    => 'http://localhost/trazabilidad_cafe/public',
];
