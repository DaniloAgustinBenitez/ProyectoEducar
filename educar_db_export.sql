-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: educar_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actividad_entregas`
--

DROP TABLE IF EXISTS `actividad_entregas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividad_entregas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actividad_id` int(11) NOT NULL,
  `alumno_nombre` varchar(255) NOT NULL,
  `fecha` varchar(50) DEFAULT NULL,
  `texto` text DEFAULT NULL,
  `archivo` varchar(500) DEFAULT '',
  `devolucion` text DEFAULT NULL,
  `nota` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_actividad_alumno` (`actividad_id`,`alumno_nombre`),
  CONSTRAINT `actividad_entregas_ibfk_1` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividad_entregas`
--

LOCK TABLES `actividad_entregas` WRITE;
/*!40000 ALTER TABLE `actividad_entregas` DISABLE KEYS */;
INSERT INTO `actividad_entregas` VALUES (1,1,'Alexis Mongelo','18/06/2026 05:42','dadas','uploads/1781754129_alum_1.jpg','iuuih','8'),(2,2,'Jose Ojeda','2026-06-18 06:21:21','dasda','uploads/1781756481_alum_WhatsApp Image 2026-05-05 at 20.47.42.jpeg','adads','7');
/*!40000 ALTER TABLE `actividad_entregas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actividades`
--

DROP TABLE IF EXISTS `actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profesor` varchar(255) NOT NULL,
  `curso` varchar(50) NOT NULL,
  `division` varchar(10) NOT NULL,
  `materia` varchar(100) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_limite` varchar(50) DEFAULT NULL,
  `archivo_adjunto` varchar(500) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividades`
--

LOCK TABLES `actividades` WRITE;
/*!40000 ALTER TABLE `actividades` DISABLE KEYS */;
INSERT INTO `actividades` VALUES (1,'Jimena Perez','5_anio','B','Matem?tica','TP','adsd','19-06-2026 12:00','uploads/1781754106_profe_mapa.png'),(2,'Jimena Perez','5_anio','C','Matem?tica','22','56','20-06-2026 12:00','uploads/1781756460_profe_mapa.png');
/*!40000 ALTER TABLE `actividades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_niveles`
--

DROP TABLE IF EXISTS `admin_niveles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_niveles` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_niveles`
--

LOCK TABLES `admin_niveles` WRITE;
/*!40000 ALTER TABLE `admin_niveles` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_niveles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admisiones`
--

DROP TABLE IF EXISTS `admisiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admisiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dni_tutor` varchar(20) NOT NULL,
  `nombre_tutor` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `nivel_interes` varchar(50) NOT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `fecha_entrevista` date DEFAULT NULL,
  `hora_entrevista` time DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_nacimiento_alumno` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admisiones`
--

LOCK TABLES `admisiones` WRITE;
/*!40000 ALTER TABLE `admisiones` DISABLE KEYS */;
INSERT INTO `admisiones` VALUES (4,'42342342','Marta Gomez (Padre de Javier Gomez)','javiergomez2001@gmail.com','36246545645','Nivel Inicial | Disp: Turno Ma?ana (8 a 12hs)','pendiente',NULL,NULL,'2026-06-18 03:00:37','2023-02-26');
/*!40000 ALTER TABLE `admisiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_nombre` varchar(100) NOT NULL,
  `curso_clave` varchar(50) NOT NULL,
  `fecha` date NOT NULL,
  `estado` varchar(20) DEFAULT 'ausente',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencias`
--

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
INSERT INTO `asistencias` VALUES (5,'Alexis Mongelo','5_anio_B','2026-06-17','ausente'),(6,'Alexis Mongelo','5_anio_B','2026-06-18','ausente'),(7,'Jose Ojeda','5_anio_C','2026-06-18','ausente');
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calificaciones`
--

DROP TABLE IF EXISTS `calificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_nombre` varchar(100) NOT NULL,
  `materia` varchar(100) NOT NULL,
  `nombre_examen` varchar(100) NOT NULL,
  `nota` decimal(4,2) NOT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones`
--

LOCK TABLES `calificaciones` WRITE;
/*!40000 ALTER TABLE `calificaciones` DISABLE KEYS */;
INSERT INTO `calificaciones` VALUES (2,'Alexis Mongelo','Matem?tica','1er Prueba',6.00,'2026-06-18 03:41:14'),(3,'Alexis Mongelo','Matem?tica','TP (TP)',8.00,'2026-06-18 03:56:46');
/*!40000 ALTER TABLE `calificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cap_inscriptos`
--

DROP TABLE IF EXISTS `cap_inscriptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cap_inscriptos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cap_id` int(11) NOT NULL,
  `docente_nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cap_docente` (`cap_id`,`docente_nombre`),
  CONSTRAINT `cap_inscriptos_ibfk_1` FOREIGN KEY (`cap_id`) REFERENCES `capacitaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cap_inscriptos`
--

LOCK TABLES `cap_inscriptos` WRITE;
/*!40000 ALTER TABLE `cap_inscriptos` DISABLE KEYS */;
INSERT INTO `cap_inscriptos` VALUES (1,1,'Jimena Perez');
/*!40000 ALTER TABLE `cap_inscriptos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `capacitaciones`
--

DROP TABLE IF EXISTS `capacitaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `capacitaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `lugar` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `capacitaciones`
--

LOCK TABLES `capacitaciones` WRITE;
/*!40000 ALTER TABLE `capacitaciones` DISABLE KEYS */;
INSERT INTO `capacitaciones` VALUES (1,'RCP','2026-06-19','13:45:00','SUM');
/*!40000 ALTER TABLE `capacitaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catedras`
--

DROP TABLE IF EXISTS `catedras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catedras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_nombre` varchar(100) NOT NULL,
  `nivel` varchar(20) NOT NULL,
  `curso` varchar(50) NOT NULL,
  `division` varchar(5) NOT NULL,
  `materia` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catedras`
--

LOCK TABLES `catedras` WRITE;
/*!40000 ALTER TABLE `catedras` DISABLE KEYS */;
INSERT INTO `catedras` VALUES (1,'Jimena Perez','secundaria','5_anio','C','Matem?tica'),(2,'Jimena Perez','secundaria','5_anio','B','Matem?tica'),(3,'Joaquin Rostan','secundaria','5_anio','B','Preceptor/a'),(4,'Joaquin Rostan','secundaria','5_anio','C','Preceptor/a'),(5,'Javier Gomez','secundaria','5_anio','A','F?sica');
/*!40000 ALTER TABLE `catedras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_nombre` varchar(255) NOT NULL,
  `tipo_doc` varchar(100) NOT NULL,
  `fecha` varchar(50) NOT NULL,
  `archivo` varchar(500) NOT NULL,
  `estado` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alumno` (`alumno_nombre`),
  KEY `idx_tipo` (`tipo_doc`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` VALUES (1,'Jose Ojeda','Certificado Medico / Justificacion de Falta','18-06-2026','uploads/1781752596_2.jpg','aprobado');
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matricula`
--

DROP TABLE IF EXISTS `matricula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matricula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_nombre` varchar(100) NOT NULL,
  `curso_clave` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matricula`
--

LOCK TABLES `matricula` WRITE;
/*!40000 ALTER TABLE `matricula` DISABLE KEYS */;
INSERT INTO `matricula` VALUES (10,'Jose Ojeda','5_anio_C'),(11,'Alexis Mongelo','5_anio_B');
/*!40000 ALTER TABLE `matricula` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_comedor`
--

DROP TABLE IF EXISTS `menu_comedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_comedor` (
  `id` int(11) NOT NULL DEFAULT 1,
  `plato_principal` varchar(255) NOT NULL DEFAULT '',
  `opcion_vegetariana` varchar(255) NOT NULL DEFAULT '',
  `postre` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_comedor`
--

LOCK TABLES `menu_comedor` WRITE;
/*!40000 ALTER TABLE `menu_comedor` DISABLE KEYS */;
INSERT INTO `menu_comedor` VALUES (1,'aaa','aaa','aaaa');
/*!40000 ALTER TABLE `menu_comedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recuperaciones`
--

DROP TABLE IF EXISTS `recuperaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(100) NOT NULL,
  `fecha` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recuperaciones`
--

LOCK TABLES `recuperaciones` WRITE;
/*!40000 ALTER TABLE `recuperaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `recuperaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservas`
--

DROP TABLE IF EXISTS `reservas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `espacio` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `profesor` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_espacio_fecha_modulo` (`espacio`,`fecha`,`modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservas`
--

LOCK TABLES `reservas` WRITE;
/*!40000 ALTER TABLE `reservas` DISABLE KEYS */;
INSERT INTO `reservas` VALUES (1,'laboratorio','2026-06-19','2? M?dulo (09:30 - 10:50)','Jimena Perez');
/*!40000 ALTER TABLE `reservas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rrhh_postulaciones`
--

DROP TABLE IF EXISTS `rrhh_postulaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rrhh_postulaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `area_interes` varchar(50) NOT NULL,
  `ruta_cv` varchar(255) NOT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  `fecha_entrevista` date DEFAULT NULL,
  `hora_entrevista` time DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rrhh_postulaciones`
--

LOCK TABLES `rrhh_postulaciones` WRITE;
/*!40000 ALTER TABLE `rrhh_postulaciones` DISABLE KEYS */;
INSERT INTO `rrhh_postulaciones` VALUES (4,'Javier Gomez','javiergomez2001@gmail.com','36246545645','Docente Nivel Primario','uploads/cvs/cv_6a386e17e6b78.pdf','pendiente',NULL,NULL,'2026-06-21 23:04:55');
/*!40000 ALTER TABLE `rrhh_postulaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taller_inscriptos`
--

DROP TABLE IF EXISTS `taller_inscriptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taller_inscriptos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taller_id` int(11) NOT NULL,
  `alumno_nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_taller_alumno` (`taller_id`,`alumno_nombre`),
  CONSTRAINT `taller_inscriptos_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taller_inscriptos`
--

LOCK TABLES `taller_inscriptos` WRITE;
/*!40000 ALTER TABLE `taller_inscriptos` DISABLE KEYS */;
INSERT INTO `taller_inscriptos` VALUES (1,1,'Alexis Mongelo');
/*!40000 ALTER TABLE `taller_inscriptos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `talleres`
--

DROP TABLE IF EXISTS `talleres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `talleres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `nivel` varchar(100) DEFAULT '',
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `lugar` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `talleres`
--

LOCK TABLES `talleres` WRITE;
/*!40000 ALTER TABLE `talleres` DISABLE KEYS */;
INSERT INTO `talleres` VALUES (1,'Futbol 5','sub 18','2026-06-19','08:00:00','Cancha Futbol 5');
/*!40000 ALTER TABLE `talleres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tutores_alumnos`
--

DROP TABLE IF EXISTS `tutores_alumnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutores_alumnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tutor_username` varchar(100) NOT NULL,
  `alumno_nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tutor_alumno` (`tutor_username`,`alumno_nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tutores_alumnos`
--

LOCK TABLES `tutores_alumnos` WRITE;
/*!40000 ALTER TABLE `tutores_alumnos` DISABLE KEYS */;
INSERT INTO `tutores_alumnos` VALUES (1,'gmongelo','Alexis Mongelo'),(2,'gmongelo','Jose Ojeda');
/*!40000 ALTER TABLE `tutores_alumnos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'admin',
  `dni` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `tutor_nombre` varchar(100) DEFAULT NULL,
  `tutor_telefono` varchar(20) DEFAULT NULL,
  `tutor_email` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (2,'admin','Administrador','$2y$10$2dfWdoVLuc8z/Mp6AvIz6uP1Wk6Ez3FPedkrQ6Yeciq7hzuEtTkJ.','admin',NULL,NULL,NULL,NULL,NULL,'2026-06-17 22:26:59'),(4,'jperez','Jimena Perez','$2y$10$HTW2jfl4l2R.HQB5Nr/4MOZTaMWDW/Ri0ZtuAnBZ/SZQZRRttnnq6','profesor',NULL,NULL,NULL,NULL,NULL,'2026-06-17 23:19:02'),(8,'amongelo','Alexis Mongelo','$2y$10$t0OCZC2uE4R8HIw.RsyKE.Ud4DjIFY5v6AwKWOuP/DbDz2xQWkwrK','alumno','43335000','2025-12-30','Marta Gomez','545464546456','gdgfddfg@dtggdf','2026-06-17 23:29:24'),(10,'jrostan','Joaquin Rostan','$2y$10$NKmS0KbHakwD9W.gh.Kdee1ut7OYPMVvN/4fOUR9/.jDof5BoAv8e','preceptor',NULL,NULL,NULL,NULL,NULL,'2026-06-17 23:47:36'),(11,'gmongelo','Gustavo Mongelo','$2y$10$gPfoqkzbreOcAYCnHdaOKuzCsFTA7H/KYCa.D/VLR4cRAW01A9fAy','tutor',NULL,NULL,NULL,NULL,NULL,'2026-06-18 01:09:20'),(12,'jojeda','Jose Ojeda','$2y$10$bHBbfIZdDOpGkkgsgJ1UHezHFzq3dtjc3rh/Csnpu9A/MbfGLSeZu','alumno','43335000','2014-05-26','jjhjik','545464546456','correo@ejemplo','2026-06-18 01:10:33'),(13,'jgomez','Javier Gomez','$2y$10$8ZerjSVLvcIn4EDKzPJWyu03A2lzeMGenXJ0zMFQDSMq8b01HivYe','profesor',NULL,NULL,NULL,NULL,NULL,'2026-06-18 04:22:44');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-21 23:36:31
