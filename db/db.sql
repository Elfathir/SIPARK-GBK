--
-- PostgreSQL database dump
--

\restrict sl4zLpVlBpXI4bYhZHADR0cpwEGcXqXpikRU3NFUONw6DYu5PzWmkAK6JHDaQTy

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

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
-- Name: area_parkir; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.area_parkir (
    id_area integer NOT NULL,
    nama_area character varying(100) NOT NULL,
    lokasi character varying(50) NOT NULL,
    kapasitas_mobil integer NOT NULL,
    kapasitas_motor integer NOT NULL,
    status character varying(20) NOT NULL,
    CONSTRAINT area_parkir_kapasitas_mobil_check CHECK ((kapasitas_mobil >= 0)),
    CONSTRAINT area_parkir_kapasitas_motor_check CHECK ((kapasitas_motor >= 0)),
    CONSTRAINT area_parkir_status_check CHECK (((status)::text = ANY ((ARRAY['Aktif'::character varying, 'Nonaktif'::character varying])::text[])))
);


ALTER TABLE public.area_parkir OWNER TO postgres;

--
-- Name: area_parkir_id_area_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.area_parkir_id_area_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.area_parkir_id_area_seq OWNER TO postgres;

--
-- Name: area_parkir_id_area_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.area_parkir_id_area_seq OWNED BY public.area_parkir.id_area;


--
-- Name: kendaraan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.kendaraan (
    id_kendaraan integer NOT NULL,
    plat_nomor character varying(15) NOT NULL,
    jenis_kendaraan character varying(20) NOT NULL,
    merk character varying(50) NOT NULL,
    warna character varying(30) NOT NULL,
    pemilik character varying(100) NOT NULL,
    CONSTRAINT kendaraan_jenis_kendaraan_check CHECK (((jenis_kendaraan)::text = ANY ((ARRAY['Mobil'::character varying, 'Motor'::character varying])::text[])))
);


ALTER TABLE public.kendaraan OWNER TO postgres;

--
-- Name: TABLE kendaraan; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.kendaraan IS 'Menyimpan data kendaraan yang melakukan parkir';


--
-- Name: kendaraan_id_kendaraan_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.kendaraan_id_kendaraan_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.kendaraan_id_kendaraan_seq OWNER TO postgres;

--
-- Name: kendaraan_id_kendaraan_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.kendaraan_id_kendaraan_seq OWNED BY public.kendaraan.id_kendaraan;


--
-- Name: log_aktivitas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.log_aktivitas (
    id_log integer NOT NULL,
    id_user integer NOT NULL,
    aktivitas text NOT NULL,
    waktu timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.log_aktivitas OWNER TO postgres;

--
-- Name: log_aktivitas_id_log_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.log_aktivitas_id_log_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.log_aktivitas_id_log_seq OWNER TO postgres;

--
-- Name: log_aktivitas_id_log_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.log_aktivitas_id_log_seq OWNED BY public.log_aktivitas.id_log;


--
-- Name: petugas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.petugas (
    id_petugas integer NOT NULL,
    id_user integer NOT NULL,
    no_hp character varying(20),
    shift character varying(20) NOT NULL,
    CONSTRAINT petugas_shift_check CHECK (((shift)::text = ANY ((ARRAY['Pagi'::character varying, 'Siang'::character varying, 'Malam'::character varying])::text[])))
);


ALTER TABLE public.petugas OWNER TO postgres;

--
-- Name: petugas_id_petugas_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.petugas_id_petugas_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.petugas_id_petugas_seq OWNER TO postgres;

--
-- Name: petugas_id_petugas_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.petugas_id_petugas_seq OWNED BY public.petugas.id_petugas;


--
-- Name: tarif_parkir; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tarif_parkir (
    id_tarif integer NOT NULL,
    jenis_kendaraan character varying(20) NOT NULL,
    tarif_awal numeric(10,2) NOT NULL,
    durasi_awal integer NOT NULL,
    tarif_tambahan numeric(10,2) DEFAULT 0 NOT NULL,
    jenis_tarif character varying(20) NOT NULL,
    CONSTRAINT tarif_parkir_jenis_kendaraan_check CHECK (((jenis_kendaraan)::text = ANY ((ARRAY['Mobil'::character varying, 'Motor'::character varying])::text[]))),
    CONSTRAINT tarif_parkir_jenis_tarif_check CHECK (((jenis_tarif)::text = ANY ((ARRAY['Flat'::character varying, 'Bertingkat'::character varying])::text[])))
);


ALTER TABLE public.tarif_parkir OWNER TO postgres;

--
-- Name: tarif_parkir_id_tarif_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tarif_parkir_id_tarif_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tarif_parkir_id_tarif_seq OWNER TO postgres;

--
-- Name: tarif_parkir_id_tarif_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tarif_parkir_id_tarif_seq OWNED BY public.tarif_parkir.id_tarif;


--
-- Name: transaksi_parkir; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transaksi_parkir (
    id_transaksi integer NOT NULL,
    id_kendaraan integer NOT NULL,
    id_area integer NOT NULL,
    id_petugas integer NOT NULL,
    id_tarif integer NOT NULL,
    waktu_masuk timestamp without time zone NOT NULL,
    waktu_keluar timestamp without time zone,
    durasi_jam integer DEFAULT 0,
    total_biaya numeric(10,2) DEFAULT 0,
    status character varying(20) NOT NULL,
    CONSTRAINT transaksi_parkir_status_check CHECK (((status)::text = ANY ((ARRAY['Aktif'::character varying, 'Selesai'::character varying])::text[])))
);


ALTER TABLE public.transaksi_parkir OWNER TO postgres;

--
-- Name: transaksi_parkir_id_transaksi_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transaksi_parkir_id_transaksi_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transaksi_parkir_id_transaksi_seq OWNER TO postgres;

--
-- Name: transaksi_parkir_id_transaksi_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transaksi_parkir_id_transaksi_seq OWNED BY public.transaksi_parkir.id_transaksi;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id_user integer NOT NULL,
    nama_lengkap character varying(100) NOT NULL,
    username character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    password_plain character varying(255) DEFAULT NULL::character varying,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'petugas'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.users IS 'Menyimpan data akun pengguna sistem';


--
-- Name: users_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_user_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_user_seq OWNER TO postgres;

--
-- Name: users_id_user_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_user_seq OWNED BY public.users.id_user;


--
-- Name: area_parkir id_area; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.area_parkir ALTER COLUMN id_area SET DEFAULT nextval('public.area_parkir_id_area_seq'::regclass);


--
-- Name: kendaraan id_kendaraan; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.kendaraan ALTER COLUMN id_kendaraan SET DEFAULT nextval('public.kendaraan_id_kendaraan_seq'::regclass);


--
-- Name: log_aktivitas id_log; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.log_aktivitas ALTER COLUMN id_log SET DEFAULT nextval('public.log_aktivitas_id_log_seq'::regclass);


--
-- Name: petugas id_petugas; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.petugas ALTER COLUMN id_petugas SET DEFAULT nextval('public.petugas_id_petugas_seq'::regclass);


--
-- Name: tarif_parkir id_tarif; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tarif_parkir ALTER COLUMN id_tarif SET DEFAULT nextval('public.tarif_parkir_id_tarif_seq'::regclass);


--
-- Name: transaksi_parkir id_transaksi; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir ALTER COLUMN id_transaksi SET DEFAULT nextval('public.transaksi_parkir_id_transaksi_seq'::regclass);


--
-- Name: users id_user; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id_user SET DEFAULT nextval('public.users_id_user_seq'::regclass);


--
-- Data for Name: area_parkir; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.area_parkir (id_area, nama_area, lokasi, kapasitas_mobil, kapasitas_motor, status) FROM stdin;
2	Parkir GBK Arena	Pintu 1	90	300	Aktif
3	Parkir Hall Basket	Pintu 1	90	30	Aktif
4	Parkir Hall Tenis	Pintu 1	65	300	Aktif
5	Parkir Area Baseball	Pintu 1	30	65	Aktif
6	Parkir Gedung B	Pintu 1	470	210	Aktif
7	Parkir Area Akuatik	Pintu 8	200	200	Aktif
8	Parkir Gedung  A	Pintu 1	400	250	Aktif
9	Parkir Istora Senayan	Pintu 5	175	100	Aktif
10	Parkir Selatan Timur	Pintu 5	175	0	Aktif
11	Parkir Selatan Barat	Pintu 5	140	165	Aktif
12	Parkir Wisma Serbaguna	Pintu 5	150	1000	Aktif
13	Parkir Plaza Timur	Luar Venue GBK	100	1000	Aktif
1	Parkir Area Panahan	Pintu 8	20	0	Aktif
14	Parkir Tenggara Selatan	Pintu 8	80	200	Aktif
\.


--
-- Data for Name: kendaraan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.kendaraan (id_kendaraan, plat_nomor, jenis_kendaraan, merk, warna, pemilik) FROM stdin;
1	B1234ABC	Mobil	Toyota Avanza	Hitam	Ahmad Fauzi
2	B2345BCD	Motor	Honda Beat	Merah	Budi Santoso
3	B3456CDE	Mobil	Honda Brio	Putih	Siti Rahma
4	B4567DEF	Motor	Yamaha NMAX	Biru	Andi Saputra
5	B5678EFG	Mobil	Mitsubishi Xpander	Silver	Rina Wijaya
6	B6789FGH	Motor	Honda Vario	Hitam	Dewi Lestari
7	B7890GHI	Mobil	Toyota Innova	Putih	Rizky Hidayat
8	B8901HIJ	Motor	Yamaha Aerox	Abu-abu	Nanda Pratama
9	B9012IJK	Mobil	Suzuki Ertiga	Merah	Farhan Akbar
10	B0123JKL	Motor	Honda PCX	Putih	Intan Permata
11	B1122KLM	Mobil	Toyota Raize	Hitam	Rafi Maulana
12	B2233LMN	Motor	Yamaha Lexi	Biru	Aisyah Putri
13	B3344MNO	Mobil	Honda WR-V	Silver	Fajar Nugraha
14	B4455NOP	Motor	Suzuki Nex II	Merah	Nabila Sari
15	B5566OPQ	Mobil	Toyota Fortuner	Putih	Dimas Prakoso
16	B6677PQR	Motor	Honda Scoopy	Krem	Nadia Safitri
17	B7788QRS	Mobil	Hyundai Stargazer	Hitam	Yoga Pratama
18	B8899RST	Motor	Yamaha Fazzio	Hijau	Rina Kartika
19	B9900STU	Mobil	Wuling Alvez	Abu-abu	Rizal Firmansyah
20	B1011TUV	Motor	Honda Genio	Merah	Maya Salsabila
21	B1213UVW	Mobil	Toyota Yaris Cross	Putih	Arif Setiawan
22	B1415VWX	Motor	Yamaha Mio M3	Hitam	Lutfi Ramadhan
23	B1617WXY	Mobil	Honda Civic	Biru	Kevin Prasetyo
24	B1819XYZ	Motor	Honda ADV160	Putih	Dewi Anggraini
25	B2021YZA	Mobil	Mazda CX-3	Merah	Bagas Saputra
26	B2223ZAB	Motor	Yamaha XMAX	Hitam	Galih Prakoso
27	B2425ABC	Mobil	Toyota Camry	Silver	Rendy Kurniawan
28	B2627BCD	Motor	Honda CB150X	Hitam	Salsa Putri
29	B2829CDE	Mobil	Nissan Livina	Putih	Naufal Hakim
30	B3031DEF	Motor	Kawasaki KLX150	Hijau	Ilham Maulana
34	A1234JKW	Mobil	BYD Seal	Putih	Joko
35	B 3942 KDL	Mobil	Toyota Avanza	Hitam	Ahmad Faisal
36	B 1083 SFG	Motor	Honda Beat	Merah	Rian Hidayat
37	B6109VBN	Mobil	Honda Civic	Putih	Citra Lestari
\.


--
-- Data for Name: log_aktivitas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.log_aktivitas (id_log, id_user, aktivitas, waktu) FROM stdin;
1	1	Administrator berhasil login	2026-06-29 19:39:39.499519
2	2	Menambahkan transaksi parkir kendaraan B1234ABC	2026-06-29 19:39:39.499519
3	3	Menambahkan transaksi parkir kendaraan B2345BCD	2026-06-29 19:39:39.499519
4	4	Mengubah status area parkir	2026-06-29 19:39:39.499519
5	5	Memperbarui data kendaraan	2026-06-29 19:39:39.499519
6	6	Menyelesaikan transaksi parkir	2026-06-29 19:39:39.499519
7	7	Menambahkan transaksi parkir baru	2026-06-29 19:39:39.499519
8	8	Menghapus data transaksi parkir	2026-06-29 19:39:39.499519
9	9	Melihat laporan transaksi	2026-06-29 19:39:39.499519
10	1	Administrator logout	2026-06-29 19:39:39.499519
11	1	Menambahkan kendaraan baru B 1235 ABC	2026-06-30 22:35:19.190356
12	1	Memperbarui detail kendaraan B 1235 ABC	2026-06-30 22:35:50.878105
13	1	Memperbarui detail kendaraan B1235ABC	2026-06-30 22:36:11.544546
14	1	Check-in kendaraan B1234ABC masuk parkir.	2026-06-30 22:41:02.872962
15	1	Check-out kendaraan B1234ABC (Biaya: Rp 7.000)	2026-06-30 22:44:24.994708
16	2	Menambahkan kendaraan baru A1234JKW	2026-06-30 23:35:29.949973
17	2	Check-in kendaraan A1234JKW masuk parkir.	2026-06-30 23:36:58.817351
18	2	Menambahkan kendaraan baru B 3942 KDL	2026-07-01 00:21:20.745206
19	2	Menambahkan kendaraan baru B 1083 SFG	2026-07-01 00:21:42.369544
20	2	Check-in kendaraan B 1083 SFG masuk parkir.	2026-07-01 00:22:19.465015
21	2	Check-in kendaraan B 3942 KDL masuk parkir.	2026-07-01 00:22:47.014816
22	2	Menambahkan pengguna baru: Farhan Nugraha (@farhan)	2026-07-01 00:28:55.407123
23	2	Memperbarui data pengguna Farhan Nugraha	2026-07-01 00:30:30.335243
24	1	Menambahkan petugas baru: Joko Susilo (@jokosu)	2026-07-01 00:33:29.239364
25	1	Menghapus pengguna Farhan Nugraha	2026-07-01 00:35:15.975657
26	1	Menghapus kendaraan B1235ABC	2026-07-01 00:37:41.448585
27	1	Menambahkan kendaraan baru B6109VBN	2026-07-01 11:47:07.912708
28	1	Check-in kendaraan B6109VBN masuk parkir.	2026-07-01 11:49:10.15357
29	1	Check-out kendaraan B 3942 KDL (Biaya: Rp 17.000)	2026-07-01 11:51:08.345317
30	1	Menambahkan petugas baru: Olivia Putri (@olivia)	2026-07-01 12:28:01.085686
31	1	Menambahkan petugas baru: Roni Perkasa (@roni)	2026-07-01 12:28:42.606735
32	1	Menambahkan area parkir baru: Parkir Tenggara Selatan	2026-07-01 13:13:54.105021
33	1	Memperbarui data pengguna Administrator	2026-07-01 13:33:49.441137
34	1	Memperbarui data pengguna Budi Santoso	2026-07-01 13:34:03.775437
35	1	Memperbarui data pengguna Budi Santoso	2026-07-01 13:34:38.864451
36	1	Memperbarui data pengguna Roni Perkasa	2026-07-01 13:37:38.467078
37	1	Memperbarui data pengguna Olivia Putri	2026-07-01 13:52:16.713748
38	1	Memperbarui data pengguna Joko Susilo	2026-07-01 13:52:54.353962
39	1	Memperbarui data pengguna Citra Lestari	2026-07-01 19:21:48.237504
40	1	Memperbarui data pengguna Dimas Prakoso	2026-07-01 19:22:06.488872
41	2	Memperbarui data petugas Budi Santoso	2026-07-01 19:24:06.030997
42	1	Menambahkan area parkir baru: Parkir Aneka Lapangan	2026-07-02 13:50:57.703413
43	1	Memperbarui area parkir: Parkir Aneka Lapangan (ID: 15)	2026-07-02 13:54:27.661892
44	1	Menghapus area parkir: Parkir Aneka Lapangan (ID: 15)	2026-07-02 13:56:09.508772
45	1	Check-out kendaraan B6109VBN (Biaya: Rp 47.000)	2026-07-02 14:00:09.75771
46	1	Memperbarui data pengguna Oscar Wijaya	2026-07-02 23:13:00.387966
47	1	Memperbarui data pengguna Nabila Sari	2026-07-02 23:13:17.654558
48	1	Memperbarui data pengguna Muhammad Rizky	2026-07-02 23:13:31.586798
49	1	Memperbarui data pengguna Laila Ramadhani	2026-07-02 23:13:48.975537
50	1	Memperbarui data pengguna Kevin Prasetyo	2026-07-02 23:14:02.683859
51	1	Memperbarui data pengguna Joko Firmansyah	2026-07-02 23:14:16.442654
52	1	Memperbarui data pengguna Indra Kurniawan	2026-07-02 23:14:33.620119
53	1	Memperbarui data pengguna Hani Wulandari	2026-07-02 23:14:50.170324
54	1	Memperbarui data pengguna Galih Pratama	2026-07-02 23:14:58.198513
55	1	Memperbarui data pengguna Andi Saputra	2026-07-02 23:15:06.889774
56	1	Memperbarui data pengguna Fajar Nugraha	2026-07-02 23:15:20.062556
57	1	Memperbarui data pengguna Eka Putri	2026-07-02 23:15:31.855741
58	1	Memperbarui data pengguna Dimas Prakoso	2026-07-02 23:15:44.168615
59	1	Memperbarui data petugas Roni Perkasa	2026-07-02 23:18:07.575134
60	1	Memperbarui data petugas Roni Perkasa	2026-07-02 23:18:28.617861
61	1	Memperbarui data petugas Roni Perkasa	2026-07-03 00:28:23.280096
62	1	Memperbarui data petugas Roni Perkasa	2026-07-03 00:28:33.71561
63	1	Memperbarui data petugas Roni Perkasa	2026-07-03 00:31:29.319803
64	1	Menghapus petugas Roni Perkasa	2026-07-03 00:31:32.495039
65	1	Menambahkan petugas baru: Roni Perkasa (@roni)	2026-07-03 00:32:43.627185
66	1	Menghapus petugas Roni Perkasa	2026-07-03 00:38:28.734072
67	1	Menambahkan petugas baru: Roni Perkasa (@roni)	2026-07-03 00:46:30.509196
68	1	Memperbarui data petugas Roni Perkasa	2026-07-03 00:47:02.541381
69	1	Memperbarui data petugas Roni Perkasa	2026-07-03 00:47:17.282532
70	1	Memperbarui data pengguna Roni Perkasa	2026-07-03 00:51:50.6402
71	1	Menambahkan petugas baru: Alisa Efrosina Dalia (@acheriyy)	2026-07-03 01:02:34.066226
72	1	Memperbarui data pengguna Alisa Efrosina Dalia	2026-07-03 01:02:41.20386
73	1	Memperbarui data pengguna Alisa Efrosina Dalia	2026-07-03 01:12:48.38904
74	1	Memperbarui data pengguna Alisa Efrosina Dalia	2026-07-03 01:12:54.034802
75	1	Memperbarui data petugas Roni Perkasa	2026-07-03 16:16:19.93811
76	1	Menghapus petugas Roni Perkasa	2026-07-03 16:16:27.978369
77	1	Check-out kendaraan B 1083 SFG (Biaya: Rp 5.000)	2026-07-03 16:17:17.18336
\.


--
-- Data for Name: petugas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.petugas (id_petugas, id_user, no_hp, shift) FROM stdin;
2	3	081234567802	Pagi
3	4	081234567803	Pagi
4	5	081234567804	Pagi
5	6	081234567805	Pagi
11	12	081234567811	Malam
12	13	081234567812	Malam
13	14	081234567813	Malam
14	15	081234567814	Malam
15	16	081234567815	Malam
16	18	081277439201	Pagi
26	28	082188776655	Malam
1	2	081234567801	Pagi
6	7	081234567806	Siang
7	8	081234567807	Siang
8	9	081234567808	Siang
9	10	081234567809	Siang
10	11	081234567810	Siang
\.


--
-- Data for Name: tarif_parkir; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tarif_parkir (id_tarif, jenis_kendaraan, tarif_awal, durasi_awal, tarif_tambahan, jenis_tarif) FROM stdin;
1	Mobil	7000.00	2	2000.00	Bertingkat
2	Motor	5000.00	0	0.00	Flat
\.


--
-- Data for Name: transaksi_parkir; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transaksi_parkir (id_transaksi, id_kendaraan, id_area, id_petugas, id_tarif, waktu_masuk, waktu_keluar, durasi_jam, total_biaya, status) FROM stdin;
1	1	2	1	1	2026-06-29 08:00:00	2026-06-29 10:00:00	2	7000.00	Selesai
2	2	3	2	2	2026-06-29 08:15:00	2026-06-29 09:45:00	2	5000.00	Selesai
3	3	6	3	1	2026-06-29 09:00:00	2026-06-29 12:00:00	3	9000.00	Selesai
4	4	4	4	2	2026-06-29 09:30:00	\N	\N	\N	Aktif
5	5	8	5	1	2026-06-29 10:00:00	\N	\N	\N	Aktif
6	6	5	6	2	2026-06-29 13:00:00	2026-06-29 15:00:00	2	5000.00	Selesai
7	7	9	7	1	2026-06-29 14:00:00	2026-06-29 17:00:00	3	9000.00	Selesai
8	8	11	8	2	2026-06-29 15:00:00	\N	\N	\N	Aktif
9	9	12	9	1	2026-06-29 16:00:00	2026-06-29 18:00:00	2	7000.00	Selesai
10	10	13	10	2	2026-06-29 17:00:00	\N	\N	\N	Aktif
11	1	10	1	1	2026-06-30 22:41:02.872962	2026-06-30 17:44:24	1	7000.00	Selesai
12	34	2	1	1	2026-06-30 23:36:58.817351	\N	0	0.00	Aktif
14	35	1	1	1	2026-07-01 00:22:47.014816	2026-07-01 06:51:08	7	17000.00	Selesai
15	37	3	1	1	2026-07-01 11:49:10.15357	2026-07-02 09:00:09	22	47000.00	Selesai
13	36	7	1	2	2026-07-01 00:22:19.465015	2026-07-03 11:17:17	59	5000.00	Selesai
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id_user, nama_lengkap, username, password, role, created_at, password_plain) FROM stdin;
4	Dimas Prakoso	dimas	$2y$10$aksovHeLpK9dzIqE/dUSLey6gvpN5/9udgS0AzPVdofQYXFHnwFTi	petugas	2026-06-29 18:44:43.161536	Dimas@2026
1	Administrator	admin	$2y$10$Yl1u51yAjBa6JTAlaPfl1OWRR5TfqNtyqua74AHvl88C6rXD4AU2G	admin	2026-06-29 18:44:42.987689	Adm!nGBK2026
28	Olivia Putri	olivia	$2y$10$Tp35/d0Y9SxgwDtX5S43VOcC8QRmr1MhEJInyBqzFccBghMW4nz/2	petugas	2026-07-01 12:28:01.085686	olivia$123
18	Joko Susilo	jokosu	$2y$10$JdABPVtOSXizy6ZmFnYDz.OFurPrvLJakXsBv4kk3dR2Plw9NVtcK	petugas	2026-07-01 00:33:29.239364	kojowi10
3	Citra Lestari	citra	$2y$10$4hSMi8dJZFx3HMnj.05Sw.pVF9WlpqDbzQf3gKkdkI8eTk1Yf29EO	petugas	2026-06-29 18:44:43.104905	Citra#2026
2	Budi Santoso	budi	$2y$10$tpqcF0Cvqr313zSoy3hbleqwAOYdiujW/1rOetnxyJHiEwVvxWOiC	petugas	2026-06-29 18:44:43.047911	Budi@2026
16	Oscar Wijaya	oscar	$2y$10$/bVmnE.z3ZdX/t.AMI43vehF.JBr/PlA7R2jT1gaUjcAcDb3XPD6q	petugas	2026-06-29 18:44:43.793084	Oscar#2026
15	Nabila Sari	nabila	$2y$10$jdvdS5YjYjK.c4KnIGuzcOKlHbIbau5vD44QRBg3zHIcB3VMn3nHW	petugas	2026-06-29 18:44:43.742362	Nabila@2026
14	Muhammad Rizky	rizky	$2y$10$xUx5Xeiu/yUlZobHBGyezOIoI62ffEK/u/p2nmtkVFGjlqVjqLiDa	petugas	2026-06-29 18:44:43.689691	Rizky!2026
13	Laila Ramadhani	laila	$2y$10$dV8z9AVqUGIPAra6ytME/.WUFyimAjFUbmLNwEvvGGzy8wMU5ogXy	petugas	2026-06-29 18:44:43.638658	Laila#2026
12	Kevin Prasetyo	kevin	$2y$10$FVgD1XMlUyojMPMzXGlcjOcX.G2NoHK1dotfpytS20rjjJRCmH/l6	petugas	2026-06-29 18:44:43.588213	Kevin@2026
11	Joko Firmansyah	joko	$2y$10$h8lgBqJY1FtSBEd7YEu3Z.JfYngweHWgd6GuvuJeAmllK3uWIHbRK	petugas	2026-06-29 18:44:43.537313	Joko#2026
10	Indra Kurniawan	indra	$2y$10$ldD8HiiCXWcPhU/3pkpeLuWQaImM0R0/f5yduJ57gQ1K0ODmrajM.	petugas	2026-06-29 18:44:43.485922	Indra@2026
9	Hani Wulandari	hani	$2y$10$fBNiHmzyG3X0wutsUea32u95I9G2aScTWZ9FL0fU0ltq9XBel4xnO	petugas	2026-06-29 18:44:43.43398	Hani!2026
8	Galih Pratama	galih	$2y$10$MQS354ZWt83Z4aYU4yD8Yuqhr/GI3yLlvR.5NoVb1PnFJe/2WnmTO	petugas	2026-06-29 18:44:43.380402	Galih#2026
7	Andi Saputra	andi	$2y$10$vVk058gYtIetH0VWivOgOee5xy6f.1MFA3MHhniWqeDODmY/8TQUm	petugas	2026-06-29 18:44:43.326885	Andi@2026
6	Fajar Nugraha	fajar	$2y$10$BTeB/ZQEdRm9c/b2thd91u18CYW0Iy4uYol9.nPzgngPdUSkZxkFy	petugas	2026-06-29 18:44:43.272685	Fajar#2026
5	Eka Putri	eka	$2y$10$TrdJLJiYYUiykCvnqVZXqOFNFtDWcU3WFG7i3kdG6wNhIdOmic/qq	petugas	2026-06-29 18:44:43.217898	Eka!2026
39	Alisa Efrosina Dalia	acheriyy	$2y$10$23qIlom3.7KEb2aB2tiNFOcgi5iQvl9/lvAmIb2cdZtiwzeT2v2Le	admin	2026-07-03 01:02:34.066226	Boboiboy
\.


--
-- Name: area_parkir_id_area_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.area_parkir_id_area_seq', 15, true);


--
-- Name: kendaraan_id_kendaraan_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.kendaraan_id_kendaraan_seq', 37, true);


--
-- Name: log_aktivitas_id_log_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.log_aktivitas_id_log_seq', 77, true);


--
-- Name: petugas_id_petugas_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.petugas_id_petugas_seq', 37, true);


--
-- Name: tarif_parkir_id_tarif_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tarif_parkir_id_tarif_seq', 5, true);


--
-- Name: transaksi_parkir_id_transaksi_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transaksi_parkir_id_transaksi_seq', 15, true);


--
-- Name: users_id_user_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_user_seq', 39, true);


--
-- Name: area_parkir area_parkir_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.area_parkir
    ADD CONSTRAINT area_parkir_pkey PRIMARY KEY (id_area);


--
-- Name: kendaraan kendaraan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.kendaraan
    ADD CONSTRAINT kendaraan_pkey PRIMARY KEY (id_kendaraan);


--
-- Name: kendaraan kendaraan_plat_nomor_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.kendaraan
    ADD CONSTRAINT kendaraan_plat_nomor_key UNIQUE (plat_nomor);


--
-- Name: log_aktivitas log_aktivitas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.log_aktivitas
    ADD CONSTRAINT log_aktivitas_pkey PRIMARY KEY (id_log);


--
-- Name: petugas petugas_id_user_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.petugas
    ADD CONSTRAINT petugas_id_user_key UNIQUE (id_user);


--
-- Name: petugas petugas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.petugas
    ADD CONSTRAINT petugas_pkey PRIMARY KEY (id_petugas);


--
-- Name: tarif_parkir tarif_parkir_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tarif_parkir
    ADD CONSTRAINT tarif_parkir_pkey PRIMARY KEY (id_tarif);


--
-- Name: transaksi_parkir transaksi_parkir_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir
    ADD CONSTRAINT transaksi_parkir_pkey PRIMARY KEY (id_transaksi);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id_user);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: log_aktivitas fk_log_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.log_aktivitas
    ADD CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: petugas fk_petugas_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.petugas
    ADD CONSTRAINT fk_petugas_user FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: transaksi_parkir fk_transaksi_area; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir
    ADD CONSTRAINT fk_transaksi_area FOREIGN KEY (id_area) REFERENCES public.area_parkir(id_area) ON DELETE CASCADE;


--
-- Name: transaksi_parkir fk_transaksi_kendaraan; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir
    ADD CONSTRAINT fk_transaksi_kendaraan FOREIGN KEY (id_kendaraan) REFERENCES public.kendaraan(id_kendaraan) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: transaksi_parkir fk_transaksi_petugas; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir
    ADD CONSTRAINT fk_transaksi_petugas FOREIGN KEY (id_petugas) REFERENCES public.petugas(id_petugas) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: transaksi_parkir fk_transaksi_tarif; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaksi_parkir
    ADD CONSTRAINT fk_transaksi_tarif FOREIGN KEY (id_tarif) REFERENCES public.tarif_parkir(id_tarif) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict sl4zLpVlBpXI4bYhZHADR0cpwEGcXqXpikRU3NFUONw6DYu5PzWmkAK6JHDaQTy

