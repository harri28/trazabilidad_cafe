-- ============================================================
--  SEED: Análisis de Laboratorio
--  Productores: Antonio Carrasco Cruz y Javier Tocto Sarango
--  Generado: 2026-04-09
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- JAVIER TOCTO SARANGO  |  lote_id = 2  (LOT-2024-0002)
-- ─────────────────────────────────────────────────────────────

-- Análisis 2 — Laboratorio externo certificado (marzo 2026)
INSERT INTO laboratorio_analisis (
    lote_id, fecha_analisis, analista, laboratorio,
    humedad_pct, rendimiento_pct, densidad_gr_l,
    defectos_cat1, defectos_cat2,
    score_taza, fragancia, aroma, sabor, post_gusto,
    acidez, cuerpo, uniformidad, balance, limpieza_taza,
    dulzura, defecto_taza, notas_catacion, aprobado
) VALUES (
    2, '2026-03-15', 'Carlos Mendoza Rivas', 'CQI Certified Lab Lima',
    11.80, 78.50, 695.00,
    0, 2,
    85.25, 8.25, 8.25, 8.50, 8.25,
    8.25, 8.00, 10.00, 8.25, 10.00,
    10.00, 0.00,
    'Taza limpia con alta dulzura. Notas a durazno maduro, panela y flores blancas. Acidez maálica brillante. Cuerpo medio-alto. Excelente uniformidad entre tazas. Cosecha Typica de altura superior.',
    true
);

-- Análisis 3 — Catación interna (abril 2026)
INSERT INTO laboratorio_analisis (
    lote_id, fecha_analisis, analista, laboratorio,
    humedad_pct, rendimiento_pct, densidad_gr_l,
    defectos_cat1, defectos_cat2,
    score_taza, fragancia, aroma, sabor, post_gusto,
    acidez, cuerpo, uniformidad, balance, limpieza_taza,
    dulzura, defecto_taza, notas_catacion, aprobado
) VALUES (
    2, '2026-04-03', 'María López Torres', 'Interno',
    12.10, 77.80, 688.00,
    0, 3,
    84.50, 8.00, 8.25, 8.50, 8.00,
    8.25, 8.00, 10.00, 8.00, 10.00,
    10.00, 0.00,
    'Fragancia intensa a caramelo y almendra. En taza: chocolate amargo fino, cereza, ligero cítrico. Post-gusto largo y agradable. Cuerpo cremoso. Bien balanceado. Apto para exportación specialty.',
    true
);

-- ─────────────────────────────────────────────────────────────
-- ANTONIO CARRASCO CRUZ  |  lote_id = 4  (LOT-2024-0004)
-- ─────────────────────────────────────────────────────────────

-- Análisis 2 — Laboratorio externo certificado (marzo 2026)
INSERT INTO laboratorio_analisis (
    lote_id, fecha_analisis, analista, laboratorio,
    humedad_pct, rendimiento_pct, densidad_gr_l,
    defectos_cat1, defectos_cat2,
    score_taza, fragancia, aroma, sabor, post_gusto,
    acidez, cuerpo, uniformidad, balance, limpieza_taza,
    dulzura, defecto_taza, notas_catacion, aprobado
) VALUES (
    4, '2026-03-18', 'Carlos Mendoza Rivas', 'CQI Certified Lab Lima',
    11.50, 80.20, 710.00,
    0, 1,
    86.00, 8.50, 8.50, 8.75, 8.25,
    8.50, 8.25, 10.00, 8.25, 10.00,
    10.00, 0.00,
    'Café sobresaliente. Fragancia floral compleja. En taza destacan jazmín, miel de abeja, uva pasa y naranja. Acidez tartárica muy elegante. Cuerpo suave pero presente. Dulzura excepcional. Candidato a lote microlote de exportación premium.',
    true
);

-- Análisis 3 — Catación de verificación (abril 2026)
INSERT INTO laboratorio_analisis (
    lote_id, fecha_analisis, analista, laboratorio,
    humedad_pct, rendimiento_pct, densidad_gr_l,
    defectos_cat1, defectos_cat2,
    score_taza, fragancia, aroma, sabor, post_gusto,
    acidez, cuerpo, uniformidad, balance, limpieza_taza,
    dulzura, defecto_taza, notas_catacion, aprobado
) VALUES (
    4, '2026-04-07', 'Pedro Flores Quispe', 'Interno',
    11.90, 79.60, 702.00,
    0, 2,
    85.50, 8.25, 8.50, 8.50, 8.25,
    8.50, 8.25, 10.00, 8.25, 10.00,
    10.00, 0.00,
    'Consistencia confirmada entre sesiones. Notas a capulí, melaza y almendra tostada. Acidez viva y limpia. Uniformidad perfecta. Score estable respecto al análisis anterior. Aprobado para contrato de exportación.',
    true
);

-- ─────────────────────────────────────────────────────────────
-- ANTONIO CARRASCO CRUZ  |  lote_id = 11  (LOT-2026-0001)
-- ─────────────────────────────────────────────────────────────

-- Análisis 1 — Primer análisis campaña 2026
INSERT INTO laboratorio_analisis (
    lote_id, fecha_analisis, analista, laboratorio,
    humedad_pct, rendimiento_pct, densidad_gr_l,
    defectos_cat1, defectos_cat2,
    score_taza, fragancia, aroma, sabor, post_gusto,
    acidez, cuerpo, uniformidad, balance, limpieza_taza,
    dulzura, defecto_taza, notas_catacion, aprobado
) VALUES (
    11, '2026-04-08', 'María López Torres', 'Interno',
    12.50, 75.00, 672.00,
    1, 4,
    82.00, 7.75, 8.00, 8.25, 7.75,
    8.00, 7.75, 10.00, 7.75, 10.00,
    10.00, 0.00,
    'Primer análisis lote nuevo 2026. Perfil dulce con notas a maracuyá, cacao y panela. Buena acidez cítrica. Requiere ajuste de humedad antes de despacho. Potencial specialty confirmado. Programar re-análisis en 15 días.',
    true
);
