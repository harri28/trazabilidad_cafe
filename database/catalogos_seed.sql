--
-- PostgreSQL database dump
--

\restrict FFIemSicnbqHnBZad1HySN0ADeSRayjbyipjQJurJi7vu8iDSqYN2ZvGTZyAZwY

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
-- Data for Name: certificaciones_catalogo; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (1, 'RFA', 'Rainforest Alliance', NULL, true);
INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (2, 'ORG', 'Orgánico / USDA Organic', NULL, true);
INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (3, 'FT', 'Fair Trade / Comercio Justo', NULL, true);
INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (4, 'UTZ', 'UTZ Certified', NULL, true);
INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (5, '4C', '4C Association', NULL, true);
INSERT INTO public.certificaciones_catalogo (id, codigo, nombre, descripcion, activo) VALUES (6, 'SPE', 'Specialty Coffee', NULL, true);


--
-- Data for Name: tipos_cafe; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tipos_cafe (id, nombre, descripcion, activo, creado_en) VALUES (1, 'Pergamino', 'Café con cascarilla, post-despulpado', true, '2026-04-06 19:08:18.259945');
INSERT INTO public.tipos_cafe (id, nombre, descripcion, activo, creado_en) VALUES (2, 'Oro', 'Café verde sin cascarilla', true, '2026-04-06 19:08:18.259945');
INSERT INTO public.tipos_cafe (id, nombre, descripcion, activo, creado_en) VALUES (3, 'Tostado', 'Café tostado listo para exportar', true, '2026-04-06 19:08:18.259945');
INSERT INTO public.tipos_cafe (id, nombre, descripcion, activo, creado_en) VALUES (4, 'Verde', 'Café verde sin procesar', true, '2026-04-06 19:08:18.259945');


--
-- Name: certificaciones_catalogo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.certificaciones_catalogo_id_seq', 6, true);


--
-- Name: tipos_cafe_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tipos_cafe_id_seq', 4, true);


--
-- PostgreSQL database dump complete
--

\unrestrict FFIemSicnbqHnBZad1HySN0ADeSRayjbyipjQJurJi7vu8iDSqYN2ZvGTZyAZwY

