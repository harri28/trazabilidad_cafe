INSERT INTO laboratorio_analisis
  (lote_id, fecha_analisis, analista, laboratorio,
   humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2,
   score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo,
   uniformidad, balance, limpieza_taza, dulzura, defecto_taza,
   notas_catacion, aprobado)
VALUES
  (1, '2026-05-10', 'Carlos Quispe', 'Lab. Calidad Cajamarca',
   11.20, 79.50, 682.00, 0, 2,
   83.75, 8.25, 8.50, 8.25, 7.75, 8.25, 8.00,
   10.00, 8.00, 10.00, 10.00, 0.00,
   'Notas a chocolate amargo y frutos rojos. Acidez brillante, cuerpo medio-alto. Excelente uniformidad.', true),

  (2, '2026-05-15', 'Maria Huanca', 'Lab. Calidad Cajamarca',
   12.10, 76.20, 668.00, 1, 4,
   76.50, 7.50, 7.75, 7.50, 7.25, 7.75, 7.50,
   10.00, 7.50, 10.00, 10.00, 0.00,
   'Perfil dulce con notas a caramelo y nuez. Acidez moderada, cuerpo medio. Buena limpieza.', true),

  (3, '2026-05-20', 'Carlos Quispe', 'Lab. Calidad Cajamarca',
   13.80, 71.30, 645.00, 3, 8,
   64.25, 6.50, 6.75, 6.50, 6.25, 6.50, 6.50,
   8.00, 6.50, 8.00, 8.00, 0.00,
   'Perfil plano, notas a madera y tierra. Acidez baja, defectos leves. Apto para mezclas comerciales.', false);
