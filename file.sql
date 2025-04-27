--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: animal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.animal (
    idanimal character varying(50) NOT NULL,
    "espÃ¨cea" character varying(50) NOT NULL,
    noma character varying(50) NOT NULL,
    racea character varying(50),
    taille numeric(15,2),
    genre character varying(50),
    poids numeric(15,2),
    castration boolean,
    date_de_naissance date,
    "idpropriÃ©taire" character varying(50) NOT NULL
);


ALTER TABLE public.animal OWNER TO postgres;

--
-- Name: consultation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.consultation (
    idconsultation character varying(50) NOT NULL,
    typec character varying(50) NOT NULL,
    datec date NOT NULL,
    heurec time without time zone NOT NULL,
    "durÃ©ec" integer,
    diagnostic text,
    motif text,
    lieuc character varying(50) NOT NULL,
    tarif numeric(15,2) NOT NULL,
    CONSTRAINT ck_lieuc CHECK (((lieuc)::text = ANY (ARRAY[('Cabinet'::character varying)::text, ('Hors Cabinet'::character varying)::text]))),
    CONSTRAINT ck_typec CHECK (((typec)::text = ANY (ARRAY[('Basique'::character varying)::text, ('OstÃ©opathique'::character varying)::text, ('HomÃ©opathique'::character varying)::text])))
);


ALTER TABLE public.consultation OWNER TO postgres;

--
-- Name: consulter; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.consulter (
    idanimal character varying(50) NOT NULL,
    idconsultation character varying(50) NOT NULL,
    tarif numeric(15,2),
    lieuc character varying(50)
);


ALTER TABLE public.consulter OWNER TO postgres;

--
-- Name: historique; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.historique (
    idconsultation character varying(50) NOT NULL,
    idancienneconsultation character varying(50) NOT NULL
);


ALTER TABLE public.historique OWNER TO postgres;

--
-- Name: manipulation; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.manipulation (
    codemanipulation character varying(8) NOT NULL,
    description character varying(150) NOT NULL,
    "durÃ©e_estimÃ©e" integer NOT NULL,
    tarif_base numeric(10,2) NOT NULL
);


ALTER TABLE public.manipulation OWNER TO postgres;

--
-- Name: propriÃ©taire; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."propriÃ©taire" (
    "idpropriÃ©taire" character varying(50) NOT NULL,
    nomp character varying(25) NOT NULL,
    "prÃ©nomp" character varying(50) NOT NULL,
    adressep character varying(50),
    "tÃ©lp" character varying(15),
    iban character varying(34),
    site_web character varying(50),
    type character varying(50) NOT NULL,
    CONSTRAINT ck_type CHECK (((type)::text = ANY (ARRAY[('Particulier'::character varying)::text, ('Professionnel'::character varying)::text])))
);


ALTER TABLE public."propriÃ©taire" OWNER TO postgres;

--
-- Name: soigner; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.soigner (
    idconsultation character varying(50) NOT NULL,
    codemanipulation character varying(8) NOT NULL
);


ALTER TABLE public.soigner OWNER TO postgres;

--
-- Name: utilisateurs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.utilisateurs (
    id integer NOT NULL,
    nom character varying(100),
    email character varying(100),
    mot_de_passe text,
    role character varying(50),
    CONSTRAINT utilisateurs_role_check CHECK (((role)::text = ANY (ARRAY[('admin'::character varying)::text, ('client'::character varying)::text, ('gestionnaire'::character varying)::text])))
);


ALTER TABLE public.utilisateurs OWNER TO postgres;

--
-- Name: utilisateurs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.utilisateurs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.utilisateurs_id_seq OWNER TO postgres;

--
-- Name: utilisateurs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.utilisateurs_id_seq OWNED BY public.utilisateurs.id;


--
-- Name: utilisateurs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs ALTER COLUMN id SET DEFAULT nextval('public.utilisateurs_id_seq'::regclass);


--
-- Data for Name: animal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.animal (idanimal, "espÃ¨cea", noma, racea, taille, genre, poids, castration, date_de_naissance, "idpropriÃ©taire") FROM stdin;
A001	Chien	Rex	Labrador	60.50	MÃ¢le	30.20	t	2018-06-12	P001
A002	Chat	Misty	Siamois	30.20	Femelle	4.50	f	2020-03-08	P003
A003	Cheval	Eclair	Pur-Sang	165.00	MÃ¢le	500.00	f	2015-05-21	P002
\.


--
-- Data for Name: consultation; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.consultation (idconsultation, typec, datec, heurec, "durÃ©ec", diagnostic, motif, lieuc, tarif) FROM stdin;
C001	Basique	2024-01-10	10:30:00	30	ProblÃ¨me digestif	Consultation de routine	Cabinet	15.00
C002	OstÃ©opathique	2024-12-15	15:00:00	60	Boiterie arriÃ¨re gauche	Chute rÃ©cente	Hors Cabinet	70.00
C522	Basique	2025-04-12	11:20:00	30	Accepté par administrateur	fish	Cabinet	15.00
\.


--
-- Data for Name: consulter; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.consulter (idanimal, idconsultation, tarif, lieuc) FROM stdin;
A001	C001	15.00	Cabinet
A003	C002	70.00	Hors Cabinet
A001	C522	15.00	Cabinet
\.


--
-- Data for Name: historique; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historique (idconsultation, idancienneconsultation) FROM stdin;
C002	C001
\.


--
-- Data for Name: manipulation; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.manipulation (codemanipulation, description, "durÃ©e_estimÃ©e", tarif_base) FROM stdin;
M001	Manipulation lombaire	20	25.00
M002	Manipulation cervicale	15	20.00
M003	Manipulation thoracique	30	30.00
\.


--
-- Data for Name: propriÃ©taire; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public."propriÃ©taire" ("idpropriÃ©taire", nomp, "prÃ©nomp", adressep, "tÃ©lp", iban, site_web, type) FROM stdin;
P001	Dupont	Marie	12 rue des Lilas, Paris	0612345678	FR7630004015870002601171220	\N	Particulier
P002	Durand	Jean	15 avenue des Champs, Lyon	0623456789	FR7630004015870002601171230	https://www.ecuriedurand.com	Professionnel
P003	Martin	Sophie	8 boulevard Haussmann, Marseille	0634567890	\N	\N	Particulier
\.


--
-- Data for Name: soigner; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.soigner (idconsultation, codemanipulation) FROM stdin;
C002	M001
C002	M003
\.


--
-- Data for Name: utilisateurs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.utilisateurs (id, nom, email, mot_de_passe, role) FROM stdin;
1	tayoken	contact@tayoken.xyz	2606	admin
2	Jean Durjardin	JeanDJ@gmail.com	1234	client
3	Jean	Jeanjean	1234	client
4	Dupont	jedihd@fdidf.fr	124	client
\.


--
-- Name: utilisateurs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.utilisateurs_id_seq', 1, false);


--
-- Name: animal animal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.animal
    ADD CONSTRAINT animal_pkey PRIMARY KEY (idanimal);


--
-- Name: consultation consultation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.consultation
    ADD CONSTRAINT consultation_pkey PRIMARY KEY (idconsultation);


--
-- Name: consulter consulter_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.consulter
    ADD CONSTRAINT consulter_pkey PRIMARY KEY (idanimal, idconsultation);


--
-- Name: historique historique_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historique
    ADD CONSTRAINT historique_pkey PRIMARY KEY (idconsultation, idancienneconsultation);


--
-- Name: manipulation manipulation_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.manipulation
    ADD CONSTRAINT manipulation_pkey PRIMARY KEY (codemanipulation);


--
-- Name: propriÃ©taire propriÃ©taire_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."propriÃ©taire"
    ADD CONSTRAINT "propriÃ©taire_pkey" PRIMARY KEY ("idpropriÃ©taire");


--
-- Name: soigner soigner_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.soigner
    ADD CONSTRAINT soigner_pkey PRIMARY KEY (idconsultation, codemanipulation);


--
-- Name: utilisateurs utilisateurs_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT utilisateurs_email_key UNIQUE (email);


--
-- Name: utilisateurs utilisateurs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT utilisateurs_pkey PRIMARY KEY (id);


--
-- Name: animal fk_animal_proprietaire; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.animal
    ADD CONSTRAINT fk_animal_proprietaire FOREIGN KEY ("idpropriÃ©taire") REFERENCES public."propriÃ©taire"("idpropriÃ©taire");


--
-- Name: consulter fk_consulter_animal; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.consulter
    ADD CONSTRAINT fk_consulter_animal FOREIGN KEY (idanimal) REFERENCES public.animal(idanimal);


--
-- Name: consulter fk_consulter_consultation; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.consulter
    ADD CONSTRAINT fk_consulter_consultation FOREIGN KEY (idconsultation) REFERENCES public.consultation(idconsultation);


--
-- Name: historique fk_historique_consultation1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historique
    ADD CONSTRAINT fk_historique_consultation1 FOREIGN KEY (idconsultation) REFERENCES public.consultation(idconsultation);


--
-- Name: historique fk_historique_consultation2; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historique
    ADD CONSTRAINT fk_historique_consultation2 FOREIGN KEY (idancienneconsultation) REFERENCES public.consultation(idconsultation);


--
-- Name: soigner fk_soigner_consultation; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.soigner
    ADD CONSTRAINT fk_soigner_consultation FOREIGN KEY (idconsultation) REFERENCES public.consultation(idconsultation);


--
-- Name: soigner fk_soigner_manipulation; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.soigner
    ADD CONSTRAINT fk_soigner_manipulation FOREIGN KEY (codemanipulation) REFERENCES public.manipulation(codemanipulation);


--
-- PostgreSQL database dump complete
--

