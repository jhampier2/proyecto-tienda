-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-05-2026 a las 19:31:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mibd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `descripcion`) VALUES
(4, 'lavadora'),
(5, 'laptop'),
(6, 'mouse'),
(7, 'parlante'),
(8, 'television'),
(9, 'camara'),
(10, 'horno');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombres` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `direccion` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombres`, `apellidos`, `direccion`, `telefono`) VALUES
(1, 'Maryori Cris', 'Taipe Tolentino', 'Av. Salsipuedes N°345', '929137780'),
(2, 'Luis Alberto', 'Utos Ceras', 'Av. Salsipuedes N°111', '912345678'),
(3, 'Magdalena Maria', 'Quiñonez Jimenes', 'Av. Marginal N°222', '987654321'),
(4, 'Ana Melisa', 'Arias Malpartida', 'Av. Francisco Bolognesi N°333', '978451261'),
(5, 'Miguel Jose', 'Torres Caysahuana', 'Av. La huerta N°444', '932165498'),
(6, 'Monica Sheyla', 'Meza Taipe', 'Av. La huerta N°444', '945216378'),
(7, 'Cristobal Colon', 'Chiricente Coco', 'Av. San Miguel N°555', '998545412'),
(8, 'Miguel Jose', 'Torres Caysahuana', 'Av. Colonos Fundadores N°666', '988785542'),
(9, 'Marcela Joaquín', 'Tito Tomas', 'Calles Madrigales N°777', '922148579'),
(10, 'Carlos Alvaro', 'Torres Caysahuana', 'Nueva Esperanza N°888', '911223345'),
(11, 'jhampier jhordch', 'ortiz vento', 'junin pasco huanca', '968351575');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detventa` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detventa`, `cantidad`, `id_producto`, `id_venta`, `subtotal`) VALUES
(17, 1, 25, 38, 1.00),
(18, 1, 27, 39, 1.00),
(19, 1, 28, 39, 1.00),
(20, 1, 25, 39, 1.00),
(21, 1, 30, 40, 1.00),
(22, 1, 37, 41, 1.00),
(23, 1, 34, 42, 1.00),
(24, 4, 25, 43, 4.00),
(25, 1, 27, 44, 1.00),
(26, 1, 26, 45, 1.00),
(27, 4, 28, 46, 4.00),
(28, 1, 25, 47, 1.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(18,0) NOT NULL,
  `stock` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT 'uploads/productos/default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre`, `descripcion`, `precio`, `stock`, `id_categoria`, `id_proveedor`, `imagen_url`) VALUES
(25, 'Auriculares Inalámbricos Pro', 'Auriculares Bluetooth 5.3 con cancelación de ruido y caja de carga.', 1, 93, NULL, NULL, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=300'),
(26, 'Reloj Inteligente Fit', 'Smartwatch con monitor de ritmo cardíaco, pasos y notificaciones.', 1, 99, NULL, NULL, 'https://images.unsplash.com/photo-1579586337278-3befd40fd17a?w=300'),
(27, 'Mini Proyector HD', 'Proyector portátil 1080p ideal para cine en casa.', 1, 98, NULL, NULL, 'https://images.unsplash.com/photo-1605773527852-c546a8584ea3?w=300'),
(28, 'Cámara de Seguridad WiFi', 'Cámara IP 360 grados con visión nocturna y audio bidireccional.', 1, 95, NULL, NULL, 'https://www.quill.com/is/image/Quill/sp192943473_s7?wid=1536&hei=1536'),
(29, 'Humidificador con Luces', 'Humidificador ultrasónico difusor de aromas con luz LED.', 1, 100, NULL, NULL, 'https://m.media-amazon.com/images/I/71sGyr-Y5PL._AC_UF894,1000_QL80_.jpg'),
(30, 'Mochila Antirrobo', 'Mochila impermeable con puerto USB para carga de celular.', 1, 99, NULL, NULL, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300'),
(31, 'Set de Brochas de Maquillaje', '15 brochas profesionales supersuaves con estuche.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=300'),
(32, 'Lámpara de Escritorio LED', 'Lámpara táctil recargable con 3 niveles de brillo.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1534073828943-f801091bb18c?w=300'),
(33, 'Teclado Mecánico RGB', 'Teclado gamer compacto con switches azules y luces personalizables.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=300'),
(34, 'Mouse Inalámbrico', 'Mouse ergonómico recargable ideal para oficina y diseño.', 1, 99, NULL, NULL, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=300'),
(35, 'Termo Inteligente Digital', 'Botella de acero inoxidable que muestra la temperatura del agua.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=300'),
(36, 'Gafas de Sol Polarizadas', 'Gafas estilo retro con protección UV400.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=300'),
(37, 'Tira de Luces LED 5M', 'Tira LED RGB con control remoto y sincronización musical.', 1, 99, NULL, NULL, 'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?w=300'),
(38, 'Soporte de Celular Auto', 'Soporte magnético universal para rejilla de ventilación.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1622547748225-3fc4abd2cca0?w=300'),
(39, 'Aro de Luz LED para Selfies', 'Aro de luz con trípode y 3 modos de iluminación para celular.', 1, 100, NULL, NULL, 'https://www.quill.com/is/image/Quill/sp131395056_s7?wid=1536&hei=1536'),
(40, 'Mini Licuadora Portátil', 'Licuadora recargable por USB para batidos y jugos en cualquier lugar.', 1, 100, NULL, NULL, 'https://www.printglobe.com/images/products/ID106145-power_pro_portable_blenders-primary.jpg'),
(41, 'Pistola de Masaje Muscular', 'Masajeador de percusión silencioso con 4 cabezales intercambiables.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=300'),
(42, 'Dispensador de Agua Eléctrico', 'Bomba de agua automática recargable para bidones.', 1, 100, NULL, NULL, 'https://www.zoro.com/static/cms/product/large/TagCo%20USA%20Inc_TIxxFLINEWDxxBLAxxxx1xxxx181fa7.jpeg'),
(43, 'Organizador de Maquillaje Acrílico', 'Caja transparente con cajones para cosméticos y joyería.', 1, 100, 4, 7, 'https://m.media-amazon.com/images/I/81VBGBdMa+L._AC_UF894,1000_QL80_.jpg'),
(44, 'Soporte Ajustable para Laptop', 'Base de aluminio plegable y ventilada para portátiles.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1616423640778-28d1b53229bd?w=300'),
(45, 'Cortadora de Cabello Profesional', 'Máquina de cortar pelo inalámbrica con peines guía y pantalla LED.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1593702295094-aea22597af65?w=300'),
(46, 'Alfombrilla de Ratón XXL RGB', 'Pad mouse gamer gigante con bordes iluminados.', 1, 100, NULL, NULL, 'https://www.imprint5.com/media/catalog/product/cache/d2de1f4ec26b6c3985030cf658854bda/f/u/full_color_custom_rgb_light_mouse_pad-1.jpg'),
(47, 'Proyector de Estrellas Galaxia', 'Lámpara nocturna que proyecta nebulosas con altavoz Bluetooth.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=300'),
(48, 'Báscula Digital de Cocina', 'Pesa alimentos de alta precisión en acero inoxidable (hasta 5kg).', 1, 100, NULL, NULL, 'https://assets.katomcdn.com/q_auto,f_auto,w_500,dpr_2/v1528920115/products/383/383-1020NFS/383-1020nfs.jpg'),
(49, 'Cepillo Secador de Cabello', 'Cepillo de aire caliente 3 en 1 para dar volumen y alisar.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1522337660859-02fbefca4702?w=300'),
(50, 'Set de Bandas de Resistencia', '5 bandas elásticas de diferentes niveles para entrenamiento en casa.', 1, 100, NULL, NULL, 'https://us.gymproluxestore.com/cdn/shop/files/trap_4_1000x.png?v=1758317014https://images.unsplash.com/photo-1598266663412-706596e4811a?w=300'),
(51, 'Repetidor WiFi de Señal', 'Amplificador de red inalámbrica de largo alcance.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=300'),
(52, 'Humidificador para Auto', 'Mini difusor de aceites esenciales portátil para el portavasos.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1617897903246-719242758050?w=300'),
(53, 'Reloj Despertador Espejo', 'Reloj digital LED con alarma y pantalla reflectante.', 1, 100, NULL, NULL, 'https://encrypted-tbn1.gstatic.com/shopping?q=tbn:ANd9GcQrLDmHU67HIEvZjdTo6K9jxY-OXQZTkVG-aBr7bOnxLY3-Qjwqg5UY9NwdCNyfFYlZXULsjvk4V37LeF_LLY1GjzrQWvt7H3DAbcrbY_moESljeM6LYjX38pDfgB1KnNJdTWfOdig&usqp=CAc'),
(54, 'Set de Cuchillos Chef', 'Juego de 6 cuchillos de acero al carbono con recubrimiento antiadherente.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1593504049359-74330189a345?w=300'),
(55, 'Foco Inteligente Multicolor', 'Bombilla WiFi compatible con asistentes de voz y millones de colores.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1550989460-0adf9ea622e2?w=300'),
(56, 'Esterilla de Yoga Premium', 'Mat antideslizante con líneas de alineación para ejercicios.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=300'),
(57, 'Luz Frontal Recargable', 'Linterna de cabeza ultrabrillante ideal para camping y reparaciones.', 1, 100, NULL, NULL, 'https://encrypted-tbn3.gstatic.com/shopping?q=tbn:ANd9GcRqsz_uzXfILpBkL2qH3TID34TzIwe3jaag23bVWurnpfl4licOD71s90Gou7F6IAEqCihN9ouu0JmjpKTMQs5KZ-QdDbcljb9j1ypHgDuf84f0b6OlOnr3F0hmhMZ_ItttmwQwf788-Xk&usqp=CAc'),
(58, 'Cargador Inalámbrico Rápido', 'Base de carga magnética para smartphones y audífonos.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1615526675159-e248c3021d3f?w=300'),
(59, 'Billetera Hombre Cuero PU', 'Billetera elegante con protección RFID.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=300'),
(60, 'Tarjetero Minimalista', 'Tarjetero de aluminio con sistema pop-up.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=300'),
(61, 'Billetera Cartera De Piel', 'Cartera larga negra lisa para mujer.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=300'),
(62, 'Cartera Con Tarjetero Metálico', 'Diseño profesional y delgado para oficina.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300'),
(63, 'Billetera Tarjetero Rfid', 'Bloqueo de clonación, cuero genuino.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1628151015968-3a4429e9ef04?w=300'),
(64, 'Tarjetero Anti-Clonación', 'Bloquea señales RFID, diseño compacto.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1584382296087-3474d2217e57?w=300'),
(65, 'Billetera de Cuero Vintage', 'Estilo retro, costuras reforzadas.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=300'),
(66, 'Monedero Pequeño Mujer', 'Cierre de cremallera, múltiples compartimentos.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=300'),
(67, 'Billetera Deportiva', 'Material resistente al agua, cierre velcro.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1606503153255-59d8b8b82176?w=300'),
(68, 'Clip para Billetes Acero', 'Clip minimalista de acero inoxidable.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300'),
(69, 'Soporte Celular Flexible', 'Soporte tipo cuello de cisne para escritorio.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1586953208448-b95a79201688?w=300'),
(70, 'Soporte Teléfono Auto', 'Ajuste para rejilla de ventilación, rotación 360.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1622547748225-3fc4abd2cca0?w=300'),
(71, 'Soporte De Ventosa Auto', 'Chupón de alta adherencia para parabrisas.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=300'),
(72, 'Soporte Magnético', 'Imán potente de neodimio para tablero.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1615526675159-e248c3021d3f?w=300'),
(73, 'Soporte Para Celular Extensible', 'Brazo telescópico para mayor alcance.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1586953208448-b95a79201688?w=300'),
(74, 'Soporte Posavasos Auto', 'Base ajustable para el portavasos del carro.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1622547748225-3fc4abd2cca0?w=300'),
(75, 'Trípode Mini Flexible', 'Trípode tipo pulpo para cualquier superficie.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=300'),
(76, 'Soporte Anillo Metálico', 'Anillo adhesivo para sostener el celular.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=300'),
(77, 'Soporte Moto Impermeable', 'Funda con soporte para manillar de moto.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1615526675159-e248c3021d3f?w=300'),
(78, 'Amplificador de Pantalla', 'Lupa 3D para ver videos en el celular.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1586953208448-b95a79201688?w=300'),
(79, 'Anillo De Compromiso Oro Blanco', 'Circón brillante estilo diamante.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1605100804763-247f67963c9e?w=300'),
(80, 'Anillo Bañado Oro Rosa', 'Diseño elegante con pedrería incrustada.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1599643478514-4a4208bd45f7?w=300'),
(81, 'Anillo Promesa Plata 925', 'Plata esterlina con diseño minimalista.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=300'),
(82, 'Anillo Circonita Cúbica', 'Corte princesa, brillo espectacular.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1596944924616-7b38e7cfac36?w=300'),
(83, 'Anillo De Plata Romántico', 'Diseño entrelazado símbolo de infinito.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1605100804763-247f67963c9e?w=300'),
(84, 'Set de Anillos Bohemios', 'Juego de 5 anillos dorados vintage.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1599643478514-4a4208bd45f7?w=300'),
(85, 'Anillo Sello Hombre', 'Acero inoxidable negro mate.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=300'),
(86, 'Anillo Ajustable Gato', 'Plata 925, diseño de huella de mascota.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1596944924616-7b38e7cfac36?w=300'),
(87, 'Anillo Corazón Zafiro', 'Imitación zafiro azul rodeado de circones.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1605100804763-247f67963c9e?w=300'),
(88, 'Alianzas de Boda Par', 'Acero quirúrgico liso clásico.', 1, 100, NULL, NULL, 'https://images.unsplash.com/photo-1599643478514-4a4208bd45f7?w=300');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL,
  `razonsocial` varchar(50) NOT NULL,
  `direccion` varchar(50) NOT NULL,
  `telefono` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`id_proveedor`, `razonsocial`, `direccion`, `telefono`) VALUES
(1, 'Grupo S.A 1', 'Jr. Colonos Fundadores', '911223344'),
(2, 'Grupo S.A 2', 'Av. Micaela', '923845210'),
(3, 'Grupo S.A 3', 'Calle Las Marvinas', '900145007'),
(4, 'Grupo S.A 4', 'Agusto B.Legia', '952400152'),
(5, 'Grupo S.A 5', 'Campos Las Flores', '988874574'),
(6, 'Grupo S.A 6', 'Calle Las Brisas del Sur', '966321008'),
(7, 'Grupo S.A 7', 'Las Praderas del Norte', '905442181'),
(8, 'Grupo S.A 8', 'Avenida Los Marginales', '971002450'),
(9, 'Grupo S.A 9', 'Cuadra Las nubes', '985456213'),
(10, 'Grupo S.A 10', 'Julio C.Tello', '912221445');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `id_cliente` int(11) DEFAULT NULL,
  `total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` varchar(50) DEFAULT 'Yape',
  `estado` varchar(20) DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `fecha`, `id_cliente`, `total`, `metodo_pago`, `estado`) VALUES
(1, '2025-08-28 16:30:24', 3, 0.00, 'Yape', 'Pagado'),
(2, '2025-08-28 16:34:08', 1, 0.00, 'Yape', 'Pendiente'),
(3, '2025-08-28 16:35:29', 2, 0.00, 'Yape', 'Pendiente'),
(4, '2025-08-28 16:36:19', 4, 26.00, 'Yape', 'Pendiente'),
(5, '2025-08-28 16:37:02', 5, 25.00, 'Yape', 'Pendiente'),
(6, '2025-08-28 16:37:44', 6, 24.00, 'Yape', 'Pendiente'),
(7, '2025-08-28 16:38:31', 7, 23.00, 'Yape', 'Pendiente'),
(8, '2025-08-28 16:39:00', 8, 22.00, 'Yape', 'Pendiente'),
(9, '2025-08-28 16:39:32', 9, 21.00, 'Yape', 'Pendiente'),
(10, '2025-08-28 16:40:13', 10, 20.00, 'Yape', 'Pendiente'),
(11, '2025-08-29 09:37:02', 2, 0.00, 'Yape', 'Pendiente'),
(32, '2026-05-06 09:18:03', 1, 1.00, 'Yape', 'Pendiente'),
(33, '2026-05-06 09:28:58', 1, 1.00, 'Yape', 'Pagado'),
(34, '2026-05-06 09:33:54', 1, 1.00, 'Yape', 'Pagado'),
(35, '2026-05-06 09:40:36', 1, 1.00, 'Yape', 'Pagado'),
(36, '2026-05-06 09:48:20', 1, 1.00, 'Yape', 'Pagado'),
(37, '2026-05-06 09:52:16', 1, 1.00, 'Yape', 'Pagado'),
(38, '2026-05-06 10:04:06', 1, 1.00, 'Yape', 'Pagado'),
(39, '2026-05-06 10:05:41', 1, 3.00, 'Yape', 'Pagado'),
(40, '2026-05-06 10:11:54', 1, 1.00, 'Yape', 'Pendiente'),
(41, '2026-05-06 10:14:13', 1, 1.00, 'Yape', 'Pendiente'),
(42, '2026-05-06 10:17:39', 1, 1.00, 'Yape', 'Pagado'),
(43, '2026-05-06 10:39:27', 1, 4.00, 'Yape', 'Pagado'),
(44, '2026-05-06 10:45:26', 1, 1.00, 'Yape', 'Pagado'),
(45, '2026-05-06 10:53:06', 1, 1.00, 'Yape', 'Pendiente'),
(46, '2026-05-06 11:07:59', 1, 4.00, 'Yape', 'Pendiente'),
(47, '2026-05-06 11:37:41', 1, 1.00, 'Yape', 'Pagado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detventa`),
  ADD KEY `fk_producto` (`id_producto`),
  ADD KEY `fk_venta` (`id_venta`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_categoria` (`id_categoria`),
  ADD KEY `fk_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `fk_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detventa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
