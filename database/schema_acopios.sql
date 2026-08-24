--
-- PostgreSQL database dump
--

\restrict ZkuupoJIMwd3duymdwW9oujmF82XQGDZUd8p4S904b8nGe289no0JDYJSA3uvxu

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
-- Name: categoria_flujo; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.categoria_flujo AS ENUM (
    'operativo',
    'financiero',
    'inversion'
);


--
-- Name: categoria_proveedor; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.categoria_proveedor AS ENUM (
    'insumos',
    'servicios',
    'transporte',
    'maquinaria',
    'otro'
);


--
-- Name: clasificacion_cafe; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.clasificacion_cafe AS ENUM (
    'specialty',
    'premium',
    'comercial',
    'descarte'
);


--
-- Name: estado_asiento; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_asiento AS ENUM (
    'borrador',
    'validado',
    'anulado'
);


--
-- Name: estado_cotizacion; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_cotizacion AS ENUM (
    'borrador',
    'enviada',
    'aceptada',
    'rechazada',
    'vencida'
);


--
-- Name: estado_cp; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_cp AS ENUM (
    'pendiente',
    'parcial',
    'pagado',
    'vencido'
);


--
-- Name: estado_lote; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_lote AS ENUM (
    'acopio',
    'proceso',
    'disponible',
    'vendido',
    'parcial'
);


--
-- Name: estado_oc; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_oc AS ENUM (
    'borrador',
    'enviada',
    'confirmada',
    'parcial',
    'completada',
    'cancelada'
);


--
-- Name: estado_ot; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_ot AS ENUM (
    'pendiente',
    'en_proceso',
    'pausada',
    'completada',
    'cancelada'
);


--
-- Name: estado_plan_maestro; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_plan_maestro AS ENUM (
    'borrador',
    'activo',
    'cerrado'
);


--
-- Name: estado_requisicion; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_requisicion AS ENUM (
    'pendiente',
    'aprobada',
    'rechazada',
    'en_proceso',
    'completada'
);


--
-- Name: estado_venta; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.estado_venta AS ENUM (
    'borrador',
    'confirmado',
    'en_proceso',
    'entregado',
    'cancelado'
);


--
-- Name: moneda_factura; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.moneda_factura AS ENUM (
    'USD',
    'PEN',
    'EUR'
);


--
-- Name: moneda_tipo; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.moneda_tipo AS ENUM (
    'PEN',
    'USD',
    'EUR'
);


--
-- Name: proceso_beneficio; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.proceso_beneficio AS ENUM (
    'lavado',
    'natural',
    'honey',
    'semi-lavado'
);


--
-- Name: tipo_almacen; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_almacen AS ENUM (
    'materia_prima',
    'proceso',
    'producto_terminado'
);


--
-- Name: tipo_cliente; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_cliente AS ENUM (
    'productor',
    'comprador',
    'ambos'
);


--
-- Name: tipo_cuenta_contable; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_cuenta_contable AS ENUM (
    'activo',
    'pasivo',
    'patrimonio',
    'ingreso',
    'gasto',
    'costo'
);


--
-- Name: tipo_documento_cp; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_documento_cp AS ENUM (
    'factura',
    'boleta',
    'recibo',
    'nota_debito',
    'otro'
);


--
-- Name: tipo_flujo; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_flujo AS ENUM (
    'ingreso',
    'egreso'
);


--
-- Name: tipo_movimiento_kard; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_movimiento_kard AS ENUM (
    'entrada',
    'salida',
    'ajuste',
    'transformacion'
);


--
-- Name: tipo_proceso_ot; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE public.tipo_proceso_ot AS ENUM (
    'secado',
    'despergaminado',
    'tostado',
    'molido',
    'envasado',
    'seleccion',
    'otro'
);


--
-- Name: fn_kardex_after_insert(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_kardex_after_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    nuevo_peso DECIMAL(10,3);
BEGIN
    IF NEW.tipo_movimiento IN ('entrada', 'ajuste') THEN
        UPDATE acopios SET peso_actual_kg = peso_actual_kg + NEW.cantidad_kg WHERE id = NEW.acopio_id;
    ELSIF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        UPDATE acopios SET peso_actual_kg = peso_actual_kg - NEW.cantidad_kg WHERE id = NEW.acopio_id;
    END IF;

    SELECT peso_actual_kg INTO nuevo_peso FROM acopios WHERE id = NEW.acopio_id;
    UPDATE kardex SET saldo_kg = nuevo_peso WHERE id = NEW.id;

    RETURN NEW;
END;
$$;


--
-- Name: fn_kardex_before_insert(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_kardex_before_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    stock_actual DECIMAL(10,3);
BEGIN
    IF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        SELECT peso_actual_kg INTO stock_actual FROM acopios WHERE id = NEW.acopio_id;
        IF stock_actual < NEW.cantidad_kg THEN
            RAISE EXCEPTION 'Stock insuficiente para realizar la salida';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_log_lote_estado(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_log_lote_estado() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.estado IS DISTINCT FROM NEW.estado THEN
        INSERT INTO acopio_eventos (acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id)
        VALUES (
            NEW.id,
            'Estado',
            'Estado Actualizado',
            OLD.estado || ' → ' || NEW.estado,
            'acopio',
            NEW.id
        );
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_log_lote_insert(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_log_lote_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO acopio_eventos (acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, creado_en)
    VALUES (
        NEW.id,
        'Acopio',
        'Acopio Registrado',
        'Ingreso de ' || NEW.peso_inicial_kg || ' kg - Finca: ' || COALESCE(NEW.finca, 'N/A'),
        'acopio',
        NEW.id,
        NEW.fecha_acopio::timestamp
    );
    RETURN NEW;
END;
$$;


--
-- Name: fn_log_venta_estado(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_log_venta_estado() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.estado IS DISTINCT FROM NEW.estado THEN
        INSERT INTO acopio_eventos (acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario)
        VALUES (
            NEW.acopio_id,
            'Estado Venta',
            'Venta ' || NEW.numero_contrato || ': ' || OLD.estado || ' → ' || NEW.estado,
            'Contrato ' || NEW.numero_contrato || ' — ' || NEW.cantidad_kg || ' kg',
            'venta',
            NEW.id,
            COALESCE(NEW.usuario, 'sistema')
        );
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_log_venta_insert(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_log_venta_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.estado <> 'borrador' THEN
        INSERT INTO acopio_eventos (acopio_id, etapa, evento, detalle, referencia_tipo, referencia_id, usuario, creado_en)
        VALUES (
            NEW.acopio_id,
            'Venta',
            'Venta Confirmada',
            'Contrato ' || NEW.numero_contrato || ' - ' || NEW.cantidad_kg || ' kg a USD ' || NEW.precio_usd_kg || '/kg',
            'venta',
            NEW.id,
            COALESCE(NEW.usuario, 'sistema'),
            NEW.fecha_contrato::timestamp
        );
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_set_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.actualizado_en = NOW();
    RETURN NEW;
END;
$$;


--
-- Name: fn_venta_after_update(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_venta_after_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    stock_restante DECIMAL(10,3);
BEGIN
    IF NEW.estado = 'confirmado' AND OLD.estado = 'borrador' THEN
        SELECT peso_actual_kg INTO stock_restante FROM acopios WHERE id = NEW.acopio_id;
        IF stock_restante <= 0 THEN
            UPDATE acopios SET estado = 'vendido' WHERE id = NEW.acopio_id;
        ELSE
            UPDATE acopios SET estado = 'parcial'  WHERE id = NEW.acopio_id;
        END IF;
    END IF;
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: acopio_certificaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.acopio_certificaciones (
    acopio_id integer NOT NULL,
    certificacion_id integer NOT NULL,
    fecha_inicio date,
    fecha_vencimiento date,
    numero_certificado character varying(80)
);


--
-- Name: acopio_eventos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.acopio_eventos (
    id integer NOT NULL,
    acopio_id integer NOT NULL,
    etapa character varying(40) DEFAULT 'Evento'::character varying NOT NULL,
    evento character varying(120) NOT NULL,
    detalle text,
    referencia_tipo character varying(40),
    referencia_id integer,
    usuario character varying(60) DEFAULT 'sistema'::character varying,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: acopios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.acopios (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    estado public.estado_lote DEFAULT 'acopio'::public.estado_lote NOT NULL,
    tipo_cafe_id integer NOT NULL,
    productor_id integer NOT NULL,
    fecha_acopio date NOT NULL,
    "campaña" smallint,
    peso_inicial_kg numeric(10,3) NOT NULL,
    peso_actual_kg numeric(10,3) NOT NULL,
    peso_final_kg numeric(10,3),
    merma_kg numeric(10,3) GENERATED ALWAYS AS ((peso_inicial_kg - COALESCE(peso_final_kg, peso_actual_kg))) STORED,
    rendimiento_pct numeric(5,2) GENERATED ALWAYS AS (
CASE
    WHEN ((peso_inicial_kg > (0)::numeric) AND (peso_final_kg IS NOT NULL)) THEN ((peso_final_kg / peso_inicial_kg) * (100)::numeric)
    ELSE NULL::numeric
END) STORED,
    region character varying(80),
    finca character varying(120),
    altitud_msnm smallint,
    variedad character varying(80),
    proceso_beneficio public.proceso_beneficio DEFAULT 'lavado'::public.proceso_beneficio,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    actualizado_en timestamp without time zone DEFAULT now() NOT NULL,
    sacos integer DEFAULT 0,
    humedad_entrada_pct numeric(5,2),
    peso_bruto_kg numeric(10,3),
    rend_entrada_pct numeric(5,2),
    hora_acopio time without time zone,
    CONSTRAINT lotes_altitud_msnm_check CHECK ((altitud_msnm >= 0))
);


--
-- Name: acopios_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.acopios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: acopios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.acopios_id_seq OWNED BY public.acopios.id;


--
-- Name: almacenes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.almacenes (
    id integer NOT NULL,
    codigo character varying(20) NOT NULL,
    nombre character varying(100) NOT NULL,
    ubicacion character varying(200),
    capacidad_kg numeric(12,2),
    tipo public.tipo_almacen DEFAULT 'materia_prima'::public.tipo_almacen,
    activo boolean DEFAULT true,
    notas text
);


--
-- Name: almacenes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.almacenes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: almacenes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.almacenes_id_seq OWNED BY public.almacenes.id;


--
-- Name: asiento_lineas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asiento_lineas (
    id integer NOT NULL,
    asiento_id integer NOT NULL,
    cuenta_id integer NOT NULL,
    centro_costo_id integer,
    debe numeric(12,2) DEFAULT 0,
    haber numeric(12,2) DEFAULT 0,
    descripcion character varying(200)
);


--
-- Name: asiento_lineas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.asiento_lineas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asiento_lineas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.asiento_lineas_id_seq OWNED BY public.asiento_lineas.id;


--
-- Name: asientos_contables; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.asientos_contables (
    id integer NOT NULL,
    numero character varying(20) NOT NULL,
    fecha date NOT NULL,
    concepto character varying(300) NOT NULL,
    referencia_tipo character varying(50),
    referencia_id integer,
    estado public.estado_asiento DEFAULT 'borrador'::public.estado_asiento,
    total_debe numeric(12,2) DEFAULT 0,
    total_haber numeric(12,2) DEFAULT 0,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: asientos_contables_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.asientos_contables_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asientos_contables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.asientos_contables_id_seq OWNED BY public.asientos_contables.id;


--
-- Name: auditoria_hallazgos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria_hallazgos (
    id integer NOT NULL,
    auditoria_id integer NOT NULL,
    tipo character varying(80) DEFAULT 'observacion'::character varying NOT NULL,
    descripcion text NOT NULL,
    area character varying(100),
    responsable character varying(150),
    fecha_limite date,
    estado character varying(50) DEFAULT 'abierto'::character varying,
    accion_correctiva text,
    fecha_cierre date,
    evidencia text
);


--
-- Name: auditoria_hallazgos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditoria_hallazgos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditoria_hallazgos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditoria_hallazgos_id_seq OWNED BY public.auditoria_hallazgos.id;


--
-- Name: auditorias; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditorias (
    id integer NOT NULL,
    codigo character varying(50),
    tipo character varying(80) DEFAULT 'interna'::character varying NOT NULL,
    titulo character varying(200) NOT NULL,
    descripcion text,
    auditor character varying(150),
    organismo character varying(150),
    fecha_auditoria date NOT NULL,
    fecha_proxima date,
    estado character varying(50) DEFAULT 'programada'::character varying,
    resultado character varying(50),
    puntaje numeric(5,2),
    campana smallint,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: auditorias_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditorias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditorias_id_seq OWNED BY public.auditorias.id;


--
-- Name: backups_registro; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.backups_registro (
    id integer NOT NULL,
    "campana_año" smallint NOT NULL,
    tipo character varying(10) NOT NULL,
    fecha_backup timestamp without time zone DEFAULT now() NOT NULL,
    descripcion character varying(255),
    realizado_por character varying(100) DEFAULT 'Administrador'::character varying,
    estado character varying(20) DEFAULT 'completado'::character varying NOT NULL,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT backups_registro_estado_check CHECK (((estado)::text = ANY ((ARRAY['completado'::character varying, 'fallido'::character varying, 'pendiente'::character varying])::text[]))),
    CONSTRAINT backups_registro_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['diario'::character varying, 'mensual'::character varying, 'anual'::character varying])::text[])))
);


--
-- Name: backups_registro_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.backups_registro_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: backups_registro_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.backups_registro_id_seq OWNED BY public.backups_registro.id;


--
-- Name: campanas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.campanas (
    "año" smallint NOT NULL,
    fecha_inicio date,
    fecha_fin date,
    estado character varying(20) DEFAULT 'activa'::character varying NOT NULL,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT campanas_estado_check CHECK (((estado)::text = ANY ((ARRAY['activa'::character varying, 'cerrada'::character varying, 'archivada'::character varying])::text[])))
);


--
-- Name: capacitacion_participantes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.capacitacion_participantes (
    id integer NOT NULL,
    capacitacion_id integer NOT NULL,
    cliente_id integer,
    nombre_participante character varying(150),
    cargo character varying(100),
    asistio boolean DEFAULT true,
    certificado_emitido boolean DEFAULT false,
    notas text
);


--
-- Name: capacitacion_participantes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.capacitacion_participantes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: capacitacion_participantes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.capacitacion_participantes_id_seq OWNED BY public.capacitacion_participantes.id;


--
-- Name: capacitaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.capacitaciones (
    id integer NOT NULL,
    titulo character varying(200) NOT NULL,
    descripcion text,
    instructor character varying(150),
    organizacion character varying(150),
    fecha_inicio date NOT NULL,
    fecha_fin date,
    lugar character varying(200),
    modalidad character varying(50) DEFAULT 'presencial'::character varying,
    estado character varying(50) DEFAULT 'programado'::character varying,
    max_participantes integer,
    campana smallint,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: capacitaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.capacitaciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: capacitaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.capacitaciones_id_seq OWNED BY public.capacitaciones.id;


--
-- Name: centros_costo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.centros_costo (
    id integer NOT NULL,
    codigo character varying(20) NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    padre_id integer,
    activo boolean DEFAULT true
);


--
-- Name: centros_costo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.centros_costo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: centros_costo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.centros_costo_id_seq OWNED BY public.centros_costo.id;


--
-- Name: certificaciones_catalogo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.certificaciones_catalogo (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: certificaciones_catalogo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.certificaciones_catalogo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: certificaciones_catalogo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.certificaciones_catalogo_id_seq OWNED BY public.certificaciones_catalogo.id;


--
-- Name: clientes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.clientes (
    id integer NOT NULL,
    tipo public.tipo_cliente NOT NULL,
    razon_social character varying(150) NOT NULL,
    ruc_dni character varying(20),
    contacto character varying(100),
    telefono character varying(20),
    email character varying(120),
    direccion text,
    departamento character varying(60),
    provincia character varying(60),
    distrito character varying(60),
    altitud_msnm smallint,
    hectareas numeric(8,2),
    asociacion character varying(120),
    pais_destino character varying(80),
    moneda_pref public.moneda_tipo DEFAULT 'USD'::public.moneda_tipo,
    activo boolean DEFAULT true NOT NULL,
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    actualizado_en timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT clientes_altitud_msnm_check CHECK ((altitud_msnm >= 0))
);


--
-- Name: clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.clientes_id_seq OWNED BY public.clientes.id;


--
-- Name: cotizaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cotizaciones (
    id integer NOT NULL,
    numero character varying(20) NOT NULL,
    comprador_id integer NOT NULL,
    acopio_id integer NOT NULL,
    fecha_cotizacion date NOT NULL,
    fecha_vencimiento date NOT NULL,
    cantidad_kg numeric(10,2) NOT NULL,
    precio_usd_kg numeric(10,4) NOT NULL,
    total_usd numeric(12,2) GENERATED ALWAYS AS ((cantidad_kg * precio_usd_kg)) STORED,
    estado public.estado_cotizacion DEFAULT 'borrador'::public.estado_cotizacion,
    incoterm character varying(10) DEFAULT 'FOB'::character varying,
    condiciones text,
    notas text,
    venta_id integer,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: cotizaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cotizaciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cotizaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cotizaciones_id_seq OWNED BY public.cotizaciones.id;


--
-- Name: cuentas_pagar; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cuentas_pagar (
    id integer NOT NULL,
    proveedor_id integer NOT NULL,
    orden_compra_id integer,
    numero_documento character varying(50) NOT NULL,
    tipo_documento public.tipo_documento_cp DEFAULT 'factura'::public.tipo_documento_cp,
    fecha_emision date NOT NULL,
    fecha_vencimiento date NOT NULL,
    monto_total numeric(12,2) NOT NULL,
    monto_pagado numeric(12,2) DEFAULT 0,
    moneda public.moneda_tipo DEFAULT 'PEN'::public.moneda_tipo,
    estado public.estado_cp DEFAULT 'pendiente'::public.estado_cp,
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: cuentas_pagar_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cuentas_pagar_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cuentas_pagar_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cuentas_pagar_id_seq OWNED BY public.cuentas_pagar.id;


--
-- Name: flujo_caja; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.flujo_caja (
    id integer NOT NULL,
    fecha date NOT NULL,
    tipo public.tipo_flujo NOT NULL,
    categoria public.categoria_flujo DEFAULT 'operativo'::public.categoria_flujo,
    concepto character varying(200) NOT NULL,
    monto numeric(12,2) NOT NULL,
    moneda public.moneda_tipo DEFAULT 'PEN'::public.moneda_tipo,
    tipo_cambio numeric(8,4) DEFAULT 1,
    monto_pen numeric(12,2) GENERATED ALWAYS AS ((monto * tipo_cambio)) STORED,
    referencia_tipo character varying(50),
    referencia_id integer,
    cuenta_banco character varying(100),
    centro_costo_id integer,
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: flujo_caja_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.flujo_caja_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: flujo_caja_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.flujo_caja_id_seq OWNED BY public.flujo_caja.id;


--
-- Name: kardex; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kardex (
    id integer NOT NULL,
    acopio_id integer NOT NULL,
    tipo_movimiento public.tipo_movimiento_kard NOT NULL,
    concepto character varying(150) NOT NULL,
    fecha date NOT NULL,
    cantidad_kg numeric(10,3) NOT NULL,
    precio_unitario numeric(10,4),
    moneda public.moneda_tipo DEFAULT 'PEN'::public.moneda_tipo,
    tipo_cambio numeric(8,4) DEFAULT 1.0000,
    total_monto numeric(14,4) GENERATED ALWAYS AS ((cantidad_kg * COALESCE(precio_unitario, (0)::numeric))) STORED,
    prima_diferencial numeric(8,4) DEFAULT 0,
    prima_total numeric(14,4) GENERATED ALWAYS AS ((cantidad_kg * prima_diferencial)) STORED,
    saldo_kg numeric(10,3),
    referencia_id integer,
    referencia_tipo character varying(30),
    usuario character varying(60),
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: kardex_almacen; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kardex_almacen (
    id integer NOT NULL,
    kardex_id integer NOT NULL,
    almacen_origen_id integer,
    almacen_destino_id integer
);


--
-- Name: kardex_almacen_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kardex_almacen_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kardex_almacen_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kardex_almacen_id_seq OWNED BY public.kardex_almacen.id;


--
-- Name: kardex_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kardex_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kardex_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kardex_id_seq OWNED BY public.kardex.id;


--
-- Name: laboratorio_analisis; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.laboratorio_analisis (
    id integer NOT NULL,
    acopio_id integer NOT NULL,
    fecha_analisis date NOT NULL,
    analista character varying(100),
    laboratorio character varying(120) DEFAULT 'Interno'::character varying,
    humedad_pct numeric(5,2),
    rendimiento_pct numeric(5,2),
    densidad_gr_l numeric(7,2),
    defectos_cat1 smallint DEFAULT 0,
    defectos_cat2 smallint DEFAULT 0,
    score_taza numeric(5,2),
    fragancia numeric(4,2),
    aroma numeric(4,2),
    sabor numeric(4,2),
    post_gusto numeric(4,2),
    acidez numeric(4,2),
    cuerpo numeric(4,2),
    uniformidad numeric(4,2),
    balance numeric(4,2),
    limpieza_taza numeric(4,2),
    dulzura numeric(4,2),
    defecto_taza numeric(4,2) DEFAULT 0,
    clasificacion public.clasificacion_cafe GENERATED ALWAYS AS (
CASE
    WHEN (score_taza >= (80)::numeric) THEN 'specialty'::public.clasificacion_cafe
    WHEN (score_taza >= (75)::numeric) THEN 'premium'::public.clasificacion_cafe
    WHEN (score_taza >= (60)::numeric) THEN 'comercial'::public.clasificacion_cafe
    WHEN (score_taza IS NOT NULL) THEN 'descarte'::public.clasificacion_cafe
    ELSE NULL::public.clasificacion_cafe
END) STORED,
    notas_catacion text,
    aprobado boolean,
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT laboratorio_analisis_defectos_cat1_check CHECK ((defectos_cat1 >= 0)),
    CONSTRAINT laboratorio_analisis_defectos_cat2_check CHECK ((defectos_cat2 >= 0)),
    CONSTRAINT laboratorio_analisis_score_taza_check CHECK (((score_taza >= (0)::numeric) AND (score_taza <= (100)::numeric)))
);


--
-- Name: laboratorio_analisis_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.laboratorio_analisis_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: laboratorio_analisis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.laboratorio_analisis_id_seq OWNED BY public.laboratorio_analisis.id;


--
-- Name: lote_eventos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lote_eventos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lote_eventos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lote_eventos_id_seq OWNED BY public.acopio_eventos.id;


--
-- Name: oc_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oc_items (
    id integer NOT NULL,
    orden_compra_id integer NOT NULL,
    descripcion character varying(200) NOT NULL,
    cantidad numeric(10,3) NOT NULL,
    unidad character varying(20) DEFAULT 'und'::character varying NOT NULL,
    precio_unitario numeric(10,4) NOT NULL,
    subtotal numeric(12,2) GENERATED ALWAYS AS ((cantidad * precio_unitario)) STORED
);


--
-- Name: oc_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.oc_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: oc_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.oc_items_id_seq OWNED BY public.oc_items.id;


--
-- Name: ordenes_compra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ordenes_compra (
    id integer NOT NULL,
    numero character varying(20) NOT NULL,
    proveedor_id integer NOT NULL,
    requisicion_id integer,
    fecha_emision date NOT NULL,
    fecha_entrega date,
    estado public.estado_oc DEFAULT 'borrador'::public.estado_oc,
    moneda public.moneda_tipo DEFAULT 'PEN'::public.moneda_tipo,
    tipo_cambio numeric(8,4) DEFAULT 1,
    subtotal numeric(12,2) DEFAULT 0,
    igv numeric(12,2) DEFAULT 0,
    total numeric(12,2) DEFAULT 0,
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: ordenes_compra_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ordenes_compra_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ordenes_compra_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ordenes_compra_id_seq OWNED BY public.ordenes_compra.id;


--
-- Name: ordenes_trabajo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ordenes_trabajo (
    id integer NOT NULL,
    numero character varying(20) NOT NULL,
    acopio_id integer NOT NULL,
    plan_maestro_id integer,
    tipo_proceso public.tipo_proceso_ot NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin_estimada date,
    fecha_fin_real date,
    avance_pct numeric(5,2) DEFAULT 0,
    estado public.estado_ot DEFAULT 'pendiente'::public.estado_ot,
    operador character varying(100),
    maquinaria character varying(100),
    notas text,
    creado_en timestamp without time zone DEFAULT now(),
    CONSTRAINT ordenes_trabajo_avance_pct_check CHECK (((avance_pct >= (0)::numeric) AND (avance_pct <= (100)::numeric)))
);


--
-- Name: ordenes_trabajo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ordenes_trabajo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ordenes_trabajo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ordenes_trabajo_id_seq OWNED BY public.ordenes_trabajo.id;


--
-- Name: ot_consumo_materiales; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ot_consumo_materiales (
    id integer NOT NULL,
    orden_trabajo_id integer NOT NULL,
    descripcion character varying(200) NOT NULL,
    cantidad numeric(10,3) NOT NULL,
    unidad character varying(20) DEFAULT 'kg'::character varying NOT NULL,
    costo_unitario numeric(10,4),
    moneda public.moneda_tipo DEFAULT 'PEN'::public.moneda_tipo,
    fecha date NOT NULL,
    notas text
);


--
-- Name: ot_consumo_materiales_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ot_consumo_materiales_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ot_consumo_materiales_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ot_consumo_materiales_id_seq OWNED BY public.ot_consumo_materiales.id;


--
-- Name: plan_cuentas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plan_cuentas (
    id integer NOT NULL,
    codigo character varying(20) NOT NULL,
    nombre character varying(200) NOT NULL,
    tipo public.tipo_cuenta_contable NOT NULL,
    padre_id integer,
    nivel smallint DEFAULT 1,
    acepta_movimientos boolean DEFAULT true,
    activo boolean DEFAULT true
);


--
-- Name: plan_cuentas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plan_cuentas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plan_cuentas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plan_cuentas_id_seq OWNED BY public.plan_cuentas.id;


--
-- Name: plan_maestro; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plan_maestro (
    id integer NOT NULL,
    "campaña" smallint NOT NULL,
    tipo_cafe_id integer NOT NULL,
    cantidad_meta_kg numeric(12,2) NOT NULL,
    cantidad_real_kg numeric(12,2) DEFAULT 0,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    estado public.estado_plan_maestro DEFAULT 'borrador'::public.estado_plan_maestro,
    responsable character varying(100),
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: plan_maestro_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plan_maestro_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plan_maestro_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plan_maestro_id_seq OWNED BY public.plan_maestro.id;


--
-- Name: proveedores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proveedores (
    id integer NOT NULL,
    razon_social character varying(200) NOT NULL,
    ruc character varying(11),
    contacto character varying(100),
    telefono character varying(20),
    email character varying(100),
    direccion text,
    categoria public.categoria_proveedor DEFAULT 'insumos'::public.categoria_proveedor,
    condiciones_pago character varying(100),
    activo boolean DEFAULT true,
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: proveedores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.proveedores_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: proveedores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.proveedores_id_seq OWNED BY public.proveedores.id;


--
-- Name: requisicion_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.requisicion_items (
    id integer NOT NULL,
    requisicion_id integer NOT NULL,
    descripcion character varying(200) NOT NULL,
    cantidad numeric(10,3) NOT NULL,
    unidad character varying(20) NOT NULL,
    justificacion text
);


--
-- Name: requisicion_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.requisicion_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: requisicion_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.requisicion_items_id_seq OWNED BY public.requisicion_items.id;


--
-- Name: requisiciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.requisiciones (
    id integer NOT NULL,
    numero character varying(20) NOT NULL,
    area_solicitante character varying(100) NOT NULL,
    solicitante character varying(100),
    fecha_solicitud date NOT NULL,
    fecha_requerida date,
    estado public.estado_requisicion DEFAULT 'pendiente'::public.estado_requisicion,
    aprobado_por character varying(100),
    notas text,
    creado_en timestamp without time zone DEFAULT now()
);


--
-- Name: requisiciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.requisiciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: requisiciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.requisiciones_id_seq OWNED BY public.requisiciones.id;


--
-- Name: seguridad_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.seguridad_log (
    id integer NOT NULL,
    usuario character varying(100),
    accion character varying(200) NOT NULL,
    modulo character varying(100),
    detalle text,
    ip_address character varying(50),
    fecha timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: seguridad_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.seguridad_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: seguridad_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.seguridad_log_id_seq OWNED BY public.seguridad_log.id;


--
-- Name: tipos_cafe; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipos_cafe (
    id integer NOT NULL,
    nombre character varying(80) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: tipos_cafe_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tipos_cafe_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tipos_cafe_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tipos_cafe_id_seq OWNED BY public.tipos_cafe.id;


--
-- Name: transformaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transformaciones (
    id integer NOT NULL,
    acopio_origen_id integer NOT NULL,
    acopio_destino_id integer,
    tipo_transformacion character varying(80) NOT NULL,
    fecha date NOT NULL,
    peso_entrada_kg numeric(10,3) NOT NULL,
    peso_salida_kg numeric(10,3),
    merma_kg numeric(10,3) GENERATED ALWAYS AS ((peso_entrada_kg - COALESCE(peso_salida_kg, (0)::numeric))) STORED,
    rendimiento_pct numeric(5,2) GENERATED ALWAYS AS (
CASE
    WHEN ((peso_entrada_kg > (0)::numeric) AND (peso_salida_kg IS NOT NULL)) THEN ((peso_salida_kg / peso_entrada_kg) * (100)::numeric)
    ELSE NULL::numeric
END) STORED,
    operador character varying(100),
    maquinaria character varying(100),
    notas text,
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: transformaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transformaciones_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transformaciones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transformaciones_id_seq OWNED BY public.transformaciones.id;


--
-- Name: v_acopios_completos; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_acopios_completos AS
SELECT
    NULL::integer AS id,
    NULL::character varying(30) AS codigo,
    NULL::public.estado_lote AS estado,
    NULL::smallint AS "campaña",
    NULL::character varying(80) AS tipo_cafe,
    NULL::character varying(150) AS productor,
    NULL::character varying(60) AS departamento,
    NULL::character varying(60) AS provincia,
    NULL::character varying(120) AS finca,
    NULL::smallint AS altitud_msnm,
    NULL::character varying(80) AS variedad,
    NULL::public.proceso_beneficio AS proceso_beneficio,
    NULL::date AS fecha_acopio,
    NULL::numeric(10,3) AS peso_inicial_kg,
    NULL::numeric(10,3) AS peso_actual_kg,
    NULL::numeric(10,3) AS peso_final_kg,
    NULL::numeric(10,3) AS merma_kg,
    NULL::numeric(5,2) AS rendimiento_pct,
    NULL::date AS ultima_analisis,
    NULL::numeric(5,2) AS score_taza,
    NULL::public.clasificacion_cafe AS clasificacion,
    NULL::numeric(5,2) AS humedad_pct,
    NULL::numeric(5,2) AS rend_lab,
    NULL::text AS certificaciones,
    NULL::numeric AS kg_vendidos,
    NULL::numeric AS kg_disponibles;


--
-- Name: v_kardex_resumen; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_kardex_resumen AS
 SELECT l.codigo AS acopio,
    k.tipo_movimiento,
    sum(k.cantidad_kg) AS total_kg,
    sum(k.total_monto) AS total_monto,
    k.moneda,
    count(*) AS num_movimientos,
    min(k.fecha) AS primera_fecha,
    max(k.fecha) AS ultima_fecha
   FROM (public.kardex k
     JOIN public.acopios l ON ((l.id = k.acopio_id)))
  GROUP BY l.id, l.codigo, k.tipo_movimiento, k.moneda;


--
-- Name: ventas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ventas (
    id integer NOT NULL,
    numero_contrato character varying(40) NOT NULL,
    estado public.estado_venta DEFAULT 'borrador'::public.estado_venta NOT NULL,
    comprador_id integer NOT NULL,
    acopio_id integer NOT NULL,
    fecha_contrato date NOT NULL,
    fecha_entrega date,
    cantidad_kg numeric(10,3) NOT NULL,
    precio_usd_kg numeric(10,4) NOT NULL,
    tipo_cambio numeric(8,4) DEFAULT 1.0000 NOT NULL,
    moneda_factura public.moneda_factura DEFAULT 'USD'::public.moneda_factura,
    total_usd numeric(14,4) GENERATED ALWAYS AS ((cantidad_kg * precio_usd_kg)) STORED,
    total_local numeric(14,4) GENERATED ALWAYS AS (((cantidad_kg * precio_usd_kg) * tipo_cambio)) STORED,
    incoterm character varying(10) DEFAULT 'FOB'::character varying,
    puerto_embarque character varying(80),
    humedad_max_pct numeric(4,2),
    defectos_max smallint,
    score_min numeric(4,2),
    notas text,
    usuario character varying(60),
    creado_en timestamp without time zone DEFAULT now() NOT NULL,
    actualizado_en timestamp without time zone DEFAULT now() NOT NULL,
    sunat_documento_id integer,
    sunat_tipo character varying(10),
    sunat_serie character varying(10),
    sunat_numero character varying(20),
    sunat_estado character varying(20),
    sunat_cdr_descripcion text,
    sunat_emitido_en timestamp without time zone
);


--
-- Name: v_rentabilidad; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_rentabilidad AS
 SELECT l.codigo,
    l."campaña",
    c.razon_social AS productor,
    sum(
        CASE
            WHEN (k.tipo_movimiento = 'entrada'::public.tipo_movimiento_kard) THEN k.total_monto
            ELSE (0)::numeric
        END) AS costo_compra,
    sum(
        CASE
            WHEN (k.tipo_movimiento = 'entrada'::public.tipo_movimiento_kard) THEN k.prima_total
            ELSE (0)::numeric
        END) AS primas_pagadas,
    COALESCE(sum((ve.total_usd * ve.tipo_cambio)), (0)::numeric) AS ingresos_locales,
    COALESCE(sum(ve.total_usd), (0)::numeric) AS ingresos_usd,
    (COALESCE(sum(ve.total_usd), (0)::numeric) - sum(
        CASE
            WHEN (k.tipo_movimiento = 'entrada'::public.tipo_movimiento_kard) THEN (k.total_monto / NULLIF(k.tipo_cambio, (0)::numeric))
            ELSE (0)::numeric
        END)) AS margen_usd
   FROM (((public.acopios l
     JOIN public.clientes c ON ((c.id = l.productor_id)))
     LEFT JOIN public.kardex k ON ((k.acopio_id = l.id)))
     LEFT JOIN public.ventas ve ON (((ve.acopio_id = l.id) AND (ve.estado <> ALL (ARRAY['cancelado'::public.estado_venta, 'borrador'::public.estado_venta])))))
  GROUP BY l.id, l.codigo, l."campaña", c.razon_social;


--
-- Name: ventas_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ventas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ventas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ventas_id_seq OWNED BY public.ventas.id;


--
-- Name: acopio_eventos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_eventos ALTER COLUMN id SET DEFAULT nextval('public.lote_eventos_id_seq'::regclass);


--
-- Name: acopios id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopios ALTER COLUMN id SET DEFAULT nextval('public.acopios_id_seq'::regclass);


--
-- Name: almacenes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.almacenes ALTER COLUMN id SET DEFAULT nextval('public.almacenes_id_seq'::regclass);


--
-- Name: asiento_lineas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asiento_lineas ALTER COLUMN id SET DEFAULT nextval('public.asiento_lineas_id_seq'::regclass);


--
-- Name: asientos_contables id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asientos_contables ALTER COLUMN id SET DEFAULT nextval('public.asientos_contables_id_seq'::regclass);


--
-- Name: auditoria_hallazgos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_hallazgos ALTER COLUMN id SET DEFAULT nextval('public.auditoria_hallazgos_id_seq'::regclass);


--
-- Name: auditorias id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditorias ALTER COLUMN id SET DEFAULT nextval('public.auditorias_id_seq'::regclass);


--
-- Name: backups_registro id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backups_registro ALTER COLUMN id SET DEFAULT nextval('public.backups_registro_id_seq'::regclass);


--
-- Name: capacitacion_participantes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitacion_participantes ALTER COLUMN id SET DEFAULT nextval('public.capacitacion_participantes_id_seq'::regclass);


--
-- Name: capacitaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitaciones ALTER COLUMN id SET DEFAULT nextval('public.capacitaciones_id_seq'::regclass);


--
-- Name: centros_costo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.centros_costo ALTER COLUMN id SET DEFAULT nextval('public.centros_costo_id_seq'::regclass);


--
-- Name: certificaciones_catalogo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.certificaciones_catalogo ALTER COLUMN id SET DEFAULT nextval('public.certificaciones_catalogo_id_seq'::regclass);


--
-- Name: clientes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id SET DEFAULT nextval('public.clientes_id_seq'::regclass);


--
-- Name: cotizaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones ALTER COLUMN id SET DEFAULT nextval('public.cotizaciones_id_seq'::regclass);


--
-- Name: cuentas_pagar id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuentas_pagar ALTER COLUMN id SET DEFAULT nextval('public.cuentas_pagar_id_seq'::regclass);


--
-- Name: flujo_caja id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flujo_caja ALTER COLUMN id SET DEFAULT nextval('public.flujo_caja_id_seq'::regclass);


--
-- Name: kardex id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex ALTER COLUMN id SET DEFAULT nextval('public.kardex_id_seq'::regclass);


--
-- Name: kardex_almacen id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex_almacen ALTER COLUMN id SET DEFAULT nextval('public.kardex_almacen_id_seq'::regclass);


--
-- Name: laboratorio_analisis id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.laboratorio_analisis ALTER COLUMN id SET DEFAULT nextval('public.laboratorio_analisis_id_seq'::regclass);


--
-- Name: oc_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oc_items ALTER COLUMN id SET DEFAULT nextval('public.oc_items_id_seq'::regclass);


--
-- Name: ordenes_compra id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_compra ALTER COLUMN id SET DEFAULT nextval('public.ordenes_compra_id_seq'::regclass);


--
-- Name: ordenes_trabajo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_trabajo ALTER COLUMN id SET DEFAULT nextval('public.ordenes_trabajo_id_seq'::regclass);


--
-- Name: ot_consumo_materiales id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ot_consumo_materiales ALTER COLUMN id SET DEFAULT nextval('public.ot_consumo_materiales_id_seq'::regclass);


--
-- Name: plan_cuentas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_cuentas ALTER COLUMN id SET DEFAULT nextval('public.plan_cuentas_id_seq'::regclass);


--
-- Name: plan_maestro id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_maestro ALTER COLUMN id SET DEFAULT nextval('public.plan_maestro_id_seq'::regclass);


--
-- Name: proveedores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proveedores ALTER COLUMN id SET DEFAULT nextval('public.proveedores_id_seq'::regclass);


--
-- Name: requisicion_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisicion_items ALTER COLUMN id SET DEFAULT nextval('public.requisicion_items_id_seq'::regclass);


--
-- Name: requisiciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisiciones ALTER COLUMN id SET DEFAULT nextval('public.requisiciones_id_seq'::regclass);


--
-- Name: seguridad_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seguridad_log ALTER COLUMN id SET DEFAULT nextval('public.seguridad_log_id_seq'::regclass);


--
-- Name: tipos_cafe id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_cafe ALTER COLUMN id SET DEFAULT nextval('public.tipos_cafe_id_seq'::regclass);


--
-- Name: transformaciones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transformaciones ALTER COLUMN id SET DEFAULT nextval('public.transformaciones_id_seq'::regclass);


--
-- Name: ventas id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ventas ALTER COLUMN id SET DEFAULT nextval('public.ventas_id_seq'::regclass);


--
-- Name: almacenes almacenes_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.almacenes
    ADD CONSTRAINT almacenes_codigo_key UNIQUE (codigo);


--
-- Name: almacenes almacenes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.almacenes
    ADD CONSTRAINT almacenes_pkey PRIMARY KEY (id);


--
-- Name: asiento_lineas asiento_lineas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asiento_lineas
    ADD CONSTRAINT asiento_lineas_pkey PRIMARY KEY (id);


--
-- Name: asientos_contables asientos_contables_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asientos_contables
    ADD CONSTRAINT asientos_contables_numero_key UNIQUE (numero);


--
-- Name: asientos_contables asientos_contables_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asientos_contables
    ADD CONSTRAINT asientos_contables_pkey PRIMARY KEY (id);


--
-- Name: auditoria_hallazgos auditoria_hallazgos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_hallazgos
    ADD CONSTRAINT auditoria_hallazgos_pkey PRIMARY KEY (id);


--
-- Name: auditorias auditorias_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditorias
    ADD CONSTRAINT auditorias_codigo_key UNIQUE (codigo);


--
-- Name: auditorias auditorias_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditorias
    ADD CONSTRAINT auditorias_pkey PRIMARY KEY (id);


--
-- Name: backups_registro backups_registro_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backups_registro
    ADD CONSTRAINT backups_registro_pkey PRIMARY KEY (id);


--
-- Name: campanas campanas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.campanas
    ADD CONSTRAINT campanas_pkey PRIMARY KEY ("año");


--
-- Name: capacitacion_participantes capacitacion_participantes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitacion_participantes
    ADD CONSTRAINT capacitacion_participantes_pkey PRIMARY KEY (id);


--
-- Name: capacitaciones capacitaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitaciones
    ADD CONSTRAINT capacitaciones_pkey PRIMARY KEY (id);


--
-- Name: centros_costo centros_costo_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.centros_costo
    ADD CONSTRAINT centros_costo_codigo_key UNIQUE (codigo);


--
-- Name: centros_costo centros_costo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.centros_costo
    ADD CONSTRAINT centros_costo_pkey PRIMARY KEY (id);


--
-- Name: certificaciones_catalogo certificaciones_catalogo_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.certificaciones_catalogo
    ADD CONSTRAINT certificaciones_catalogo_codigo_key UNIQUE (codigo);


--
-- Name: certificaciones_catalogo certificaciones_catalogo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.certificaciones_catalogo
    ADD CONSTRAINT certificaciones_catalogo_pkey PRIMARY KEY (id);


--
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id);


--
-- Name: clientes clientes_ruc_dni_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_ruc_dni_key UNIQUE (ruc_dni);


--
-- Name: cotizaciones cotizaciones_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones
    ADD CONSTRAINT cotizaciones_numero_key UNIQUE (numero);


--
-- Name: cotizaciones cotizaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones
    ADD CONSTRAINT cotizaciones_pkey PRIMARY KEY (id);


--
-- Name: cuentas_pagar cuentas_pagar_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuentas_pagar
    ADD CONSTRAINT cuentas_pagar_pkey PRIMARY KEY (id);


--
-- Name: flujo_caja flujo_caja_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flujo_caja
    ADD CONSTRAINT flujo_caja_pkey PRIMARY KEY (id);


--
-- Name: kardex_almacen kardex_almacen_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex_almacen
    ADD CONSTRAINT kardex_almacen_pkey PRIMARY KEY (id);


--
-- Name: kardex kardex_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex
    ADD CONSTRAINT kardex_pkey PRIMARY KEY (id);


--
-- Name: laboratorio_analisis laboratorio_analisis_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.laboratorio_analisis
    ADD CONSTRAINT laboratorio_analisis_pkey PRIMARY KEY (id);


--
-- Name: acopio_certificaciones lote_certificaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_certificaciones
    ADD CONSTRAINT lote_certificaciones_pkey PRIMARY KEY (acopio_id, certificacion_id);


--
-- Name: acopio_eventos lote_eventos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_eventos
    ADD CONSTRAINT lote_eventos_pkey PRIMARY KEY (id);


--
-- Name: acopios lotes_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopios
    ADD CONSTRAINT lotes_codigo_key UNIQUE (codigo);


--
-- Name: acopios lotes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopios
    ADD CONSTRAINT lotes_pkey PRIMARY KEY (id);


--
-- Name: oc_items oc_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oc_items
    ADD CONSTRAINT oc_items_pkey PRIMARY KEY (id);


--
-- Name: ordenes_compra ordenes_compra_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_compra
    ADD CONSTRAINT ordenes_compra_numero_key UNIQUE (numero);


--
-- Name: ordenes_compra ordenes_compra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_compra
    ADD CONSTRAINT ordenes_compra_pkey PRIMARY KEY (id);


--
-- Name: ordenes_trabajo ordenes_trabajo_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_trabajo
    ADD CONSTRAINT ordenes_trabajo_numero_key UNIQUE (numero);


--
-- Name: ordenes_trabajo ordenes_trabajo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_trabajo
    ADD CONSTRAINT ordenes_trabajo_pkey PRIMARY KEY (id);


--
-- Name: ot_consumo_materiales ot_consumo_materiales_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ot_consumo_materiales
    ADD CONSTRAINT ot_consumo_materiales_pkey PRIMARY KEY (id);


--
-- Name: plan_cuentas plan_cuentas_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_cuentas
    ADD CONSTRAINT plan_cuentas_codigo_key UNIQUE (codigo);


--
-- Name: plan_cuentas plan_cuentas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_cuentas
    ADD CONSTRAINT plan_cuentas_pkey PRIMARY KEY (id);


--
-- Name: plan_maestro plan_maestro_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_maestro
    ADD CONSTRAINT plan_maestro_pkey PRIMARY KEY (id);


--
-- Name: proveedores proveedores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proveedores
    ADD CONSTRAINT proveedores_pkey PRIMARY KEY (id);


--
-- Name: proveedores proveedores_ruc_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proveedores
    ADD CONSTRAINT proveedores_ruc_key UNIQUE (ruc);


--
-- Name: requisicion_items requisicion_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisicion_items
    ADD CONSTRAINT requisicion_items_pkey PRIMARY KEY (id);


--
-- Name: requisiciones requisiciones_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisiciones
    ADD CONSTRAINT requisiciones_numero_key UNIQUE (numero);


--
-- Name: requisiciones requisiciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisiciones
    ADD CONSTRAINT requisiciones_pkey PRIMARY KEY (id);


--
-- Name: seguridad_log seguridad_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.seguridad_log
    ADD CONSTRAINT seguridad_log_pkey PRIMARY KEY (id);


--
-- Name: tipos_cafe tipos_cafe_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipos_cafe
    ADD CONSTRAINT tipos_cafe_pkey PRIMARY KEY (id);


--
-- Name: transformaciones transformaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transformaciones
    ADD CONSTRAINT transformaciones_pkey PRIMARY KEY (id);


--
-- Name: ventas ventas_numero_contrato_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_numero_contrato_key UNIQUE (numero_contrato);


--
-- Name: ventas ventas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_pkey PRIMARY KEY (id);


--
-- Name: idx_acopio_eventos_acopio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acopio_eventos_acopio ON public.acopio_eventos USING btree (acopio_id, creado_en);


--
-- Name: idx_acopios_campana; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acopios_campana ON public.acopios USING btree ("campaña");


--
-- Name: idx_acopios_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acopios_estado ON public.acopios USING btree (estado);


--
-- Name: idx_acopios_productor; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_acopios_productor ON public.acopios USING btree (productor_id);


--
-- Name: idx_asiento_ref; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_asiento_ref ON public.asientos_contables USING btree (referencia_tipo, referencia_id);


--
-- Name: idx_cotiz_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cotiz_estado ON public.cotizaciones USING btree (estado, fecha_vencimiento);


--
-- Name: idx_cxp_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cxp_estado ON public.cuentas_pagar USING btree (estado, fecha_vencimiento);


--
-- Name: idx_fc_fecha_tipo; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_fc_fecha_tipo ON public.flujo_caja USING btree (fecha, tipo);


--
-- Name: idx_kardex_acopio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_kardex_acopio ON public.kardex USING btree (acopio_id);


--
-- Name: idx_kardex_fecha; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_kardex_fecha ON public.kardex USING btree (fecha);


--
-- Name: idx_lab_acopio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_lab_acopio ON public.laboratorio_analisis USING btree (acopio_id);


--
-- Name: idx_ot_acopio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ot_acopio ON public.ordenes_trabajo USING btree (acopio_id);


--
-- Name: idx_ot_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ot_estado ON public.ordenes_trabajo USING btree (estado);


--
-- Name: idx_ventas_acopio; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ventas_acopio ON public.ventas USING btree (acopio_id);


--
-- Name: idx_ventas_comprador; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ventas_comprador ON public.ventas USING btree (comprador_id);


--
-- Name: idx_ventas_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ventas_estado ON public.ventas USING btree (estado);


--
-- Name: idx_ventas_sunat_documento; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ventas_sunat_documento ON public.ventas USING btree (sunat_tipo, sunat_serie, sunat_numero) WHERE (sunat_documento_id IS NOT NULL);


--
-- Name: idx_ventas_sunat_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_ventas_sunat_estado ON public.ventas USING btree (sunat_estado) WHERE (sunat_estado IS NOT NULL);


--
-- Name: v_acopios_completos _RETURN; Type: RULE; Schema: public; Owner: -
--

CREATE OR REPLACE VIEW public.v_acopios_completos AS
 SELECT l.id,
    l.codigo,
    l.estado,
    l."campaña",
    tc.nombre AS tipo_cafe,
    c.razon_social AS productor,
    c.departamento,
    c.provincia,
    l.finca,
    l.altitud_msnm,
    l.variedad,
    l.proceso_beneficio,
    l.fecha_acopio,
    l.peso_inicial_kg,
    l.peso_actual_kg,
    l.peso_final_kg,
    l.merma_kg,
    l.rendimiento_pct,
    la.fecha_analisis AS ultima_analisis,
    la.score_taza,
    la.clasificacion,
    la.humedad_pct,
    la.rendimiento_pct AS rend_lab,
    string_agg(DISTINCT (cert.codigo)::text, ', '::text ORDER BY (cert.codigo)::text) AS certificaciones,
    COALESCE(sum(v.cantidad_kg), (0)::numeric) AS kg_vendidos,
    (l.peso_actual_kg - COALESCE(sum(v.cantidad_kg), (0)::numeric)) AS kg_disponibles
   FROM ((((((public.acopios l
     JOIN public.tipos_cafe tc ON ((tc.id = l.tipo_cafe_id)))
     JOIN public.clientes c ON ((c.id = l.productor_id)))
     LEFT JOIN LATERAL ( SELECT laboratorio_analisis.id,
            laboratorio_analisis.acopio_id,
            laboratorio_analisis.fecha_analisis,
            laboratorio_analisis.analista,
            laboratorio_analisis.laboratorio,
            laboratorio_analisis.humedad_pct,
            laboratorio_analisis.rendimiento_pct,
            laboratorio_analisis.densidad_gr_l,
            laboratorio_analisis.defectos_cat1,
            laboratorio_analisis.defectos_cat2,
            laboratorio_analisis.score_taza,
            laboratorio_analisis.fragancia,
            laboratorio_analisis.aroma,
            laboratorio_analisis.sabor,
            laboratorio_analisis.post_gusto,
            laboratorio_analisis.acidez,
            laboratorio_analisis.cuerpo,
            laboratorio_analisis.uniformidad,
            laboratorio_analisis.balance,
            laboratorio_analisis.limpieza_taza,
            laboratorio_analisis.dulzura,
            laboratorio_analisis.defecto_taza,
            laboratorio_analisis.clasificacion,
            laboratorio_analisis.notas_catacion,
            laboratorio_analisis.aprobado,
            laboratorio_analisis.creado_en
           FROM public.laboratorio_analisis
          WHERE (laboratorio_analisis.acopio_id = l.id)
          ORDER BY laboratorio_analisis.fecha_analisis DESC
         LIMIT 1) la ON (true))
     LEFT JOIN public.acopio_certificaciones lc ON ((lc.acopio_id = l.id)))
     LEFT JOIN public.certificaciones_catalogo cert ON ((cert.id = lc.certificacion_id)))
     LEFT JOIN public.ventas v ON (((v.acopio_id = l.id) AND (v.estado <> 'cancelado'::public.estado_venta))))
  GROUP BY l.id, tc.nombre, c.razon_social, c.departamento, c.provincia, la.fecha_analisis, la.score_taza, la.clasificacion, la.humedad_pct, la.rendimiento_pct;


--
-- Name: acopios trg_acopio_estado_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_acopio_estado_log AFTER UPDATE OF estado ON public.acopios FOR EACH ROW EXECUTE FUNCTION public.fn_log_lote_estado();


--
-- Name: acopios trg_acopio_insert_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_acopio_insert_log AFTER INSERT ON public.acopios FOR EACH ROW EXECUTE FUNCTION public.fn_log_lote_insert();


--
-- Name: acopios trg_acopios_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_acopios_updated_at BEFORE UPDATE ON public.acopios FOR EACH ROW EXECUTE FUNCTION public.fn_set_updated_at();


--
-- Name: clientes trg_clientes_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_clientes_updated_at BEFORE UPDATE ON public.clientes FOR EACH ROW EXECUTE FUNCTION public.fn_set_updated_at();


--
-- Name: kardex trg_kardex_after_insert; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_kardex_after_insert AFTER INSERT ON public.kardex FOR EACH ROW EXECUTE FUNCTION public.fn_kardex_after_insert();


--
-- Name: kardex trg_kardex_before_insert; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_kardex_before_insert BEFORE INSERT ON public.kardex FOR EACH ROW EXECUTE FUNCTION public.fn_kardex_before_insert();


--
-- Name: ventas trg_venta_after_update; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_venta_after_update AFTER UPDATE ON public.ventas FOR EACH ROW EXECUTE FUNCTION public.fn_venta_after_update();


--
-- Name: ventas trg_venta_estado_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_venta_estado_log AFTER UPDATE OF estado ON public.ventas FOR EACH ROW EXECUTE FUNCTION public.fn_log_venta_estado();


--
-- Name: ventas trg_venta_insert_log; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_venta_insert_log AFTER INSERT ON public.ventas FOR EACH ROW EXECUTE FUNCTION public.fn_log_venta_insert();


--
-- Name: ventas trg_ventas_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_ventas_updated_at BEFORE UPDATE ON public.ventas FOR EACH ROW EXECUTE FUNCTION public.fn_set_updated_at();


--
-- Name: asiento_lineas asiento_lineas_asiento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asiento_lineas
    ADD CONSTRAINT asiento_lineas_asiento_id_fkey FOREIGN KEY (asiento_id) REFERENCES public.asientos_contables(id) ON DELETE CASCADE;


--
-- Name: asiento_lineas asiento_lineas_centro_costo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asiento_lineas
    ADD CONSTRAINT asiento_lineas_centro_costo_id_fkey FOREIGN KEY (centro_costo_id) REFERENCES public.centros_costo(id);


--
-- Name: asiento_lineas asiento_lineas_cuenta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.asiento_lineas
    ADD CONSTRAINT asiento_lineas_cuenta_id_fkey FOREIGN KEY (cuenta_id) REFERENCES public.plan_cuentas(id);


--
-- Name: auditoria_hallazgos auditoria_hallazgos_auditoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria_hallazgos
    ADD CONSTRAINT auditoria_hallazgos_auditoria_id_fkey FOREIGN KEY (auditoria_id) REFERENCES public.auditorias(id) ON DELETE CASCADE;


--
-- Name: capacitacion_participantes capacitacion_participantes_capacitacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitacion_participantes
    ADD CONSTRAINT capacitacion_participantes_capacitacion_id_fkey FOREIGN KEY (capacitacion_id) REFERENCES public.capacitaciones(id) ON DELETE CASCADE;


--
-- Name: capacitacion_participantes capacitacion_participantes_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.capacitacion_participantes
    ADD CONSTRAINT capacitacion_participantes_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.clientes(id);


--
-- Name: centros_costo centros_costo_padre_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.centros_costo
    ADD CONSTRAINT centros_costo_padre_id_fkey FOREIGN KEY (padre_id) REFERENCES public.centros_costo(id);


--
-- Name: cotizaciones cotizaciones_comprador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones
    ADD CONSTRAINT cotizaciones_comprador_id_fkey FOREIGN KEY (comprador_id) REFERENCES public.clientes(id);


--
-- Name: cotizaciones cotizaciones_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones
    ADD CONSTRAINT cotizaciones_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- Name: cotizaciones cotizaciones_venta_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizaciones
    ADD CONSTRAINT cotizaciones_venta_id_fkey FOREIGN KEY (venta_id) REFERENCES public.ventas(id);


--
-- Name: cuentas_pagar cuentas_pagar_orden_compra_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuentas_pagar
    ADD CONSTRAINT cuentas_pagar_orden_compra_id_fkey FOREIGN KEY (orden_compra_id) REFERENCES public.ordenes_compra(id);


--
-- Name: cuentas_pagar cuentas_pagar_proveedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cuentas_pagar
    ADD CONSTRAINT cuentas_pagar_proveedor_id_fkey FOREIGN KEY (proveedor_id) REFERENCES public.proveedores(id);


--
-- Name: flujo_caja flujo_caja_centro_costo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flujo_caja
    ADD CONSTRAINT flujo_caja_centro_costo_id_fkey FOREIGN KEY (centro_costo_id) REFERENCES public.centros_costo(id);


--
-- Name: kardex_almacen kardex_almacen_almacen_destino_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex_almacen
    ADD CONSTRAINT kardex_almacen_almacen_destino_id_fkey FOREIGN KEY (almacen_destino_id) REFERENCES public.almacenes(id);


--
-- Name: kardex_almacen kardex_almacen_almacen_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex_almacen
    ADD CONSTRAINT kardex_almacen_almacen_origen_id_fkey FOREIGN KEY (almacen_origen_id) REFERENCES public.almacenes(id);


--
-- Name: kardex_almacen kardex_almacen_kardex_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex_almacen
    ADD CONSTRAINT kardex_almacen_kardex_id_fkey FOREIGN KEY (kardex_id) REFERENCES public.kardex(id);


--
-- Name: kardex kardex_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kardex
    ADD CONSTRAINT kardex_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- Name: laboratorio_analisis laboratorio_analisis_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.laboratorio_analisis
    ADD CONSTRAINT laboratorio_analisis_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- Name: acopio_certificaciones lote_certificaciones_certificacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_certificaciones
    ADD CONSTRAINT lote_certificaciones_certificacion_id_fkey FOREIGN KEY (certificacion_id) REFERENCES public.certificaciones_catalogo(id);


--
-- Name: acopio_certificaciones lote_certificaciones_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_certificaciones
    ADD CONSTRAINT lote_certificaciones_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id) ON DELETE CASCADE;


--
-- Name: acopio_eventos lote_eventos_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopio_eventos
    ADD CONSTRAINT lote_eventos_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- Name: acopios lotes_productor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopios
    ADD CONSTRAINT lotes_productor_id_fkey FOREIGN KEY (productor_id) REFERENCES public.clientes(id);


--
-- Name: acopios lotes_tipo_cafe_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.acopios
    ADD CONSTRAINT lotes_tipo_cafe_id_fkey FOREIGN KEY (tipo_cafe_id) REFERENCES public.tipos_cafe(id);


--
-- Name: oc_items oc_items_orden_compra_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oc_items
    ADD CONSTRAINT oc_items_orden_compra_id_fkey FOREIGN KEY (orden_compra_id) REFERENCES public.ordenes_compra(id) ON DELETE CASCADE;


--
-- Name: ordenes_compra ordenes_compra_proveedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_compra
    ADD CONSTRAINT ordenes_compra_proveedor_id_fkey FOREIGN KEY (proveedor_id) REFERENCES public.proveedores(id);


--
-- Name: ordenes_compra ordenes_compra_requisicion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_compra
    ADD CONSTRAINT ordenes_compra_requisicion_id_fkey FOREIGN KEY (requisicion_id) REFERENCES public.requisiciones(id);


--
-- Name: ordenes_trabajo ordenes_trabajo_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_trabajo
    ADD CONSTRAINT ordenes_trabajo_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- Name: ordenes_trabajo ordenes_trabajo_plan_maestro_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ordenes_trabajo
    ADD CONSTRAINT ordenes_trabajo_plan_maestro_id_fkey FOREIGN KEY (plan_maestro_id) REFERENCES public.plan_maestro(id);


--
-- Name: ot_consumo_materiales ot_consumo_materiales_orden_trabajo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ot_consumo_materiales
    ADD CONSTRAINT ot_consumo_materiales_orden_trabajo_id_fkey FOREIGN KEY (orden_trabajo_id) REFERENCES public.ordenes_trabajo(id) ON DELETE CASCADE;


--
-- Name: plan_cuentas plan_cuentas_padre_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_cuentas
    ADD CONSTRAINT plan_cuentas_padre_id_fkey FOREIGN KEY (padre_id) REFERENCES public.plan_cuentas(id);


--
-- Name: plan_maestro plan_maestro_tipo_cafe_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_maestro
    ADD CONSTRAINT plan_maestro_tipo_cafe_id_fkey FOREIGN KEY (tipo_cafe_id) REFERENCES public.tipos_cafe(id);


--
-- Name: requisicion_items requisicion_items_requisicion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.requisicion_items
    ADD CONSTRAINT requisicion_items_requisicion_id_fkey FOREIGN KEY (requisicion_id) REFERENCES public.requisiciones(id) ON DELETE CASCADE;


--
-- Name: transformaciones transformaciones_lote_destino_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transformaciones
    ADD CONSTRAINT transformaciones_lote_destino_id_fkey FOREIGN KEY (acopio_destino_id) REFERENCES public.acopios(id);


--
-- Name: transformaciones transformaciones_lote_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transformaciones
    ADD CONSTRAINT transformaciones_lote_origen_id_fkey FOREIGN KEY (acopio_origen_id) REFERENCES public.acopios(id);


--
-- Name: ventas ventas_comprador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_comprador_id_fkey FOREIGN KEY (comprador_id) REFERENCES public.clientes(id);


--
-- Name: ventas ventas_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ventas
    ADD CONSTRAINT ventas_lote_id_fkey FOREIGN KEY (acopio_id) REFERENCES public.acopios(id);


--
-- PostgreSQL database dump complete
--

\unrestrict ZkuupoJIMwd3duymdwW9oujmF82XQGDZUd8p4S904b8nGe289no0JDYJSA3uvxu

