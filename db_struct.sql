--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


SET search_path = public, pg_catalog;

--
-- Name: klaus_varetype; Type: TYPE; Schema: public; Owner: -
--
DO $$
BEGIN
IF (select exists (select 1 from pg_type where typname = 'klaus_varetype')) THEN
ELSE
CREATE TYPE klaus_varetype AS ENUM (
    'beer',
    'wine',
    'spirits',
    'snacks',
    'soda',
    'other'
);
END IF;
END$$;

--
-- Name: TYPE klaus_varetype; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TYPE klaus_varetype IS 'Inneholder de forskjellige varetypene i baren';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: klaus; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE IF NOT EXISTS klaus (
    id serial NOT NULL CONSTRAINT klaus_pkey PRIMARY KEY,
    bruker integer NOT NULL,
    type integer,
    belop integer DEFAULT 0,
    kommentar character varying,
    dato date DEFAULT now(),
    registrert timestamp without time zone DEFAULT date_trunc('second'::text, now())
);


--
-- Name: TABLE klaus; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE klaus IS 'Inneholder alle Klaus-transaksjoner';


--
-- Name: COLUMN klaus.type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN klaus.type IS 'Angir type transaksjon (0 = innskudd, 1 = FK-liste, 2 = BSF)';


--
-- Name: COLUMN klaus.dato; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN klaus.dato IS 'Dato på krysseliste';


--
-- Name: COLUMN klaus.registrert; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN klaus.registrert IS 'Tidspunkt for registrering';


--
-- Name: personer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE IF NOT EXISTS personer (
    id serial NOT NULL CONSTRAINT personer_pkey PRIMARY KEY,
    kallenavn character varying,
    fornavn character varying,
    etternavn character varying,
    epost character varying,
    kull integer,
    liste smallint,
    svartegrense integer,
    aktiv boolean NOT NULL,
    slettet boolean NOT NULL,
    oppdatert timestamp without time zone DEFAULT date_trunc('second'::text, now()) NOT NULL,
    tlf character varying
);


--
-- Name: TABLE personer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE personer IS 'Inneholder oversikt over alle FK-ere';


--
-- Name: klaus_personer; Type: VIEW; Schema: public; Owner: -
--

CREATE OR REPLACE VIEW klaus_personer AS
    SELECT klaus.registrert, klaus.dato, klaus.type, klaus.belop, klaus.bruker, klaus.kommentar, personer.id, personer.liste, personer.fornavn, personer.etternavn, personer.kallenavn, personer.slettet FROM (klaus JOIN personer ON ((klaus.bruker = personer.id)));


--
-- Name: klaus_saldoer; Type: VIEW; Schema: public; Owner: -; Tablespace: 
--

CREATE OR REPLACE VIEW klaus_saldoer AS
   SELECT sum(klaus.belop) AS saldo, personer.svartegrense, personer.kull, personer.liste, personer.fornavn, personer.etternavn, personer.kallenavn, personer.id
   FROM klaus
   RIGHT JOIN personer ON klaus.bruker = personer.id
  WHERE personer.slettet = false
  GROUP BY personer.id, personer.svartegrense, personer.kull, personer.liste, personer.fornavn, personer.etternavn, personer.kallenavn;


--
-- Name: varer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE IF NOT EXISTS varer (
    id serial NOT NULL CONSTRAINT varer_pkey PRIMARY KEY,
    navn character varying DEFAULT 'Mangler varenavn'::character varying NOT NULL,
    pris integer DEFAULT 0,
    antall integer DEFAULT 0,
    kategori klaus_varetype DEFAULT 'other'::klaus_varetype,
    strekkode character varying,
    slettet boolean DEFAULT false
);


--
-- Name: TABLE varer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE varer IS 'Inneholder oversikt over varer som finnes i Klaus Minnefond';


--
-- Name: COLUMN varer.id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN varer.id IS 'Fordi kjekt å ha noe fast å referere til';


--
-- Name: COLUMN varer.navn; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN varer.navn IS 'Navnet på varen';


--
-- Name: verv; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE IF NOT EXISTS verv (
    id serial NOT NULL,
    person integer NOT NULL,
    verv character varying NOT NULL,
    dato date
);


--
-- Name: TABLE verv; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE verv IS 'Inneholder oversikt over hvem som har hvilke verv';


--
-- Name: COLUMN verv.person; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN verv.person IS 'Brukes for å linke mot medlemstabellen';


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY klaus ALTER COLUMN id SET DEFAULT nextval('klaus_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY personer ALTER COLUMN id SET DEFAULT nextval('personer_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY varer ALTER COLUMN id SET DEFAULT nextval('varer_id_seq'::regclass);


--
-- PostgreSQL database dump complete
--

