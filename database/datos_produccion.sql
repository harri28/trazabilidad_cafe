--
-- PostgreSQL database dump
--

\restrict IBHi5KmKUzGCJoqLCDQ6oyMVODQ1AyhKyG9NPZGKHNf5huUgeB8tCGNmxmvavBX

-- Dumped from database version 17.9
-- Dumped by pg_dump version 17.9

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: -
--

SET SESSION AUTHORIZATION DEFAULT;

ALTER TABLE public.clientes DISABLE TRIGGER ALL;

INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (2, 'comprador', 'OLAM International Ltd.', '20601234567', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USA', 'USD', true, 'Principal comprador exportacion — contratos FOB Callao', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (3, 'productor', 'SEGUNDO EMILIO CALLE CUEVA', '03127948', NULL, NULL, NULL, NULL, 'Cajamarca', 'SANTA ROSA', 'EL PALMAL', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: EL PALMAL-SANTA ROSA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (4, 'productor', 'JAVIER TOCTO SARANGO', '02878717', NULL, NULL, NULL, NULL, 'Cajamarca', 'LA COIPA', 'EL REJO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: EL REJO-LA COIPA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (5, 'productor', 'JOSE LEONIL ARMIJOS GUERRERO', '80679617', NULL, NULL, NULL, NULL, 'Cajamarca', 'LA COIPA', 'EL PINDO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: EL PINDO-LA COIPA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (6, 'productor', 'ANTONIO CARRASCO CRUZ', '80602255', NULL, NULL, NULL, NULL, 'Cajamarca', 'JAEN', 'LAUREL ALTO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: LAUREL ALTO-JAEN', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (7, 'productor', 'ARTEMIO DE LA CRUZ VENTURA', '80142277', NULL, NULL, NULL, NULL, 'Cajamarca', 'LA COIPA', 'HUACORA', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: HUACORA  - LA COIPA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (8, 'productor', 'MARIA CELIDA MEDINA DIAZ', '76629557', NULL, NULL, NULL, NULL, 'Cajamarca', 'CHONTALI', 'RUMISAPA', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: RUMISAPA - CHONTALI', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (9, 'productor', 'LEYTER CIEZA TERRONES', '76353597', NULL, NULL, NULL, NULL, 'Cajamarca', 'SANTA ROSA', 'EL PALMAL', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: EL PALMAL-SANTA ROSA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (10, 'productor', 'YOMER TORRES MENDOZA', '75883041', NULL, NULL, NULL, NULL, 'Cajamarca', 'JAEN', 'LAUREL ALTO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: LAUREL ALTO-JAEN', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (11, 'productor', 'CHRISTIAN MEJIA ALTAMIRANO', '75746523', NULL, NULL, NULL, NULL, 'Cajamarca', 'JAEN', 'LAUREL ALTO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: LAUREL ALTO-JAEN', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (12, 'productor', 'JOSE JHORDIN PEDRAZA CERCADO', '75481970', NULL, NULL, NULL, NULL, 'Cajamarca', 'JAEN', 'PALMA CENTRAL', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: PALMA CENTRAL-JAEN', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (13, 'productor', 'WILMER MUNDACA FARRO', '75475843', NULL, NULL, NULL, NULL, 'Cajamarca', 'JAEN', 'ZONANGA ALTA', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: ZONANGA ALTA-JAEN', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (14, 'productor', 'WALTER JHOEL NEYRA CAMIZAN', '75448140', NULL, NULL, NULL, NULL, 'Cajamarca', 'LA COIPA', 'EL REJO', 1800, NULL, 'Ingeniería Consultora', NULL, 'USD', true, 'Sector: EL REJO-LA COIPA', '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (15, 'productor', 'Harris Steifer Tinoco Rodas', '73463054', NULL, '932269582', 'harristr045@gamil.com', NULL, 'San Martín', 'San Martín', 'Tarapoto', NULL, NULL, 'El Comerciante', NULL, 'USD', true, 'ninguna', '2026-04-15 12:15:44.928103', '2026-04-15 12:15:44.928103');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (16, 'productor', 'Felimon', '10734630549', NULL, '932269582', 'usuario@gmail.com', NULL, 'San Martín', 'San Martín', 'Tarapoto', NULL, NULL, 'Asociación', NULL, 'USD', true, NULL, '2026-05-29 04:19:16.728484', '2026-05-29 04:19:16.728484');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (17, 'productor', 'Felimon', '10466798440', NULL, '932458384', 'felimon@gmail.com', NULL, 'San Martín', 'San Martín', 'Tarapoto', NULL, NULL, 'El cafeterito', NULL, 'USD', true, NULL, '2026-05-29 10:41:26.824786', '2026-05-29 10:41:26.824786');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (18, 'comprador', 'Agrotech Partner', '20606359218', NULL, '932269582', 'agrotechpartner01@gmail.com', NULL, 'San Martín', 'San Martín', 'Tarapoto', NULL, NULL, 'Cafe-Tarapoto', NULL, 'USD', true, 'Ninguna', '2026-05-29 11:00:04.58544', '2026-05-29 11:00:04.58544');
INSERT INTO public.clientes (id, tipo, razon_social, ruc_dni, contacto, telefono, email, direccion, departamento, provincia, distrito, altitud_msnm, hectareas, asociacion, pais_destino, moneda_pref, activo, notas, creado_en, actualizado_en) VALUES (19, 'productor', 'Harri', '1073463054', NULL, '932357463', 'harri@gmail.com', NULL, 'san martin', 'san martin', 'tarapoto', NULL, NULL, 'ninguna', NULL, 'USD', true, NULL, '2026-06-01 11:47:57.885573', '2026-06-01 11:47:57.885573');


ALTER TABLE public.clientes ENABLE TRIGGER ALL;

--
-- Data for Name: acopios; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.acopios DISABLE TRIGGER ALL;

INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (11, 'LOT-2026-0001', 'parcial', 2, 6, '2026-04-09', 2026, 3456.000, 0.000, NULL, NULL, 'f', 1800, '3rergerewr', 'lavado', NULL, '2026-04-09 04:05:35.794582', '2026-05-29 03:32:19.403935', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (3, 'LOT-2024-0003', 'parcial', 1, 5, '2024-05-02', 2024, 142.600, 42.600, NULL, 'Cajamarca', 'EL PINDO-LA COIPA', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 70.0% | Humedad: 12.0%', '2026-04-06 19:11:46.743913', '2026-05-29 12:21:10.512816', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (2, 'LOT-2024-0002', 'parcial', 1, 4, '2024-05-02', 2024, 139.400, 9.400, NULL, 'Cajamarca', 'EL REJO-LA COIPA', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 70.0% | Humedad: 13.0%', '2026-04-06 19:11:46.743913', '2026-05-29 12:28:59.161526', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (1, 'LOT-2024-0001', 'acopio', 1, 3, '2024-05-02', 2024, 151.600, 151.600, NULL, 'Cajamarca', 'EL PALMAL-SANTA ROSA', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 72.0% | Humedad: 12.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (6, 'LOT-2024-0006', 'vendido', 1, 8, '2024-05-02', 2024, 141.600, 141.600, NULL, 'Cajamarca', 'RUMISAPA - CHONTALI', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 68.5% | Humedad: 13.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (7, 'LOT-2024-0007', 'vendido', 1, 9, '2024-05-02', 2024, 144.600, 144.600, NULL, 'Cajamarca', 'EL PALMAL-SANTA ROSA', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 70.5% | Humedad: 11.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (8, 'LOT-2024-0008', 'vendido', 1, 10, '2024-05-02', 2024, 146.900, 146.900, NULL, 'Cajamarca', 'LAUREL ALTO-JAEN', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 69.0% | Humedad: 13.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (9, 'LOT-2024-0009', 'vendido', 1, 11, '2024-05-02', 2024, 142.100, 142.100, NULL, 'Cajamarca', 'LAUREL ALTO-JAEN', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 72.0% | Humedad: 13.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (10, 'LOT-2024-0010', 'vendido', 1, 12, '2024-05-02', 2024, 142.600, 142.600, NULL, 'Cajamarca', 'PALMA CENTRAL-JAEN', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 73.0% | Humedad: 12.0%', '2026-04-06 19:11:46.743913', '2026-05-29 03:31:28.256101', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (12, 'LOT-2026-0002', 'parcial', 1, 15, '2026-04-15', 2026, 1000.000, 0.000, NULL, NULL, 'Ninguno', 3500, 'Typa', 'lavado', NULL, '2026-04-15 12:19:51.984022', '2026-05-29 12:34:15.62268', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (4, 'LOT-2024-0004', 'acopio', 1, 6, '2024-05-02', 2024, 143.900, 3.900, NULL, 'Cajamarca', 'LAUREL ALTO-JAEN', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 71.0% | Humedad: 12.0%', '2026-04-06 19:11:46.743913', '2026-05-29 12:34:48.842548', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (5, 'LOT-2024-0005', 'parcial', 1, 7, '2024-05-02', 2024, 140.100, 40.100, NULL, 'Cajamarca', 'HUACORA  - LA COIPA', 1800, 'Typica', 'lavado', 'Acopio directo campaña 2024 | Rendimiento: 70.0% | Humedad: 12.0%', '2026-04-06 19:11:46.743913', '2026-05-29 12:38:03.525856', 0, NULL, NULL, NULL, NULL);
INSERT INTO public.acopios (id, codigo, estado, tipo_cafe_id, productor_id, fecha_acopio, "campaña", peso_inicial_kg, peso_actual_kg, peso_final_kg, region, finca, altitud_msnm, variedad, proceso_beneficio, notas, creado_en, actualizado_en, sacos, humedad_entrada_pct, peso_bruto_kg, rend_entrada_pct, hora_acopio) VALUES (15, 'ACOP-2026-0003', 'acopio', 1, 15, '2026-06-01', 2026, 151.600, 151.600, NULL, 'EL PALMAL-SANTA ROSA', NULL, NULL, NULL, 'lavado', NULL, '2026-06-01 00:22:28.976379', '2026-06-01 00:22:28.976379', 2, 12.00, 152.000, NULL, NULL);


ALTER TABLE public.acopios ENABLE TRIGGER ALL;

--
-- Data for Name: acopio_certificaciones; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.acopio_certificaciones DISABLE TRIGGER ALL;



ALTER TABLE public.acopio_certificaciones ENABLE TRIGGER ALL;

--
-- Data for Name: acopio_eventos; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.acopio_eventos DISABLE TRIGGER ALL;

INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (41, 6, 'Venta', 'Venta Confirmada', 'Contrato FG24-043 - 141.600 kg a USD 5.8490/kg', 'venta', 1, 'sistema', '2024-08-15 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (42, 7, 'Venta', 'Venta Confirmada', 'Contrato FG24-044 - 144.600 kg a USD 5.8490/kg', 'venta', 2, 'sistema', '2024-08-15 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (43, 8, 'Venta', 'Venta Confirmada', 'Contrato FG24-045 - 146.900 kg a USD 5.9483/kg', 'venta', 3, 'sistema', '2024-08-19 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (44, 9, 'Venta', 'Venta Confirmada', 'Contrato FG24-046 - 142.100 kg a USD 5.9483/kg', 'venta', 4, 'sistema', '2024-08-19 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (45, 10, 'Venta', 'Venta Confirmada', 'Contrato FG24-047 - 142.600 kg a USD 5.9703/kg', 'venta', 5, 'sistema', '2024-08-19 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (46, 5, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0006 - 280.000 kg a USD 12.0000/kg', 'venta', 6, 'sistema', '2026-04-09 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (47, 11, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0007 - 6912.000 kg a USD 12.0000/kg', 'venta', 7, 'sistema', '2026-04-09 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (48, 12, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0009 - 500.000 kg a USD 4.8000/kg', 'venta', 9, 'harri28', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (49, 3, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0011 - 100.000 kg a USD 12.3000/kg', 'venta', 11, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (50, 2, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0012 - 100.000 kg a USD 12.3000/kg', 'venta', 12, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (51, 2, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0013 - 30.000 kg a USD 12.0000/kg', 'venta', 13, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (52, 4, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0008 - 287.000 kg a USD 13.0000/kg', 'venta', 8, 'sistema', '2026-04-09 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (53, 12, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0010 - 500.000 kg a USD 12.0000/kg', 'venta', 10, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (54, 12, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0014 - 500.000 kg a USD 12.0000/kg', 'venta', 14, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (55, 4, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0015 - 140.000 kg a USD 12.0000/kg', 'venta', 15, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (56, 5, 'Venta', 'Venta Confirmada', 'Contrato CONT-2026-0016 - 100.000 kg a USD 12.0000/kg', 'venta', 16, 'sistema', '2026-05-29 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (57, 12, 'Estado Venta', 'Venta CONT-2026-0014: confirmado → en_proceso', 'Contrato CONT-2026-0014 — 500.000 kg', 'venta', 14, 'sistema', '2026-05-29 12:54:57.206739');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (58, 12, 'Estado Venta', 'Venta CONT-2026-0014: en_proceso → confirmado', 'Contrato CONT-2026-0014 — 500.000 kg', 'venta', 14, 'sistema', '2026-05-29 12:55:21.36211');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (29, 11, 'Acopio', 'Lote Registrado', 'Ingreso de 3456.000 kg - Finca: f', 'acopio', 11, 'sistema', '2026-04-09 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (30, 3, 'Acopio', 'Lote Registrado', 'Ingreso de 142.600 kg - Finca: EL PINDO-LA COIPA', 'acopio', 3, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (31, 2, 'Acopio', 'Lote Registrado', 'Ingreso de 139.400 kg - Finca: EL REJO-LA COIPA', 'acopio', 2, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (32, 1, 'Acopio', 'Lote Registrado', 'Ingreso de 151.600 kg - Finca: EL PALMAL-SANTA ROSA', 'acopio', 1, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (33, 6, 'Acopio', 'Lote Registrado', 'Ingreso de 141.600 kg - Finca: RUMISAPA - CHONTALI', 'acopio', 6, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (34, 7, 'Acopio', 'Lote Registrado', 'Ingreso de 144.600 kg - Finca: EL PALMAL-SANTA ROSA', 'acopio', 7, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (35, 8, 'Acopio', 'Lote Registrado', 'Ingreso de 146.900 kg - Finca: LAUREL ALTO-JAEN', 'acopio', 8, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (36, 9, 'Acopio', 'Lote Registrado', 'Ingreso de 142.100 kg - Finca: LAUREL ALTO-JAEN', 'acopio', 9, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (37, 10, 'Acopio', 'Lote Registrado', 'Ingreso de 142.600 kg - Finca: PALMA CENTRAL-JAEN', 'acopio', 10, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (38, 12, 'Acopio', 'Lote Registrado', 'Ingreso de 1000.000 kg - Finca: Ninguno', 'acopio', 12, 'sistema', '2026-04-15 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (39, 4, 'Acopio', 'Lote Registrado', 'Ingreso de 143.900 kg - Finca: LAUREL ALTO-JAEN', 'acopio', 4, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (40, 5, 'Acopio', 'Lote Registrado', 'Ingreso de 140.100 kg - Finca: HUACORA  - LA COIPA', 'acopio', 5, 'sistema', '2024-05-02 00:00:00');
INSERT INTO public.acopio_eventos (id, acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en) VALUES (59, 15, 'Acopio', 'Acopio Registrado', 'Ingreso de 151.600 kg - Finca: N/A', 'acopio', 15, 'sistema', '2026-06-01 00:00:00');


ALTER TABLE public.acopio_eventos ENABLE TRIGGER ALL;

--
-- Data for Name: almacenes; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.almacenes DISABLE TRIGGER ALL;



ALTER TABLE public.almacenes ENABLE TRIGGER ALL;

--
-- Data for Name: asientos_contables; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.asientos_contables DISABLE TRIGGER ALL;



ALTER TABLE public.asientos_contables ENABLE TRIGGER ALL;

--
-- Data for Name: centros_costo; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.centros_costo DISABLE TRIGGER ALL;

INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (1, 'PROD', 'Producción', NULL, NULL, true);
INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (2, 'INVEN', 'Inventario', NULL, NULL, true);
INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (3, 'COMP', 'Compras', NULL, NULL, true);
INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (4, 'VENT', 'Ventas', NULL, NULL, true);
INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (5, 'ADMIN', 'Administración', NULL, NULL, true);
INSERT INTO public.centros_costo (id, codigo, nombre, descripcion, padre_id, activo) VALUES (6, 'CALID', 'Control de Calidad', NULL, NULL, true);


ALTER TABLE public.centros_costo ENABLE TRIGGER ALL;

--
-- Data for Name: plan_cuentas; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.plan_cuentas DISABLE TRIGGER ALL;



ALTER TABLE public.plan_cuentas ENABLE TRIGGER ALL;

--
-- Data for Name: asiento_lineas; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.asiento_lineas DISABLE TRIGGER ALL;



ALTER TABLE public.asiento_lineas ENABLE TRIGGER ALL;

--
-- Data for Name: auditorias; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.auditorias DISABLE TRIGGER ALL;

INSERT INTO public.auditorias (id, codigo, tipo, titulo, descripcion, auditor, organismo, fecha_auditoria, fecha_proxima, estado, resultado, puntaje, campana, notas, creado_en) VALUES (1, 'AUD-2026-0001', 'interna', 'Auditoria Interna Semestral 2026', NULL, 'Carlos Mendoza', NULL, '2026-06-15', NULL, 'programada', NULL, NULL, 2026, NULL, '2026-05-29 03:44:16.361536');


ALTER TABLE public.auditorias ENABLE TRIGGER ALL;

--
-- Data for Name: auditoria_hallazgos; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.auditoria_hallazgos DISABLE TRIGGER ALL;

INSERT INTO public.auditoria_hallazgos (id, auditoria_id, tipo, descripcion, area, responsable, fecha_limite, estado, accion_correctiva, fecha_cierre, evidencia) VALUES (1, 1, 'no_conformidad_menor', 'Falta registro de temperatura en almacen', 'Almacen', 'Jefe de Almacen', '2026-06-30', 'abierto', NULL, NULL, NULL);


ALTER TABLE public.auditoria_hallazgos ENABLE TRIGGER ALL;

--
-- Data for Name: backups_registro; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.backups_registro DISABLE TRIGGER ALL;



ALTER TABLE public.backups_registro ENABLE TRIGGER ALL;

--
-- Data for Name: campanas; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.campanas DISABLE TRIGGER ALL;

INSERT INTO public.campanas ("año", fecha_inicio, fecha_fin, estado, notas, creado_en) VALUES (2025, '2025-01-01', NULL, 'cerrada', NULL, '2026-05-29 00:07:20.965295');
INSERT INTO public.campanas ("año", fecha_inicio, fecha_fin, estado, notas, creado_en) VALUES (2024, '2024-01-01', NULL, 'archivada', NULL, '2026-05-29 00:07:20.965295');
INSERT INTO public.campanas ("año", fecha_inicio, fecha_fin, estado, notas, creado_en) VALUES (2026, '2026-05-29', '2026-05-29', 'activa', NULL, '2026-05-29 00:07:20.965295');


ALTER TABLE public.campanas ENABLE TRIGGER ALL;

--
-- Data for Name: capacitaciones; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.capacitaciones DISABLE TRIGGER ALL;

INSERT INTO public.capacitaciones (id, titulo, descripcion, instructor, organizacion, fecha_inicio, fecha_fin, lugar, modalidad, estado, max_participantes, campana, notas, creado_en) VALUES (1, 'Buenas Practicas de Acopio', NULL, 'Ing. Maria Lopez', 'SENASA', '2026-06-10', NULL, 'Cajamarca', 'presencial', 'programado', 25, 2026, NULL, '2026-05-29 03:43:39.261184');


ALTER TABLE public.capacitaciones ENABLE TRIGGER ALL;

--
-- Data for Name: capacitacion_participantes; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.capacitacion_participantes DISABLE TRIGGER ALL;

INSERT INTO public.capacitacion_participantes (id, capacitacion_id, cliente_id, nombre_participante, cargo, asistio, certificado_emitido, notas) VALUES (1, 1, NULL, 'Pedro Ramirez', 'Productor', true, false, NULL);


ALTER TABLE public.capacitacion_participantes ENABLE TRIGGER ALL;

--
-- Data for Name: ventas; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.ventas DISABLE TRIGGER ALL;

INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (1, 'FG24-043', 'confirmado', 2, 6, '2024-08-15', '2024-08-15', 141.600, 5.8490, 3.7440, 'USD', 'FOB', 'Callao', NULL, NULL, 80.00, 'Venta a OLAM | Contrato FG24-043 | Score mín: 80 pts', NULL, '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (2, 'FG24-044', 'confirmado', 2, 7, '2024-08-15', '2024-08-15', 144.600, 5.8490, 3.7440, 'USD', 'FOB', 'Callao', NULL, NULL, 80.00, 'Venta a OLAM | Contrato FG24-044 | Score mín: 80 pts', NULL, '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (3, 'FG24-045', 'confirmado', 2, 8, '2024-08-19', '2024-08-19', 146.900, 5.9483, 3.7480, 'USD', 'FOB', 'Callao', NULL, NULL, 80.00, 'Venta a OLAM | Contrato FG24-045 | Score mín: 80 pts', NULL, '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (4, 'FG24-046', 'confirmado', 2, 9, '2024-08-19', '2024-08-19', 142.100, 5.9483, 3.7480, 'USD', 'FOB', 'Callao', NULL, NULL, 80.00, 'Venta a OLAM | Contrato FG24-046 | Score mín: 80 pts', NULL, '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (5, 'FG24-047', 'confirmado', 2, 10, '2024-08-19', '2024-08-19', 142.600, 5.9703, 3.7480, 'USD', 'FOB', 'Callao', NULL, NULL, 80.00, 'Venta a OLAM | Contrato FG24-047 | Score mín: 80 pts', NULL, '2026-04-06 19:11:46.743913', '2026-04-06 19:11:46.743913', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (6, 'CONT-2026-0006', 'cancelado', 2, 5, '2026-04-09', NULL, 280.000, 12.0000, 3.7000, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-04-09 03:26:57.166007', '2026-04-09 03:55:20.316415', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (7, 'CONT-2026-0007', 'cancelado', 2, 11, '2026-04-09', NULL, 6912.000, 12.0000, 1.0000, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-04-09 09:29:33.543066', '2026-05-29 01:28:17.54176', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (9, 'CONT-2026-0009', 'entregado', 2, 12, '2026-05-29', '2026-06-30', 500.000, 4.8000, 3.7500, 'USD', 'FOB', 'Callao', NULL, NULL, NULL, 'Prueba de venta via API', 'harri28', '2026-05-29 11:49:46.658156', '2026-05-29 11:49:59.040711', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (11, 'CONT-2026-0011', 'confirmado', 18, 3, '2026-05-29', NULL, 100.000, 12.3000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:21:10.467125', '2026-05-29 12:21:10.512816', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (12, 'CONT-2026-0012', 'confirmado', 18, 2, '2026-05-29', NULL, 100.000, 12.3000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:22:47.384051', '2026-05-29 12:22:47.425859', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (13, 'CONT-2026-0013', 'confirmado', 18, 2, '2026-05-29', NULL, 30.000, 12.0000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:28:58.999453', '2026-05-29 12:28:59.161526', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (8, 'CONT-2026-0008', 'cancelado', 2, 4, '2026-04-09', NULL, 287.000, 13.0000, 1.0000, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-04-09 09:33:52.670216', '2026-05-29 12:33:52.225068', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (10, 'CONT-2026-0010', 'cancelado', 18, 12, '2026-05-29', NULL, 500.000, 12.0000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:07:57.825514', '2026-05-29 12:33:52.225068', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (15, 'CONT-2026-0015', 'confirmado', 18, 4, '2026-05-29', NULL, 140.000, 12.0000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:34:48.842548', '2026-05-29 12:34:48.842548', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (16, 'CONT-2026-0016', 'confirmado', 18, 5, '2026-05-29', NULL, 100.000, 12.0000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:38:03.525856', '2026-05-29 12:38:03.525856', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO public.ventas (id, numero_contrato, estado, comprador_id, acopio_id, fecha_contrato, fecha_entrega, cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura, incoterm, puerto_embarque, humedad_max_pct, defectos_max, score_min, notas, usuario, creado_en, actualizado_en, sunat_documento_id, sunat_tipo, sunat_serie, sunat_numero, sunat_estado, sunat_cdr_descripcion, sunat_emitido_en) VALUES (14, 'CONT-2026-0014', 'confirmado', 18, 12, '2026-05-29', NULL, 500.000, 12.0000, 3.7500, 'USD', 'FOB', NULL, NULL, NULL, NULL, NULL, 'sistema', '2026-05-29 12:34:15.62268', '2026-05-29 12:55:21.36211', NULL, NULL, NULL, NULL, NULL, NULL, NULL);


ALTER TABLE public.ventas ENABLE TRIGGER ALL;

--
-- Data for Name: cotizaciones; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.cotizaciones DISABLE TRIGGER ALL;



ALTER TABLE public.cotizaciones ENABLE TRIGGER ALL;

--
-- Data for Name: proveedores; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.proveedores DISABLE TRIGGER ALL;

INSERT INTO public.proveedores (id, razon_social, ruc, contacto, telefono, email, direccion, categoria, condiciones_pago, activo, notas, creado_en) VALUES (1, 'agrotech partner', '123456778', NULL, '12345678', '2345@gmai.com', NULL, 'insumos', '12', true, NULL, '2026-04-09 04:07:45.478059');
INSERT INTO public.proveedores (id, razon_social, ruc, contacto, telefono, email, direccion, categoria, condiciones_pago, activo, notas, creado_en) VALUES (2, 'Agrotech Partner SAC', '20606359218', NULL, '952932465', 'agrotecpartner@gmail.com', NULL, 'otro', 'Al contado', true, NULL, '2026-05-29 10:45:13.54187');


ALTER TABLE public.proveedores ENABLE TRIGGER ALL;

--
-- Data for Name: requisiciones; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.requisiciones DISABLE TRIGGER ALL;



ALTER TABLE public.requisiciones ENABLE TRIGGER ALL;

--
-- Data for Name: ordenes_compra; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.ordenes_compra DISABLE TRIGGER ALL;



ALTER TABLE public.ordenes_compra ENABLE TRIGGER ALL;

--
-- Data for Name: cuentas_pagar; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.cuentas_pagar DISABLE TRIGGER ALL;



ALTER TABLE public.cuentas_pagar ENABLE TRIGGER ALL;

--
-- Data for Name: flujo_caja; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.flujo_caja DISABLE TRIGGER ALL;

INSERT INTO public.flujo_caja (id, fecha, tipo, categoria, concepto, monto, moneda, tipo_cambio, referencia_tipo, referencia_id, cuenta_banco, centro_costo_id, notas, creado_en) VALUES (1, '2026-05-29', 'ingreso', 'operativo', 'Cobro de factura', 500.00, 'PEN', 1.0000, NULL, NULL, '055123456688', NULL, NULL, '2026-05-29 11:02:30.055114');


ALTER TABLE public.flujo_caja ENABLE TRIGGER ALL;

--
-- Data for Name: kardex; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.kardex DISABLE TRIGGER ALL;

INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (1, 1, 'entrada', 'Acopio café pergamino — LOT-2024-0001', '2024-05-02', 151.600, 12.0740, 'PEN', 1.0000, 0.4000, 303.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.4740/kg | Total: S/ 1891.06', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (2, 2, 'entrada', 'Acopio café pergamino — LOT-2024-0002', '2024-05-02', 139.400, 11.7300, 'PEN', 1.0000, 0.4000, 278.800, NULL, NULL, NULL, 'Sacos: 3 | Precio + Prima: S/ 12.1300/kg | Total: S/ 1690.92', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (3, 3, 'entrada', 'Acopio café pergamino — LOT-2024-0003', '2024-05-02', 142.600, 11.7300, 'PEN', 1.0000, 0.4000, 285.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.1300/kg | Total: S/ 1729.74', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (4, 4, 'entrada', 'Acopio café pergamino — LOT-2024-0004', '2024-05-02', 143.900, 11.8930, 'PEN', 1.0000, 0.4000, 287.800, NULL, NULL, NULL, 'Sacos: 3 | Precio + Prima: S/ 12.2930/kg | Total: S/ 1768.96', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (5, 5, 'entrada', 'Acopio café pergamino — LOT-2024-0005', '2024-05-02', 140.100, 11.7300, 'PEN', 1.0000, 0.4000, 280.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.1300/kg | Total: S/ 1699.41', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (6, 6, 'entrada', 'Acopio café pergamino — LOT-2024-0006', '2024-05-02', 141.600, 11.4760, 'PEN', 1.0000, 0.4000, 283.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 11.8760/kg | Total: S/ 1681.64', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (7, 7, 'entrada', 'Acopio café pergamino — LOT-2024-0007', '2024-05-02', 144.600, 11.8200, 'PEN', 1.0000, 0.4000, 289.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.2200/kg | Total: S/ 1767.01', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (8, 8, 'entrada', 'Acopio café pergamino — LOT-2024-0008', '2024-05-02', 146.900, 11.5670, 'PEN', 1.0000, 0.4000, 293.800, NULL, NULL, NULL, 'Sacos: 3 | Precio + Prima: S/ 11.9670/kg | Total: S/ 1757.95', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (9, 9, 'entrada', 'Acopio café pergamino — LOT-2024-0009', '2024-05-02', 142.100, 12.0740, 'PEN', 1.0000, 0.4000, 284.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.4740/kg | Total: S/ 1772.56', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (10, 10, 'entrada', 'Acopio café pergamino — LOT-2024-0010', '2024-05-02', 142.600, 12.2370, 'PEN', 1.0000, 0.4000, 285.200, NULL, NULL, NULL, 'Sacos: 2 | Precio + Prima: S/ 12.6370/kg | Total: S/ 1802.04', '2026-04-06 19:11:46.743913');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (11, 5, 'salida', 'Venta - Contrato CONT-2026-0006', '2026-04-09', 280.000, 12.0000, 'USD', 3.7000, 0.0000, 0.200, 6, 'venta', 'sistema', NULL, '2026-04-09 03:27:19.191595');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (12, 5, 'entrada', 'Reversión por cancelación - Contrato CONT-2026-0006', '2026-04-09', 280.000, NULL, 'PEN', 1.0000, 0.0000, 280.200, 6, 'cancelacion', 'sistema', NULL, '2026-04-09 03:55:20.316415');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (13, 11, 'entrada', 'Compra inicial - Acopio', '2026-04-09', 3456.000, 12.0000, 'PEN', 1.0000, 2.0000, 6912.000, NULL, 'compra', 'sistema', NULL, '2026-04-09 04:05:35.794582');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (14, 11, 'salida', 'Venta - Contrato CONT-2026-0007', '2026-04-09', 6912.000, 12.0000, 'USD', 1.0000, 0.0000, 0.000, 7, 'venta', 'sistema', NULL, '2026-04-09 09:29:41.599367');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (15, 12, 'entrada', 'Compra inicial - Acopio', '2026-04-15', 1000.000, 8.4900, 'PEN', 1.0000, 5.0000, 2000.000, NULL, 'compra', 'sistema', NULL, '2026-04-15 12:19:51.984022');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (18, 12, 'salida', 'Venta - Contrato CONT-2026-0009', '2026-05-29', 500.000, 4.8000, 'USD', 3.7500, 0.0000, 500.000, 9, 'venta', 'sistema', NULL, '2026-05-29 11:49:52.453949');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (19, 3, 'salida', 'Venta - Contrato CONT-2026-0011', '2026-05-29', 100.000, 12.3000, 'USD', 3.7500, 0.0000, 42.600, 11, 'venta', 'sistema', NULL, '2026-05-29 12:21:10.512816');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (20, 2, 'salida', 'Venta - Contrato CONT-2026-0012', '2026-05-29', 100.000, 12.3000, 'USD', 3.7500, 0.0000, 39.400, 12, 'venta', 'sistema', NULL, '2026-05-29 12:22:47.425859');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (21, 2, 'salida', 'Venta - Contrato CONT-2026-0013', '2026-05-29', 30.000, 12.0000, 'USD', 3.7500, 0.0000, 9.400, 13, 'venta', 'sistema', NULL, '2026-05-29 12:28:59.161526');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (22, 12, 'salida', 'Venta - Contrato CONT-2026-0014', '2026-05-29', 500.000, 12.0000, 'USD', 3.7500, 0.0000, 0.000, 14, 'venta', 'sistema', NULL, '2026-05-29 12:34:15.62268');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (23, 4, 'salida', 'Venta - Contrato CONT-2026-0015', '2026-05-29', 140.000, 12.0000, 'USD', 3.7500, 0.0000, 3.900, 15, 'venta', 'sistema', NULL, '2026-05-29 12:34:48.842548');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (24, 5, 'salida', 'Venta - Contrato CONT-2026-0016', '2026-05-29', 100.000, 12.0000, 'USD', 3.7500, 0.0000, 40.100, 16, 'venta', 'sistema', NULL, '2026-05-29 12:38:03.525856');
INSERT INTO public.kardex (id, acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, moneda, tipo_cambio, prima_diferencial, saldo_kg, referencia_id, referencia_tipo, usuario, notas, creado_en) VALUES (25, 15, 'entrada', 'Compra inicial - Acopio', '2026-06-01', 151.600, 12.0740, 'PEN', 1.0000, 0.4000, 151.600, NULL, 'compra', 'sistema', NULL, '2026-06-01 00:22:28.976379');


ALTER TABLE public.kardex ENABLE TRIGGER ALL;

--
-- Data for Name: kardex_almacen; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.kardex_almacen DISABLE TRIGGER ALL;



ALTER TABLE public.kardex_almacen ENABLE TRIGGER ALL;

--
-- Data for Name: laboratorio_analisis; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.laboratorio_analisis DISABLE TRIGGER ALL;

INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (1, 1, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 12.60, 68.00, NULL, 0, 2, 82.25, 8.25, 8.00, 8.25, 8.00, 8.25, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Notas a chocolate amargo, caramelo y frutos rojos. Acidez brillante. Limpio.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (2, 2, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 11.50, 69.24, NULL, 0, 2, 83.50, 8.50, 8.25, 8.50, 8.25, 8.50, 8.00, 10.00, 8.25, 10.00, 10.00, 0.00, 'Floral intenso, durazno maduro, acidez cítrica. Excelente balance.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (3, 3, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 12.40, 69.60, NULL, 0, 2, 81.75, 8.00, 8.00, 8.00, 7.75, 8.00, 7.75, 10.00, 7.75, 10.00, 10.00, 0.00, 'Cacao, miel, nuez. Cuerpo medio-alto. Post-gusto prolongado.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (4, 4, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 12.10, 69.50, NULL, 0, 2, 84.00, 8.75, 8.25, 8.75, 8.25, 8.50, 8.25, 10.00, 8.25, 10.00, 10.00, 0.00, 'Jazmín, maracuyá, bergamota. Acidez muy viva. Score alto.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (5, 5, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 13.30, 66.74, NULL, 0, 2, 82.00, 8.25, 8.00, 8.00, 8.00, 8.25, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Caramelo, almendra, ciruela. Balance sobresaliente. Certificable specialty.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (6, 6, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 13.00, 70.44, NULL, 0, 2, 80.75, 8.00, 7.75, 8.00, 7.75, 8.00, 7.75, 10.00, 7.75, 10.00, 10.00, 0.00, 'Chocolate con leche, vainilla. Acidez suave. Buen cuerpo.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (7, 7, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 13.50, 68.50, NULL, 0, 2, 81.25, 8.25, 8.00, 8.25, 8.00, 8.00, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Frutas tropicales, miel, cacao. Uniforme y limpio.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (8, 8, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 13.20, 65.98, NULL, 0, 2, 83.75, 8.50, 8.50, 8.50, 8.25, 8.50, 8.25, 10.00, 8.25, 10.00, 10.00, 0.00, 'Rosa, fresas, naranja. Muy aromático. One of the best.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (9, 9, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 14.40, 68.54, NULL, 0, 2, 82.50, 8.25, 8.25, 8.25, 8.00, 8.25, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Avellana, chocolate oscuro. Cuerpo cremoso. Post-gusto largo.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (10, 10, '2024-05-02', 'Equipo Q-Grader — Laboratorio Central', 'Centro de Catación Café Peru', 14.30, 69.18, NULL, 0, 2, 80.25, 8.00, 7.75, 8.00, 7.75, 8.00, 7.75, 10.00, 7.75, 10.00, 10.00, 0.00, 'Manzana verde, canela. Acidez media. Buen para blend.', true, '2026-04-06 19:11:46.743913');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (11, 2, '2026-03-15', 'Carlos Mendoza Rivas', 'CQI Certified Lab Lima', 11.80, 78.50, 695.00, 0, 2, 85.25, 8.25, 8.25, 8.50, 8.25, 8.25, 8.00, 10.00, 8.25, 10.00, 10.00, 0.00, 'Taza limpia con alta dulzura. Notas a durazno maduro, panela y flores blancas. Acidez maálica brillante. Cuerpo medio-alto. Excelente uniformidad entre tazas. Cosecha Typica de altura superior.', true, '2026-04-09 12:49:34.048318');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (12, 2, '2026-04-03', 'María López Torres', 'Interno', 12.10, 77.80, 688.00, 0, 3, 84.50, 8.00, 8.25, 8.50, 8.00, 8.25, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Fragancia intensa a caramelo y almendra. En taza: chocolate amargo fino, cereza, ligero cítrico. Post-gusto largo y agradable. Cuerpo cremoso. Bien balanceado. Apto para exportación specialty.', true, '2026-04-09 12:49:34.063293');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (13, 4, '2026-03-18', 'Carlos Mendoza Rivas', 'CQI Certified Lab Lima', 11.50, 80.20, 710.00, 0, 1, 86.00, 8.50, 8.50, 8.75, 8.25, 8.50, 8.25, 10.00, 8.25, 10.00, 10.00, 0.00, 'Café sobresaliente. Fragancia floral compleja. En taza destacan jazmín, miel de abeja, uva pasa y naranja. Acidez tartárica muy elegante. Cuerpo suave pero presente. Dulzura excepcional. Candidato a lote microlote de exportación premium.', true, '2026-04-09 12:49:34.063963');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (14, 4, '2026-04-07', 'Pedro Flores Quispe', 'Interno', 11.90, 79.60, 702.00, 0, 2, 85.50, 8.25, 8.50, 8.50, 8.25, 8.50, 8.25, 10.00, 8.25, 10.00, 10.00, 0.00, 'Consistencia confirmada entre sesiones. Notas a capulí, melaza y almendra tostada. Acidez viva y limpia. Uniformidad perfecta. Score estable respecto al análisis anterior. Aprobado para contrato de exportación.', true, '2026-04-09 12:49:34.064489');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (15, 11, '2026-04-08', 'María López Torres', 'Interno', 12.50, 75.00, 672.00, 1, 4, 82.00, 7.75, 8.00, 8.25, 7.75, 8.00, 7.75, 10.00, 7.75, 10.00, 10.00, 0.00, 'Primer análisis lote nuevo 2026. Perfil dulce con notas a maracuyá, cacao y panela. Buena acidez cítrica. Requiere ajuste de humedad antes de despacho. Potencial specialty confirmado. Programar re-análisis en 15 días.', true, '2026-04-09 12:49:34.065069');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (16, 5, '2026-04-09', 'Steifer', 'Interno', 11.40, 75.00, NULL, 0, 1, 80.00, NULL, 21.00, 12.00, NULL, 12.00, 2.00, NULL, 12.00, NULL, NULL, 0.00, NULL, true, '2026-04-09 13:19:17.103847');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (17, 3, '2026-04-09', 'Steifer', 'Interno', 12.30, 12.30, NULL, 1, 1, 82.30, NULL, 3.00, 12.00, NULL, 23.00, 21.00, NULL, 12.00, NULL, NULL, 0.00, NULL, true, '2026-04-09 13:20:09.080167');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (18, 12, '2026-04-15', 'Felimon Lopez García', 'Interno', 11.50, 85.00, NULL, 0, 2, NULL, NULL, NULL, 2.00, NULL, 1.00, 1.00, NULL, NULL, NULL, NULL, 0.00, NULL, true, '2026-04-15 12:24:03.014621');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (19, 1, '2026-05-10', 'Carlos Quispe', 'Lab. Calidad Cajamarca', 11.20, 79.50, 682.00, 0, 2, 83.75, 8.25, 8.50, 8.25, 7.75, 8.25, 8.00, 10.00, 8.00, 10.00, 10.00, 0.00, 'Notas a chocolate amargo y frutos rojos. Acidez brillante, cuerpo medio-alto. Excelente uniformidad.', true, '2026-05-29 00:31:21.101757');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (20, 2, '2026-05-15', 'Maria Huanca', 'Lab. Calidad Cajamarca', 12.10, 76.20, 668.00, 1, 4, 76.50, 7.50, 7.75, 7.50, 7.25, 7.75, 7.50, 10.00, 7.50, 10.00, 10.00, 0.00, 'Perfil dulce con notas a caramelo y nuez. Acidez moderada, cuerpo medio. Buena limpieza.', true, '2026-05-29 00:31:21.101757');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (21, 3, '2026-05-20', 'Carlos Quispe', 'Lab. Calidad Cajamarca', 13.80, 71.30, 645.00, 3, 8, 64.25, 6.50, 6.75, 6.50, 6.25, 6.50, 6.50, 8.00, 6.50, 8.00, 8.00, 0.00, 'Perfil plano, notas a madera y tierra. Acidez baja, defectos leves. Apto para mezclas comerciales.', false, '2026-05-29 00:31:21.101757');
INSERT INTO public.laboratorio_analisis (id, acopio_id, fecha_analisis, analista, laboratorio, humedad_pct, rendimiento_pct, densidad_gr_l, defectos_cat1, defectos_cat2, score_taza, fragancia, aroma, sabor, post_gusto, acidez, cuerpo, uniformidad, balance, limpieza_taza, dulzura, defecto_taza, notas_catacion, aprobado, creado_en) VALUES (22, 12, '2026-05-29', 'Harris Steifer', 'Interno', 12.50, 94.00, NULL, 1, 0, NULL, NULL, 7.25, 6.25, NULL, 6.00, 7.00, NULL, 6.50, NULL, NULL, 0.00, NULL, NULL, '2026-05-29 10:51:32.135311');


ALTER TABLE public.laboratorio_analisis ENABLE TRIGGER ALL;

--
-- Data for Name: oc_items; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.oc_items DISABLE TRIGGER ALL;



ALTER TABLE public.oc_items ENABLE TRIGGER ALL;

--
-- Data for Name: plan_maestro; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.plan_maestro DISABLE TRIGGER ALL;



ALTER TABLE public.plan_maestro ENABLE TRIGGER ALL;

--
-- Data for Name: ordenes_trabajo; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.ordenes_trabajo DISABLE TRIGGER ALL;



ALTER TABLE public.ordenes_trabajo ENABLE TRIGGER ALL;

--
-- Data for Name: ot_consumo_materiales; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.ot_consumo_materiales DISABLE TRIGGER ALL;



ALTER TABLE public.ot_consumo_materiales ENABLE TRIGGER ALL;

--
-- Data for Name: requisicion_items; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.requisicion_items DISABLE TRIGGER ALL;



ALTER TABLE public.requisicion_items ENABLE TRIGGER ALL;

--
-- Data for Name: seguridad_log; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.seguridad_log DISABLE TRIGGER ALL;



ALTER TABLE public.seguridad_log ENABLE TRIGGER ALL;

--
-- Data for Name: transformaciones; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.transformaciones DISABLE TRIGGER ALL;



ALTER TABLE public.transformaciones ENABLE TRIGGER ALL;

--
-- Name: acopios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.acopios_id_seq', 15, true);


--
-- Name: almacenes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.almacenes_id_seq', 1, false);


--
-- Name: asiento_lineas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.asiento_lineas_id_seq', 1, false);


--
-- Name: asientos_contables_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.asientos_contables_id_seq', 1, false);


--
-- Name: auditoria_hallazgos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.auditoria_hallazgos_id_seq', 1, true);


--
-- Name: auditorias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.auditorias_id_seq', 1, true);


--
-- Name: backups_registro_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.backups_registro_id_seq', 1, false);


--
-- Name: capacitacion_participantes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.capacitacion_participantes_id_seq', 1, true);


--
-- Name: capacitaciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.capacitaciones_id_seq', 1, true);


--
-- Name: centros_costo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.centros_costo_id_seq', 6, true);


--
-- Name: certificaciones_catalogo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.certificaciones_catalogo_id_seq', 6, true);


--
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.clientes_id_seq', 19, true);


--
-- Name: cotizaciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cotizaciones_id_seq', 1, false);


--
-- Name: cuentas_pagar_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cuentas_pagar_id_seq', 1, false);


--
-- Name: flujo_caja_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.flujo_caja_id_seq', 1, true);


--
-- Name: kardex_almacen_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.kardex_almacen_id_seq', 1, false);


--
-- Name: kardex_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.kardex_id_seq', 25, true);


--
-- Name: laboratorio_analisis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.laboratorio_analisis_id_seq', 22, true);


--
-- Name: lote_eventos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.lote_eventos_id_seq', 59, true);


--
-- Name: oc_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.oc_items_id_seq', 1, false);


--
-- Name: ordenes_compra_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ordenes_compra_id_seq', 1, false);


--
-- Name: ordenes_trabajo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ordenes_trabajo_id_seq', 1, false);


--
-- Name: ot_consumo_materiales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ot_consumo_materiales_id_seq', 1, false);


--
-- Name: plan_cuentas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.plan_cuentas_id_seq', 1, false);


--
-- Name: plan_maestro_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.plan_maestro_id_seq', 1, false);


--
-- Name: proveedores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.proveedores_id_seq', 2, true);


--
-- Name: requisicion_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.requisicion_items_id_seq', 1, false);


--
-- Name: requisiciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.requisiciones_id_seq', 1, false);


--
-- Name: seguridad_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.seguridad_log_id_seq', 1, false);


--
-- Name: tipos_cafe_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tipos_cafe_id_seq', 4, true);


--
-- Name: transformaciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transformaciones_id_seq', 1, false);


--
-- Name: ventas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ventas_id_seq', 16, true);


--
-- PostgreSQL database dump complete
--

\unrestrict IBHi5KmKUzGCJoqLCDQ6oyMVODQ1AyhKyG9NPZGKHNf5huUgeB8tCGNmxmvavBX

