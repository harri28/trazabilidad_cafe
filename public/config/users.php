<?php
// Usuarios del sistema — contraseñas almacenadas con bcrypt (password_hash)
// Para agregar usuarios: php -r "echo password_hash('nueva_clave', PASSWORD_DEFAULT);"
// El campo 'email' se usa para recuperación de contraseña vía SMTP.
return array (
  'harris' =>
  array (
    'password' => '$2y$10$4.Y9WcbWiNiPoEyNOhxade/f1zzTeJ8vaRd46zPsL5a9uK/gbSbwq',
    'nombre' => 'Harris',
    'rol' => 'Administrador',
    'email' => 'harristr045@gmail.com',
  ),
  'admin' =>
  array (
    'password' => '$2y$10$ECOg1BhSg4oY9Nv9elEIlOCupz6rSVAiyC9QaC0BnsOhclJYawuYa',
    'nombre' => 'Administrador',
    'rol' => 'Administrador',
    'email' => 'harristr045@gmail.com',
  ),
);
