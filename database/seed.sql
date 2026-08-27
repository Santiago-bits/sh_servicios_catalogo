-- =====================================================================
--  SH SERVICIOS · DATOS DE PRUEBA (demo)
--  Ejecutar DESPUÉS de database.sql
--  ¡ATENCIÓN! Son datos ficticios de demostración. Borralos antes
--  de poner el sistema en producción:  CALL sp_limpiar_demo();  o
--  simplemente eliminá los productos desde el panel.
-- =====================================================================

USE `sh_servicios`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Usuarios de prueba ---------------------------------------------------
-- operario@shservicios.com.ar / Operario2026!
-- vendedor@shservicios.com.ar / Vendedor2026!
INSERT INTO `users` (`id`,`role_id`,`name`,`email`,`password`,`position`,`phone`,`active`) VALUES
 (2,2,'Juan Pérez','operario@shservicios.com.ar','$2y$12$acdJ94JVDUpj9H7ikplIr.Iaty/9nL2HvBDVWHv2fabRes2i0aDzG','Jefe de taller','11-4000-0002',1),
 (3,3,'Carla Gómez','vendedor@shservicios.com.ar','$2y$12$JFDBw.Yn75uMGRZyvHm2IOCYTUxg54OLtsuLbu17uBW7eFfCmut3O','Ventas','11-4000-0003',1);

-- Marcas ---------------------------------------------------------------
INSERT INTO `brands` (`id`,`name`,`slug`,`description`,`website`,`country`,`featured`,`sort_order`,`active`) VALUES
 (1,'Toyota','toyota','Líder mundial en equipos de movimiento de materiales.','https://toyota-forklifts.com','Japón',1,1,1),
 (2,'Hyster','hyster','Equipos robustos para aplicaciones exigentes.','https://hyster.com','Estados Unidos',1,2,1),
 (3,'Yale','yale','Autoelevadores y equipos de almacenamiento.','https://yale.com','Estados Unidos',1,3,1),
 (4,'Caterpillar','caterpillar','Maquinaria pesada e industrial.','https://cat.com','Estados Unidos',1,4,1),
 (5,'Komatsu','komatsu','Equipos industriales y de construcción.','https://komatsu.com','Japón',1,5,1),
 (6,'Mitsubishi','mitsubishi','Autoelevadores y equipos de almacén.','https://mitforklift.com','Japón',1,6,1),
 (7,'Linde','linde','Tecnología hidrostática de alto rendimiento.','https://linde-mh.com','Alemania',1,7,1),
 (8,'Jungheinrich','jungheinrich','Especialistas en intralogística.','https://jungheinrich.com','Alemania',1,8,1),
 (9,'Nissan','nissan','Autoelevadores industriales.','https://nissanforklift.com','Japón',0,9,1),
 (10,'Clark','clark','Pioneros del autoelevador.','https://clarkmhc.com','Estados Unidos',0,10,1),
 (11,'Genie','genie','Plataformas de elevación de personas.','https://genielift.com','Estados Unidos',0,11,1),
 (12,'Genérico','generico','Repuestos alternativos de fabricación nacional e importada.',NULL,NULL,0,20,1);

-- =====================================================================
-- MAQUINARIA
-- =====================================================================
INSERT INTO `products`
 (`id`,`type`,`code`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,
  `cost_price`,`profit_percent`,`profit_amount`,`final_price`,`currency`,`price_visible`,
  `availability`,`featured`,`is_new`,`is_offer`,`offer_price`,`stock`,`track_stock`,`views`,`active`,`created_by`) VALUES

 (1,'machine','AE-001','Autoelevador Toyota 8FG25 2.500 kg','autoelevador-toyota-8fg25-2500-kg',1,1,
  'Autoelevador a gas de 2.500 kg de capacidad, mástil triple de 4.700 mm y desplazador lateral.',
  'Autoelevador Toyota serie 8 con motor 4Y a gas, uno de los equipos más confiables del mercado para operación continua en depósitos y plantas. Revisado íntegramente en nuestro taller: motor, transmisión, sistema hidráulico y frenos. Incluye desplazador lateral hidráulico y mástil triple con visión libre. Se entrega con garantía escrita de 6 meses y service inicial sin cargo.',
  16000000.00,25.000,4000000.00,20000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,1245,1,1),

 (2,'machine','AE-002','Autoelevador Toyota 8FD30 3.000 kg diésel','autoelevador-toyota-8fd30-3000-kg-diesel',1,1,
  'Autoelevador diésel de 3.000 kg, mástil triple 4.500 mm, ideal para exterior.',
  'Equipo diésel de 3 toneladas con motor Toyota 1DZ-III, pensado para operación en exteriores, patios de maniobra y superficies irregulares. Cuenta con cabina con techo protector, luces de trabajo LED y neumáticos neumáticos nuevos. Mantenimiento al día con historial documentado.',
  22400000.00,25.000,5600000.00,28000000.00,'ARS',1,'disponible',1,0,1,25900000.00,1,0,876,1,1),

 (3,'machine','AE-003','Autoelevador eléctrico Hyster E30XN 1.400 kg','autoelevador-electrico-hyster-e30xn-1400-kg',2,1,
  'Autoelevador eléctrico contrabalanceado, batería 48 V, ideal para uso interior.',
  'Autoelevador eléctrico de tres ruedas con excelente maniobrabilidad en pasillos estrechos. Batería de tracción de 48 V con 80% de capacidad remanente y cargador incluido. Cero emisiones: apto para industria alimenticia, farmacéutica y depósitos cerrados.',
  19200000.00,25.000,4800000.00,24000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,654,1,1),

 (4,'machine','AE-004','Autoelevador Yale GLP050 2.300 kg','autoelevador-yale-glp050-2300-kg',3,1,
  'Autoelevador a GLP de 2.300 kg con mástil dúplex de 3.300 mm.',
  'Unidad Yale a gas licuado, de bajo consumo y mantenimiento sencillo. Recomendado para operaciones mixtas interior/exterior. Se entrega con dos tubos de GLP y kit de conversión revisado.',
  13600000.00,25.000,3400000.00,17000000.00,'ARS',1,'reservada',0,0,0,NULL,1,0,412,1,1),

 (5,'machine','AP-001','Apilador eléctrico Jungheinrich EJC 112 1.200 kg','apilador-electrico-jungheinrich-ejc-112',8,2,
  'Apilador eléctrico conductor acompañante, 1.200 kg y 3.300 mm de elevación.',
  'Apilador eléctrico compacto ideal para almacenes con espacio reducido. Timón ergonómico con control de velocidad progresivo, batería de 24 V y cargador incorporado. Muy bajo nivel sonoro.',
  8000000.00,30.000,2400000.00,10400000.00,'ARS',1,'disponible',1,1,0,NULL,2,0,533,1,1),

 (6,'machine','AP-002','Apilador manual hidráulico 1.000 kg','apilador-manual-hidraulico-1000-kg',12,2,
  'Apilador manual de 1.000 kg con elevación de 1.600 mm.',
  'Apilador manual con bomba hidráulica de doble pistón. Equipo nuevo, sin uso, con garantía de fábrica de 12 meses. Solución económica para movimientos ocasionales.',
  2400000.00,35.000,840000.00,3240000.00,'ARS',1,'disponible',0,1,0,NULL,4,0,298,1,1),

 (7,'machine','ZE-001','Zorra eléctrica Linde T20 2.000 kg','zorra-electrica-linde-t20-2000-kg',7,3,
  'Transpaleta eléctrica de 2.000 kg con batería de litio.',
  'Transpaleta eléctrica Linde T20 con batería de litio de carga rápida (30 minutos al 80%). Reduce drásticamente el esfuerzo del operador y aumenta la productividad en la carga y descarga de camiones.',
  5600000.00,30.000,1680000.00,7280000.00,'ARS',1,'disponible',1,0,0,NULL,3,0,721,1,1),

 (8,'machine','ZE-002','Zorra hidráulica manual 2.500 kg','zorra-hidraulica-manual-2500-kg',12,3,
  'Transpaleta manual reforzada de 2.500 kg, uñas de 1.150 mm.',
  'Transpaleta manual de uso intensivo con ruedas de nylon y timón de tres posiciones. Equipo nuevo con garantía.',
  600000.00,40.000,240000.00,840000.00,'ARS',1,'disponible',0,1,0,NULL,12,1,489,1,1),

 (9,'machine','PL-001','Plataforma elevadora tijera Genie GS-1932','plataforma-elevadora-tijera-genie-gs-1932',11,4,
  'Plataforma tijera eléctrica, 7,80 m de altura de trabajo, 227 kg.',
  'Plataforma tipo tijera eléctrica para trabajo en interiores. Altura de trabajo de 7,80 m, plataforma extensible y tracción en dos ruedas. Ideal para mantenimiento edilicio, montaje e instalaciones.',
  14400000.00,25.000,3600000.00,18000000.00,'ARS',1,'disponible',1,0,0,NULL,1,0,602,1,1),

 (10,'machine','MT-001','Manipulador telescópico Caterpillar TH255C','manipulador-telescopico-caterpillar-th255c',4,5,
  'Manipulador telescópico 2.500 kg, 5,60 m de alcance en altura.',
  'Manipulador telescópico compacto con tracción 4x4 y dirección en cuatro ruedas. Excelente para obra, agro e industria. Incluye pala y horquillas.',
  36000000.00,22.000,7920000.00,43920000.00,'ARS',1,'consultar',0,0,0,NULL,1,0,341,1,1),

 (11,'machine','MC-001','Minicargadora Komatsu SK820-5','minicargadora-komatsu-sk820-5',5,6,
  'Minicargadora de 900 kg de capacidad operativa.',
  'Minicargadora compacta con motor diésel de 74 HP, sistema hidráulico auxiliar y cabina cerrada con aire acondicionado. Apta para múltiples implementos.',
  25600000.00,25.000,6400000.00,32000000.00,'ARS',1,'mantenimiento',0,0,0,NULL,1,0,187,1,1),

 (12,'machine','EQ-001','Pinza para bobinas hidráulica','pinza-para-bobinas-hidraulica',12,8,
  'Implemento hidráulico para manipulación de bobinas de papel.',
  'Pinza hidráulica para bobinas con apertura de 300 a 1.500 mm, montaje clase II. Compatible con la mayoría de los autoelevadores de 2 a 3 toneladas.',
  4400000.00,30.000,1320000.00,5720000.00,'ARS',1,'disponible',0,0,0,NULL,2,0,156,1,1);

INSERT INTO `machines`
 (`product_id`,`model`,`year`,`condition_type`,`hours`,`fuel`,`engine`,`power_hp`,`transmission`,
  `capacity_kg`,`lift_height_mm`,`closed_height_mm`,`weight_kg`,`length_mm`,`width_mm`,`turn_radius_mm`,
  `battery`,`voltage`,`mast_type`,`tire_type`,`location`,`warranty`) VALUES
 (1,'8FG25',2018,'usado',6200,'gas','Toyota 4Y 2.2L',52.00,'Automática (convertidor)',2500,4700,2100,3800,3695,1150,2200,NULL,NULL,'Triple visión libre','Neumático','Depósito Central','6 meses'),
 (2,'8FD30',2019,'usado',4800,'diesel','Toyota 1DZ-III 2.5L',54.00,'Automática (convertidor)',3000,4500,2200,4400,3830,1250,2350,NULL,NULL,'Triple','Neumático','Depósito Central','6 meses'),
 (3,'E30XN',2020,'usado',3100,'electrico','Motor AC 48V',NULL,'Eléctrica AC',1400,4500,2000,3200,3050,1070,1600,'Tracción 48V/625Ah','48','Triple visión libre','Cushion','Depósito Central','6 meses'),
 (4,'GLP050',2016,'usado',9400,'glp','Yale 2.0L GLP',48.00,'Automática',2300,3300,1990,3450,3500,1140,2150,NULL,NULL,'Dúplex','Neumático','Depósito Taller','3 meses'),
 (5,'EJC 112',2021,'usado',1800,'electrico','Motor AC 24V',NULL,'Eléctrica AC',1200,3300,1990,1150,1800,800,1520,'Tracción 24V/250Ah','24','Dúplex','Poliuretano','Depósito Central','6 meses'),
 (6,'MH-1016',2026,'nuevo',0,'manual',NULL,NULL,'Manual',1000,1600,1900,320,1650,800,1300,NULL,NULL,'Simple','Nylon','Depósito Central','12 meses'),
 (7,'T20',2022,'usado',900,'electrico','Motor AC 24V litio',NULL,'Eléctrica AC',2000,205,1250,520,1750,720,1550,'Litio 24V/205Ah','24',NULL,'Poliuretano','Depósito Central','6 meses'),
 (8,'ZM-2500',2026,'nuevo',0,'manual',NULL,NULL,'Manual',2500,200,1220,78,1550,685,1400,NULL,NULL,NULL,'Nylon','Depósito Central','12 meses'),
 (9,'GS-1932',2017,'usado',2400,'electrico','Motor DC 24V',NULL,'Eléctrica DC',227,5800,1980,1520,1830,810,NULL,'Tracción 24V/220Ah','24',NULL,'Antihuella','Depósito Taller','3 meses'),
 (10,'TH255C',2018,'usado',3900,'diesel','Perkins 404D-22T',74.00,'Hidrostática',2500,5600,1900,4900,4090,1810,3400,NULL,NULL,'Telescópico','Neumático','Depósito Taller','3 meses'),
 (11,'SK820-5',2015,'usado',5600,'diesel','Komatsu 3D88E',74.00,'Hidrostática',900,NULL,1990,3100,3350,1550,NULL,NULL,NULL,NULL,'Neumático','Depósito Taller',NULL),
 (12,'PB-1500',2026,'nuevo',NULL,NULL,NULL,NULL,NULL,2000,NULL,NULL,420,NULL,1500,NULL,NULL,NULL,NULL,NULL,'Depósito Central','12 meses');

-- =====================================================================
-- REPUESTOS
-- =====================================================================
INSERT INTO `products`
 (`id`,`type`,`code`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,
  `cost_price`,`profit_percent`,`profit_amount`,`final_price`,`currency`,`price_visible`,
  `availability`,`featured`,`is_new`,`is_offer`,`offer_price`,`stock`,`stock_reserved`,`stock_min`,`track_stock`,`views`,`active`,`created_by`) VALUES

 (101,'spare_part','FIL-00125','Filtro de aceite motor Toyota serie 8','filtro-de-aceite-motor-toyota-serie-8',1,27,
  'Filtro de aceite para motores Toyota 4Y y 1DZ de la serie 8.',
  'Filtro de aceite de flujo total con válvula antirretorno. Recomendado para cambio cada 250 horas de operación. Compatible con la mayoría de los autoelevadores Toyota serie 7 y 8 con motor 4Y (gas/nafta) y 1DZ (diésel).',
  12000.00,60.000,7200.00,19200.00,'ARS',1,'disponible',1,0,0,NULL,24,2,5,1,342,1,1),

 (102,'spare_part','FIL-00210','Filtro de aire primario autoelevador 2-3 t','filtro-de-aire-primario-autoelevador-2-3-t',12,27,
  'Filtro de aire primario para autoelevadores de 2 a 3 toneladas.',
  'Elemento filtrante de papel plisado de alta eficiencia. Fundamental para prolongar la vida del motor en ambientes con polvo. Se recomienda su reemplazo cada 500 horas o antes en ambientes severos.',
  18000.00,55.000,9900.00,27900.00,'ARS',1,'disponible',1,0,1,24900.00,11,0,4,1,268,1,1),

 (103,'spare_part','FIL-00330','Filtro hidráulico de retorno','filtro-hidraulico-de-retorno',12,27,
  'Filtro hidráulico de retorno roscado, 10 micrones.',
  'Filtro hidráulico de retorno con elemento de 10 micrones. Protege bomba, válvulas y cilindros de la contaminación del aceite.',
  26000.00,55.000,14300.00,40300.00,'ARS',1,'disponible',0,0,0,NULL,7,0,3,1,141,1,1),

 (104,'spare_part','BOM-01000','Bomba hidráulica de engranajes 2.500 kg','bomba-hidraulica-de-engranajes-2500-kg',12,24,
  'Bomba hidráulica de engranajes para autoelevadores de 2 a 3 toneladas.',
  'Bomba de engranajes de alta presión con carcasa de aluminio y engranajes rectificados. Caudal de 32 l/min a 2.000 rpm, presión máxima de 210 bar. Se entrega probada en banco con certificado de ensayo.',
  480000.00,45.000,216000.00,696000.00,'ARS',1,'disponible',1,0,0,NULL,3,1,2,1,412,1,1),

 (105,'spare_part','MAN-00450','Manguera hidráulica R2 1/2" x 1.200 mm','manguera-hidraulica-r2-1-2-1200-mm',12,34,
  'Manguera hidráulica de dos mallas, 1/2 pulgada, con racores prensados.',
  'Manguera hidráulica SAE 100 R2AT de 1/2" con terminales JIC prensados en ambos extremos. Presión de trabajo 275 bar. Fabricación a medida en el día.',
  62000.00,50.000,31000.00,93000.00,'ARS',1,'disponible',0,0,0,NULL,18,0,6,1,98,1,1),

 (106,'spare_part','ROD-00088','Rodamiento de rueda directriz 6208-2RS','rodamiento-de-rueda-directriz-6208-2rs',12,28,
  'Rodamiento rígido de bolas 6208-2RS sellado.',
  'Rodamiento rígido de una hilera de bolas, 40 x 80 x 18 mm, con doble sello de goma y engrase permanente. Aplicación en ruedas directrices y rodillos de mástil.',
  28000.00,60.000,16800.00,44800.00,'ARS',1,'disponible',0,0,0,NULL,26,0,8,1,76,1,1),

 (107,'spare_part','FRE-00512','Juego de cintas de freno 2.500 kg','juego-de-cintas-de-freno-2500-kg',12,22,
  'Juego completo de cintas de freno con remaches.',
  'Juego de cintas de freno de material sinterizado libre de asbesto, con remaches incluidos. Reemplazo recomendado cada 2.000 horas o cuando el espesor sea menor a 3 mm.',
  95000.00,55.000,52250.00,147250.00,'ARS',1,'disponible',1,0,0,NULL,4,0,2,1,203,1,1),

 (108,'spare_part','FRE-00610','Bomba de freno principal','bomba-de-freno-principal',12,22,
  'Cilindro maestro de freno para autoelevadores 2-3 t.',
  'Bomba de freno principal con depósito integrado, diámetro de pistón 22,2 mm. Incluye kit de sellos.',
  156000.00,50.000,78000.00,234000.00,'ARS',1,'consultar',0,0,0,NULL,0,0,1,1,64,1,1),

 (109,'spare_part','SEN-00021','Sensor de temperatura de motor','sensor-de-temperatura-de-motor',12,35,
  'Sensor/bulbo de temperatura de agua con rosca M16.',
  'Sensor de temperatura de refrigerante con rosca M16 x 1,5 y conector tipo pala. Rango de medición -40 a 130 °C.',
  34000.00,60.000,20400.00,54400.00,'ARS',1,'disponible',0,1,0,NULL,9,0,3,1,55,1,1),

 (110,'spare_part','BAT-00480','Batería de tracción 48V 625Ah','bateria-de-traccion-48v-625ah',12,26,
  'Batería de tracción de plomo-ácido 48 V 625 Ah con cuba metálica.',
  'Batería de tracción de 24 elementos de 2 V, 625 Ah en descarga de 5 horas. Incluye cuba metálica, tapones y sistema de llenado centralizado. Garantía de 12 meses.',
  5200000.00,35.000,1820000.00,7020000.00,'ARS',1,'consultar',1,0,0,NULL,0,0,1,1,187,1,1),

 (111,'spare_part','NEU-00700','Neumático sólido 7.00-12','neumatico-solido-7-00-12',12,29,
  'Cubierta sólida (maciza) 7.00-12 para autoelevador.',
  'Neumático macizo de tres compuestos con banda de rodamiento antihuella. Larga duración y cero pinchaduras. Requiere prensa para el montaje (servicio disponible en nuestro taller).',
  310000.00,45.000,139500.00,449500.00,'ARS',1,'disponible',1,0,0,NULL,6,2,2,1,231,1,1),

 (112,'spare_part','HOR-01070','Par de horquillas 1.070 x 100 x 40 mm','par-de-horquillas-1070-x-100-x-40-mm',12,30,
  'Par de uñas clase II de 1.070 mm para 2.500 kg.',
  'Par de horquillas forjadas clase II, 1.070 x 100 x 40 mm, capacidad 2.500 kg a 500 mm de centro de carga. Certificadas y con marcado de capacidad.',
  520000.00,45.000,234000.00,754000.00,'ARS',1,'disponible',0,0,0,NULL,3,0,1,1,144,1,1),

 (113,'spare_part','MAS-00330','Cadena de mástil Leaf BL634','cadena-de-mastil-leaf-bl634',12,31,
  'Cadena de mástil tipo Leaf BL634 por metro.',
  'Cadena de placas tipo Leaf BL634 para mástiles de autoelevador. Se vende por metro. Recomendamos reemplazar siempre de a pares y verificar el alargamiento cada 1.000 horas.',
  74000.00,50.000,37000.00,111000.00,'ARS',1,'disponible',0,0,0,NULL,15,0,5,1,89,1,1),

 (114,'spare_part','CIL-00120','Cilindro de inclinación completo','cilindro-de-inclinacion-completo',12,32,
  'Cilindro hidráulico de inclinación con vástago cromado.',
  'Cilindro de inclinación completo, vástago cromado rectificado y kit de sellos nuevo. Probado a 250 bar.',
  620000.00,45.000,279000.00,899000.00,'ARS',1,'disponible',0,0,0,NULL,2,0,1,1,71,1,1),

 (115,'spare_part','ELE-00090','Contactor de potencia 48V 400A','contactor-de-potencia-48v-400a',12,25,
  'Contactor principal para equipos eléctricos de 48 V.',
  'Contactor de potencia de simple contacto, bobina de 48 V, corriente de trabajo 400 A. Aplicación en autoelevadores y apiladores eléctricos.',
  178000.00,50.000,89000.00,267000.00,'ARS',1,'disponible',0,1,0,NULL,5,0,2,1,63,1,1),

 (116,'spare_part','TRA-00340','Kit de embrague de transmisión','kit-de-embrague-de-transmision',12,21,
  'Kit completo de discos de embrague de transmisión.',
  'Kit de discos y contra-discos para transmisión automática de autoelevador, incluye juntas y sellos.',
  430000.00,45.000,193500.00,623500.00,'ARS',1,'disponible',0,0,0,NULL,2,0,1,1,58,1,1),

 (117,'spare_part','DIR-00210','Orbitrol de dirección hidráulica','orbitrol-de-direccion-hidraulica',12,23,
  'Unidad de dirección hidrostática (orbitrol) 80 cc.',
  'Orbitrol de dirección de 80 cc/rev con válvula de alivio incorporada. Reacondicionado y probado en banco.',
  580000.00,45.000,261000.00,841000.00,'ARS',1,'disponible',0,0,0,NULL,1,0,1,1,47,1,1),

 (118,'spare_part','MOT-00990','Kit de juntas de motor 4Y','kit-de-juntas-de-motor-4y',1,20,
  'Juego completo de juntas para motor Toyota 4Y.',
  'Juego completo de juntas de motor Toyota 4Y incluyendo tapa de cilindros, cárter y colectores. Material de alta temperatura.',
  138000.00,55.000,75900.00,213900.00,'ARS',1,'disponible',1,0,0,NULL,6,0,2,1,152,1,1);

INSERT INTO `spare_parts`
 (`product_id`,`oem_code`,`manufacturer_code`,`manufacturer`,`origin`,`unit`,`weight_kg`,`warehouse_id`,`sector`,`shelf`,`position`,`lead_time_days`) VALUES
 (101,'15601-U2100-71','W68/3','Toyota','original','unidad',0.450,1,'A','Estantería A','Fila 3 · Pos. 4',0),
 (102,'17801-U2140-71','AF-2140','Toyota','alternativo','unidad',0.900,1,'A','Estantería A','Fila 3 · Pos. 7',0),
 (103,'67501-23320-71','HF-3320','Genérico','alternativo','unidad',0.700,1,'A','Estantería B','Fila 1 · Pos. 2',0),
 (104,'67110-23620-71','BH-2362','Genérico','alternativo','unidad',9.400,1,'B','Estantería C','Fila 2 · Pos. 1',0),
 (105,'R2AT-12-1200','MH-1200','Genérico','alternativo','unidad',1.800,1,'B','Estantería D','Fila 4 · Pos. 6',2),
 (106,'6208-2RS','SKF-6208','SKF','original','unidad',0.370,1,'A','Estantería B','Fila 5 · Pos. 3',0),
 (107,'47411-23320-71','BR-3320','Genérico','alternativo','juego',3.200,1,'B','Estantería C','Fila 3 · Pos. 5',0),
 (108,'47201-23320-71','MC-2233','Genérico','alternativo','unidad',1.400,1,'B','Estantería C','Fila 3 · Pos. 6',7),
 (109,'83420-U2110-71','TS-2110','Genérico','alternativo','unidad',0.120,1,'A','Estantería B','Fila 2 · Pos. 9',0),
 (110,'BT-48-625','BT48625','Genérico','alternativo','unidad',980.000,2,'Playón','Sector baterías','Pos. 2',30),
 (111,'70012-SOL','NS-70012','Genérico','alternativo','unidad',52.000,2,'Playón','Sector neumáticos','Pos. 5',0),
 (112,'HOR-1070-II','FK-1070','Genérico','alternativo','par',86.000,2,'Playón','Sector horquillas','Pos. 1',0),
 (113,'BL634','LC-634','Genérico','alternativo','metro',2.900,1,'B','Estantería D','Fila 1 · Pos. 1',0),
 (114,'65500-23330-71','TC-2333','Genérico','alternativo','unidad',14.500,2,'Taller','Estantería E','Fila 2 · Pos. 2',0),
 (115,'CT-48-400','SW200-48','Genérico','alternativo','unidad',1.100,1,'A','Estantería B','Fila 4 · Pos. 8',0),
 (116,'32210-23330-71','CK-2333','Genérico','alternativo','kit',11.000,2,'Taller','Estantería E','Fila 3 · Pos. 4',5),
 (117,'45510-23320-71','OR-80','Genérico','remanufacturado','unidad',6.800,2,'Taller','Estantería E','Fila 1 · Pos. 3',0),
 (118,'04111-78156-71','GK-4Y','Toyota','original','kit',1.900,1,'A','Estantería A','Fila 6 · Pos. 2',0);

-- Códigos alternativos / cruzados --------------------------------------
INSERT INTO `spare_part_codes` (`product_id`,`code_type`,`code`,`brand_id`,`note`) VALUES
 (101,'oem','15601-U2100-71',1,'Código original Toyota'),
 (101,'alternativo','1560123020-71',1,'Código superado'),
 (101,'cruzado','W68/3',NULL,'Equivalente Mann'),
 (101,'cruzado','LF3349',NULL,'Equivalente Fleetguard'),
 (102,'oem','17801-U2140-71',1,NULL),
 (102,'cruzado','C17137',NULL,'Equivalente Mann'),
 (103,'oem','67501-23320-71',1,NULL),
 (104,'oem','67110-23620-71',1,NULL),
 (104,'alternativo','67110-U2170-71',1,'Aplicación 3 t'),
 (106,'cruzado','6208-2RS1',NULL,'SKF'),
 (106,'cruzado','6208DDU',NULL,'NSK'),
 (107,'oem','47411-23320-71',1,NULL),
 (111,'alternativo','700-12',NULL,'Medida comercial'),
 (118,'oem','04111-78156-71',1,NULL);

-- Compatibilidad libre (marca + modelo) --------------------------------
INSERT INTO `spare_part_compatibility` (`spare_part_id`,`brand_id`,`model`,`year_from`,`year_to`,`note`) VALUES
 (101,1,'8FG25',2010,2024,'Motor 4Y'),
 (101,1,'8FG30',2010,2024,'Motor 4Y'),
 (101,1,'8FD25',2010,2024,'Motor 1DZ'),
 (101,1,'8FD30',2010,2024,'Motor 1DZ'),
 (101,1,'7FG25',2003,2010,'Motor 4Y'),
 (101,2,'H2.5FT',2012,2022,'Verificar motor'),
 (102,1,'8FG25',2010,2024,NULL),
 (102,1,'8FD30',2010,2024,NULL),
 (102,2,'H2.5FT',2012,2022,NULL),
 (102,3,'GLP050',2012,2020,NULL),
 (103,1,'8FG25',2010,2024,NULL),
 (103,1,'8FD30',2010,2024,NULL),
 (104,1,'8FG25',2010,2024,NULL),
 (104,1,'8FD30',2010,2024,NULL),
 (104,3,'GLP050',2012,2020,NULL),
 (105,NULL,'Universal 2-3 t',NULL,NULL,'Fabricación a medida'),
 (106,1,'8FG25',2010,2024,'Rueda directriz'),
 (106,2,'E30XN',2015,2023,'Rodillo de mástil'),
 (107,1,'8FG25',2010,2024,NULL),
 (107,1,'8FD30',2010,2024,NULL),
 (108,1,'8FG25',2010,2024,NULL),
 (109,1,'8FD30',2010,2024,NULL),
 (110,2,'E30XN',2015,2023,'Batería de tracción'),
 (110,8,'EJC 112',2018,2024,'Verificar cuba'),
 (111,1,'8FG25',2010,2024,'Rueda delantera'),
 (111,3,'GLP050',2012,2020,NULL),
 (112,1,'8FG25',2010,2024,'Clase II'),
 (112,1,'8FD30',2010,2024,'Clase II'),
 (113,1,'8FG25',2010,2024,NULL),
 (113,2,'E30XN',2015,2023,NULL),
 (114,1,'8FG25',2010,2024,NULL),
 (115,2,'E30XN',2015,2023,NULL),
 (115,8,'EJC 112',2018,2024,NULL),
 (115,7,'T20',2019,2024,NULL),
 (116,1,'8FG25',2010,2024,NULL),
 (117,1,'8FG25',2010,2024,NULL),
 (118,1,'8FG25',2010,2024,'Motor 4Y'),
 (118,1,'7FG25',2003,2010,'Motor 4Y');

-- Relación explícita máquina del catálogo <-> repuesto -----------------
INSERT INTO `machine_spare_parts` (`machine_id`,`spare_part_id`,`recommended`,`note`) VALUES
 (1,101,1,'Cambio cada 250 h'),(1,102,1,'Cambio cada 500 h'),(1,103,1,NULL),(1,104,0,NULL),
 (1,107,1,NULL),(1,111,0,NULL),(1,112,0,NULL),(1,113,0,NULL),(1,118,0,NULL),(1,106,0,NULL),
 (2,101,1,NULL),(2,102,1,NULL),(2,103,1,NULL),(2,104,0,NULL),(2,107,1,NULL),(2,109,0,NULL),(2,112,0,NULL),
 (3,106,0,NULL),(3,110,1,'Batería de reemplazo'),(3,113,0,NULL),(3,115,1,NULL),
 (4,102,1,NULL),(4,104,0,NULL),(4,111,0,NULL),
 (5,110,0,'Verificar cuba'),(5,115,1,NULL),
 (7,115,1,NULL);

-- Características técnicas cargadas ------------------------------------
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 1, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'2.500' txt, 2500 num UNION ALL
  SELECT 'altura-maxima','4.700',4700 UNION ALL
  SELECT 'altura-replegada','2.100',2100 UNION ALL
  SELECT 'centro-de-carga','500',500 UNION ALL
  SELECT 'peso-operativo','3.800',3800 UNION ALL
  SELECT 'largo-total','3.695',3695 UNION ALL
  SELECT 'ancho-total','1.150',1150 UNION ALL
  SELECT 'radio-de-giro','2.200',2200 UNION ALL
  SELECT 'combustible','Gas',NULL UNION ALL
  SELECT 'motor','Toyota 4Y 2.2L',NULL UNION ALL
  SELECT 'potencia','52',52 UNION ALL
  SELECT 'transmision-feature','Automática (convertidor)',NULL UNION ALL
  SELECT 'horas-de-uso','6.200',6200 UNION ALL
  SELECT 'tipo-de-mastil','Triple visión libre',NULL UNION ALL
  SELECT 'tipo-de-rueda','Neumático',NULL UNION ALL
  SELECT 'velocidad-traslacion','19',19 UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 2, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'3.000' txt, 3000 num UNION ALL
  SELECT 'altura-maxima','4.500',4500 UNION ALL
  SELECT 'peso-operativo','4.400',4400 UNION ALL
  SELECT 'combustible','Diésel',NULL UNION ALL
  SELECT 'motor','Toyota 1DZ-III 2.5L',NULL UNION ALL
  SELECT 'potencia','54',54 UNION ALL
  SELECT 'horas-de-uso','4.800',4800 UNION ALL
  SELECT 'tipo-de-mastil','Triple',NULL UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 3, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'1.400' txt, 1400 num UNION ALL
  SELECT 'altura-maxima','4.500',4500 UNION ALL
  SELECT 'peso-operativo','3.200',3200 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','48',48 UNION ALL
  SELECT 'tipo-de-bateria','Plomo-ácido 625Ah',NULL UNION ALL
  SELECT 'horas-de-uso','3.100',3100 UNION ALL
  SELECT 'radio-de-giro','1.600',1600 UNION ALL
  SELECT 'garantia','6 meses',NULL
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 5, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'1.200' txt, 1200 num UNION ALL
  SELECT 'altura-maxima','3.300',3300 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'horas-de-uso','1.800',1800 UNION ALL
  SELECT 'peso-operativo','1.150',1150
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 7, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'2.000' txt, 2000 num UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'tipo-de-bateria','Litio 205Ah',NULL UNION ALL
  SELECT 'horas-de-uso','900',900
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 9, f.id, v.txt, v.num FROM (
  SELECT 'capacidad-de-carga' s,'227' txt, 227 num UNION ALL
  SELECT 'altura-maxima','5.800',5800 UNION ALL
  SELECT 'combustible','Eléctrico',NULL UNION ALL
  SELECT 'voltaje','24',24 UNION ALL
  SELECT 'horas-de-uso','2.400',2400
) v JOIN features f ON f.slug = v.s;

INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 101, f.id, 'Papel plisado', NULL FROM features f WHERE f.slug='material';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 101, f.id, '3/4"-16 UNF', NULL FROM features f WHERE f.slug='rosca';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 106, f.id, '40 x 80 x 18 mm', NULL FROM features f WHERE f.slug='medida';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 106, f.id, '80', 80 FROM features f WHERE f.slug='diametro';
INSERT INTO `feature_values` (`product_id`,`feature_id`,`value_text`,`value_number`)
SELECT 111, f.id, '7.00-12', NULL FROM features f WHERE f.slug='medida';

-- Etiquetas por producto -----------------------------------------------
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'usado'
 WHERE p.id IN (1,2,3,4,5,7,9,10,11);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'nuevo'
 WHERE p.id IN (6,8,12,109,115);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'oferta'
 WHERE p.id IN (2,102);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'electrico'
 WHERE p.id IN (3,5,7,9);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'diesel'
 WHERE p.id IN (2,10,11);
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'destacado'
 WHERE p.featured = 1;
INSERT INTO `product_tags` (`product_id`,`tag_id`)
SELECT p.id, t.id FROM products p JOIN tags t ON t.slug = 'ultimas-unidades'
 WHERE p.id IN (104,107,114,117);

-- Videos y documentación -----------------------------------------------
INSERT INTO `videos` (`product_id`,`title`,`provider`,`video_ref`,`sort_order`) VALUES
 (1,'Ver la máquina trabajando','youtube','dQw4w9WgXcQ',1),
 (3,'Demostración en depósito','youtube','dQw4w9WgXcQ',1);

-- Historial de precios --------------------------------------------------
INSERT INTO `price_history`
 (`product_id`,`old_cost`,`new_cost`,`old_profit_percent`,`new_profit_percent`,`old_profit_amount`,`new_profit_amount`,`old_price`,`new_price`,`reason`,`user_id`,`created_at`) VALUES
 (1,15200000.00,16000000.00,25.000,25.000,3800000.00,4000000.00,19000000.00,20000000.00,'Actualización de lista de proveedor',2,'2026-08-15 10:24:00'),
 (1,14400000.00,15200000.00,25.000,25.000,3600000.00,3800000.00,18000000.00,19000000.00,'Ajuste por tipo de cambio',1,'2026-06-02 09:10:00'),
 (101,10000.00,12000.00,60.000,60.000,6000.00,7200.00,16000.00,19200.00,'Actualización de proveedor',2,'2026-08-10 16:40:00'),
 (104,420000.00,480000.00,45.000,45.000,189000.00,216000.00,609000.00,696000.00,'Aumento importación',1,'2026-07-22 11:05:00');

-- Movimientos de stock --------------------------------------------------
INSERT INTO `stock_movements` (`product_id`,`type`,`quantity`,`stock_before`,`stock_after`,`reason`,`reference`,`user_id`,`created_at`) VALUES
 (101,'entrada',30,0,30,'Compra a proveedor','OC-1042',2,'2026-08-01 09:00:00'),
 (101,'salida',4,30,26,'Service preventivo cliente Logística SA','OT-2211',2,'2026-08-08 14:30:00'),
 (101,'salida',2,26,24,'Venta mostrador',NULL,3,'2026-08-19 11:12:00'),
 (102,'entrada',15,0,15,'Compra a proveedor','OC-1042',2,'2026-08-01 09:05:00'),
 (102,'salida',4,15,11,'Service preventivo','OT-2214',2,'2026-08-12 10:00:00'),
 (104,'entrada',4,0,4,'Compra importación','OC-1050',1,'2026-07-25 12:00:00'),
 (104,'salida',1,4,3,'Reparación equipo cliente','OT-2230',2,'2026-08-20 15:45:00'),
 (111,'entrada',8,0,8,'Compra a proveedor','OC-1055',2,'2026-08-05 08:30:00'),
 (111,'salida',2,8,6,'Cambio de cubiertas','OT-2240',2,'2026-08-21 09:20:00'),
 (108,'entrada',2,0,2,'Compra a proveedor','OC-1060',2,'2026-07-15 10:00:00'),
 (108,'salida',2,2,0,'Venta','FC-A-0001',3,'2026-08-18 17:00:00');

-- Consultas -------------------------------------------------------------
INSERT INTO `inquiries` (`name`,`email`,`phone`,`company`,`product_id`,`subject`,`message`,`channel`,`status`,`created_at`) VALUES
 ('Martín Suárez','msuarez@ejemplo.com','11-5555-1122','Logística del Sur SA',1,'Consulta por Autoelevador Toyota 8FG25','Buenas tardes, necesito saber disponibilidad y si aceptan parte de pago con un equipo usado. Gracias.','web','nueva','2026-08-24 10:15:00'),
 ('Verónica Díaz','vdiaz@ejemplo.com','11-5555-3344','Depósitos Norte SRL',101,'Consulta por Filtro de aceite','Necesito 20 unidades del filtro FIL-00125. ¿Tienen stock y qué precio por cantidad?','web','en_proceso','2026-08-25 09:02:00'),
 ('Roberto Klein','rklein@ejemplo.com','351-555-7788','Metalúrgica Klein',NULL,'Alquiler mensual','Quisiera cotizar el alquiler de dos autoelevadores de 2.5 t por seis meses en Córdoba.','web','nueva','2026-08-25 16:40:00'),
 ('Pablo Ferrari','pferrari@ejemplo.com','11-5555-9900',NULL,3,'Autoelevador eléctrico','¿El equipo eléctrico sirve para cámara de frío? ¿Qué autonomía tiene?','whatsapp','respondida','2026-08-20 11:30:00');

-- Cotizaciones ----------------------------------------------------------
INSERT INTO `quotes`
 (`id`,`number`,`status`,`source`,`customer_name`,`customer_company`,`customer_email`,`customer_phone`,`customer_taxid`,
  `subtotal`,`discount_percent`,`discount_amount`,`shipping_cost`,`other_costs`,`interest_amount`,`total`,`currency`,
  `financing_option_id`,`down_payment`,`installments`,`installment_amount`,`notes`,`valid_until`,`user_id`,`created_at`) VALUES
 (1,'COT-000001','enviada','admin','Martín Suárez','Logística del Sur SA','msuarez@ejemplo.com','11-5555-1122','30-71234567-9',
  21500000.00,0.00,1000000.00,500000.00,0.00,0.00,21000000.00,'ARS',
  2,6300000.00,6,2695000.00,'Incluye entrega en planta y puesta en marcha.','2026-09-10',3,'2026-08-22 12:00:00'),
 (2,'COT-000002','borrador','admin','Verónica Díaz','Depósitos Norte SRL','vdiaz@ejemplo.com','11-5555-3344',NULL,
  384000.00,5.00,19200.00,0.00,0.00,0.00,364800.00,'ARS',
  NULL,0.00,0,0.00,'Cotización por 20 filtros de aceite.','2026-09-08',3,'2026-08-25 09:30:00');

INSERT INTO `quote_items` (`quote_id`,`product_id`,`item_type`,`code`,`description`,`quantity`,`unit_price`,`discount_percent`,`line_total`,`sort_order`) VALUES
 (1,1,'machine','AE-001','Autoelevador Toyota 8FG25 2.500 kg',1.00,20000000.00,0.00,20000000.00,1),
 (1,12,'machine','EQ-001','Pinza para bobinas hidráulica',1.00,5720000.00,0.00,5720000.00,2),
 (1,NULL,'service',NULL,'Puesta en marcha y capacitación de operadores',1.00,280000.00,0.00,280000.00,3),
 (2,101,'spare_part','FIL-00125','Filtro de aceite motor Toyota serie 8',20.00,19200.00,0.00,384000.00,1);

UPDATE `quotes` SET `subtotal` = 26000000.00, `discount_amount` = 5000000.00, `total` = 21500000.00 WHERE `id` = 1;

INSERT INTO `quote_payments` (`quote_id`,`concept`,`amount`,`due_date`,`sort_order`) VALUES
 (1,'Anticipo 30%',6300000.00,'2026-09-01',1),
 (1,'Cuota 1 de 6',2695000.00,'2026-10-01',2),
 (1,'Cuota 2 de 6',2695000.00,'2026-11-01',3);

-- Analítica de ejemplo ---------------------------------------------------
INSERT INTO `search_logs` (`term`,`normalized`,`context`,`results_count`,`created_at`) VALUES
 ('8FG25','8fg25','spare_part',9,'2026-08-25 10:00:00'),
 ('filtro toyota','filtro toyota','spare_part',3,'2026-08-25 10:04:00'),
 ('filtro de aceite','filtro de aceite','spare_part',2,'2026-08-24 15:20:00'),
 ('bomba hidraulica','bomba hidraulica','spare_part',1,'2026-08-24 16:00:00'),
 ('pastillas de freno','pastillas de freno','spare_part',2,'2026-08-23 11:00:00'),
 ('autoelevador electrico','autoelevador electrico','machine',2,'2026-08-23 09:40:00'),
 ('8FG25','8fg25','global',10,'2026-08-22 18:05:00'),
 ('15601-U2100-71','15601-u2100-71','spare_part',1,'2026-08-22 12:30:00'),
 ('apilador','apilador','machine',2,'2026-08-21 14:10:00'),
 ('zorra electrica','zorra electrica','machine',1,'2026-08-20 10:00:00');

INSERT INTO `activity_logs` (`user_id`,`user_name`,`action`,`module`,`entity_type`,`entity_id`,`description`,`ip`,`created_at`) VALUES
 (1,'Administrador','login','auth',NULL,NULL,'Inicio de sesión correcto','127.0.0.1','2026-08-26 08:00:00'),
 (2,'Juan Pérez','price_change','prices','product',1,'Precio actualizado de $19.000.000 a $20.000.000','127.0.0.1','2026-08-15 10:24:00'),
 (2,'Juan Pérez','stock_change','stock','product',101,'Salida de 2 unidades','127.0.0.1','2026-08-19 11:12:00'),
 (3,'Carla Gómez','create','quotes','quote',1,'Cotización COT-000001 creada','127.0.0.1','2026-08-22 12:00:00');

SET FOREIGN_KEY_CHECKS = 1;
