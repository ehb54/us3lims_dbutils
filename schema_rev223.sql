--
-- ------------------------------------------------------
-- Server version	10.3.28-MariaDB

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
-- Table structure for table `2DSA_CG_Settings`
--

DROP TABLE IF EXISTS `2DSA_CG_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `2DSA_CG_Settings` (
  `2DSA_CG_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `CG_modelID` int(11) NOT NULL,
  `uniform_grid` int(11) NOT NULL DEFAULT 6,
  `mc_iterations` int(11) NOT NULL DEFAULT 1,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `regularization` int(11) NOT NULL DEFAULT 0,
  `meniscus_range` double NOT NULL DEFAULT 0.01,
  `meniscus_points` double NOT NULL DEFAULT 3,
  `max_iterations` int(11) NOT NULL DEFAULT 1,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`2DSA_CG_SettingsID`),
  KEY `ndx_2DSA_CG_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_2DSA_CG_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `2DSA_MW_Settings`
--

DROP TABLE IF EXISTS `2DSA_MW_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `2DSA_MW_Settings` (
  `2DSA_MW_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `mw_min` double NOT NULL DEFAULT 100,
  `mw_max` double NOT NULL DEFAULT 1000,
  `grid_resolution` int(11) NOT NULL DEFAULT 10,
  `oligomer` int(11) NOT NULL DEFAULT 4,
  `ff0_min` double NOT NULL DEFAULT 1,
  `ff0_max` double NOT NULL DEFAULT 4,
  `ff0_resolution` int(11) NOT NULL DEFAULT 10,
  `uniform_grid` int(11) NOT NULL DEFAULT 6,
  `montecarlo_value` int(11) NOT NULL DEFAULT 0,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `regularization` int(11) NOT NULL DEFAULT 0,
  `meniscus_value` double NOT NULL DEFAULT 0.01,
  `meniscus_points` int(11) NOT NULL DEFAULT 3,
  `iterations_value` int(11) NOT NULL DEFAULT 3,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`2DSA_MW_SettingsID`),
  KEY `ndx_2DSA_MW_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_2DSA_MW_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `2DSA_Settings`
--

DROP TABLE IF EXISTS `2DSA_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `2DSA_Settings` (
  `2DSA_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `s_min` double NOT NULL DEFAULT 1,
  `s_max` double NOT NULL DEFAULT 10,
  `s_resolution` double NOT NULL DEFAULT 10,
  `ff0_min` double NOT NULL DEFAULT 1,
  `ff0_max` double NOT NULL DEFAULT 4,
  `ff0_resolution` double NOT NULL DEFAULT 10,
  `uniform_grid` int(11) NOT NULL DEFAULT 6,
  `mc_iterations` int(11) NOT NULL DEFAULT 1,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `regularization` int(11) NOT NULL DEFAULT 0,
  `meniscus_range` double NOT NULL DEFAULT 0.01,
  `meniscus_points` double NOT NULL DEFAULT 3,
  `max_iterations` int(11) NOT NULL DEFAULT 1,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`2DSA_SettingsID`),
  KEY `ndx_2DSA_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_2DSA_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DMGA_Settings`
--

DROP TABLE IF EXISTS `DMGA_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DMGA_Settings` (
  `DMGA_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `DC_modelID` int(11) NOT NULL,
  `mc_iterations` int(11) NOT NULL DEFAULT 0,
  `demes` int(11) NOT NULL DEFAULT 31,
  `population` int(11) NOT NULL DEFAULT 100,
  `generations` int(11) NOT NULL DEFAULT 100,
  `mutation` int(11) NOT NULL DEFAULT 50,
  `crossover` int(11) NOT NULL DEFAULT 50,
  `plague` int(11) NOT NULL DEFAULT 4,
  `elitism` int(11) NOT NULL DEFAULT 2,
  `migration` int(11) NOT NULL DEFAULT 3,
  `p_grid` int(11) NOT NULL DEFAULT 500,
  `seed` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`DMGA_SettingsID`),
  KEY `ndx_DMGA_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_DMGA_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GA_MW_Settings`
--

DROP TABLE IF EXISTS `GA_MW_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GA_MW_Settings` (
  `GA_MW_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `mw_min` double NOT NULL DEFAULT 100,
  `mw_max` double NOT NULL DEFAULT 1000,
  `oligomer` int(11) NOT NULL DEFAULT 4,
  `ff0_min` double NOT NULL DEFAULT 1,
  `ff0_max` double NOT NULL DEFAULT 4,
  `montecarlo_value` int(11) NOT NULL DEFAULT 0,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `demes_value` int(11) NOT NULL DEFAULT 31,
  `genes_value` int(11) NOT NULL DEFAULT 100,
  `generations_value` int(11) NOT NULL DEFAULT 100,
  `crossover_value` int(11) NOT NULL DEFAULT 50,
  `mutation_value` int(11) NOT NULL DEFAULT 50,
  `plague_value` int(11) NOT NULL DEFAULT 4,
  `elitism_value` int(11) NOT NULL DEFAULT 2,
  `migration_value` int(11) NOT NULL DEFAULT 3,
  `regularization_value` double NOT NULL DEFAULT 5,
  `seed_value` int(11) NOT NULL DEFAULT 0,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `meniscus_value` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`GA_MW_SettingsID`),
  KEY `ndx_GA_MW_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_GA_MW_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GA_SC_Settings`
--

DROP TABLE IF EXISTS `GA_SC_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GA_SC_Settings` (
  `GA_SC_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `montecarlo_value` int(11) NOT NULL DEFAULT 0,
  `demes_value` int(11) NOT NULL DEFAULT 31,
  `genes_value` int(11) NOT NULL DEFAULT 100,
  `generations_value` int(11) NOT NULL DEFAULT 100,
  `crossover_value` int(11) NOT NULL DEFAULT 50,
  `mutation_value` int(11) NOT NULL DEFAULT 50,
  `plague_value` int(11) NOT NULL DEFAULT 4,
  `elitism_value` int(11) NOT NULL DEFAULT 2,
  `migration_value` int(11) NOT NULL DEFAULT 3,
  `regularization_value` double NOT NULL DEFAULT 5,
  `seed_value` int(11) NOT NULL DEFAULT 0,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `meniscus_option` tinyint(1) NOT NULL DEFAULT 0,
  `meniscus_value` double NOT NULL DEFAULT 0,
  `constraint_data` mediumtext DEFAULT NULL,
  PRIMARY KEY (`GA_SC_SettingsID`),
  KEY `ndx_GA_SC_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_GA_SC_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GA_Settings`
--

DROP TABLE IF EXISTS `GA_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GA_Settings` (
  `GA_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `montecarlo_value` int(11) NOT NULL DEFAULT 0,
  `demes_value` int(11) NOT NULL DEFAULT 31,
  `genes_value` int(11) NOT NULL DEFAULT 100,
  `generations_value` int(11) NOT NULL DEFAULT 100,
  `crossover_value` int(11) NOT NULL DEFAULT 50,
  `mutation_value` int(11) NOT NULL DEFAULT 50,
  `plague_value` int(11) NOT NULL DEFAULT 4,
  `elitism_value` int(11) NOT NULL DEFAULT 2,
  `migration_value` int(11) NOT NULL DEFAULT 3,
  `regularization_value` double NOT NULL DEFAULT 5,
  `seed_value` int(11) NOT NULL DEFAULT 0,
  `conc_threshold` float NOT NULL DEFAULT 0.000001,
  `s_grid` int(11) NOT NULL DEFAULT 100,
  `k_grid` int(11) NOT NULL DEFAULT 100,
  `mutate_sigma` float NOT NULL DEFAULT 2,
  `mutate_s` int(11) NOT NULL DEFAULT 20,
  `mutate_k` int(11) NOT NULL DEFAULT 20,
  `mutate_sk` int(11) NOT NULL DEFAULT 20,
  PRIMARY KEY (`GA_SettingsID`),
  KEY `ndx_GA_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_GA_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCAnalysisRequest`
--

DROP TABLE IF EXISTS `HPCAnalysisRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCAnalysisRequest` (
  `HPCAnalysisRequestID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestGUID` char(36) NOT NULL,
  `investigatorGUID` char(36) NOT NULL,
  `submitterGUID` char(36) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `experimentID` int(11) DEFAULT NULL,
  `requestXMLFile` longtext DEFAULT NULL,
  `editXMLFilename` varchar(255) NOT NULL DEFAULT '',
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `clusterName` varchar(80) DEFAULT NULL,
  `method` enum('2DSA','2DSA_CG','2DSA_MW','GA','GA_MW','GA_SC','DMGA','PCSA') NOT NULL DEFAULT '2DSA',
  `analType` text DEFAULT NULL,
  PRIMARY KEY (`HPCAnalysisRequestID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCAnalysisResult`
--

DROP TABLE IF EXISTS `HPCAnalysisResult`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCAnalysisResult` (
  `HPCAnalysisResultID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `startTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endTime` datetime DEFAULT NULL,
  `queueStatus` enum('queued','failed','running','aborted','completed') DEFAULT 'queued',
  `lastMessage` text DEFAULT NULL,
  `updateTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gfacID` text DEFAULT NULL,
  `jobfile` text DEFAULT NULL,
  `wallTime` int(11) NOT NULL DEFAULT 0,
  `CPUTime` double NOT NULL DEFAULT 0,
  `CPUCount` int(11) DEFAULT 0,
  `mgroupcount` int(11) NOT NULL DEFAULT 1,
  `max_rss` int(11) DEFAULT 0,
  `calculatedData` text DEFAULT NULL,
  `stderr` longtext DEFAULT NULL,
  `stdout` longtext DEFAULT NULL,
  PRIMARY KEY (`HPCAnalysisResultID`),
  KEY `ndx_HPCAnalysisResult_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_HPCAnalysisResult_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCAnalysisResultData`
--

DROP TABLE IF EXISTS `HPCAnalysisResultData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCAnalysisResultData` (
  `HPCAnalysisResultDataID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisResultID` int(11) NOT NULL,
  `HPCAnalysisResultType` enum('model','noise','job_stats','mrecs','unknown') NOT NULL DEFAULT 'unknown',
  `resultID` int(11) NOT NULL,
  PRIMARY KEY (`HPCAnalysisResultDataID`),
  KEY `ndx_HPCAnalysisResultData_HPCAnalysisResultID` (`HPCAnalysisResultID`),
  CONSTRAINT `fk_HPCAnalysisResultData_HPCAnalysisResultID` FOREIGN KEY (`HPCAnalysisResultID`) REFERENCES `HPCAnalysisResult` (`HPCAnalysisResultID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCDataset`
--

DROP TABLE IF EXISTS `HPCDataset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCDataset` (
  `HPCDatasetID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `editedDataID` int(11) DEFAULT NULL,
  `simpoints` int(11) DEFAULT NULL,
  `band_volume` double DEFAULT NULL,
  `radial_grid` tinyint(4) DEFAULT NULL,
  `time_grid` tinyint(4) DEFAULT NULL,
  `rotor_stretch` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`HPCDatasetID`),
  KEY `ndx_HPCDataset_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  KEY `ndx_HPCDataset_editedDataID` (`editedDataID`),
  CONSTRAINT `fk_HPCDataset_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_HPCDataset_editedDataID` FOREIGN KEY (`editedDataID`) REFERENCES `editedData` (`editedDataID`) ON DELETE SET NULL ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCRequestData`
--

DROP TABLE IF EXISTS `HPCRequestData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCRequestData` (
  `HPCRequestDataID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCDatasetID` int(11) NOT NULL,
  `noiseID` int(11) DEFAULT NULL,
  PRIMARY KEY (`HPCRequestDataID`),
  KEY `ndx_HPCRequestData_HPCDatasetID` (`HPCDatasetID`),
  KEY `ndx_HPCRequestData_noiseID` (`noiseID`),
  CONSTRAINT `fk_HPCRequestData_HPCDatasetID` FOREIGN KEY (`HPCDatasetID`) REFERENCES `HPCDataset` (`HPCDatasetID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_HPCRequestData_noiseID` FOREIGN KEY (`noiseID`) REFERENCES `noise` (`noiseID`) ON DELETE SET NULL ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HPCSoluteData`
--

DROP TABLE IF EXISTS `HPCSoluteData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HPCSoluteData` (
  `HPCSoluteDataID` int(11) NOT NULL AUTO_INCREMENT,
  `GA_SettingsID` int(11) NOT NULL,
  `s_min` double NOT NULL DEFAULT 0,
  `s_max` double NOT NULL DEFAULT 0,
  `ff0_min` double NOT NULL DEFAULT 0,
  `ff0_max` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`HPCSoluteDataID`),
  KEY `ndx_HPCSoluteData_GA_SettingsID` (`GA_SettingsID`),
  CONSTRAINT `fk_HPCSoluteData_GA_SettingsID` FOREIGN KEY (`GA_SettingsID`) REFERENCES `GA_Settings` (`GA_SettingsID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PCSA_Settings`
--

DROP TABLE IF EXISTS `PCSA_Settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PCSA_Settings` (
  `PCSA_SettingsID` int(11) NOT NULL AUTO_INCREMENT,
  `HPCAnalysisRequestID` int(11) NOT NULL,
  `curve_type` varchar(8) NOT NULL DEFAULT 'SL',
  `s_min` double NOT NULL DEFAULT 1,
  `s_max` double NOT NULL DEFAULT 10,
  `ff0_min` double NOT NULL DEFAULT 1,
  `ff0_max` double NOT NULL DEFAULT 4,
  `vars_count` int(11) NOT NULL DEFAULT 10,
  `gfit_iterations` int(11) NOT NULL DEFAULT 3,
  `curves_points` int(11) NOT NULL DEFAULT 200,
  `thr_deltr_ratio` double NOT NULL DEFAULT 0.0001,
  `tikreg_option` tinyint(1) NOT NULL DEFAULT 0,
  `tikreg_alpha` double NOT NULL DEFAULT 0,
  `mc_iterations` int(11) NOT NULL DEFAULT 1,
  `tinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  `rinoise_option` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`PCSA_SettingsID`),
  KEY `ndx_PCSA_Settings_HPCAnalysisRequestID` (`HPCAnalysisRequestID`),
  CONSTRAINT `fk_PCSA_Settings_HPCAnalysisRequestID` FOREIGN KEY (`HPCAnalysisRequestID`) REFERENCES `HPCAnalysisRequest` (`HPCAnalysisRequestID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `abstractCenterpiece`
--

DROP TABLE IF EXISTS `abstractCenterpiece`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abstractCenterpiece` (
  `abstractCenterpieceID` int(11) NOT NULL,
  `loadMethod` enum('top','side') DEFAULT NULL,
  `abstractCenterpieceGUID` char(36) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `materialName` text DEFAULT NULL,
  `channels` int(11) NOT NULL,
  `bottom` varchar(20) NOT NULL,
  `shape` enum('standard','rectangular','circular','synthetic','band forming','meniscus matching','sector') NOT NULL DEFAULT 'standard',
  `maxRPM` int(11) DEFAULT NULL,
  `pathLength` float DEFAULT NULL,
  `angle` float DEFAULT NULL,
  `width` float DEFAULT NULL,
  `canHoldSample` int(11) DEFAULT NULL,
  `materialRefURI` text DEFAULT NULL,
  `centerpieceRefURI` text DEFAULT NULL,
  `dataUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`abstractCenterpieceID`),
  UNIQUE KEY `abstractCenterpieceID` (`abstractCenterpieceID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `abstractChannel`
--

DROP TABLE IF EXISTS `abstractChannel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abstractChannel` (
  `abstractChannelID` int(11) NOT NULL AUTO_INCREMENT,
  `channelType` enum('reference','sample') DEFAULT NULL,
  `channelShape` enum('sector','rectangular') DEFAULT NULL,
  `abstractChannelGUID` char(36) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `radialBegin` float DEFAULT NULL,
  `radialEnd` float DEFAULT NULL,
  `degreesWide` float DEFAULT NULL,
  `degreesOffset` float DEFAULT NULL,
  `radialBandTop` float DEFAULT NULL,
  `radialBandBottom` float DEFAULT NULL,
  `radialMeniscusPos` float DEFAULT NULL,
  `dateUpdated` date DEFAULT NULL,
  PRIMARY KEY (`abstractChannelID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `abstractRotor`
--

DROP TABLE IF EXISTS `abstractRotor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abstractRotor` (
  `abstractRotorID` int(11) NOT NULL,
  `abstractRotorGUID` char(36) DEFAULT NULL,
  `name` enum('Simulation','AN50','AN60','CFA') DEFAULT NULL,
  `materialName` enum('Titanium','CarbonFiber','Simulation') DEFAULT NULL,
  `numHoles` int(11) DEFAULT NULL,
  `maxRPM` int(11) DEFAULT NULL,
  `magnetOffset` float DEFAULT NULL,
  `cellCenter` float DEFAULT 6.5,
  `manufacturer` enum('Beckman','SpinAnalytical','Simulation') DEFAULT NULL,
  PRIMARY KEY (`abstractRotorID`),
  UNIQUE KEY `abstractRotorID` (`abstractRotorID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `analysisprofile`
--

DROP TABLE IF EXISTS `analysisprofile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analysisprofile` (
  `aprofileID` int(11) NOT NULL AUTO_INCREMENT,
  `aprofileGUID` char(36) NOT NULL,
  `name` text NOT NULL,
  `xml` longtext NOT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`aprofileID`),
  UNIQUE KEY `aprofileGUID` (`aprofileGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `analyte`
--

DROP TABLE IF EXISTS `analyte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analyte` (
  `analyteID` int(11) NOT NULL AUTO_INCREMENT,
  `analyteGUID` char(36) NOT NULL,
  `type` enum('DNA','RNA','Protein','Other') DEFAULT 'Other',
  `sequence` text DEFAULT NULL,
  `vbar` float DEFAULT NULL,
  `description` text DEFAULT NULL,
  `spectrum` text DEFAULT NULL,
  `molecularWeight` float DEFAULT NULL,
  `gradientForming` tinyint(1) DEFAULT 0,
  `doubleStranded` tinyint(1) DEFAULT 0,
  `complement` tinyint(1) DEFAULT 0,
  `_3prime` tinyint(1) DEFAULT 0,
  `_5prime` tinyint(1) DEFAULT 0,
  `sodium` double DEFAULT 0,
  `potassium` double DEFAULT 0,
  `lithium` double DEFAULT 0,
  `magnesium` double DEFAULT 0,
  `calcium` double DEFAULT 0,
  PRIMARY KEY (`analyteID`),
  UNIQUE KEY `analyteGUID` (`analyteGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `analytePerson`
--

DROP TABLE IF EXISTS `analytePerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analytePerson` (
  `analyteID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  PRIMARY KEY (`analyteID`),
  KEY `ndx_analytePerson_personID` (`personID`),
  KEY `ndx_analytePerson_analyteID` (`analyteID`),
  CONSTRAINT `fk_analytePerson_analyteID` FOREIGN KEY (`analyteID`) REFERENCES `analyte` (`analyteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_analytePerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflow`
--

DROP TABLE IF EXISTS `autoflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflow` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `protName` varchar(80) DEFAULT NULL,
  `cellChNum` varchar(80) DEFAULT NULL,
  `tripleNum` varchar(80) DEFAULT NULL,
  `duration` int(10) DEFAULT NULL,
  `runName` varchar(300) DEFAULT NULL,
  `expID` int(10) DEFAULT NULL,
  `runID` int(10) DEFAULT NULL,
  `status` enum('LIVE_UPDATE','EDITING','EDIT_DATA','ANALYSIS','REPORT') NOT NULL,
  `dataPath` varchar(300) DEFAULT NULL,
  `optimaName` varchar(300) DEFAULT NULL,
  `runStarted` timestamp NULL DEFAULT NULL,
  `invID` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `corrRadii` enum('YES','NO') NOT NULL,
  `expAborted` enum('NO','YES') NOT NULL,
  `label` varchar(80) DEFAULT NULL,
  `gmpRun` enum('NO','YES') NOT NULL,
  `filename` varchar(300) DEFAULT NULL,
  `aprofileGUID` varchar(80) DEFAULT NULL,
  `analysisIDs` longtext DEFAULT NULL,
  PRIMARY KEY (`ID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowAnalysis`
--

DROP TABLE IF EXISTS `autoflowAnalysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowAnalysis` (
  `requestID` int(11) NOT NULL AUTO_INCREMENT,
  `tripleName` text NOT NULL,
  `clusterDefault` text DEFAULT 'localhost',
  `filename` text NOT NULL,
  `aprofileGUID` char(36) NOT NULL,
  `invID` int(11) NOT NULL,
  `currentGfacID` varchar(80) DEFAULT NULL,
  `currentHPCARID` int(11) DEFAULT NULL,
  `statusJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` text DEFAULT 'unknown',
  `statusMsg` text DEFAULT '',
  `nextWaitStatus` text DEFAULT NULL,
  `nextWaitStatusMsg` text DEFAULT NULL,
  `stageSubmitTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `updateTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `createUser` varchar(128) DEFAULT current_user(),
  `updateUser` varchar(128) DEFAULT '',
  PRIMARY KEY (`requestID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowAnalysisHistory`
--

DROP TABLE IF EXISTS `autoflowAnalysisHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowAnalysisHistory` (
  `requestID` int(11) NOT NULL,
  `tripleName` text NOT NULL,
  `clusterDefault` text DEFAULT 'localhost',
  `filename` text NOT NULL,
  `aprofileGUID` char(36) NOT NULL,
  `invID` int(11) NOT NULL,
  `currentGfacID` varchar(80) DEFAULT NULL,
  `currentHPCARID` int(11) DEFAULT NULL,
  `statusJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` text DEFAULT 'unknown',
  `statusMsg` text DEFAULT '',
  `nextWaitStatus` text DEFAULT NULL,
  `nextWaitStatusMsg` text DEFAULT NULL,
  `stageSubmitTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `updateTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `createUser` varchar(128) DEFAULT current_user(),
  `updateUser` varchar(128) DEFAULT '',
  PRIMARY KEY (`requestID`),
  UNIQUE KEY `requestID` (`requestID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowAnalysisStages`
--

DROP TABLE IF EXISTS `autoflowAnalysisStages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowAnalysisStages` (
  `requestID` int(11) NOT NULL,
  `analysisFitmen` text DEFAULT 'unknown',
  PRIMARY KEY (`requestID`),
  UNIQUE KEY `requestID` (`requestID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowReport`
--

DROP TABLE IF EXISTS `autoflowReport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowReport` (
  `reportID` int(11) NOT NULL AUTO_INCREMENT,
  `reportGUID` varchar(80) DEFAULT NULL,
  `channelName` varchar(80) DEFAULT NULL,
  `totalConc` float DEFAULT NULL,
  `totalConcTol` float DEFAULT NULL,
  `rmsdLimit` float DEFAULT NULL,
  `avIntensity` float DEFAULT NULL,
  `expDuration` int(10) DEFAULT NULL,
  `expDurationTol` float DEFAULT NULL,
  `wavelength` int(10) DEFAULT NULL,
  `triplesDropped` longtext DEFAULT 'none',
  PRIMARY KEY (`reportID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowReportItem`
--

DROP TABLE IF EXISTS `autoflowReportItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowReportItem` (
  `reportItemID` int(11) NOT NULL AUTO_INCREMENT,
  `reportGUID` varchar(80) NOT NULL,
  `reportID` int(11) NOT NULL,
  `type` enum('s','D','f','f/f0','MW') NOT NULL,
  `method` enum('2DSA-IT','PCSA-SL/DS/IS','2DSA-MC','raw') NOT NULL,
  `rangeLow` float DEFAULT NULL,
  `rangeHi` float DEFAULT NULL,
  `integration` float DEFAULT NULL,
  `tolerance` float DEFAULT NULL,
  `totalPercent` float DEFAULT NULL,
  PRIMARY KEY (`reportItemID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `autoflowStages`
--

DROP TABLE IF EXISTS `autoflowStages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoflowStages` (
  `autoflowID` int(11) NOT NULL,
  `liveUpdate` text DEFAULT 'unknown',
  `import` text DEFAULT 'unknown',
  `editing` text DEFAULT 'unknown',
  PRIMARY KEY (`autoflowID`),
  UNIQUE KEY `autoflowID` (`autoflowID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `avivFluorescence`
--

DROP TABLE IF EXISTS `avivFluorescence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avivFluorescence` (
  `avivFluorescenceID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `topRadius` float DEFAULT NULL,
  `bottomRadius` float DEFAULT NULL,
  `mmStepSize` float DEFAULT NULL,
  `replicates` int(11) DEFAULT NULL,
  `nmExcitation` float DEFAULT NULL,
  `nmEmission` float DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`avivFluorescenceID`),
  KEY `ndx_avivFluorescence_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beckmanInterference`
--

DROP TABLE IF EXISTS `beckmanInterference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beckmanInterference` (
  `beckmanInterferenceID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `topRadius` float DEFAULT NULL,
  `bottomRadius` float DEFAULT NULL,
  `pixelsPerFringe` int(11) DEFAULT NULL,
  `numberOfFringes` int(11) DEFAULT NULL,
  `radialIncrement` float DEFAULT NULL,
  `cameraGain` float DEFAULT NULL,
  `cameraOffset` float DEFAULT NULL,
  `cameraGamma` float DEFAULT NULL,
  `barAngleDegrees` float DEFAULT NULL,
  `startingRow` int(11) DEFAULT NULL,
  `endingRow` int(11) DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`beckmanInterferenceID`),
  KEY `ndx_beckmanInterference_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beckmanRadialAbsorbance`
--

DROP TABLE IF EXISTS `beckmanRadialAbsorbance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beckmanRadialAbsorbance` (
  `beckmanRadialAbsorbanceID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `topRadius` float DEFAULT NULL,
  `bottomRadius` float DEFAULT NULL,
  `mmStepSize` float DEFAULT NULL,
  `replicates` int(11) DEFAULT NULL,
  `isContinuousMode` tinyint(1) DEFAULT NULL,
  `isIntensity` tinyint(1) DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`beckmanRadialAbsorbanceID`),
  KEY `ndx_beckmanRadialAbsorbance_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `beckmanWavelengthAbsorbance`
--

DROP TABLE IF EXISTS `beckmanWavelengthAbsorbance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beckmanWavelengthAbsorbance` (
  `beckmanWavelengthAbsorbanceID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `radialPosition` float DEFAULT NULL,
  `startWavelength` float DEFAULT NULL,
  `endWavelength` float DEFAULT NULL,
  `nmStepsize` float DEFAULT NULL,
  `replicates` int(11) DEFAULT NULL,
  `isIntensity` tinyint(1) DEFAULT NULL,
  `secondsBetween` int(11) DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`beckmanWavelengthAbsorbanceID`),
  KEY `ndx_beckmanWavelengthAbsorbance_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `buffer`
--

DROP TABLE IF EXISTS `buffer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buffer` (
  `bufferID` int(11) NOT NULL AUTO_INCREMENT,
  `bufferGUID` char(36) NOT NULL,
  `description` text DEFAULT NULL,
  `compressibility` float DEFAULT NULL,
  `pH` float DEFAULT NULL,
  `viscosity` float DEFAULT NULL,
  `density` float DEFAULT NULL,
  `manual` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bufferID`),
  UNIQUE KEY `bufferGUID` (`bufferGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bufferComponent`
--

DROP TABLE IF EXISTS `bufferComponent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bufferComponent` (
  `bufferComponentID` int(11) NOT NULL,
  `units` varchar(16) NOT NULL DEFAULT 'mM',
  `description` text DEFAULT NULL,
  `viscosity` text DEFAULT NULL,
  `density` text DEFAULT NULL,
  `c_range` text DEFAULT NULL,
  `gradientForming` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`bufferComponentID`),
  UNIQUE KEY `bufferComponentID` (`bufferComponentID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bufferLink`
--

DROP TABLE IF EXISTS `bufferLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bufferLink` (
  `bufferID` int(11) NOT NULL,
  `bufferComponentID` int(11) NOT NULL,
  `concentration` float DEFAULT NULL,
  KEY `ndx_bufferLink_bufferID` (`bufferID`),
  KEY `ndx_bufferLink_bufferComponentID` (`bufferComponentID`),
  CONSTRAINT `fk_bufferLink_bufferComponentID` FOREIGN KEY (`bufferComponentID`) REFERENCES `bufferComponent` (`bufferComponentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bufferLink_bufferID` FOREIGN KEY (`bufferID`) REFERENCES `buffer` (`bufferID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bufferPerson`
--

DROP TABLE IF EXISTS `bufferPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bufferPerson` (
  `bufferID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  `private` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`bufferID`),
  KEY `ndx_bufferPerson_personID` (`personID`),
  KEY `ndx_bufferPerson_bufferID` (`bufferID`),
  CONSTRAINT `fk_bufferPerson_bufferID` FOREIGN KEY (`bufferID`) REFERENCES `buffer` (`bufferID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bufferPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cell`
--

DROP TABLE IF EXISTS `cell`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cell` (
  `cellID` int(11) NOT NULL AUTO_INCREMENT,
  `cellType` enum('counterbalance','sample','reference') DEFAULT NULL,
  `windowType` enum('quartz','saffire') DEFAULT NULL,
  `abstractCenterpieceID` int(11) DEFAULT NULL,
  `experimentID` int(11) DEFAULT NULL,
  `cellGUID` char(36) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `holeNumber` int(11) DEFAULT NULL,
  `housing` text DEFAULT NULL,
  `centerpieceSerialNumber` text DEFAULT NULL,
  `numChannels` int(11) DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cellID`),
  KEY `ndx_cell_abstractCenterpieceID` (`abstractCenterpieceID`),
  KEY `ndx_cell_experimentID` (`experimentID`),
  CONSTRAINT `fk_cell_abstractCenterpieceID` FOREIGN KEY (`abstractCenterpieceID`) REFERENCES `abstractCenterpiece` (`abstractCenterpieceID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cell_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE SET NULL ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `channel`
--

DROP TABLE IF EXISTS `channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel` (
  `channelID` int(11) NOT NULL AUTO_INCREMENT,
  `abstractChannelID` int(11) DEFAULT NULL,
  `channelGUID` char(36) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`channelID`),
  KEY `ndx_channel_abstractChannelID` (`abstractChannelID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documentLink`
--

DROP TABLE IF EXISTS `documentLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentLink` (
  `documentLinkID` int(11) NOT NULL AUTO_INCREMENT,
  `reportTripleID` int(11) NOT NULL,
  `reportDocumentID` int(11) NOT NULL,
  PRIMARY KEY (`documentLinkID`),
  KEY `ndx_documentLink_reportTripleID` (`reportTripleID`),
  KEY `ndx_documentLink_reportDocumentID` (`reportDocumentID`),
  CONSTRAINT `fk_documentLink_reportDocumentID` FOREIGN KEY (`reportDocumentID`) REFERENCES `reportDocument` (`reportDocumentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_documentLink_reportTripleID` FOREIGN KEY (`reportTripleID`) REFERENCES `reportTriple` (`reportTripleID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `editedData`
--

DROP TABLE IF EXISTS `editedData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `editedData` (
  `editedDataID` int(11) NOT NULL AUTO_INCREMENT,
  `rawDataID` int(11) DEFAULT NULL,
  `editGUID` char(36) NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT '',
  `data` longblob DEFAULT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `comment` text DEFAULT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`editedDataID`),
  UNIQUE KEY `editGUID` (`editGUID`),
  KEY `ndx_editedData_rawDataID` (`rawDataID`),
  CONSTRAINT `fk_editedData_rawDataID` FOREIGN KEY (`rawDataID`) REFERENCES `rawData` (`rawDataID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experiment`
--

DROP TABLE IF EXISTS `experiment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `experiment` (
  `experimentID` int(11) NOT NULL AUTO_INCREMENT,
  `projectID` int(11) DEFAULT NULL,
  `runID` varchar(80) NOT NULL,
  `labID` int(11) DEFAULT NULL,
  `instrumentID` int(11) NOT NULL,
  `operatorID` int(11) DEFAULT NULL,
  `rotorID` int(11) DEFAULT NULL,
  `rotorCalibrationID` int(11) DEFAULT NULL,
  `experimentGUID` char(36) DEFAULT NULL,
  `type` enum('velocity','equilibrium','diffusion','buoyancy','calibration','other') DEFAULT 'velocity',
  `runType` enum('RA','RI','IP','FI','WA','WI','RI+IP','RI+FI','IP+FI','RI+IP+FI') DEFAULT NULL,
  `dateBegin` date NOT NULL,
  `runTemp` float DEFAULT NULL,
  `label` varchar(80) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `RIProfile` longtext DEFAULT NULL,
  `protocolGUID` char(36) DEFAULT NULL,
  `centrifugeProtocol` text DEFAULT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`experimentID`),
  UNIQUE KEY `experimentGUID` (`experimentGUID`),
  KEY `ndx_experiment_projectID` (`projectID`),
  KEY `ndx_experiment_operatorID` (`operatorID`),
  KEY `ndx_experiment_instrumentID` (`instrumentID`),
  KEY `ndx_experiment_labID` (`labID`),
  KEY `ndx_experiment_rotorID` (`rotorID`),
  KEY `ndx_experiment_calibrationID` (`rotorCalibrationID`),
  CONSTRAINT `fk_experiment_calibrationID` FOREIGN KEY (`rotorCalibrationID`) REFERENCES `rotorCalibration` (`rotorCalibrationID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_experiment_instrumentID` FOREIGN KEY (`instrumentID`) REFERENCES `instrument` (`instrumentID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_experiment_labID` FOREIGN KEY (`labID`) REFERENCES `lab` (`labID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_experiment_operatorID` FOREIGN KEY (`operatorID`) REFERENCES `people` (`personID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_experiment_projectID` FOREIGN KEY (`projectID`) REFERENCES `project` (`projectID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_experiment_rotorID` FOREIGN KEY (`rotorID`) REFERENCES `rotor` (`rotorID`) ON DELETE SET NULL ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experimentPerson`
--

DROP TABLE IF EXISTS `experimentPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `experimentPerson` (
  `experimentID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  KEY `ndx_experimentPerson_experimentID` (`experimentID`),
  KEY `ndx_experimentPerson_personID` (`personID`),
  CONSTRAINT `fk_experimentPerson_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experimentPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experimentProtocol`
--

DROP TABLE IF EXISTS `experimentProtocol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `experimentProtocol` (
  `experimentID` int(11) NOT NULL,
  `protocolID` int(11) NOT NULL,
  KEY `ndx_experimentProtocol_protocolID` (`protocolID`),
  KEY `ndx_experimentProtocol_experimentID` (`experimentID`),
  CONSTRAINT `fk_experimentProtocol_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_experimentProtocol_protocolID` FOREIGN KEY (`protocolID`) REFERENCES `protocol` (`protocolID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `experimentSolutionChannel`
--

DROP TABLE IF EXISTS `experimentSolutionChannel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `experimentSolutionChannel` (
  `experimentID` int(11) NOT NULL,
  `solutionID` int(11) NOT NULL,
  `channelID` int(11) NOT NULL,
  KEY `ndx_experimentSolutionChannel_experimentID` (`experimentID`),
  KEY `ndx_experimentSolutionChannel_channelID` (`channelID`),
  KEY `ndx_experimentSolutionChannel_solutionID` (`solutionID`),
  CONSTRAINT `fk_experimentSolutionChannel_channelID` FOREIGN KEY (`channelID`) REFERENCES `channel` (`channelID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_experimentSolutionChannel_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_experimentSolutionChannel_solutionID` FOREIGN KEY (`solutionID`) REFERENCES `solution` (`solutionID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extinctionProfile`
--

DROP TABLE IF EXISTS `extinctionProfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extinctionProfile` (
  `profileID` int(11) NOT NULL AUTO_INCREMENT,
  `componentID` int(11) NOT NULL,
  `componentType` enum('Buffer','Analyte','Sample') NOT NULL,
  `valueType` enum('absorbance','molarExtinction','massExtinction') NOT NULL,
  `xml` longtext DEFAULT NULL,
  PRIMARY KEY (`profileID`),
  KEY `ndx_component_ID` (`componentID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `imageID` int(11) NOT NULL AUTO_INCREMENT,
  `imageGUID` char(36) DEFAULT NULL,
  `description` varchar(80) NOT NULL DEFAULT 'No description was entered for this image',
  `gelPicture` longblob NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`imageID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageAnalyte`
--

DROP TABLE IF EXISTS `imageAnalyte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageAnalyte` (
  `imageID` int(11) NOT NULL,
  `analyteID` int(11) NOT NULL,
  PRIMARY KEY (`imageID`),
  KEY `ndx_imageAnalyte_imageID` (`imageID`),
  KEY `ndx_imageAnalyte_analyteID` (`analyteID`),
  CONSTRAINT `fk_imageAnalyte_analyteID` FOREIGN KEY (`analyteID`) REFERENCES `analyte` (`analyteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_imageAnalyte_imageID` FOREIGN KEY (`imageID`) REFERENCES `image` (`imageID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageBuffer`
--

DROP TABLE IF EXISTS `imageBuffer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageBuffer` (
  `imageID` int(11) NOT NULL,
  `bufferID` int(11) NOT NULL,
  PRIMARY KEY (`imageID`),
  KEY `ndx_imageBuffer_imageID` (`imageID`),
  KEY `ndx_imageBuffer_bufferID` (`bufferID`),
  CONSTRAINT `fk_imageBuffer_bufferID` FOREIGN KEY (`bufferID`) REFERENCES `buffer` (`bufferID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_imageBuffer_imageID` FOREIGN KEY (`imageID`) REFERENCES `image` (`imageID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagePerson`
--

DROP TABLE IF EXISTS `imagePerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagePerson` (
  `imageID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  KEY `ndx_imagePerson_personID` (`personID`),
  KEY `ndx_imagePerson_imageID` (`imageID`),
  CONSTRAINT `fk_imagePerson_imageID` FOREIGN KEY (`imageID`) REFERENCES `image` (`imageID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_imagePerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageSolution`
--

DROP TABLE IF EXISTS `imageSolution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageSolution` (
  `imageID` int(11) NOT NULL,
  `solutionID` int(11) NOT NULL,
  PRIMARY KEY (`imageID`),
  KEY `ndx_imageSolution_imageID` (`imageID`),
  KEY `ndx_imageSolution_solutionID` (`solutionID`),
  CONSTRAINT `fk_imageSolution_imageID` FOREIGN KEY (`imageID`) REFERENCES `image` (`imageID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_imageSolution_solutionID` FOREIGN KEY (`solutionID`) REFERENCES `solution` (`solutionID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `instrument`
--

DROP TABLE IF EXISTS `instrument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instrument` (
  `instrumentID` int(11) NOT NULL AUTO_INCREMENT,
  `labID` int(11) NOT NULL,
  `name` text DEFAULT NULL,
  `serialNumber` text DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  `radialCalID` int(11) NOT NULL DEFAULT 0,
  `optimaHost` text DEFAULT NULL,
  `optimaPort` int(11) DEFAULT NULL,
  `optimaDBname` text DEFAULT NULL,
  `optimaDBusername` text DEFAULT NULL,
  `optimaDBpassw` blob DEFAULT NULL,
  `chromaticAB` text DEFAULT NULL,
  `selected` tinyint(1) DEFAULT 0,
  `opsys1` enum('UV/visible','Rayleigh Interference','Fluorescense','(not installed)') NOT NULL,
  `opsys2` enum('UV/visible','Rayleigh Interference','Fluorescense','(not installed)') NOT NULL,
  `opsys3` enum('UV/visible','Rayleigh Interference','Fluorescense','(not installed)') NOT NULL,
  `RadCalWvl` int(11) DEFAULT NULL,
  `optimaPortMsg` int(11) DEFAULT NULL,
  PRIMARY KEY (`instrumentID`),
  KEY `ndx_instrument_labID` (`labID`),
  CONSTRAINT `fk_instrument_labID` FOREIGN KEY (`labID`) REFERENCES `lab` (`labID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lab`
--

DROP TABLE IF EXISTS `lab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lab` (
  `labID` int(11) NOT NULL AUTO_INCREMENT,
  `labGUID` char(36) NOT NULL,
  `name` text DEFAULT NULL,
  `building` text DEFAULT NULL,
  `room` text DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`labID`),
  UNIQUE KEY `labGUID` (`labGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model`
--

DROP TABLE IF EXISTS `model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model` (
  `modelID` int(11) NOT NULL AUTO_INCREMENT,
  `editedDataID` int(11) NOT NULL DEFAULT 1,
  `modelGUID` char(36) NOT NULL,
  `meniscus` double NOT NULL DEFAULT 0,
  `MCIteration` int(11) NOT NULL DEFAULT 1,
  `variance` double NOT NULL DEFAULT 0,
  `description` varchar(160) DEFAULT NULL,
  `xml` longtext DEFAULT NULL,
  `globalType` enum('NORMAL','MENISCUS','GLOBAL','SUPERGLOBAL') DEFAULT 'NORMAL',
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`modelID`),
  UNIQUE KEY `modelGUID` (`modelGUID`),
  KEY `ndx_model_editedDataID` (`editedDataID`),
  CONSTRAINT `fk_model_editDataID` FOREIGN KEY (`editedDataID`) REFERENCES `editedData` (`editedDataID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modelPerson`
--

DROP TABLE IF EXISTS `modelPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modelPerson` (
  `modelID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  PRIMARY KEY (`modelID`),
  KEY `ndx_modelPerson_personID` (`personID`),
  KEY `ndx_modelPerson_modelID` (`modelID`),
  CONSTRAINT `fk_modelPerson_modelID` FOREIGN KEY (`modelID`) REFERENCES `model` (`modelID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_modelPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `multiWavelengthSystem`
--

DROP TABLE IF EXISTS `multiWavelengthSystem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `multiWavelengthSystem` (
  `multiWavelengthSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `startWavelength` float DEFAULT NULL,
  `endWavelength` float DEFAULT NULL,
  `nmStepsize` float DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`multiWavelengthSystemID`),
  KEY `ndx_multiWavelengthSystem_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `noise`
--

DROP TABLE IF EXISTS `noise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `noise` (
  `noiseID` int(11) NOT NULL AUTO_INCREMENT,
  `noiseGUID` char(36) NOT NULL,
  `editedDataID` int(11) NOT NULL,
  `modelID` int(11) NOT NULL,
  `modelGUID` char(36) DEFAULT NULL,
  `noiseType` enum('ri_noise','ti_noise') DEFAULT 'ti_noise',
  `description` varchar(160) DEFAULT NULL,
  `xml` longtext DEFAULT NULL,
  `timeEntered` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`noiseID`),
  UNIQUE KEY `noiseGUID` (`noiseGUID`),
  KEY `ndx_noise_editedDataID` (`editedDataID`),
  KEY `ndx_noise_modelID` (`modelID`),
  CONSTRAINT `fk_noise_editDataID` FOREIGN KEY (`editedDataID`) REFERENCES `editedData` (`editedDataID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `opticalSystemSetting`
--

DROP TABLE IF EXISTS `opticalSystemSetting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opticalSystemSetting` (
  `opticalSystemSettingID` int(11) NOT NULL AUTO_INCREMENT,
  `rawDataID` int(11) DEFAULT NULL,
  `opticalSystemSettingGUID` char(36) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `value` float DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  `secondsDuration` int(11) DEFAULT NULL,
  `hwType` enum('fluor','wlAbsorb','interfere','radialAbs','MWL','other') DEFAULT NULL,
  `hwIndex` int(11) DEFAULT NULL,
  `channelID` int(11) DEFAULT NULL,
  PRIMARY KEY (`opticalSystemSettingID`),
  KEY `ndx_opticalSystemSetting_rawDataID` (`rawDataID`),
  KEY `ndx_opticalSystemSetting_channelID` (`channelID`),
  KEY `ndx_opticalSystemSetting_fluorescenceOpSysID` (`opticalSystemSettingID`),
  CONSTRAINT `fk_opticalSystemSetting_MWLOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `multiWavelengthSystem` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_channelID` FOREIGN KEY (`channelID`) REFERENCES `channel` (`channelID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_opticalSystemSetting_fluorescenceOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `avivFluorescence` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_interferenceOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `beckmanInterference` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_otherOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `otherOpticalSystem` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_radialOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `beckmanRadialAbsorbance` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_rawDataID` FOREIGN KEY (`rawDataID`) REFERENCES `rawData` (`rawDataID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_opticalSystemSetting_wavelengthOpSysID` FOREIGN KEY (`opticalSystemSettingID`) REFERENCES `beckmanWavelengthAbsorbance` (`opticalSystemSettingID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otherOpticalSystem`
--

DROP TABLE IF EXISTS `otherOpticalSystem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otherOpticalSystem` (
  `otherOpticalSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `opticalSystemSettingID` int(11) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`otherOpticalSystemID`),
  KEY `ndx_otherOpticalSystem_opticalSystemSettingID` (`opticalSystemSettingID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pcsa_modelrecs`
--

DROP TABLE IF EXISTS `pcsa_modelrecs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pcsa_modelrecs` (
  `mrecsID` int(11) NOT NULL AUTO_INCREMENT,
  `editedDataID` int(11) NOT NULL DEFAULT 1,
  `modelID` int(11) NOT NULL DEFAULT 0,
  `mrecsGUID` char(36) NOT NULL,
  `description` varchar(160) DEFAULT NULL,
  `xml` longtext DEFAULT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`mrecsID`),
  UNIQUE KEY `mrecsGUID` (`mrecsGUID`),
  KEY `ndx_mrecs_editedDataID` (`editedDataID`),
  CONSTRAINT `fk_mrecs_editDataID` FOREIGN KEY (`editedDataID`) REFERENCES `editedData` (`editedDataID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `people`
--

DROP TABLE IF EXISTS `people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people` (
  `personID` int(11) NOT NULL AUTO_INCREMENT,
  `personGUID` char(36) NOT NULL,
  `fname` varchar(30) DEFAULT NULL,
  `lname` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `phone` varchar(24) DEFAULT NULL,
  `email` varchar(63) NOT NULL,
  `organization` varchar(45) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `password` varchar(80) NOT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT 0,
  `signup` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastLogin` datetime DEFAULT NULL,
  `clusterAuthorizations` varchar(255) NOT NULL DEFAULT,
  `userlevel` tinyint(4) NOT NULL DEFAULT 0,
  `advancelevel` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`personID`),
  UNIQUE KEY `personGUID` (`personGUID`),
  UNIQUE KEY `email` (`email`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permits`
--

DROP TABLE IF EXISTS `permits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permits` (
  `permitID` int(11) NOT NULL AUTO_INCREMENT,
  `personID` int(11) NOT NULL,
  `collaboratorID` int(11) DEFAULT NULL,
  `instrumentID` int(11) DEFAULT NULL,
  PRIMARY KEY (`permitID`),
  KEY `ndx_permits_personID` (`personID`),
  KEY `ndx_permits_collaboratorID` (`collaboratorID`),
  KEY `ndx_permits_instrumentID` (`instrumentID`),
  CONSTRAINT `fk_permits_collaboratorID` FOREIGN KEY (`collaboratorID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_permits_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project` (
  `projectID` int(11) NOT NULL AUTO_INCREMENT,
  `projectGUID` char(36) NOT NULL,
  `goals` text DEFAULT NULL,
  `molecules` text DEFAULT NULL,
  `purity` varchar(10) DEFAULT NULL,
  `expense` text DEFAULT NULL,
  `bufferComponents` text DEFAULT NULL,
  `saltInformation` text DEFAULT NULL,
  `AUC_questions` text DEFAULT NULL,
  `expDesign` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('submitted','designed','scheduled','uploaded','anlyzed','invoiced','paid','other') NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`projectID`),
  UNIQUE KEY `projectGUID` (`projectGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `projectPerson`
--

DROP TABLE IF EXISTS `projectPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projectPerson` (
  `projectID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  KEY `ndx_projectPerson_personID` (`personID`),
  KEY `ndx_projectPerson_projectID` (`projectID`),
  CONSTRAINT `fk_projectPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_projectPerson_projectID` FOREIGN KEY (`projectID`) REFERENCES `project` (`projectID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `protocol`
--

DROP TABLE IF EXISTS `protocol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocol` (
  `protocolID` int(11) NOT NULL AUTO_INCREMENT,
  `protocolGUID` char(36) NOT NULL,
  `description` varchar(160) NOT NULL,
  `xml` longtext NOT NULL,
  `optimaHost` varchar(24) NOT NULL,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rotorID` int(11) DEFAULT NULL,
  `speed1` int(11) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `usedcells` int(11) DEFAULT NULL,
  `estscans` int(11) DEFAULT NULL,
  `solution1` varchar(80) DEFAULT NULL,
  `solution2` varchar(80) DEFAULT NULL,
  `wavelengths` int(11) DEFAULT NULL,
  `aprofileGUID` char(36) DEFAULT NULL,
  PRIMARY KEY (`protocolID`),
  UNIQUE KEY `protocolGUID` (`protocolGUID`),
  UNIQUE KEY `description` (`description`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `protocolAprofile`
--

DROP TABLE IF EXISTS `protocolAprofile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocolAprofile` (
  `protocolID` int(11) NOT NULL,
  `aprofileID` int(11) NOT NULL,
  KEY `ndx_protocolAprofile_aprofileID` (`aprofileID`),
  KEY `ndx_protocolAprofile_protocolID` (`protocolID`),
  CONSTRAINT `fk_protocolAprofile_aprofileID` FOREIGN KEY (`aprofileID`) REFERENCES `analysisprofile` (`aprofileID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_protocolAprofile_protocolID` FOREIGN KEY (`protocolID`) REFERENCES `protocol` (`protocolID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `protocolPerson`
--

DROP TABLE IF EXISTS `protocolPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocolPerson` (
  `protocolID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  KEY `ndx_protocolPerson_personID` (`personID`),
  KEY `ndx_protocolPerson_protocolID` (`protocolID`),
  CONSTRAINT `fk_protocolPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_protocolPerson_protocolID` FOREIGN KEY (`protocolID`) REFERENCES `protocol` (`protocolID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `radialCalibration`
--

DROP TABLE IF EXISTS `radialCalibration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `radialCalibration` (
  `radialCalID` int(11) NOT NULL AUTO_INCREMENT,
  `radialCalGUID` char(36) NOT NULL,
  `speed` int(11) NOT NULL DEFAULT 0,
  `rotorCalID` int(11) NOT NULL DEFAULT 0,
  `dateUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`radialCalID`),
  UNIQUE KEY `radialCalGUID` (`radialCalGUID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rawData`
--

DROP TABLE IF EXISTS `rawData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rawData` (
  `rawDataID` int(11) NOT NULL AUTO_INCREMENT,
  `rawDataGUID` char(36) NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `data` longblob DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `experimentID` int(11) NOT NULL,
  `solutionID` int(11) NOT NULL,
  `channelID` int(11) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rawDataID`),
  UNIQUE KEY `rawDataGUID` (`rawDataGUID`),
  KEY `ndx_rawData_experimentID` (`experimentID`),
  KEY `ndx_rawData_channelID` (`channelID`),
  CONSTRAINT `fk_rawData_channelID` FOREIGN KEY (`channelID`) REFERENCES `channel` (`channelID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_rawData_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE NO ACTION ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report` (
  `reportID` int(11) NOT NULL AUTO_INCREMENT,
  `reportGUID` char(36) NOT NULL,
  `experimentID` int(11) NOT NULL,
  `runID` varchar(80) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `html` longtext DEFAULT NULL,
  PRIMARY KEY (`reportID`),
  UNIQUE KEY `reportGUID` (`reportGUID`),
  KEY `ndx_report_experimentID` (`experimentID`),
  CONSTRAINT `fk_report_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reportDocument`
--

DROP TABLE IF EXISTS `reportDocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reportDocument` (
  `reportDocumentID` int(11) NOT NULL AUTO_INCREMENT,
  `reportDocumentGUID` char(36) NOT NULL,
  `editedDataID` int(11) NOT NULL DEFAULT 1,
  `label` varchar(160) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `analysis` varchar(20) DEFAULT '2DSA',
  `subAnalysis` varchar(20) DEFAULT 'report',
  `documentType` varchar(20) DEFAULT 'png',
  `contents` longblob DEFAULT NULL,
  PRIMARY KEY (`reportDocumentID`),
  UNIQUE KEY `reportDocumentGUID` (`reportDocumentGUID`),
  KEY `ndx_reportDocument_editedDataID` (`editedDataID`),
  CONSTRAINT `fk_reportDocument_editedDataID` FOREIGN KEY (`editedDataID`) REFERENCES `editedData` (`editedDataID`) ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reportPerson`
--

DROP TABLE IF EXISTS `reportPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reportPerson` (
  `reportID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  PRIMARY KEY (`reportID`),
  KEY `ndx_reportPerson_personID` (`personID`),
  KEY `ndx_reportPerson_reportID` (`reportID`),
  CONSTRAINT `fk_reportPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reportPerson_reportID` FOREIGN KEY (`reportID`) REFERENCES `report` (`reportID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reportTriple`
--

DROP TABLE IF EXISTS `reportTriple`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reportTriple` (
  `reportTripleID` int(11) NOT NULL AUTO_INCREMENT,
  `reportTripleGUID` char(36) NOT NULL,
  `reportID` int(11) NOT NULL,
  `resultID` int(11) DEFAULT NULL,
  `triple` varchar(20) NOT NULL DEFAULT '',
  `dataDescription` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`reportTripleID`),
  UNIQUE KEY `reportTripleGUID` (`reportTripleGUID`),
  KEY `ndx_reportTriple_reportID` (`reportID`),
  CONSTRAINT `fk_reportTriple_reportID` FOREIGN KEY (`reportID`) REFERENCES `report` (`reportID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rotor`
--

DROP TABLE IF EXISTS `rotor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rotor` (
  `rotorID` int(11) NOT NULL AUTO_INCREMENT,
  `abstractRotorID` int(11) DEFAULT NULL,
  `labID` int(11) NOT NULL,
  `rotorGUID` char(36) NOT NULL,
  `name` text DEFAULT NULL,
  `serialNumber` text DEFAULT NULL,
  PRIMARY KEY (`rotorID`),
  UNIQUE KEY `rotorGUID` (`rotorGUID`),
  KEY `ndx_rotor_abstractRotorID` (`abstractRotorID`),
  KEY `ndx_rotor_labID` (`labID`),
  CONSTRAINT `fk_rotor_abstractRotorID` FOREIGN KEY (`abstractRotorID`) REFERENCES `abstractRotor` (`abstractRotorID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rotor_labID` FOREIGN KEY (`labID`) REFERENCES `lab` (`labID`) ON DELETE CASCADE ON UPDATE NO ACTION
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rotorCalibration`
--

DROP TABLE IF EXISTS `rotorCalibration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rotorCalibration` (
  `rotorCalibrationID` int(11) NOT NULL AUTO_INCREMENT,
  `rotorID` int(11) DEFAULT NULL,
  `rotorCalibrationGUID` char(36) NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT '',
  `report` text DEFAULT NULL,
  `coeff1` float DEFAULT 0,
  `coeff2` float DEFAULT 0,
  `omega2_t` float DEFAULT NULL,
  `dateUpdated` timestamp NULL DEFAULT NULL,
  `calibrationExperimentID` int(11) DEFAULT NULL,
  PRIMARY KEY (`rotorCalibrationID`),
  UNIQUE KEY `rotorCalibrationGUID` (`rotorCalibrationGUID`),
  KEY `ndx_rotorCalibration_rotorID` (`rotorID`),
  KEY `ndx_rotorCalibration_experimentID` (`calibrationExperimentID`),
  CONSTRAINT `fk_rotorCalibration_rotorID` FOREIGN KEY (`rotorID`) REFERENCES `rotor` (`rotorID`) ON DELETE SET NULL ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solution`
--

DROP TABLE IF EXISTS `solution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solution` (
  `solutionID` int(11) NOT NULL AUTO_INCREMENT,
  `solutionGUID` char(36) DEFAULT NULL,
  `description` varchar(80) NOT NULL,
  `commonVbar20` double DEFAULT 0,
  `storageTemp` float DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`solutionID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solutionAnalyte`
--

DROP TABLE IF EXISTS `solutionAnalyte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solutionAnalyte` (
  `solutionID` int(11) NOT NULL,
  `analyteID` int(11) NOT NULL,
  `amount` float NOT NULL,
  KEY `ndx_solutionAnalyte_solutionID` (`solutionID`),
  KEY `ndx_solutionAnalyte_analyteID` (`analyteID`),
  CONSTRAINT `fk_solutionAnalyte_analyteID` FOREIGN KEY (`analyteID`) REFERENCES `analyte` (`analyteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solutionAnalyte_solutionID` FOREIGN KEY (`solutionID`) REFERENCES `solution` (`solutionID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solutionBuffer`
--

DROP TABLE IF EXISTS `solutionBuffer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solutionBuffer` (
  `solutionID` int(11) NOT NULL,
  `bufferID` int(11) NOT NULL,
  PRIMARY KEY (`solutionID`),
  KEY `ndx_solutionBuffer_solutionID` (`solutionID`),
  KEY `ndx_solutionBuffer_bufferID` (`bufferID`),
  CONSTRAINT `fk_solutionBuffer_bufferID` FOREIGN KEY (`bufferID`) REFERENCES `buffer` (`bufferID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solutionBuffer_solutionID` FOREIGN KEY (`solutionID`) REFERENCES `solution` (`solutionID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solutionPerson`
--

DROP TABLE IF EXISTS `solutionPerson`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solutionPerson` (
  `solutionID` int(11) NOT NULL,
  `personID` int(11) NOT NULL,
  PRIMARY KEY (`solutionID`),
  KEY `ndx_solutionPerson_personID` (`personID`),
  KEY `ndx_solutionPerson_solutionID` (`solutionID`),
  CONSTRAINT `fk_solutionPerson_personID` FOREIGN KEY (`personID`) REFERENCES `people` (`personID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solutionPerson_solutionID` FOREIGN KEY (`solutionID`) REFERENCES `solution` (`solutionID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spectrum`
--

DROP TABLE IF EXISTS `spectrum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spectrum` (
  `spectrumID` int(11) NOT NULL AUTO_INCREMENT,
  `componentID` int(11) NOT NULL,
  `componentType` enum('Buffer','Analyte') NOT NULL,
  `opticsType` enum('Extinction','Refraction','Fluorescence') NOT NULL,
  `lambda` float NOT NULL,
  `molarCoefficient` float NOT NULL,
  PRIMARY KEY (`spectrumID`),
  KEY `ndx_component_ID` (`componentID`)
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `speedstep`
--

DROP TABLE IF EXISTS `speedstep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `speedstep` (
  `speedstepID` int(11) NOT NULL AUTO_INCREMENT,
  `experimentID` int(11) NOT NULL,
  `scans` int(11) NOT NULL,
  `durationhrs` int(11) NOT NULL,
  `durationmins` double NOT NULL,
  `delayhrs` int(11) NOT NULL,
  `delaymins` double NOT NULL,
  `rotorspeed` int(11) NOT NULL,
  `acceleration` int(11) NOT NULL,
  `accelerflag` tinyint(1) DEFAULT 1,
  `w2tfirst` float NOT NULL,
  `w2tlast` float NOT NULL,
  `timefirst` int(11) NOT NULL,
  `timelast` int(11) NOT NULL,
  `setspeed` int(11) NOT NULL,
  `avgspeed` float NOT NULL,
  `speedsdev` float NOT NULL,
  PRIMARY KEY (`speedstepID`),
  KEY `fk_speedstep_experimentID` (`experimentID`),
  CONSTRAINT `fk_speedstep_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timestate`
--

DROP TABLE IF EXISTS `timestate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timestate` (
  `timestateID` int(11) NOT NULL AUTO_INCREMENT,
  `experimentID` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `definitions` longtext DEFAULT NULL,
  `data` longblob DEFAULT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`timestateID`),
  KEY `fk_timestate_experimentID` (`experimentID`),
  CONSTRAINT `fk_timestate_experimentID` FOREIGN KEY (`experimentID`) REFERENCES `experiment` (`experimentID`) ON DELETE CASCADE ON UPDATE CASCADE
/*!40101 SET character_set_client = @saved_cs_client */;

--
--

--
--
/*!50003 DROP FUNCTION IF EXISTS `check_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `check_GUID`( p_personGUID CHAR(36),
                            p_password   VARCHAR(80),
                            p_tableGUID  CHAR(36) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE pattern       CHAR(100);
  DECLARE GUID_formatOK INT;
  CALL config();

  SET @US3_LAST_ERRNO   = @OK;
  SET @US3_LAST_ERROR   = '';
  SET pattern = '^(([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12})$';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    
    SET p_tableGUID = TRIM( p_tableGUID );
    SELECT p_tableGUID REGEXP pattern
    INTO GUID_formatOK;

    
    IF ( p_tableGUID = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The GUID parameter to the check_GUID ',
                                    'function cannot be empty' );

    ELSEIF ( NOT GUID_formatOK ) THEN
      SET @US3_LAST_ERRNO = @BADGUID;
      SET @US3_LAST_ERROR = 'MySQL: The specified GUID is not the correct format';

    ELSE
      SET @US3_LAST_ERRNO = @OK;
      SET @US3_LAST_ERROR = '';

    END IF;

  END IF;

  RETURN( @US3_LAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `check_user` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `check_user`( p_personGUID     CHAR(36),
                            p_password VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_user INT;
  DECLARE md5_pw VARCHAR(80);
  DECLARE l_password VARCHAR(80);
  DECLARE activated INT;

  call config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
  SET @US3_ID         = NULL;
  SET @FNAME          = NULL;
  SET @LNAME          = NULL;
  SET @PHONE          = NULL;
  SET @EMAIL          = NULL;
  SET @personGUID     = NULL;
  SET @USERLEVEL      = NULL;

  SET md5_pw          = MD5( p_password );

  SELECT COUNT(*)
  INTO   count_user
  FROM   people
  WHERE  personGUID = p_personGUID;

  IF ( TRIM( p_personGUID ) = '' ) THEN
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = CONCAT( 'MySQL: The personGUID parameter to the ',
                                  'check_user function cannot be empty' );

  ELSEIF ( count_user = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_ACCT;
    SET @US3_LAST_ERROR = CONCAT( 'MySQL: The account identified by personGUID ',
                                  p_personGUID,
                                  ' is not set up correctly. ',
                                  'Please contact the administrator: ',
                                  @ADMIN_EMAIL );

  ELSE
    
    SELECT personID, password, fname, lname, phone, email, userlevel, activated
    INTO   @US3_ID, l_password, @FNAME, @LNAME, @PHONE, @EMAIL, @USERLEVEL, activated
    FROM   people
    WHERE  personGUID = p_personGUID;

    SET @personGUID   = p_personGUID;

    IF ( l_password != md5_pw ) THEN
      SET @US3_LAST_ERRNO = @BADPASS;
      SET @US3_LAST_ERROR = 'MySQL: Invalid password';

      SET @US3_ID     = NULL;
      SET @FNAME      = NULL;
      SET @LNAME      = NULL;
      SET @PHONE      = NULL;
      SET @EMAIL      = NULL;
      SET @personGUID = NULL;
      SET @USERLEVEL  = NULL;

    ELSEIF ( activated = false ) THEN
      SET @US3_LAST_ERRNO = @INACTIVE;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: This account has not been activated yet. ',
                                    'Please activate your account first. ',
                                    'The activation code was sent to your e-mail address: ',
                                     p_email);
      SET @US3_ID     = NULL;
      SET @FNAME      = NULL;
      SET @LNAME      = NULL;
      SET @PHONE      = NULL;
      SET @EMAIL      = NULL;
      SET @personGUID = NULL;
      SET @USERLEVEL  = NULL;

    ELSE
      
      UPDATE people
      SET    lastLogin = NOW()
      WHERE  personID = @US3_ID;

      SET @US3_LAST_ERROR = '';
      SET @US3_LAST_ERRNO = @OK;

    END IF;

  END IF;

  RETURN( @US3_LAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `check_user_email` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `check_user_email`( p_email    VARCHAR(63),
                                  p_password VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_user INT;
  DECLARE md5_pw     VARCHAR(80);
  DECLARE l_password VARCHAR(80);
  DECLARE activated  INT;

  call config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
  SET @US3_ID         = NULL;
  SET @FNAME          = NULL;
  SET @LNAME          = NULL;
  SET @PHONE          = NULL;
  SET @EMAIL          = NULL;
  SET @personGUID     = NULL;
  SET @USERLEVEL      = NULL;

  SET md5_pw          = MD5( p_password );

  SELECT COUNT(*)
  INTO   count_user
  FROM   people
  WHERE  email = p_email;

  IF ( TRIM( p_email ) = '' ) THEN
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = CONCAT( 'MySQL: The email parameter to the check_user_email ',
                                  'function cannot be empty' );

  ELSEIF ( count_user > 1 ) THEN
    SET @US3_LAST_ERRNO = @DUP_EMAIL;
    SET @US3_LAST_ERROR = CONCAT( 'MySQL: There was a problem with duplicate email addresses. ',
                                  'Please contact the administrator: ',
                                  @ADMIN_EMAIL );

  ELSEIF ( count_user < 1 ) THEN
    SET @US3_LAST_ERRNO = @NO_ACCT;
    SET @US3_LAST_ERROR = CONCAT( 'MySQL: The account for ',
                                  p_email,
                                  ' is not set up correctly. ',
                                  'Please contact the administrator: ',
                                  @ADMIN_EMAIL );

  ELSE
    
    SELECT personID, password, fname, lname, phone, personGUID, userlevel, activated
    INTO   @US3_ID, l_password, @FNAME, @LNAME, @PHONE, @personGUID, @USERLEVEL, activated
    FROM   people
    WHERE  email = p_email;

    SET @EMAIL        = p_email;

    IF ( l_password != md5_pw ) THEN
      SET @US3_LAST_ERRNO = @BADPASS;
      SET @US3_LAST_ERROR = 'MySQL: Invalid password';

      SET @US3_ID     = NULL;
      SET @FNAME      = NULL;
      SET @LNAME      = NULL;
      SET @PHONE      = NULL;
      SET @EMAIL      = NULL;
      SET @personGUID = NULL;
      SET @USERLEVEL  = NULL;

    ELSEIF ( activated = false ) THEN
      SET @US3_LAST_ERRNO = @INACTIVE;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: This account has not been activated yet. ',
                                    'Please activate your account first. ',
                                    'The activation code was sent to your e-mail address: ',
                                     p_email);
      SET @US3_ID     = NULL;
      SET @FNAME      = NULL;
      SET @LNAME      = NULL;
      SET @PHONE      = NULL;
      SET @EMAIL      = NULL;
      SET @personGUID = NULL;
      SET @USERLEVEL  = NULL;

    ELSE
      
      UPDATE people
      SET    lastLogin = NOW()
      WHERE  personID  = @US3_ID;

      SET @US3_LAST_ERRNO = @OK;
      SET @US3_LAST_ERROR = '';

    END IF;

  END IF;

  RETURN( @US3_LSAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_analytes` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_analytes`( p_personGUID     CHAR(36),
                                p_password VARCHAR(80),
                                p_ID       INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_analytes INT;

  CALL config();
  SET count_analytes = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_analytes
      FROM   analytePerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_analytes
      FROM   analytePerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those analytes';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_analytes
      FROM   analytePerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_analytes );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_aprofiles` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_aprofiles`( p_personGUID CHAR(36),
                                 p_password VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_aprofiles INT;

  CALL config();
  SET count_aprofiles = 0;

  SELECT COUNT(*)
  INTO   count_aprofiles
  FROM   analysisprofile;

  RETURN( count_aprofiles );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_autoflow_records` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_autoflow_records`( p_personGUID CHAR(36),
                                       p_password   VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_records INT;

  CALL config();
  SET count_records = 0;

       
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_records
    FROM      autoflow;
    
  END IF;

  RETURN( count_records );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_buffers` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_buffers`( p_personGUID CHAR(36),
                               p_password   VARCHAR(80),
                               p_ID         INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_buffers INT;

  CALL config();
  SET count_buffers = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_buffers
      FROM   bufferPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_buffers
      FROM   bufferPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those buffers';

    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_buffers
      FROM   bufferPerson
      WHERE  personID = @US3_ID;

    END IF;

  END IF;

  RETURN( count_buffers );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_calibration_experiments` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_calibration_experiments`( p_personGUID CHAR(36),
                                                p_password   VARCHAR(80),
                                                p_experimentID INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_profiles    INT;
  DECLARE count_experiments INT;

  CALL config();
  SET count_profiles = -1;    

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      experimentID = p_experimentID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_experiments < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_EXPERIMENT;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No experiment with ID ',
                                   p_experimentID,
                                   ' exists' );

    ELSE
      SELECT    COUNT(*)
      INTO      count_profiles
      FROM      rotorCalibration
      WHERE     calibrationExperimentID = p_experimentID;

    END IF;

  END IF;

  RETURN( count_profiles );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_editedData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_editedData`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_ID           INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_editedData INT;

  CALL config();
  SET count_editedData = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT     COUNT(*)
      INTO       count_editedData
      FROM       editedData, rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = p_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      AND        editedData.rawDataID = rawData.rawDataID;

    ELSE
      SELECT     COUNT(*)
      INTO       count_editedData
      FROM       editedData;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those experiments';
     
    ELSE
      
      
      SELECT     COUNT(*)
      INTO       count_editedData
      FROM       editedData, rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      AND        editedData.rawDataID = rawData.rawDataID;

    END IF;
    
  END IF;

  RETURN( count_editedData );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_editedData_by_rawData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_editedData_by_rawData`( p_personGUID   CHAR(36),
                                             p_password     VARCHAR(80),
                                             p_rawDataID    INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_editedData INT;
  DECLARE l_experimentID   INT;

  CALL config();
  SET count_editedData = 0;

  
  SELECT     experimentID
  INTO       l_experimentID
  FROM       rawData
  WHERE      rawDataID = p_rawDataID;

  IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_editedData
    FROM   editedData
    WHERE  rawDataID = p_rawDataID;

  END IF;

  RETURN( count_editedData );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_editprofiles` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_editprofiles`( p_personGUID CHAR(36),
                                     p_password   VARCHAR(80),
				     p_label      VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_records INT;

  CALL config();
  SET count_records = 0;

       
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_records
    FROM      editedData
    WHERE     label = p_label;
    
  END IF;

  RETURN( count_records );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_eprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_eprofile`( p_personGUID    CHAR(36),
                                p_password      VARCHAR(80),
                                p_componentID   INT,
                                p_componentType enum( 'Buffer', 'Analyte', 'Sample' ) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_component INT;

  CALL config();
  SET count_component = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT COUNT(*)
    INTO   count_component
    FROM   extinctionProfile
    WHERE  componentID   = p_componentID
    AND    componentType = p_componentType;

  END IF;

  RETURN( count_component );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_experiments` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_experiments`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_ID           INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_experiments INT;

  CALL config();
  SET count_experiments = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_experiments
      FROM   experimentPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_experiments
      FROM   experimentPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those experiments';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_experiments
      FROM   experimentPerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_experiments );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_instruments` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_instruments`( p_personGUID CHAR(36),
                                    p_password   VARCHAR(80),
                                    p_labID      INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_instruments INT;

  CALL config();
  SET count_instruments = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_instruments
    FROM      instrument
    WHERE     labID = p_labID;

  END IF;

  RETURN( count_instruments );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_labs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_labs`( p_personGUID CHAR(36),
                             p_password   VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_labs INT;

  CALL config();
  SET count_labs = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_labs
    FROM      lab;

  END IF;

  RETURN( count_labs );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_models` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_models`( p_personGUID CHAR(36),
                              p_password VARCHAR(80),
                              p_ID       INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_models INT;

  CALL config();
  SET count_models = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_models
      FROM   modelPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_models
      FROM   modelPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those models';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_models
      FROM   modelPerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_models );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_models_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_models_by_editID`( p_personGUID CHAR(36),
                                        p_password   VARCHAR(80),
                                        p_ID         INT,
                                        p_editID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_models INT;

  CALL config();
  SET count_models = 0;

  IF ( p_ID <= 0 ) THEN
    
    RETURN ( 0 );
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_models
    FROM   modelPerson, model
    WHERE  personID = p_ID
    AND    modelPerson.modelID = model.modelID
    AND    editedDataID        = p_editID;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those models';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_models
      FROM   modelPerson, model
      WHERE  personID = @US3_ID
      AND    modelPerson.modelID = model.modelID
      AND    editedDataID        = p_editID;

    END IF;
    
  END IF;

  RETURN( count_models );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_models_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_models_by_runID`( p_personGUID CHAR(36),
                                        p_password   VARCHAR(80),
                                        p_ID         INT,
                                        p_runID      VARCHAR(250) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_models INT;
  DECLARE run_pattern VARCHAR(254);

  CALL config();
  SET count_models = 0;
  SET run_pattern = CONCAT( p_runID, '.%' );

  IF ( p_ID <= 0 ) THEN
    
    RETURN ( 0 );
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_models
    FROM   modelPerson, model
    WHERE  personID = p_ID
    AND    modelPerson.modelID = model.modelID
    AND    description LIKE run_pattern ;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those models';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_models
      FROM   modelPerson, model
      WHERE  personID = @US3_ID
      AND    modelPerson.modelID = model.modelID
      AND    description LIKE run_pattern ;

    END IF;
    
  END IF;

  RETURN( count_models );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_mrecs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_mrecs`( p_personGUID CHAR(36),
                             p_password VARCHAR(80),
                             p_ID       INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_mrecs INT;

  CALL config();
  SET count_mrecs = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_mrecs
      FROM   pcsa_modelrecs c, editedData e, rawData r, experimentPerson p
      WHERE  p.personID     = p_ID
      AND    r.experimentID = p.experimentID
      AND    e.rawDataID    = r.rawDataID
      AND    c.editedDataID = e.editedDataID;

    ELSE
      SELECT COUNT(*)
      INTO   count_mrecs
      FROM   pcsa_modelrecs;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those mrecs files';

    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_mrecs
      FROM   pcsa_modelrecs c, editedData e, rawData r, experimentPerson p
      WHERE  p.personID     = @US3_ID
      AND    r.experimentID = p.experimentID
      AND    e.rawDataID    = r.rawDataID
      AND    c.editedDataID = e.editedDataID;

    END IF;

  END IF;

  RETURN( count_mrecs );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_mrecs_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_mrecs_by_editID`( p_personGUID CHAR(36),
                                       p_password VARCHAR(80),
                                       p_ID         INT,
                                       p_editID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_mrecs INT;

  CALL config();
  SET count_mrecs = 0;

  IF ( p_ID <= 0 ) THEN
    
    RETURN ( 0 );

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_mrecs
    FROM   pcsa_modelrecs c, editedData e, rawData r, experimentPerson p
    WHERE  p.personID     = p_ID
    AND    r.experimentID = p.experimentID
    AND    e.rawDataID    = r.rawDataID
    AND    c.editedDataID = e.editedDataID
    AND    c.editedDataID = p_editID;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those mrecs files';

    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_mrecs
      FROM   pcsa_modelrecs c, editedData e, rawData r, experimentPerson p
      WHERE  p.personID     = @US3_ID
      AND    r.experimentID = p.experimentID
      AND    e.rawDataID    = r.rawDataID
      AND    c.editedDataID = e.editedDataID
      AND    c.editedDataID = p_editID;

    END IF;

  END IF;

  RETURN( count_mrecs );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_noise` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_noise`( p_personGUID CHAR(36),
                              p_password VARCHAR(80),
                              p_ID       INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_noise INT;

  CALL config();
  SET count_noise = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_noise
      FROM   modelPerson, noise
      WHERE  personID = p_ID
      AND    modelPerson.modelID = noise.modelID;

    ELSE
      SELECT COUNT(*)
      INTO   count_noise
      FROM   noise;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those noise files';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_noise
      FROM   modelPerson, noise
      WHERE  modelPerson.modelID = noise.modelID
      AND    personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_noise );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_noise_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_noise_by_editID`( p_personGUID CHAR(36),
                                       p_password VARCHAR(80),
                                       p_ID         INT,
                                       p_editID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_noise INT;

  CALL config();
  SET count_noise = 0;

  IF ( p_ID <= 0 ) THEN
    
    RETURN ( 0 );
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_noise
    FROM   modelPerson, noise
    WHERE  personID = p_ID
    AND    modelPerson.modelID = noise.modelID
    AND    editedDataID        = p_editID;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those noise files';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_noise
      FROM   modelPerson, noise
      WHERE  personID = @US3_ID
      AND    modelPerson.modelID = noise.modelID
      AND    editedDataID        = p_editID;

    END IF;
    
  END IF;

  RETURN( count_noise );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_people` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_people`( p_personGUID CHAR(36),
                              p_password   VARCHAR(80),
                              p_template   VARCHAR(30) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE template    VARCHAR(40);
  DECLARE count_names INT;

  CALL config();
  SET p_template = TRIM( p_template );

  IF ( LENGTH(p_template) = 0 ) THEN
    SELECT     COUNT(*)
    INTO       count_names
    FROM       people;

  ELSE
    SET template = CONCAT('%', p_template, '%');

    SELECT     COUNT(*)
    INTO       count_names
    FROM       people
    WHERE      lname LIKE template
    OR         fname LIKE template;

  END IF;

  RETURN( count_names );
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_projects` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_projects`( p_personGUID     CHAR(36),
                                p_password       VARCHAR(80),
                                p_ID             INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_projects INT;

  CALL config();
  SET count_projects = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_projects
      FROM   projectPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_projects
      FROM   projectPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those projects';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_projects
      FROM   projectPerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_projects );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_protocols` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_protocols`( p_personGUID CHAR(36),
                                 p_password VARCHAR(80),
                                 p_ID       INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_protocols INT;

  CALL config();
  SET count_protocols = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_protocols
      FROM   protocolPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_protocols
      FROM   protocolPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those protocols';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_protocols
      FROM   protocolPerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_protocols );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_rawData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_rawData`( p_personGUID   CHAR(36),
                               p_password     VARCHAR(80),
                               p_ID           INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_rawData INT;

  CALL config();
  SET count_rawData = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT     COUNT(*)
      INTO       count_rawData
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = p_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID;

    ELSE
      SELECT     COUNT(*)
      INTO       count_rawData
      FROM       rawData;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those experiments';
     
    ELSE
      
      
      SELECT     COUNT(*)
      INTO       count_rawData
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID;

    END IF;
    
  END IF;

  RETURN( count_rawData );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_rawData_by_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_rawData_by_experiment`( p_personGUID   CHAR(36),
                                             p_password     VARCHAR(80),
                                             p_experimentID INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_rawData INT;

  CALL config();
  SET count_rawData = 0;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_rawData
    FROM   rawData
    WHERE  experimentID = p_experimentID;

  END IF;

  RETURN( count_rawData );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_reportDocument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_reportDocument`( p_personGUID       CHAR(36),
                                      p_password         VARCHAR(80),
                                      p_reportTripleID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE l_reportID    INT;
  DECLARE count_reports INT;

  CALL config();
  SET count_reports = 0;

  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_reports
    FROM   documentLink
    WHERE  reportTripleID = p_reportTripleID;

  END IF;

  RETURN( count_reports );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_reports` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_reports`( p_personGUID CHAR(36),
                               p_password   VARCHAR(80),
                               p_ID         INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_reports INT;

  CALL config();
  SET count_reports = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT COUNT(*)
      INTO   count_reports
      FROM   reportPerson
      WHERE  personID = p_ID;

    ELSE
      SELECT COUNT(*)
      INTO   count_reports
      FROM   reportPerson;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those reports';
     
    ELSE
      
      
      SELECT COUNT(*)
      INTO   count_reports
      FROM   reportPerson
      WHERE  personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_reports );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_reportTriple` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_reportTriple`( p_personGUID CHAR(36),
                                    p_password   VARCHAR(80),
                                    p_reportID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_reports INT;

  CALL config();
  SET count_reports = 0;

  IF ( verify_report_permission( p_personGUID, p_password, p_reportID ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_reports
    FROM   reportTriple
    WHERE  reportID = p_reportID;

  END IF;

  RETURN( count_reports );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_rotors` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_rotors`( p_personGUID CHAR(36),
                               p_password   VARCHAR(80),
                               p_labID      INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_rotors INT;

  CALL config();
  SET count_rotors = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_rotors
    FROM      rotor
    WHERE     labID = p_labID;

  END IF;

  RETURN( count_rotors );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_rotor_calibrations` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_rotor_calibrations`( p_personGUID CHAR(36),
                                           p_password   VARCHAR(80),
                                           p_rotorID    INT ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_profiles INT;
  DECLARE count_rotors      INT;

  CALL config();
  SET count_profiles = 0;

  SELECT     COUNT(*)
  INTO       count_rotors
  FROM       rotor
  WHERE      rotorID = p_rotorID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_rotors < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ROTOR;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No rotor with ID ',
                                   p_rotorID,
                                   ' exists' );

    ELSE
      SELECT    COUNT(*)
      INTO      count_profiles
      FROM      rotorCalibration
      WHERE     rotorID = p_rotorID;

    END IF;

  END IF;

  RETURN( count_profiles );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_solutions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_solutions`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_ID           INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_solutions INT;

  CALL config();
  SET count_solutions = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( p_ID > 0 ) THEN
      SELECT     COUNT(*)
      INTO       count_solutions
      FROM       solutionPerson
      WHERE      personID = p_ID;

    ELSE
      SELECT     COUNT(*)
      INTO       count_solutions
      FROM       solution;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those solutions';
     
    ELSE
      
      
      SELECT     COUNT(*)
      INTO       count_solutions
      FROM       solutionPerson
      WHERE      personID = @US3_ID;

    END IF;
    
  END IF;

  RETURN( count_solutions );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_solutions_by_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_solutions_by_experiment`( p_personGUID   CHAR(36),
                                              p_password     VARCHAR(80),
                                              p_experimentID INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_solutions INT;

  CALL config();
  SET count_solutions = 0;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    
    SELECT     COUNT(*)
    INTO       count_solutions
    FROM       experimentSolutionChannel
    WHERE      experimentID = p_experimentID;

  END IF;

  RETURN( count_solutions );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `count_spectrum` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `count_spectrum`( p_personGUID    CHAR(36),
                                p_password      VARCHAR(80),
                                p_componentID   INT,
                                p_componentType enum( 'Buffer', 'Analyte' ),
                                p_opticsType    enum( 'Extinction', 'Refraction', 'Fluorescence' ) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  
  DECLARE count_component INT;

  CALL config();
  SET count_component = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT COUNT(*)
    INTO   count_component
    FROM   spectrum
    WHERE  componentID   = p_componentID
    AND    componentType = p_componentType
    AND    opticsType    = p_opticsType;

  END IF;

  RETURN( count_component );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `last_debug` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `last_debug`() RETURNS text CHARSET latin1
    NO SQL
BEGIN
  RETURN( @DEBUG );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `last_errno` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `last_errno`() RETURNS int(11)
    NO SQL
BEGIN
  RETURN( @US3_LAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `last_error` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `last_error`() RETURNS text CHARSET latin1
    NO SQL
BEGIN
  RETURN( @US3_LAST_ERROR );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `last_insertID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `last_insertID`() RETURNS text CHARSET latin1
    NO SQL
BEGIN
  RETURN( @LAST_INSERT_ID );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `new_autoflow_analysis_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `new_autoflow_analysis_record`( p_personGUID CHAR(36),
                                      	      p_password   VARCHAR(80),
					      p_triplename TEXT,
					      p_filename   TEXT,
					      p_aprofileguid CHAR(36),
					      p_invID    int(11),
					      p_json TEXT ) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN

  DECLARE record_id INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflowAnalysis SET
      tripleName        = p_triplename,
      filename          = p_filename,
      aprofileGUID      = p_aprofileguid,
      invID             = p_invID,	
      statusJson        = p_json;
     
    SELECT LAST_INSERT_ID() INTO record_id;

  END IF;

  RETURN( record_id );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `new_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `new_report`( p_personGUID CHAR(36),
                             p_password   VARCHAR(80),
                             p_guid        varchar(80),
			     p_channame    varchar(80),
			     p_totconc     float,
			     p_rmsdlim     float,
			     p_avintensity float,
                             p_expduration INT ,
                             p_wvl         INT ,
                             p_totconc_tol float ,
                             p_expduration_tol float ) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN

  DECLARE report_id INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflowReport SET
      reportGUID        = p_guid,
      channelName       = p_channame,
      totalConc         = p_totconc,
      rmsdLimit         = p_rmsdlim,	
      avIntensity       = p_avintensity,
      expDuration       = p_expduration,
      wavelength        = p_wvl,
      totalConcTol      = p_totconc_tol,
      expDurationTol    = p_expduration_tol;
     
    SELECT LAST_INSERT_ID() INTO report_id;

  END IF;

  RETURN( report_id );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `new_report_item` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `new_report_item`( p_personGUID CHAR(36),
                                p_password    VARCHAR(80),
                                p_reportguid  varchar(80),
			        p_reportid    int,
			        p_type        text,
			        p_method      text,
			        p_low         float,
                                p_hi          float,
                                p_intval      float,
                                p_tolerance   float,
                                p_percent     float ) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN

  DECLARE reportitem_id INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflowReportItem SET
      reportGUID        = p_reportguid,
      reportID          = p_reportid,
      type              = p_type,
      method            = p_method,
      rangeLow          = p_low,	
      rangeHi           = p_hi,
      integration       = p_intval,
      tolerance         = p_tolerance,
      totalPercent      = p_percent;
     
    SELECT LAST_INSERT_ID() INTO reportitem_id;

  END IF;

  RETURN( reportitem_id );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `read_autoflow_times` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `read_autoflow_times`( p_personGUID CHAR(36),
                                       p_password   VARCHAR(80), 
				       p_runID      INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE l_sec_difference INT; 

  SET l_sec_difference = 0;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID;

  SELECT TIMESTAMPDIFF( SECOND, runStarted, NOW() ) 
  INTO l_sec_difference 
  FROM autoflow WHERE runID = p_runID AND runStarted IS NOT NULL;


  RETURN( l_sec_difference );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `read_autoflow_times_mod` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `read_autoflow_times_mod`( p_personGUID CHAR(36),
                                       	p_password   VARCHAR(80), 
				       	p_runID      INT,
                                        p_optima      VARCHAR(300) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE l_sec_difference INT;
  DECLARE cur_runStarted TIMESTAMP; 
  	  
  SET l_sec_difference = 0;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  SELECT     runStarted
  INTO       cur_runStarted
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;
  
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records > 0 ) THEN
      IF ( cur_runStarted IS NOT NULL ) THEN 
        	
	SELECT TIMESTAMPDIFF( SECOND, runStarted, NOW() )
	INTO l_sec_difference FROM autoflow WHERE runID = p_runID AND optimaName = p_optima; 

      END IF;	
    END IF;
  END IF;
    
  RETURN( l_sec_difference );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `read_autoflow_times_mod_test` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `read_autoflow_times_mod_test`( p_personGUID CHAR(36),
                                       	     p_password   VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE l_sec_difference INT;
  DECLARE cur_runStarted TIMESTAMP; 
  	  
  SET l_sec_difference = 0;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow;
 
  SELECT     created
  INTO       cur_runStarted
  FROM       autoflow LIMIT 1;
   
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records > 0 ) THEN
      IF ( cur_runStarted IS NOT NULL ) THEN 
        
	SELECT TIMESTAMPDIFF( SECOND, cur_runStarted, NOW() ) 
 	INTO l_sec_difference; 
  	
      END IF;	
    END IF;
  END IF;
    
  RETURN( l_sec_difference );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `timestamp2UTC` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `timestamp2UTC`( p_ts TIMESTAMP ) RETURNS timestamp
    NO SQL
BEGIN
  DECLARE count_gmt INT;
  DECLARE utcTime   TIMESTAMP;

  
  SELECT COUNT(*) INTO count_gmt
  FROM mysql.time_zone_name
  WHERE name LIKE '%GMT%';

  IF ( count_gmt > 0 ) THEN
    
    SELECT CONVERT_TZ( p_ts, @@global.time_zone, 'GMT') INTO utcTime;

  ELSE
    
    SELECT ADDTIME( p_ts, @UTC_DIFF ) INTO utcTime;

  END IF;

  RETURN( utcTime );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_analyte_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_analyte_permission`( p_personGUID CHAR(36),
                                           p_password   VARCHAR(80),
                                           p_analyteID  INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_analytes    INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying analyte permission';

  SELECT COUNT(*)
  INTO   count_analytes
  FROM   analyte
  WHERE  analyteID = p_analyteID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   analytePerson
  WHERE  analyteID = p_analyteID
  AND    personID = @US3_ID;
 
  IF ( count_analytes = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_ANALYTE;
    SET @US3_LAST_ERROR = 'MySQL: the specified analyte does not exist';

    SET status = @NO_ANALYTE;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this analyte';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_buffer_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_buffer_permission`( p_personGUID CHAR(36),
                                          p_password   VARCHAR(80),
                                          p_bufferID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_buffers     INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying buffer permission';

  SELECT COUNT(*)
  INTO   count_buffers
  FROM   buffer
  WHERE  bufferID = p_bufferID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   bufferPerson
  WHERE  bufferID = p_bufferID
  AND    personID = @US3_ID;

  IF ( count_buffers = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_BUFFER;
    SET @US3_LAST_ERROR = 'MySQL: the specified buffer does not exist';

    SET status = @NO_BUFFER;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this buffer';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_componentID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_componentID`( p_personGUID    CHAR(36),
                                    p_password      VARCHAR(80),
                                    p_componentID   INT,
                                    p_componentType enum( 'Buffer', 'Analyte', 'Sample' ) ) RETURNS int(11)
    READS SQL DATA
BEGIN

  DECLARE count_componentID INT;

  CALL config();
  SET count_componentID = 0;
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( p_componentType = 'Buffer' ) THEN
      SELECT COUNT(*)
      INTO   count_componentID
      FROM   buffer
      WHERE  bufferID = p_componentID;
  
      IF ( count_componentID < 1 ) THEN
        SET @US3_LAST_ERRNO = @NO_BUFFER;
        SET @US3_LAST_ERROR = CONCAT('MySQL: No buffer with ID ',
                                     p_componentID,
                                     ' exists' );
  
      END IF;

    ELSEIF ( p_componentType = 'Analyte' ) THEN
      SELECT COUNT(*)
      INTO   count_componentID
      FROM   analyte
      WHERE  analyteID = p_componentID;
  
      IF ( count_componentID < 1 ) THEN
        SET @US3_LAST_ERRNO = @NO_ANALYTE;
        SET @US3_LAST_ERROR = CONCAT('MySQL: No analyte with ID ',
                                     p_componentID,
                                     ' exists' );
  
      END IF;

    END IF;

  END IF;

  RETURN( @US3_LAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_editData_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_editData_permission`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_editedDataID INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_experiments INT;
  DECLARE status            INT;
  DECLARE l_experimentID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   count_experiments
  FROM   editedData, rawData
  WHERE  editedDataID = p_editedDataID
  AND    editedData.rawDataID = rawData.rawDataID;
 
  IF ( p_editedDataID = 1 ) THEN
    
    SET status = @OK;
    
  ELSEIF ( count_experiments = 1 ) THEN           
    
    SELECT experimentID
    INTO   l_experimentID
    FROM   editedData, rawData
    WHERE  editedDataID = p_editedDataID
    AND    editedData.rawDataID = rawData.rawDataID;

    IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
      SET status = @OK;

    ELSE
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this experiment';

      SET status = @NOTPERMITTED;

    END IF;

  ELSE
    SET @US3_LAST_ERRNO = @NO_EXPERIMENT;
    SET @US3_LAST_ERROR = 'MySQL: the associated experiment does not exist';

    SET status = @NO_EXPERIMENT;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_experiment_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_experiment_permission`( p_personGUID   CHAR(36),
                                              p_password     VARCHAR(80),
                                              p_experimentID INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_experiments INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying experiment permission';

  SELECT COUNT(*)
  INTO   count_experiments
  FROM   experiment
  WHERE  experimentID = p_experimentID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   experimentPerson
  WHERE  experimentID = p_experimentID
  AND    personID = @US3_ID;

  IF ( p_experimentID = 1 ) THEN    
    SET count_experiments = 1;
    SET count_permissions = 1;
  END IF;

  IF ( count_experiments = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_EXPERIMENT;
    SET @US3_LAST_ERROR = 'MySQL: the specified experiment does not exist';

    SET status = @NO_EXPERIMENT;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this experiment';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_model_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_model_permission`( p_personGUID  CHAR(36),
                                         p_password    VARCHAR(80),
                                         p_modelID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_models   INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying model permission';

  SELECT COUNT(*)
  INTO   count_models
  FROM   model
  WHERE  modelID = p_modelID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   modelPerson
  WHERE  modelID = p_modelID
  AND    personID = @US3_ID;

  IF ( count_models = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_MODEL;
    SET @US3_LAST_ERROR = 'MySQL: the specified model does not exist';

    SET status = @NO_MODEL;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this model';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_mrecs_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_mrecs_permission`( p_personGUID  CHAR(36),
                                         p_password    VARCHAR(80),
                                         p_mrecsID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_mrecs       INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying mrecs permission';

  SELECT COUNT(*)
  INTO   count_mrecs
  FROM   pcsa_modelrecs
  WHERE  mrecsID = p_mrecsID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   pcsa_modelrecs c, editedData e, rawData r, experimentPerson p
  WHERE  p.personID     = @US3_ID
  AND    r.experimentID = p.experimentID
  AND    e.rawDataID    = r.rawDataID
  AND    c.editedDataID = e.editedDataID;

  IF ( count_mrecs = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_MRECS;
    SET @US3_LAST_ERROR = 'MySQL: the specified mrecs file does not exist';

    SET status = @NO_MRECS;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this mrecs file';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_noise_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_noise_permission`( p_personGUID  CHAR(36),
                                         p_password    VARCHAR(80),
                                         p_noiseID     INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_noise       INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying noise permission';

  SELECT COUNT(*)
  INTO   count_noise
  FROM   noise
  WHERE  noiseID = p_noiseID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   noise, modelPerson
  WHERE  noise.noiseID = p_noiseID
  AND    noise.modelID = modelPerson.modelID
  AND    personID = @US3_ID;

  IF ( count_noise = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_NOISE;
    SET @US3_LAST_ERROR = 'MySQL: the specified noise file does not exist';

    SET status = @NO_NOISE;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this noise file';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_operator_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_operator_permission`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_labID        INT,
                                            p_instrumentID INT,
                                            p_operatorID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_instruments INT;
  DECLARE count_labs        INT;

  CALL config();
  SET @US3_LAST_ERRNO = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying operator permission';

  
  SELECT COUNT(*)
  INTO   count_instruments
  FROM   permits
  WHERE  personID     = p_operatorID 
  AND    instrumentID = p_instrumentID;

  
  SELECT COUNT(*)
  INTO   count_labs
  FROM   instrument
  WHERE  instrumentID = p_instrumentID
  AND    labID        = p_labID;
 
  IF ( verify_user( p_personGUID, p_password ) != @OK ) THEN
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to use this instrument';

  ELSEIF ( count_labs < 1 ) THEN
    SET @US3_LAST_ERRNO = @BADLABLOCATION;
    SET @US3_LAST_ERROR = 'MySQL: that instrument is not located in that lab';

  ELSEIF ( count_instruments < 1 ) THEN
    SET @US3_LAST_ERRNO = @BADOPERATOR;
    SET @US3_LAST_ERROR = 'MySQL: operator is not permitted to work on this instrument';

  ELSE
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

  END IF;

  RETURN( @US3_LAST_ERRNO );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_project_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_project_permission`( p_personGUID  CHAR(36),
                                           p_password    VARCHAR(80),
                                           p_projectID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_projects   INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying project permission';

  SELECT COUNT(*)
  INTO   count_projects
  FROM   project
  WHERE  projectID = p_projectID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   projectPerson
  WHERE  projectID = p_projectID
  AND    personID = @US3_ID;

  IF ( count_projects = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_PROJECT;
    SET @US3_LAST_ERROR = 'MySQL: the specified project does not exist';

    SET status = @NO_PROJECT;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this project';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_protocol_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_protocol_permission`( p_personGUID  CHAR(36),
                                            p_password    VARCHAR(80),
                                            p_protocolID  INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_protocols   INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying protocol permission';

  SELECT COUNT(*)
  INTO   count_protocols
  FROM   protocol
  WHERE  protocolID = p_protocolID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   protocolPerson
  WHERE  protocolID = p_protocolID
  AND    personID = @US3_ID;

  IF ( count_protocols = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_PROTOCOL;
    SET @US3_LAST_ERROR = 'MySQL: the specified protocol does not exist';

    SET status = @NO_PROTOCOL;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this run protocol';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_report_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_report_permission`( p_personGUID  CHAR(36),
                                          p_password    VARCHAR(80),
                                          p_reportID    INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_reports     INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying report permission';

  SELECT COUNT(*)
  INTO   count_reports
  FROM   report
  WHERE  reportID = p_reportID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   reportPerson
  WHERE  reportID = p_reportID
  AND    personID = @US3_ID;

  IF ( count_reports = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_REPORT;
    SET @US3_LAST_ERROR = 'MySQL: the specified report does not exist';

    SET status = @NO_REPORT;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this report';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_solution_permission` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_solution_permission`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_solutionID   INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_solutions   INT;
  DECLARE count_permissions INT;
  DECLARE status            INT;

  CALL config();
  SET status   = @ERROR;
  SET @US3_LAST_ERROR = 'MySQL: error verifying solution permission';

  SELECT COUNT(*)
  INTO   count_solutions
  FROM   solution
  WHERE  solutionID = p_solutionID;

  SELECT COUNT(*)
  INTO   count_permissions
  FROM   solutionPerson
  WHERE  solutionID = p_solutionID
  AND    personID = @US3_ID;

  IF ( count_solutions = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_SOLUTION;
    SET @US3_LAST_ERROR = 'MySQL: the specified solution does not exist';

    SET status = @NO_SOLUTION;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( count_permissions > 0                         ) ) THEN
    SET @US3_LAST_ERRNO = @OK;
    SET @US3_LAST_ERROR = '';

    SET status = @OK;

  ELSE
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view or modify this solution';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_user` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_user`( p_personGUID CHAR(36),
                             p_password   VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE status       INT;

  CALL config();
  SET status = @OK;

  IF ( @US3_ID IS NULL ) THEN
    SET status = check_user( p_personGUID, p_password );

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_userlevel` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_userlevel`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_userlevel  INT ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE status       INT;

  CALL config();
  SET status = verify_user( p_personGUID, p_password );

  IF ( status = @OK && @USERLEVEL < p_userlevel ) THEN
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: Not permitted at that userlevel';

    SET status = @NOTPERMITTED;

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `verify_user_email` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `verify_user_email`( p_email    VARCHAR(63),
                                   p_password VARCHAR(80) ) RETURNS int(11)
    READS SQL DATA
BEGIN
  DECLARE count_user   INT;
  DECLARE status       INT;

  CALL config();

  SET status = @OK;
  IF ( @US3_ID IS NULL ) THEN
    SET status = check_user_email( p_email, p_password );

  END IF;

  RETURN( status );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_autoflow_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_autoflow_record`( p_personGUID  CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_protname      VARCHAR(80),
                                     p_cellchnum     VARCHAR(80),
                                     p_triplenum     VARCHAR(80),
				     p_duration      INT,
				     p_runname       VARCHAR(80),
				     p_expid         INT,
				     p_optimaname    VARCHAR(300),
				     p_invID         INT,
				     p_label         VARCHAR(80),
				     p_gmprun        VARCHAR(80),
				     p_aprofileguid  VARCHAR(80) )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflow SET
      protname          = p_protname,
      cellChNum         = p_cellchnum,
      tripleNum         = p_triplenum,
      duration          = p_duration,
      runName           = p_runname,
      expID             = p_expid,
      optimaName        = p_optimaname,
      invID             = p_invID,
      label		= p_label,
      created           = NOW(),
      gmpRun            = p_gmprun,
      aprofileGUID      = p_aprofileguid;

    SET @LAST_INSERT_ID = LAST_INSERT_ID();

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_autoflow_stages_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_autoflow_stages_record`( p_personGUID  CHAR(36),
                                            p_password      VARCHAR(80),
                                            p_id      INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflowStages SET
      autoflowID        = p_id;

    SET @LAST_INSERT_ID = LAST_INSERT_ID();

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_buffer_component` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_buffer_component`( p_personGUID    CHAR(36),
                                        p_password      VARCHAR(80),
                                        p_bufferID      INT,
                                        p_componentID   INT,
                                        p_concentration FLOAT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_buffers    INT;
  DECLARE count_components INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  SELECT     COUNT(*)
  INTO       count_buffers
  FROM       buffer
  WHERE      bufferID = p_bufferID;

  SELECT     COUNT(*)
  INTO       count_components
  FROM       bufferComponent
  WHERE      bufferComponentID = p_componentID;

  IF ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) THEN
    IF ( count_buffers < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_BUFFER;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No buffer with ID ',
                                   p_bufferID,
                                   ' exists' );

    ELSEIF ( count_components < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_COMPONENT;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No buffer component with ID ',
                                   p_componentID,
                                   ' exists' );

    ELSE
      INSERT INTO bufferLink SET
        bufferID          = p_bufferID,
        bufferComponentID = p_componentID,
        concentration     = p_concentration;

      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_instrument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_instrument`( p_personGUID    CHAR(36),
                                  p_password      VARCHAR(80),
                                  p_name          TEXT,
                                  p_serialNumber  TEXT,
                                  p_labID         INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    INSERT INTO instrument SET
      name              = p_name,
      serialNumber      = p_serialNumber,
      labID             = p_labID,
      dateUpdated       = NOW();

    SET @LAST_INSERT_ID = LAST_INSERT_ID();

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_instrument_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_instrument_new`( p_personGUID    CHAR(36),
                                    p_password      VARCHAR(80),
                                    p_name          TEXT,
                                    p_serialNumber  TEXT,
                                    p_labID         INT,
				    p_host          TEXT,
				    p_port          INT,
        			    p_optimadbname  TEXT,
				    p_optimadbuser  TEXT,
				    p_optimadbpassw  VARCHAR(100),
				    p_opsys1        TEXT,
                                    p_opsys2        TEXT,
                                    p_opsys3        TEXT,
				    p_radcalwvl     INT,
                                    p_chromoab      TEXT,
				    p_msgport       INT  )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    INSERT INTO instrument SET
      name              = p_name,
      serialNumber      = p_serialNumber,
      labID             = p_labID,
      optimaHost        = p_host,
      optimaPort        = p_port,
      optimaDBname      = p_optimadbname,
      optimaDBusername  = p_optimadbuser,
      optimaDBpassw     = ENCODE( p_optimadbpassw, 'secretOptimaDB' ),
      opsys1            = p_opsys1,
      opsys2		= p_opsys2,
      opsys3            = p_opsys3,
      RadCalWvl         = p_radcalwvl,
      chromaticAB       = p_chromoab,
      dateUpdated       = NOW(),
      optimaPortMsg     = p_msgport;

    SET @LAST_INSERT_ID = LAST_INSERT_ID();

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_lab` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_lab`( p_personGUID        CHAR(36),
                           p_password          VARCHAR(80),
                           p_labGUID           CHAR(36),
                           p_name              TEXT,
                           p_building          TEXT,
                           p_room              TEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) &&
       ( check_GUID      ( p_personGUID, p_password, p_labGUID  ) = @OK ) ) THEN
    INSERT INTO lab SET
      labGUID           = p_labGUID,
      name              = p_name,
      building          = p_building,
      room              = p_room,
      dateUpdated       = NOW();

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for labGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for labGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_radialcal` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_radialcal`( p_personGUID      CHAR(36),
                                 p_password        VARCHAR(80),
                                 p_radialCalGUID   CHAR(36),
                                 p_speed           INT,
                                 p_rotorCalID      INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_rotorcals   INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  SELECT     COUNT(*)
  INTO       count_rotorcals
  FROM       rotorCalibration
  WHERE      rotorCalibrationID = p_rotorCalID;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN       ) = @OK ) &&
       ( check_GUID      ( p_personGUID, p_password, p_radialCalGUID  ) = @OK ) ) THEN
    IF ( count_rotorcals < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ROTOR_CAL;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No rotor calibration with ID ',
                                   p_rotorCalID,
                                   ' exists' );

    ELSE
      INSERT INTO radialCalibration SET
        radialCalGUID   = p_radialCalGUID,
        speed           = p_speed,
        rotorCalID      = p_rotorCalID,
        dateUpdated     = NOW() ;
        
      IF ( duplicate_key = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTDUP;
        SET @US3_LAST_ERROR = "MySQL: Duplicate entry for radialCalGUID field";
  
      ELSEIF ( null_field = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTNULL;
        SET @US3_LAST_ERROR = "MySQL: NULL value for radialCalGUID field";
  
      ELSE
        SET @LAST_INSERT_ID = LAST_INSERT_ID();
  
      END IF;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_rotor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_rotor`( p_personGUID        CHAR(36),
                             p_password          VARCHAR(80),
                             p_abstractRotorID   INT,
                             p_abstractRotorGUID CHAR(36),
                             p_labID             INT,
                             p_rotorGUID         CHAR(36),
                             p_name              TEXT,
                             p_serialNumber      TEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_abstract_rotors      INT;
  DECLARE count_labs                 INT;
  DECLARE l_abstractRotorID          INT;
  DECLARE l_abstractRotorID_count    INT;
  DECLARE l_abstractRotorGUID        CHAR(36);
  DECLARE l_abstractRotorGUID_count  INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  
  SELECT COUNT(*)
  INTO   l_abstractRotorGUID_count
  FROM   abstractRotor
  WHERE  abstractRotorGUID = p_abstractRotorGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_abstractRotorID_count
  FROM   abstractRotor
  WHERE  abstractRotorID = p_abstractRotorID
  LIMIT  1;                         

  IF ( l_abstractRotorGUID_count = 1 ) THEN 
    SET l_abstractRotorGUID = p_abstractRotorGUID;

    SELECT abstractRotorID
    INTO   l_abstractRotorID
    FROM   abstractRotor
    WHERE  abstractRotorGUID = p_abstractRotorGUID
    LIMIT  1;

  ELSEIF ( l_abstractRotorID_count = 1 ) THEN
    SET l_abstractRotorID = p_abstractRotorID;

    SELECT abstractRotorGUID
    INTO   l_abstractRotorGUID
    FROM   abstractRotor
    WHERE  abstractRotorID = p_abstractRotorID;

  ELSE
    
    SET l_abstractRotorID   = 0;
    SET l_abstractRotorGUID = p_abstractRotorGUID;

  END IF;

  SELECT     COUNT(*)
  INTO       count_abstract_rotors
  FROM       abstractRotor
  WHERE      abstractRotorID = l_abstractRotorID;

  SELECT     COUNT(*)
  INTO       count_labs
  FROM       lab
  WHERE      labID = p_labID;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN   ) = @OK ) &&
       ( check_GUID      ( p_personGUID, p_password, p_rotorGUID  ) = @OK ) ) THEN
    IF ( count_abstract_rotors < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ROTOR;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No abstract rotor with ID ',
                                   p_abstractRotorID,
                                   ' and/or GUID ',
                                   p_abstractRotorGUID,
                                   ' exists' );

    ELSEIF ( count_labs < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_LAB;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No lab with ID ',
                                   p_labID,
                                   ' exists' );

    ELSE
      INSERT INTO rotor SET
        abstractRotorID   = l_abstractRotorID,
        labID             = p_labID,
        name              = p_name,
        rotorGUID         = p_rotorGUID,
        serialNumber      = p_serialNumber;
        
      IF ( duplicate_key = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTDUP;
        SET @US3_LAST_ERROR = "MySQL: Duplicate entry for abstractRotorGUID field";
  
      ELSEIF ( null_field = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTNULL;
        SET @US3_LAST_ERROR = "MySQL: NULL value for abstractRotorGUID field";
  
      ELSE
        SET @LAST_INSERT_ID = LAST_INSERT_ID();
  
      END IF;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_rotor_calibration` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_rotor_calibration`( p_personGUID        CHAR(36),
                                         p_password          VARCHAR(80),
                                         p_rotorID           INT,
                                         p_calibrationGUID   CHAR(36),
                                         p_report            TEXT,
                                         p_coeff1            FLOAT,
                                         p_coeff2            FLOAT,
                                         p_omega2_t          FLOAT,
                                         p_experimentID      INT,
                                         p_label             VARCHAR(80) )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_rotors      INT;
  DECLARE count_experiments INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  SELECT     COUNT(*)
  INTO       count_rotors
  FROM       rotor
  WHERE      rotorID = p_rotorID;

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      experimentID = p_experimentID;

  
  IF ( p_experimentID = -1 ) THEN
    SET count_experiments = 1;
  END IF;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN         ) = @OK ) &&
       ( check_GUID      ( p_personGUID, p_password, p_calibrationGUID  ) = @OK ) ) THEN
    IF ( count_rotors < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ROTOR;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No rotor with ID ',
                                   p_rotorID,
                                   ' exists' );

    ELSEIF ( count_experiments < 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_EXPERIMENT;
      SET @US3_LAST_ERROR = CONCAT('MySQL: No experiment with ID ',
                                   p_experimentID,
                                   ' exists' );

    ELSE
      INSERT INTO rotorCalibration SET
        rotorID                 = p_rotorID,
        rotorCalibrationGUID    = p_calibrationGUID,
        label                   = p_label,
        report                  = p_report,
        coeff1                  = p_coeff1,
        coeff2                  = p_coeff2,
        omega2_t                = p_omega2_t,
        dateUpdated             = NOW(),
        calibrationExperimentID = p_experimentID;
        
      IF ( duplicate_key = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTDUP;
        SET @US3_LAST_ERROR = "MySQL: Duplicate entry for rotorCalibrationGUID field";
  
      ELSEIF ( null_field = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTNULL;
        SET @US3_LAST_ERROR = "MySQL: NULL value for rotorCalibrationGUID field";
  
      ELSE
        SET @LAST_INSERT_ID = LAST_INSERT_ID();
  
      END IF;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `all_cell_experiments` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `all_cell_experiments`( p_personGUID   CHAR(36),
                                        p_password     VARCHAR(80),
                                        p_experimentID INT )
    READS SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    SELECT @OK AS status;

    SELECT   cellID, cellGUID, name, holeNumber, abstractCenterpieceID
    FROM     cell
    WHERE    experimentID = p_experimentID
    ORDER BY dateUpdated DESC;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `all_editedDataIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `all_editedDataIDs`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_ID           INT )
    READS SQL DATA
BEGIN
  DECLARE count_editData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_editedData( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT     editedDataID, editedData.label, editedData.filename,
                   editedData.rawDataID, rawData.experimentID,
                   timestamp2UTC( editedData.lastUpdated) AS UTC_lastUpdated, 
                   MD5( editedData.data ) AS checksum, LENGTH( editedData.data ) AS size,
                   experiment.type, editedData.editGUID
        FROM       editedData, rawData, experiment, experimentPerson
        WHERE      experimentPerson.personID = p_ID
        AND        experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        AND        editedData.rawDataID = rawData.rawDataID
        ORDER BY   editedData.lastUpdated DESC;

      ELSE
        SELECT     editedDataID, editedData.label, editedData.filename,
                   editedData.rawDataID, rawData.experimentID,
                   timestamp2UTC( editedData.lastUpdated) AS UTC_lastUpdated, 
                   MD5( editedData.data ) AS checksum, LENGTH( editedData.data ) AS size,
                   experiment.type, editedData.editGUID
        FROM       editedData, rawData, experiment, experimentPerson
        WHERE      experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        AND        editedData.rawDataID = rawData.rawDataID
        ORDER BY   editedData.lastUpdated DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this experiment';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_experiments( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT     editedDataID, editedData.label, editedData.filename,
                 editedData.rawDataID, rawData.experimentID, 
                 timestamp2UTC( editedData.lastUpdated) AS UTC_lastUpdated, 
                 MD5( editedData.data ) AS checksum, LENGTH( editedData.data ) AS size,
                 experiment.type, editedData.editGUID
      FROM       editedData, rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      AND        editedData.rawDataID = rawData.rawDataID
      ORDER BY   editedData.lastUpdated DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `all_rawDataIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `all_rawDataIDs`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_ID           INT )
    READS SQL DATA
BEGIN
  DECLARE count_rawData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_rawData( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT     rawDataID, rawData.label, rawData.filename,
                   rawData.experimentID, rawData.solutionID, 
                   timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                   MD5( rawData.data ) AS checksum, LENGTH( rawData.data ) AS size,
                   experimentPerson.personID,
                   rawDataGUID, rawData.comment, experiment.experimentGUID
        FROM       rawData, experiment, experimentPerson
        WHERE      experimentPerson.personID = p_ID
        AND        experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        ORDER BY   rawData.lastUpdated DESC;

      ELSE
        SELECT     rawDataID, rawData.label, rawData.filename,
                   rawData.experimentID, rawData.solutionID,
                   timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                   MD5( rawData.data ) AS checksum, LENGTH( rawData.data ) AS size,
                   experimentPerson.personID,
                   rawDataGUID, rawData.comment, experiment.experimentGUID
        FROM       rawData, experiment, experimentPerson
        WHERE      experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        ORDER BY   rawData.lastUpdated DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this experiment';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_experiments( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT     rawDataID, rawData.label, rawData.filename,
                 rawData.experimentID, rawData.solutionID, 
                 timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                 MD5( rawData.data ) AS checksum, LENGTH( rawData.data ) AS size,
                 experimentPerson.personID,
                 rawDataGUID, rawData.comment, experiment.experimentGUID
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      ORDER BY   rawData.lastUpdated DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `all_solutionIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `all_solutionIDs`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_ID           INT )
    READS SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_solutions( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT     s.solutionID, description
        FROM       solutionPerson sp, solution s
        WHERE      sp.personID = p_ID
        AND        sp.solutionID = s.solutionID
        ORDER BY   s.description;

      ELSE
        SELECT     s.solutionID, description
        FROM       solutionPerson sp, solution s
        WHERE      sp.solutionID = s.solutionID
        ORDER BY   s.description;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this solution';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_solutions( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT     s.solutionID, description
      FROM       solutionPerson sp, solution s
      WHERE      sp.personID = @US3_ID
      AND        sp.solutionID = s.solutionID
      ORDER BY   s.description;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `all_speedsteps` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `all_speedsteps`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_experimentID INT )
    READS SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    SELECT @OK AS status;

    SELECT   speedstepID, scans, durationhrs, durationmins, delayhrs, delaymins,
             rotorspeed, acceleration, accelerflag, w2tfirst, w2tlast, timefirst, timelast,
             setspeed, avgspeed, speedsdev
    FROM     speedstep
    WHERE    experimentID = p_experimentID
    ORDER BY speedstepID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_edit_status` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_edit_status`( p_personGUID CHAR(36),
                                        p_password   VARCHAR(80),
                                        p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  DECLARE unique_start TINYINT DEFAULT 0;
       

  DECLARE exit handler for sqlexception
   BEGIN
      
    ROLLBACK;
   END;
   
  DECLARE exit handler for sqlwarning
   BEGIN
     
    ROLLBACK;
   END;


  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';


  START TRANSACTION;
  
  SELECT     editing 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id FOR UPDATE;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status = 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     editing = 'STARTED'
      WHERE   autoflowID = p_id;

      SET unique_start = 1;

    END IF;

  END IF;

  SELECT unique_start as status;
  
  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_edit_status_revert` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_edit_status_revert`( p_personGUID CHAR(36),
                                               p_password   VARCHAR(80),
                                               p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  
       
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT     editing 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status != 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     editing = DEFAULT
      WHERE   autoflowID = p_id;

    END IF;

  END IF;

  
  
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_import_status` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_import_status`( p_personGUID CHAR(36),
                                          p_password   VARCHAR(80),
                                          p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  DECLARE unique_start TINYINT DEFAULT 0;
       

  DECLARE exit handler for sqlexception
   BEGIN
      
    ROLLBACK;
   END;
   
  DECLARE exit handler for sqlwarning
   BEGIN
     
    ROLLBACK;
   END;


  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';


  START TRANSACTION;
  
  SELECT     import 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id FOR UPDATE;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status = 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     import = 'STARTED'
      WHERE   autoflowID = p_id;

      SET unique_start = 1;

    END IF;

  END IF;

  SELECT unique_start as status;
  
  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_import_status_revert` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_import_status_revert`( p_personGUID CHAR(36),
                                                 p_password   VARCHAR(80),
                                                 p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  
       
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT     import 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status != 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     import = DEFAULT
      WHERE   autoflowID = p_id;

    END IF;

  END IF;

  
  
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_liveupdate_status` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_liveupdate_status`( p_personGUID CHAR(36),
                                              p_password   VARCHAR(80),
                                              p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  DECLARE unique_start TINYINT DEFAULT 0;
       

  DECLARE exit handler for sqlexception
   BEGIN
      
    ROLLBACK;
   END;
   
  DECLARE exit handler for sqlwarning
   BEGIN
     
    ROLLBACK;
   END;


  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';


  START TRANSACTION;
  
  SELECT     liveUpdate 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id FOR UPDATE;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status = 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     liveUpdate = 'STARTED'
      WHERE   autoflowID = p_id;

      SET unique_start = 1;

    END IF;

  END IF;

  SELECT unique_start as status;
  
  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `autoflow_liveupdate_status_revert` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `autoflow_liveupdate_status_revert`( p_personGUID CHAR(36),
                                                   p_password   VARCHAR(80),
                                                   p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  
       
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT     liveUpdate 
  INTO       current_status
  FROM       autoflowStages
  WHERE      autoflowID = p_id;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status != 'unknown' ) THEN
      UPDATE  autoflowStages
      SET     liveUpdate = DEFAULT
      WHERE   autoflowID = p_id;

    END IF;

  END IF;

  
  
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `config` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `config`()
BEGIN
  SET @SQL_MODE       = 'traditional';
  SET @ADMIN_EMAIL    = 'dzollars@gmail.com';
  SET @USERNAME       = LEFT( USER(), LOCATE( '@', USER() ) - 1 );

  SELECT timediff( UTC_TIMESTAMP(), CURRENT_TIMESTAMP() ) INTO @UTC_DIFF;


  SET @OK             = 0;
  SET @ERROR          = -1;
  SET @NOT_CONNECTED  = 1;

  SET @DUP_EMAIL      = 101;
  SET @NO_ACCT        = 102;
  SET @INACTIVE       = 103;
  SET @BADPASS        = 104;
  SET @EMPTY          = 105;
  SET @BAD_CHECKSUM   = 106;

  SET @NOTPERMITTED   = 201;
  SET @BADOPERATOR    = 202;
  SET @BADLABLOCATION = 203;
  SET @BADGUID        = 204;

  SET @NOROWS         = 301;

  SET @INSERTNULL     = 401;
  SET @INSERTDUP      = 402;
  SET @DUPFIELD       = 403;
  SET @CONSTRAINT_FAILED = 404;

  SET @NO_BUFFER      = 501;
  SET @NO_COMPONENT   = 502;
  SET @NO_ROTOR       = 503;
  SET @NO_ANALYTE     = 504;
  SET @NO_LAB         = 505;
  SET @NO_PERSON      = 506;
  SET @NO_MODEL       = 507;
  SET @NO_EXPERIMENT  = 508;
  SET @NO_RAWDATA     = 509;
  SET @NO_EDITDATA    = 510;
  SET @NO_SOLUTION    = 511;
  SET @CALIB_IN_USE   = 512;    
  SET @ROTOR_IN_USE   = 513;    
  SET @NO_NOISE       = 514;
  SET @NO_PROJECT     = 515;
  SET @BUFFER_IN_USE  = 516;
  SET @ANALYTE_IN_USE = 517;
  SET @SOLUTION_IN_USE = 518;
  SET @NO_CALIB        = 519;
  SET @NO_REPORT       = 520;
  SET @NO_REPORT_TRIPLE     = 521;
  SET @NO_REPORT_DOCUMENT   = 522;
  SET @NO_DOCUMENT_LINK     = 523;
  SET @MORE_THAN_SINGLE_ROW = 524;
  SET @NO_MRECS       = 525;
  SET @NO_PROTOCOL    = 526;
  SET @NO_ROTOR_CAL   = 527;
  SET @INSTRUMENT_IN_USE = 553;
  SET @PROTOCOL_IN_USE   = 554;
  SET @NO_AUTOFLOW_RECORD = 555;  
 
  
  SET @US3_USER       = 0;
  SET @US3_PRIV       = 1;
  SET @US3_ANALYST    = 2;
  SET @US3_ADMIN      = 3;
  SET @US3_SUPER      = 4;
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `decode_instrument_passw` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `decode_instrument_passw`( p_personGUID    CHAR(36),
                                     	  p_password      VARCHAR(80),
                                     	   p_instrumentID  INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    SELECT DECODE(optimaDBpassw,'secretOptimaDB') FROM instrument
    WHERE instrumentID = p_instrumentID;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_analyte` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_analyte`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_analyteID  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_analytes INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_analyte_permission( p_personGUID, p_password, p_analyteID ) = @OK ) THEN

    
    SELECT COUNT(*) INTO count_analytes
    FROM solutionAnalyte
    WHERE analyteID = p_analyteID;

    IF ( count_analytes = 0 ) THEN
    
      DELETE FROM analytePerson
      WHERE analyteID = p_analyteID;
      
      DELETE FROM spectrum
      WHERE componentID = p_analyteID
      AND   componentType = 'Analyte';
      
      DELETE FROM analyte
      WHERE analyteID = p_analyteID;

    ELSE
      SET @US3_LAST_ERRNO = @ANALYTE_IN_USE;
      SET @US3_last_ERROR = 'The analyte is in use in a solution';

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_aprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_aprofile`( p_personGUID  CHAR(36),
                                   p_password  VARCHAR(80),
                                   p_aprofileID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_aprofiles INT;	

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_aprofile_permission( p_personGUID, p_password, p_aprofileID ) = @OK ) THEN

     
     
     DELETE FROM aprofile
     WHERE aprofileID = p_aprofileID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_autoflow_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_autoflow_record`( p_personGUID    CHAR(36),
                                     	p_password      VARCHAR(80),
                			p_runID         INT,
                                        p_optima        VARCHAR(300) )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;	
  
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    
    
    SELECT COUNT(*) INTO count_records 
    FROM autoflow 
    WHERE runID = p_runID AND optimaName = p_optima;

    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'Record cannot be deleted as it does not exist for current experiment run';   

    ELSE
      DELETE FROM autoflow
      WHERE runID = p_runID AND optimaName = p_optima;
    
    END IF;  

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_autoflow_record_by_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_autoflow_record_by_id`( p_personGUID    CHAR(36),
                                     	      p_password      VARCHAR(80),
                			      p_ID            INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;	
  DECLARE count_records_stages INT;	

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    
    
    SELECT COUNT(*) INTO count_records 
    FROM autoflow 
    WHERE ID = p_ID;

    
    SELECT COUNT(*) INTO count_records_stages 
    FROM autoflowStages 
    WHERE autoflowID = p_ID;



    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'Record cannot be deleted as it does not exist for current experiment run';   

    ELSE
      DELETE FROM autoflow
      WHERE ID = p_ID;
    
    END IF;

    IF ( count_records_stages > 0 ) THEN
       DELETE FROM autoflowStages
       WHERE autoflowID = p_ID;

    END IF;   

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_autoflow_stages_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_autoflow_stages_record`( p_personGUID    CHAR(36),
                                     	       p_password      VARCHAR(80),
                			       p_ID            INT )
    MODIFIES SQL DATA
BEGIN
  
  DECLARE count_records_stages INT;	

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    
    
    SELECT COUNT(*) INTO count_records_stages 
    FROM autoflowStages 
    WHERE autoflowID = p_ID;

    IF ( count_records_stages > 0 ) THEN
       DELETE FROM autoflowStages
       WHERE autoflowID = p_ID;

    END IF;   

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_buffer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_buffer`( p_personGUID CHAR(36),
                                 p_password   VARCHAR(80),
                                 p_bufferID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_buffers INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) THEN

    
    SELECT COUNT(*) INTO count_buffers
    FROM solutionBuffer
    WHERE bufferID = p_bufferID;

    IF ( count_buffers = 0 ) THEN
    
      DELETE FROM bufferLink
      WHERE bufferID = p_bufferID;

      DELETE FROM bufferPerson
      WHERE bufferID = p_bufferID;

      DELETE FROM extinctionProfile 
      WHERE componentID = p_bufferID
      AND   componentType = 'Buffer';

      DELETE FROM buffer
      WHERE bufferID = p_bufferID;

    ELSE
      SET @US3_LAST_ERRNO = @BUFFER_IN_USE;
      SET @US3_LAST_ERROR = 'The buffer is in use in a solution';

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_buffer_components` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_buffer_components`( p_personGUID CHAR(36),
                                            p_password   VARCHAR(80),
                                            p_bufferID   INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) THEN
    DELETE FROM bufferLink
    WHERE bufferID = p_bufferID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_cell_experiments` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_cell_experiments`( p_personGUID   CHAR(36),
                                           p_password     VARCHAR(80),
                                           p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    DELETE FROM cell
    WHERE experimentID = p_experimentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_editedData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_editedData`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_editedDataID INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN

    
    DELETE      reportDocument, documentLink
    FROM        reportDocument
    LEFT JOIN   documentLink ON ( documentLink.reportDocumentID = reportDocument.reportDocumentID )
    WHERE       reportDocument.editedDataID = p_editedDataID;

    DELETE      model, noise, modelPerson, pcsa_modelrecs
    FROM        editedData
    LEFT JOIN   model       ON ( model.editedDataID   = editedData.editedDataID )
    LEFT JOIN   noise       ON ( noise.modelID        = model.modelID )
    LEFT JOIN   modelPerson ON ( modelPerson.modelID  = model.modelID )
    LEFT JOIN   pcsa_modelrecs ON ( pcsa_modelrecs.editedDataID = editedData.editedDataID )
    WHERE       editedData.editedDataID = p_editedDataID;

    DELETE      FROM editedData
    WHERE       editedData.editedDataID = p_editedDataID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_eprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_eprofile`( p_personGUID    CHAR(36),
                                  p_password      VARCHAR(80),
                                  p_componentID   INT,
                                  p_componentType enum( 'Buffer', 'Analyte', 'Sample' ) )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    DELETE FROM extinctionProfile
    WHERE  componentID   = p_componentID
    AND    componentType = p_componentType;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_experiment`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE no_more_requestIDs TINYINT DEFAULT 0;
  DECLARE l_requestID INT;
  DECLARE l_reportID  INT;

  
  DECLARE request_csr CURSOR FOR
    SELECT HPCAnalysisRequestID FROM HPCAnalysisRequest
    WHERE  experimentID = p_experimentID;

  DECLARE CONTINUE HANDLER FOR NOT FOUND 
    SET no_more_requestIDs = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET no_more_requestIDs = 0;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN

    
    
    DELETE      noise
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    LEFT JOIN   model       ON ( model.editedDataID   = editedData.editedDataID )
    LEFT JOIN   noise       ON ( noise.modelID        = model.modelID )
    WHERE       rawData.experimentID = p_experimentID;

    DELETE      model, modelPerson
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    LEFT JOIN   model       ON ( model.editedDataID   = editedData.editedDataID )
    LEFT JOIN   modelPerson ON ( modelPerson.modelID  = model.modelID )
    WHERE       rawData.experimentID = p_experimentID;

    
    
    
    
    
    
    
    
    
    
    
    

    
    SELECT reportID INTO l_reportID
    FROM   report
    WHERE  experimentID = p_experimentID;

    CALL delete_report( p_personGUID, p_password, l_reportID );

    DELETE      editedData
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    WHERE       rawData.experimentID = p_experimentID;

    DELETE FROM rawData
    WHERE       experimentID = p_experimentID;

    DELETE FROM experimentPerson
    WHERE experimentID = p_experimentID;

    DELETE FROM speedstep
    WHERE experimentID = p_experimentID;

    DELETE FROM timestate
    WHERE experimentID = p_experimentID;

    DELETE FROM experiment
    WHERE experimentID = p_experimentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_experiment_solutions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_experiment_solutions`( p_personGUID   CHAR(36),
                                               p_password     VARCHAR(80),
                                               p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN

    DELETE FROM experimentSolutionChannel
    WHERE       experimentID = p_experimentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_HPCRequest` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_HPCRequest`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_requestID    INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_investigatorGUID CHAR(36);
  DECLARE l_method           ENUM('2DSA','2DSA_MW','GA','GA_MW','GA_SC','DMGA','PCSA');

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT    investigatorGUID
  INTO      l_investigatorGUID
  FROM      HPCAnalysisRequest
  WHERE     HPCAnalysisRequestID = p_requestID;

  
  SELECT    method
  INTO      l_method
  FROM      HPCAnalysisRequest
  WHERE     HPCAnalysisRequestID = p_requestID;

  
  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) ||
       ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
         ( l_investigatorGUID = p_personGUID )           )              ) THEN
  
    
    
    DELETE      HPCAnalysisResultData, noise
    FROM        HPCAnalysisResult
    LEFT JOIN   HPCAnalysisResultData
      ON        ( HPCAnalysisResult.HPCAnalysisResultID = HPCAnalysisResultData.HPCAnalysisResultID )
    LEFT JOIN   noise
      ON        ( HPCAnalysisResultData.resultID = noise.noiseID )
    WHERE       HPCAnalysisResultData.HPCAnalysisResultType = 'noise'
    AND         HPCAnalysisResult.HPCAnalysisRequestID = p_requestID;

    DELETE      HPCAnalysisResultData, model, modelPerson
    FROM        HPCAnalysisResult
    LEFT JOIN   HPCAnalysisResultData
      ON        ( HPCAnalysisResult.HPCAnalysisResultID = HPCAnalysisResultData.HPCAnalysisResultID )
    LEFT JOIN   model       ON ( HPCAnalysisResultData.resultID = model.modelID )
    LEFT JOIN   modelPerson ON ( model.modelID  = modelPerson.modelID )
    WHERE       HPCAnalysisResultData.HPCAnalysisResultType = 'model'
    AND         HPCAnalysisResult.HPCAnalysisRequestID = p_requestID;

    DELETE FROM HPCAnalysisResult
    WHERE       HPCAnalysisResult.HPCAnalysisRequestID = p_requestID;

    DELETE      HPCDataset, HPCRequestData
    FROM        HPCAnalysisRequest
    LEFT JOIN   HPCDataset
      ON        ( HPCAnalysisRequest.HPCAnalysisRequestID = HPCDataset.HPCAnalysisRequestID )
    LEFT JOIN   HPCRequestData
      ON        ( HPCDataset.HPCDatasetID = HPCDataset.HPCDatasetID )
    WHERE       HPCAnalysisRequest.HPCAnalysisRequestID = p_requestID;

    IF ( l_method = '2DSA' ) THEN
      DELETE FROM 2DSA_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    ELSEIF ( l_method = '2DSA_MW' ) THEN
      DELETE FROM 2DSA_MW_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    ELSEIF ( l_method = 'GA_MW' ) THEN
      DELETE FROM GA_MW_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    ELSEIF ( l_method = 'GA_SC' ) THEN
      DELETE FROM GA_SC_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    ELSEIF ( l_method = 'GA' ) THEN
      DELETE      GA_Settings, HPCSoluteData
      FROM        GA_Settings
      LEFT JOIN   HPCSoluteData
        ON        ( GA_Settings.GA_SettingsID = HPCSoluteData.GA_SettingsID )
      WHERE       HPCAnalysisRequestID = p_requestID;

    ELSEIF ( l_method = 'DMGA' ) THEN
      DELETE FROM DMGA_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    ELSEIF ( l_method = 'PCSA' ) THEN
      DELETE FROM PCSA_Settings
      WHERE       HPCAnalysisRequestID = p_requestID;
  
    END IF;

    
    DELETE FROM HPCAnalysisRequest
    WHERE       HPCAnalysisRequestID = p_requestID;

  ELSEIF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) != @OK ) &&
           ( ( verify_user( p_personGUID, p_password ) != @OK ) ||
             ( l_investigatorGUID = p_personGUID )           )              ) THEN
    
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to delete this analysis';

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_instrument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_instrument`( p_personGUID    CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_instrumentID  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_instruments INT;	
	
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN  ) = @OK ) THEN
    
    
    SELECT COUNT(*) INTO count_instruments 
    FROM experiment 
    WHERE instrumentID = p_instrumentID;

    IF ( count_instruments = 0 ) THEN
       DELETE FROM instrument
       WHERE instrumentID = p_instrumentID;

    ELSE
      SET @US3_LAST_ERRNO = @INSTRUMENT_IN_USE;
      SET @US3_LAST_ERROR = 'The instrument is in use in experiment';   
    
    END IF;  

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_model` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_model`( p_personGUID  CHAR(36),
                                p_password  VARCHAR(80),
                                p_modelID   INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_model_permission( p_personGUID, p_password, p_modelID ) = @OK ) THEN

    
    
    DELETE      noise
    FROM        model
    LEFT JOIN   noise ON ( noise.modelID = model.modelID )
    WHERE       model.modelID = p_modelID;

    DELETE FROM modelPerson
    WHERE modelID = p_modelID;

    DELETE FROM model
    WHERE modelID = p_modelID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_mrecs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_mrecs`( p_personGUID  CHAR(36),
                                p_password    VARCHAR(80),
                                p_mrecsID     INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_mrecs_permission( p_personGUID, p_password, p_mrecsID ) = @OK ) THEN

    DELETE FROM pcsa_modelrecs
    WHERE mrecsID = p_mrecsID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_noise` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_noise`( p_personGUID  CHAR(36),
                                p_password    VARCHAR(80),
                                p_noiseID     INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_noise_permission( p_personGUID, p_password, p_noiseID ) = @OK ) THEN

    DELETE FROM noise
    WHERE noiseID = p_noiseID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_person` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_person`( p_personGUID     CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_ID           INT )
    MODIFIES SQL DATA
BEGIN
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    UPDATE people SET
           activated    = false,
           lastLogin    = NOW()
    WHERE  personID     = p_ID;

    SET @LAST_INSERT_ID = LAST_INSERT_ID();

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_project` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_project`( p_personGUID  CHAR(36),
                                  p_password    VARCHAR(80),
                                  p_projectID   INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_project_permission( p_personGUID, p_password, p_projectID ) = @OK ) THEN

    DELETE FROM projectPerson
    WHERE projectID = p_projectID;

    DELETE FROM project
    WHERE projectID = p_projectID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_protocol` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_protocol`( p_personGUID  CHAR(36),
                                   p_password  VARCHAR(80),
                                   p_protocolID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_protocols INT;	

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_protocol_permission( p_personGUID, p_password, p_protocolID ) = @OK ) THEN

     
     
     DELETE FROM protocolPerson
     WHERE protocolID = p_protocolID;

     DELETE FROM protocol
     WHERE protocolID = p_protocolID;






















  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_rawData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_rawData`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  
  DECLARE l_last_row INT DEFAULT 0;
  DECLARE l_reportID INT;
  DECLARE get_reports CURSOR FOR
    SELECT reportID
    FROM   report
    WHERE  experimentID = p_experimentID;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET l_last_row = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    
    OPEN get_reports;
    rpt_cursor: LOOP
      FETCH get_reports INTO l_reportID;
      IF ( l_last_row = 1 ) THEN
         LEAVE rpt_cursor;
      END IF;

      
      CALL delete_report( p_personGUID, p_password, l_reportID );

    END LOOP rpt_cursor;
    CLOSE get_reports;

    
    
    DELETE      noise
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    LEFT JOIN   model       ON ( model.editedDataID   = editedData.editedDataID )
    LEFT JOIN   noise       ON ( noise.modelID        = model.modelID )
    WHERE       rawData.experimentID = p_experimentID;

    DELETE      model, modelPerson
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    LEFT JOIN   model       ON ( model.editedDataID   = editedData.editedDataID )
    LEFT JOIN   modelPerson ON ( modelPerson.modelID  = model.modelID )
    WHERE       rawData.experimentID = p_experimentID;

    DELETE      editedData
    FROM        rawData
    LEFT JOIN   editedData  ON ( editedData.rawDataID = rawData.rawDataID )
    WHERE       rawData.experimentID = p_experimentID;

    DELETE FROM rawData
    WHERE       experimentID = p_experimentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_report`( p_personGUID  CHAR(36),
                                 p_password    VARCHAR(80),
                                 p_reportID    INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_report_permission( p_personGUID, p_password, p_reportID ) = @OK ) THEN

    
    
    DELETE      documentLink, reportDocument
    FROM        report
    LEFT JOIN   reportTriple   ON ( report.reportID = reportTriple.reportID )
    LEFT JOIN   documentLink   ON ( reportTriple.reportTripleID = documentLink.reportTripleID )
    LEFT JOIN   reportDocument ON ( documentLink.reportDocumentID = reportDocument.reportDocumentID )
    WHERE       report.reportID = p_reportID;

    DELETE      reportTriple
    FROM        report
    LEFT JOIN   reportTriple ON ( report.reportID = reportTriple.reportID )
    WHERE       report.reportID = p_reportID;

    DELETE FROM report
    WHERE reportID = p_reportID;
    
    DELETE FROM reportPerson
    WHERE reportID = p_reportID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_reportDocument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_reportDocument`( p_personGUID        CHAR(36),
                                         p_password          VARCHAR(80),
                                         p_reportDocumentID  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_reportCount    INT;
  DECLARE l_reportID       INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   l_reportCount
  FROM   reportDocument
  WHERE  reportDocumentID = p_reportDocumentID;

  SELECT reportID
  INTO   l_reportID
  FROM   documentLink, reportTriple
  WHERE  reportDocumentID = p_reportDocumentID
  AND    documentLink.reportTripleID = reportTriple.reportTripleID;

  IF ( l_reportCount != 1 ) THEN
    
    SET @US3_LAST_ERRNO = @NO_REPORT_DOCUMENT;
    SET @US3_LAST_ERROR = 'MySQL: No report document with that ID exists';

  ELSEIF ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) THEN

    
    
    DELETE FROM reportDocument 
    WHERE       reportDocumentID = p_reportDocumentID;

    DELETE FROM documentLink 
    WHERE       reportDocumentID = p_reportDocumentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_reportTriple` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_reportTriple`( p_personGUID        CHAR(36),
                                       p_password          VARCHAR(80),
                                       p_reportTripleID    INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_reportID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) THEN

    
    
    DELETE      documentLink, reportDocument
    FROM        reportTriple
    LEFT JOIN   documentLink   ON ( reportTriple.reportTripleID = documentLink.reportTripleID )
    LEFT JOIN   reportDocument ON ( documentLink.reportDocumentID = reportDocument.reportDocumentID )
    WHERE       reportTriple.reportTripleID = p_reportTripleID;

    DELETE FROM reportTriple
    WHERE       reportTripleID = p_reportTripleID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_rotor` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_rotor`( p_personGUID   CHAR(36),
                                p_password     VARCHAR(80),
                                p_rotorID      INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_experiments          INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      rotorID = p_rotorID;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) ) THEN
    IF ( count_experiments > 0 ) THEN
      
      SET @US3_LAST_ERRNO = @ROTOR_IN_USE;
      SET @US3_LAST_ERROR = CONCAT( "MySQL: the rotor is in use, ",
                                    "and cannot be deleted\n" );

    ELSE
      
      
      
      DELETE FROM rotorCalibration 
      WHERE       rotorID   = p_rotorID;

      DELETE FROM rotor
      WHERE       rotorID   = p_rotorID;
        
    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_rotor_calibration` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_rotor_calibration`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_rotor_calibrationID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_experiments          INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      rotorCalibrationID = p_rotor_calibrationID;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) ) THEN
    IF ( count_experiments > 0 ) THEN
      
      SET @US3_LAST_ERRNO = @CALIB_IN_USE;
      SET @US3_LAST_ERROR = CONCAT( "MySQL: the rotor calibration profile is in use, ",
                                    "and cannot be deleted\n" );

    ELSE
      
      
      DELETE FROM rotorCalibration
      WHERE       rotorCalibrationID   = p_rotor_calibrationID;
        
    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_solution` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_solution`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_solutionID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_solutions INT;
  DECLARE count_protosols INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) = @OK ) THEN

    
    SELECT COUNT(*) INTO count_solutions
    FROM experimentSolutionChannel
    WHERE solutionID = p_solutionID;

    
    SELECT COUNT(*) INTO count_protosols
    FROM solution ss, protocol pp
    WHERE ss.solutionID = p_solutionID
    AND   ( ss.description = pp.solution1 OR ss.description = pp.solution2 );

    IF ( ( count_solutions = 0 ) && ( count_protosols = 0 ) ) THEN
    
      
      
      DELETE FROM solutionBuffer
      WHERE       solutionID   = p_solutionID;
      
      DELETE FROM solutionAnalyte
      WHERE       solutionID   = p_solutionID;
      
      DELETE FROM solutionPerson
      WHERE       solutionID   = p_solutionID;
      
      DELETE FROM solution
      WHERE       solutionID   = p_solutionID;

    ELSE
      SET @US3_LAST_ERRNO = @SOLUTION_IN_USE;
      SET @US3_last_ERROR = 'The solution is in use in an experiment or protocol';

    END IF;
    
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_solutionAnalytes` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_solutionAnalytes`( p_personGUID   CHAR(36),
                                           p_password     VARCHAR(80),
                                           p_solutionID   INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) = @OK ) THEN

    DELETE FROM solutionAnalyte
    WHERE       solutionID = p_solutionID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_spectrum` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_spectrum`( p_personGUID    CHAR(36),
                                  p_password      VARCHAR(80),
                                  p_componentID   INT,
                                  p_componentType enum( 'Buffer', 'Analyte' ),
                                  p_opticsType    enum( 'Extinction', 'Refraction', 'Fluorescence' ) )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    DELETE FROM spectrum
    WHERE  componentID   = p_componentID
    AND    componentType = p_componentType
    AND    opticsType    = p_opticsType;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_speedsteps` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_speedsteps`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    DELETE FROM speedstep
    WHERE experimentID = p_experimentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `delete_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `delete_timestate`( p_personGUID   CHAR(36),
                                    p_password     VARCHAR(80),
                                    p_timestateID  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_timestates INT;
  DECLARE l_experimentID   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT experimentID
  INTO   l_experimentID
  FROM   timestate
  WHERE  timestateID = p_timestateID;

  IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN

    DELETE FROM timestate
    WHERE       timestateID   = p_timestateID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `download_aucData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `download_aucData`( p_personGUID   CHAR(36),
                                    p_password     VARCHAR(80),
                                    p_rawDataID    INT )
    READS SQL DATA
BEGIN
  DECLARE l_count_aucData INT;
  DECLARE l_experimentID  INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT COUNT(*)
  INTO   l_count_aucData
  FROM   rawData
  WHERE  rawDataID = p_rawDataID;

SET @DEBUG = CONCAT('Raw data ID = ', p_rawDataID,
                    'Count = ', l_count_aucData );
  
  SELECT experimentID
  INTO   l_experimentID
  FROM   rawData
  WHERE  rawDataID = p_rawDataID;

  IF ( l_count_aucData != 1 ) THEN
    
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows exist with that ID (or too many rows)';

    SELECT @NOROWS AS status;
    
  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) != @OK ) THEN
 
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE

    
    SELECT @OK AS status;

    SELECT data, MD5( data )
    FROM   rawData
    WHERE  rawDataID = p_rawDataID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `download_editData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `download_editData`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_editedDataID INT )
    READS SQL DATA
BEGIN
  DECLARE l_count_editData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT COUNT(*)
  INTO   l_count_editData
  FROM   editedData
  WHERE  editedDataID = p_editedDataID;

SET @DEBUG = CONCAT('Edited data ID = ', p_editedDataID,
                    'Count = ', l_count_editData );
  IF ( l_count_editData != 1 ) THEN
    
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows exist with that ID (or too many rows)';

    SELECT @NOROWS AS status;
    
  ELSEIF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) != @OK ) THEN
 
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE

    
    SELECT @OK AS status;

    SELECT data, MD5( data )
    FROM   editedData
    WHERE  editedDataID = p_editedDataID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `download_reportContents` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `download_reportContents`( p_personGUID       CHAR(36),
                                           p_password         VARCHAR(80),
                                           p_reportDocumentID INT )
    READS SQL DATA
BEGIN
  DECLARE l_count_reportContents INT;
  DECLARE l_reportID             INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT COUNT(*)
  INTO   l_count_reportContents
  FROM   reportDocument
  WHERE  reportDocumentID = p_reportDocumentID;

SET @DEBUG = CONCAT('Report document ID = ', p_reportDocumentID,
                    'Count = ', l_count_reportContents );

  
  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple, documentLink
  WHERE  reportDocumentID = p_reportDocumentID
  AND    reportTriple.reportTripleID = documentLink.reportTripleID;

  IF ( l_count_reportContents != 1 ) THEN
    
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows exist with that ID (or too many rows)';

    SELECT @NOROWS AS status;
    
  ELSEIF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
 
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE

    
    SELECT @OK AS status;

    SELECT contents, MD5( contents )
    FROM   reportDocument
    WHERE  reportDocumentID = p_reportDocumentID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `download_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `download_timestate`( p_personGUID   CHAR(36),
                                      p_password     VARCHAR(80),
                                      p_timestateID  INT )
    READS SQL DATA
BEGIN
  DECLARE l_count_timestate INT;
  DECLARE l_experimentID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT COUNT(*)
  INTO   l_count_timestate
  FROM   timestate
  WHERE  timestateID = p_timestateID;

SET @DEBUG = CONCAT('timestateID = ', p_timestateID,
                    'Count = ', l_count_timestate );
  
  SELECT experimentID
  INTO   l_experimentID
  FROM   timestate
  WHERE  timestateID = p_timestateID;

  IF ( l_count_timestate != 1 ) THEN
    
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows exist with that ID (or too many rows)';

    SELECT @NOROWS AS status;
    
  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) != @OK ) THEN
 
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE

    
    SELECT @OK AS status;

    SELECT data, MD5( data )
    FROM   timestate
    WHERE  timestateID = p_timestateID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `fitmen_autoflow_analysis_status` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `fitmen_autoflow_analysis_status`( p_personGUID CHAR(36),
                                              p_password   VARCHAR(80),
                                              p_requestID  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  DECLARE unique_start TINYINT DEFAULT 0;
       

  DECLARE exit handler for sqlexception
   BEGIN
      
    ROLLBACK;
   END;
   
  DECLARE exit handler for sqlwarning
   BEGIN
     
    ROLLBACK;
   END;


  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';


  START TRANSACTION;
  
  SELECT     analysisFitmen 
  INTO       current_status
  FROM       autoflowAnalysisStages
  WHERE      requestID = p_requestID FOR UPDATE;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status = 'unknown' ) THEN
      UPDATE  autoflowAnalysisStages
      SET     analysisFitmen = 'STARTED'
      WHERE   requestID = p_requestID;

      SET unique_start = 1;

    END IF;

  END IF;

  SELECT unique_start as status;
  
  COMMIT;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `fitmen_autoflow_analysis_status_revert` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `fitmen_autoflow_analysis_status_revert`( p_personGUID CHAR(36),
                                                        p_password   VARCHAR(80),
                                                        p_id  INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE current_status TEXT;
  
       
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SELECT     analysisFitmen 
  INTO       current_status
  FROM       autoflowAnalysisStages
  WHERE      requestID = p_id;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( current_status != 'unknown' ) THEN
      UPDATE  autoflowAnalysisStages
      SET     analysisFitmen = DEFAULT
      WHERE   requestID = p_id;

    END IF;

  END IF;

  
  
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_abstractCenterpiece_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_abstractCenterpiece_info`( p_personGUID CHAR(36),
                                                p_password   VARCHAR(80),
                                                p_abstractCenterpieceID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_abstract_centerpieces INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_abstract_centerpieces
  FROM       abstractCenterpiece
  WHERE      abstractCenterpieceID = p_abstractCenterpieceID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_abstract_centerpieces = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   abstractCenterpieceGUID, name, channels, bottom, shape,
               maxRPM, pathLength, angle, width
      FROM     abstractCenterpiece
      WHERE    abstractCenterpieceID = p_abstractCenterpieceID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_abstractCenterpiece_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_abstractCenterpiece_names`( p_personGUID CHAR(36),
                                                 p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_abstract_centerpieces INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_abstract_centerpieces
    FROM      abstractCenterpiece;

    IF ( count_abstract_centerpieces = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT abstractCenterpieceID, name
      FROM abstractCenterpiece
      ORDER BY name;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_abstractRotorID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_abstractRotorID_from_GUID`( p_abstractRotorGUID   CHAR(36),
                                                 p_password     VARCHAR(80),
                                                 p_lookupGUID   CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_abstractRotor  INT;
  DECLARE l_abstractRotorID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_abstractRotor
  FROM       abstractRotor
  WHERE      abstractRotorGUID = p_lookupGUID;

  IF ( count_abstractRotor = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT abstractRotorID
    INTO   l_abstractRotorID
    FROM   abstractRotor
    WHERE  abstractRotorGUID = p_lookupGUID
    LIMIT  1;                           

    SELECT @OK AS status;

    SELECT l_abstractRotorID AS abstractRotorID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_abstractRotor_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_abstractRotor_info`( p_personGUID CHAR(36),
                                          p_password   VARCHAR(80),
                                          p_abstractRotorID    INT )
    READS SQL DATA
BEGIN
  DECLARE count_abstractRotors INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_abstractRotors
  FROM       abstractRotor
  WHERE      abstractRotorID = p_abstractRotorID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_abstractRotors = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   abstractRotorGUID, name, materialName, numHoles,
               maxRPM, magnetOffset, cellCenter, manufacturer
      FROM     abstractRotor
      WHERE    abstractRotorID = p_abstractRotorID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_abstractRotor_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_abstractRotor_names`( p_personGUID CHAR(36),
                                           p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_abstract_rotors INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_abstract_rotors
    FROM      abstractRotor;

    IF ( count_abstract_rotors = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   abstractRotorID, name
      FROM     abstractRotor
      ORDER BY UPPER( name );
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_analyteID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_analyteID`( p_personGUID  CHAR(36),
                                 p_password    VARCHAR(80),
                                 p_analyteGUID CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_anal INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_anal      = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_anal
    FROM      analyte
    WHERE     analyteGUID = p_analyteGUID;

    IF ( TRIM( p_analyteGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The analyteGUID parameter to the get_analyteID ',
                                    'function cannot be empty' );

    ELSEIF ( count_anal < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   analyteID
      FROM     analyte
      WHERE    analyteGUID = p_analyteGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_analyte_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_analyte_desc`( p_personGUID CHAR(36),
                                    p_password   VARCHAR(80),
                                    p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_analytes( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   a.analyteID, description, type, gradientForming
        FROM     analyte a, analytePerson
        WHERE    a.analyteID = analytePerson.analyteID
        AND      analytePerson.personID = p_ID
        ORDER BY a.analyteID DESC;
   
      ELSE
        SELECT   a.analyteID, description, type, gradientForming
        FROM     analyte a, analytePerson
        WHERE    a.analyteID = analytePerson.analyteID
        ORDER BY a.analyteID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this analyte';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_analytes( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   a.analyteID, description, type
      FROM     analyte a, analytePerson
      WHERE    a.analyteID = analytePerson.analyteID
      AND      analytePerson.personID = @US3_ID
      ORDER BY a.analyteID DESC;
      

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_analyte_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_analyte_info`( p_personGUID CHAR(36),
                                    p_password   VARCHAR(80),
                                    p_analyteID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_analytes INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_analytes
  FROM       analyte
  WHERE      analyteID = p_analyteID;

  IF ( verify_analyte_permission( p_personGUID, p_password, p_analyteID ) = @OK ) THEN
    IF ( count_analytes = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   analyteGUID, type, sequence, vbar, description, spectrum, molecularWeight, gradientForming, personID 
      FROM     analyte a, analytePerson ap
      WHERE    a.analyteID = ap.analyteID
      AND      a.analyteID = p_analyteID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_aprofile_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_aprofile_desc`( p_personGUID CHAR(36),
                                     p_password VARCHAR(80) )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( count_aprofiles( p_personGUID, p_password ) < 1 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;
  
    SELECT   aprofileID, aprofileGUID, name, xml,
             timestamp2UTC( dateUpdated ) AS UTC_lastUpdated
    FROM     analysisprofile
    ORDER BY aprofileID DESC;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_aprofile_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_aprofile_info`( p_personGUID    CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_aprofileGUID  CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_aprofiles INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_aprofiles
  FROM       analysisprofile
  WHERE      aprofileGUID = p_aprofileGUID;

  IF ( count_aprofiles = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_aprofiles > 1 ) THEN
    SET @US3_LAST_ERRNO = @MORE_THAN_SINGLE_ROW;
    SET @US3_LAST_ERROR = 'MySQL: more than a single row for an analysis profile';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;

    SELECT   aprofileID, name, xml,
             timestamp2UTC( dateUpdated ) AS UTC_lastUpdated
    FROM     analysisprofile
    WHERE    aprofileGUID = p_aprofileGUID;
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_aprofile_info_byID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_aprofile_info_byID`( p_personGUID  CHAR(36),
                                          p_password    VARCHAR(80),
                                          p_aprofileID  INT(11) )
    READS SQL DATA
BEGIN
  DECLARE count_aprofiles INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_aprofiles
  FROM       analysisprofile
  WHERE      aprofileID = p_aprofileID;

  IF ( count_aprofiles = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_aprofiles > 1 ) THEN
    SET @US3_LAST_ERRNO = @MORE_THAN_SINGLE_ROW;
    SET @US3_LAST_ERROR = 'MySQL: more than a single row for an analysis profile';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;

    SELECT   aprofileGUID, name, xml,
             timestamp2UTC( dateUpdated ) AS UTC_lastUpdated
    FROM     analysisprofile
    WHERE    aprofileID = p_aprofileID;
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_autoflow_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_autoflow_desc`( p_personGUID    CHAR(36),
                                       	p_password      VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow;


  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   ID, protName, cellChNum, tripleNum, duration, runName, expID, 
      	       runID, status, dataPath, optimaName, runStarted, invID, created, gmpRun  
      FROM     autoflow;
     
    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_bufferID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_bufferID`( p_personGUID CHAR(36),
                                p_password   VARCHAR(80),
                                p_bufferGUID CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_buff INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_buff   = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_buff
    FROM      buffer
    WHERE     bufferGUID = p_bufferGUID;

    IF ( TRIM( p_bufferGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The bufferGUID parameter to the get_bufferID ',
                                    'function cannot be empty' );

    ELSEIF ( count_buff < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   bufferID
      FROM     buffer
      WHERE    bufferGUID = p_bufferGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_buffer_components` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_buffer_components`( p_personGUID CHAR(36),
                                         p_password   VARCHAR(80),
                                         p_bufferID   INT )
    READS SQL DATA
BEGIN
  DECLARE count_components INT;
  DECLARE is_private       TINYINT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     private
  INTO       is_private
  FROM       bufferPerson
  WHERE      bufferID = p_bufferID;

  
  IF ( ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) ||
       ( ( verify_user( p_personGUID, p_password ) = @OK ) && ! is_private ) ) THEN
    SELECT    COUNT(*)
    INTO      count_components
    FROM      bufferLink
    WHERE     bufferID = p_bufferID;

    IF ( count_components = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   l.bufferComponentID, description, viscosity, density, concentration
      FROM     bufferLink l, bufferComponent c
      WHERE    l.bufferComponentID = c.bufferComponentID
      AND      l.bufferID = p_bufferID
      ORDER BY description;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_buffer_component_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_buffer_component_desc`( p_personGUID CHAR(36),
                                             p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_components INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_components
    FROM      bufferComponent;

    IF ( count_components = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT bufferComponentID, description
      FROM bufferComponent
      ORDER BY description;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_buffer_component_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_buffer_component_info`( p_personGUID  CHAR(36),
                                             p_password    VARCHAR(80),
                                             p_componentID INT )
    READS SQL DATA
BEGIN
  DECLARE count_components INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_components
  FROM       bufferComponent
  WHERE      bufferComponentID = p_componentID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_components = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   units, description, viscosity, density, c_range,
               gradientForming
      FROM     bufferComponent
      WHERE    bufferComponentID = p_componentID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_buffer_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_buffer_desc`( p_personGUID CHAR(36),
                                   p_password   VARCHAR(80),
                                   p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_buffers( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   b.bufferID, description
        FROM     buffer b, bufferPerson
        WHERE    b.bufferID = bufferPerson.bufferID
        AND      bufferPerson.personID = p_ID
        ORDER BY b.bufferID DESC;
   
      ELSE
        SELECT   b.bufferID, description
        FROM     buffer b, bufferPerson
        WHERE    b.bufferID = bufferPerson.bufferID
        ORDER BY b.bufferID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_buffers( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      IF ( p_ID > 0 ) THEN
        SELECT   b.bufferID, description
        FROM     buffer b, bufferPerson
        WHERE    b.bufferID = bufferPerson.bufferID
        AND      bufferPerson.personID = @US3_ID 
        ORDER BY b.bufferID DESC;
 
      ELSE
        SELECT   b.bufferID, description
        FROM     buffer b, bufferPerson
        WHERE    b.bufferID = bufferPerson.bufferID
        AND      ( ( bufferPerson.personID = @US3_ID ) ||
                 ( private = 0 ) )
        ORDER BY b.bufferID DESC;
      END IF;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_buffer_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_buffer_info`( p_personGUID CHAR(36),
                                   p_password   VARCHAR(80),
                                   p_bufferID   INT )
    READS SQL DATA
BEGIN
  DECLARE count_buffers INT;
  DECLARE is_private    TINYINT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET is_private      = 1;

  SELECT     COUNT(*)
  INTO       count_buffers
  FROM       buffer
  WHERE      bufferID = p_bufferID;

  SELECT     private
  INTO       is_private
  FROM       bufferPerson
  WHERE      bufferID = p_bufferID;

  
  IF ( ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) ||
       ( ( verify_user( p_personGUID, p_password ) = @OK ) && ! is_private ) ) THEN
    IF ( count_buffers = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   bufferGUID, description, compressibility, pH, viscosity,
               density, manual, personID, private
      FROM     buffer b, bufferPerson bp
      WHERE    b.bufferID = bp.bufferID
      AND      b.bufferID = p_bufferID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_editedData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_editedData`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_editedDataID INT )
    READS SQL DATA
BEGIN

  DECLARE count_editedData   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  SELECT     COUNT(*)
  INTO       count_editedData
  FROM       editedData
  WHERE      editedDataID = p_editedDataID;

  IF ( count_editedData = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    
    SELECT @OK as status;

    SELECT  rawDataID, editGUID, label, filename, comment, 
            timestamp2UTC( lastUpdated ) AS UTC_lastUpdated, 
            MD5( data ) AS checksum, LENGTH( data ) AS size
    FROM    editedData
    WHERE   editedDataID = p_editedDataID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_editedDataFilenamesIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_editedDataFilenamesIDs`( p_personGUID   CHAR(36),
                                 	     p_password     VARCHAR(80),
                                  	     p_label VARCHAR(80))
    READS SQL DATA
BEGIN
  DECLARE count_editedData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_editedData
  FROM       editedData
  WHERE      label = p_label;

  
  IF ( count_editedData = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;

    SELECT   filename, editedDataID, rawDataID, lastUpdated
    FROM     editedData
    WHERE    label = p_label;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_editedDataIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_editedDataIDs`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_rawDataID INT )
    READS SQL DATA
BEGIN
  DECLARE count_editedData INT;
  DECLARE l_experimentID   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_editedData
  FROM       editedData
  WHERE      rawDataID = p_rawDataID;

  
  SELECT     experimentID
  INTO       l_experimentID
  FROM       rawData
  WHERE      rawDataID = p_rawDataID;

  IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
    IF ( count_editedData = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   editedDataID, label, filename
      FROM     editedData
      WHERE    rawDataID = p_rawDataID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_editID`( p_personGUID    CHAR(36),
                              p_password      VARCHAR(80),
                              p_editGUID      CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_editedData INT;

  CALL config();
  SET @US3_LAST_ERRNO  = @OK;
  SET @US3_LAST_ERROR  = '';
  SET count_editedData = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_editedData
    FROM      editedData
    WHERE     editGUID = p_editGUID;

    IF ( TRIM( p_editGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The editGUID parameter to the ',
                                    'get_editID function cannot be empty' );

    ELSEIF ( count_editedData < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   editedDataID
      FROM     editedData
      WHERE    editGUID = p_editGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_edit_desc_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_edit_desc_by_runID`( p_personGUID   CHAR(36),
                                          p_password     VARCHAR(80),
                                          p_ID           INT,
                                          p_runID        VARCHAR(250) )
    READS SQL DATA
BEGIN
  DECLARE count_editData INT;
  DECLARE run_pattern VARCHAR(254);

  CALL config();
  SET run_pattern = CONCAT( p_runID, '.%' );
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT    COUNT(*)
  INTO      count_editData
  FROM      editedData
  WHERE     filename LIKE run_pattern;

  IF ( p_ID <= 0 ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';
   
      SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_editData < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT     editedDataID, editedData.label, editedData.filename,
                 editedData.rawDataID, rawData.experimentID,
                 timestamp2UTC( editedData.lastUpdated) AS UTC_lastUpdated, 
                 MD5( editedData.data ) AS checksum, LENGTH( editedData.data ) AS size,
                 experiment.type, editedData.editGUID
      FROM       editedData, rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = p_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        editedData.filename LIKE run_pattern
      AND        rawData.experimentID = experiment.experimentID
      AND        editedData.rawDataID = rawData.rawDataID
      ORDER BY   editedData.lastUpdated DESC;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view those edits';
     
    ELSEIF ( count_editData < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT     editedDataID, editedData.label, editedData.filename,
                 editedData.rawDataID, rawData.experimentID, 
                 timestamp2UTC( editedData.lastUpdated) AS UTC_lastUpdated, 
                 MD5( editedData.data ) AS checksum, LENGTH( editedData.data ) AS size,
                 experiment.type, editedData.editGUID
      FROM       editedData, rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        editedData.filename LIKE run_pattern
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      AND        editedData.rawDataID = rawData.rawDataID
      ORDER BY   editedData.lastUpdated DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_eprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_eprofile`( p_personGUID    CHAR(36),
                               p_password      VARCHAR(80),
                               p_componentID   INT,
                               p_componentType enum( 'Buffer', 'Analyte', 'Sample' ) )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_eprofile( p_personGUID, p_password, p_componentID, p_componentType ) < 1 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';
  
    SELECT @US3_LAST_ERRNO AS status;
      
  ELSE
    
    SELECT @OK AS status;
  
    SELECT   profileID, valueType, xml
    FROM     extinctionProfile
    WHERE    componentID   = p_componentID
    AND      componentType = p_componentType;
  
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_experiment_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_experiment_desc`( p_personGUID CHAR(36),
                                       p_password   VARCHAR(80),
                                       p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_experiments( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   e.experimentID, runID, type, runType, label,
                 timestamp2UTC( dateUpdated ) AS UTC_dateUpdated
        FROM     experiment e, experimentPerson p
        WHERE    e.experimentID = p.experimentID
        AND      p.personID = p_ID
        ORDER BY runID;
   
      ELSE
        SELECT   e.experimentID, runID, type, runType, label,
                 timestamp2UTC( dateUpdated ) AS UTC_dateUpdated
        FROM     experiment e, experimentPerson p
        WHERE    e.experimentID = p.experimentID
        ORDER BY runID;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this experiment';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_experiments( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   e.experimentID, runID, type, runType
      FROM     experiment e, experimentPerson p
      WHERE    e.experimentID = p.experimentID
      AND      p.personID = @US3_ID
      ORDER BY runID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_experiment_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_experiment_info`( p_personGUID   CHAR(36),
                                       p_password     VARCHAR(80),
                                       p_experimentID INT )
    READS SQL DATA
BEGIN
  DECLARE count_experiments INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      experimentID = p_experimentID;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    IF ( count_experiments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   experimentGUID, projectID, runID, labID, instrumentID, 
               operatorID, rotorID, rotorCalibrationID, type, runTemp, label, comment, 
               centrifugeProtocol, timestamp2UTC( dateUpdated ) AS UTC_dateUpdated, 
               personID, runType, RIProfile
      FROM     experiment e, experimentPerson ep
      WHERE    e.experimentID = ep.experimentID
      AND      e.experimentID = p_experimentID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_experiment_info_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_experiment_info_by_runID`( p_personGUID CHAR(36),
                                                p_password   VARCHAR(80),
                                                p_runID      VARCHAR(255),
                                                p_ID         INT )
    READS SQL DATA
BEGIN
  DECLARE count_experiments INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    SELECT COUNT(*)
    INTO   count_experiments
    FROM   experiment exp, experimentPerson ep
    WHERE  exp.experimentID = ep.experimentID
    AND    ep.personID      = p_ID
    AND    runID            = p_runID;

    
    
    IF ( p_ID = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_PERSON;
      SET @US3_LAST_ERROR = 'MySQL: No user with that ID exists';

      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_experiments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT projectID, ep.experimentID, experimentGUID, labID, instrumentID, 
             operatorID, rotorID, rotorCalibrationID, type, runTemp, label, comment, 
             centrifugeProtocol, timestamp2UTC( dateUpdated ) AS UTC_dateUpdated, 
             personID, runType, RIProfile
      FROM   experiment exp, experimentPerson ep
      WHERE  exp.experimentID   = ep.experimentID
      AND    ep.personID        = p_ID
      AND    runID              = p_runID;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT COUNT(*)
    INTO   count_experiments
    FROM   experiment exp, experimentPerson ep
    WHERE  exp.experimentID = ep.experimentID
    AND    ep.personID      = @US3_ID
    AND    runID            = p_runID;

    IF ( count_experiments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT projectID, ep.experimentID, experimentGUID, labID, instrumentID, 
             operatorID, rotorID, rotorCalibrationID, type, runTemp, label, comment, 
             centrifugeProtocol, timestamp2UTC( dateUpdated ) AS UTC_dateUpdated, 
             personID, runType, RIProfile
      FROM   experiment exp, experimentPerson ep
      WHERE  exp.experimentID   = ep.experimentID
      AND    ep.personID        = @US3_ID
      AND    runID              = p_runID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_experiment_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_experiment_timestate`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_experimentID INT )
    READS SQL DATA
BEGIN

  DECLARE count_timestate INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  SELECT     COUNT(*)
  INTO       count_timestate
  FROM       timestate
  WHERE      experimentID = p_experimentID;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_timestate < 1 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_timestate > 1 ) THEN
    SET @US3_LAST_ERRNO = @MORE_THAN_SINGLE_ROW;
    SET @US3_LAST_ERROR = 'MySQL: more than a single row for an experiment';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT @OK as status;

    SELECT  timestateID, filename, definitions,
            MD5( data ) AS checksum, LENGTH( data ) AS size,
            timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
    FROM    timestate
    WHERE   experimentID = p_experimentID
    ORDER BY timestateID
    LIMIT 1;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_instrument_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_instrument_info`( p_personGUID    CHAR(36),
                                       p_password      VARCHAR(80),
                                       p_instrumentID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_instruments INT;
  DECLARE count_rcal_instrs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_instruments
  FROM       instrument
  WHERE      instrumentID = p_instrumentID;

  SELECT     COUNT(*)
  INTO       count_rcal_instrs
  FROM       instrument ins, radialCalibration rac
  WHERE      ins.instrumentID = p_instrumentID
  AND        rac.radialCalID = ins.radialCalID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_instruments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      IF ( count_rcal_instrs = 0 ) THEN
        SELECT   name, serialNumber, labID, dateUpdated, radialCalID
        FROM     instrument
        WHERE    instrumentID = p_instrumentID;

      ELSE
        SELECT   name, serialNumber, labID, dateUpdated, radialCalID,
                 rac.speed, rac.rotorCalID, roc.coeff1, roc.coeff2
        FROM     instrument ins, radialCalibration rac, rotorCalibration roc
        WHERE    instrumentID = p_instrumentID
        AND      rac.radialCalID = ins.radialCalID
        AND      roc.rotorCalibrationID = rac.rotorCalID ;

      END IF;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_instrument_info_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_instrument_info_new`( p_personGUID    CHAR(36),
                                       p_password      VARCHAR(80),
                                       p_instrumentID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_instruments INT;
  DECLARE count_rcal_instrs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_instruments
  FROM       instrument
  WHERE      instrumentID = p_instrumentID;

  SELECT     COUNT(*)
  INTO       count_rcal_instrs
  FROM       instrument ins, radialCalibration rac
  WHERE      ins.instrumentID = p_instrumentID
  AND        rac.radialCalID = ins.radialCalID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_instruments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      IF ( count_rcal_instrs = 0 ) THEN
        SELECT   name, serialNumber, labID, dateUpdated, radialCalID, 
	         optimaHost, optimaPort, optimaDBname, optimaDBusername, 
		 DECODE(optimaDBpassw,'secretOptimaDB'), selected, opsys1, opsys2, opsys3, RadCalWvl, chromaticAB, optimaPortMsg
        FROM     instrument
        WHERE    instrumentID = p_instrumentID;

      ELSE
        SELECT   name, serialNumber, labID, dateUpdated, radialCalID,
                 rac.speed, rac.rotorCalID, roc.coeff1, roc.coeff2
        FROM     instrument ins, radialCalibration rac, rotorCalibration roc
        WHERE    instrumentID = p_instrumentID
        AND      rac.radialCalID = ins.radialCalID
        AND      roc.rotorCalibrationID = rac.rotorCalID ;

      END IF;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_instrument_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_instrument_names`( p_personGUID CHAR(36),
                                        p_password   VARCHAR(80),
                                        p_labID      INT )
    READS SQL DATA
BEGIN
  DECLARE count_instruments INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_instruments
    FROM      instrument
    WHERE     labID = p_labID;

    IF ( count_instruments = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   instrumentID, name, selected 
      FROM     instrument
      WHERE    labID = p_labID 
      ORDER BY name;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_instrument_selected` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_instrument_selected`( p_personGUID    CHAR(36),
                                           p_password      VARCHAR(80),
					   p_selected      TINYINT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    SELECT instrumentID FROM instrument WHERE selected = p_selected;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_labID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_labID_from_GUID`( p_labGUID      CHAR(36),
                                       p_password     VARCHAR(80),
                                       p_lookupGUID   CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_lab  INT;
  DECLARE l_labID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_lab
  FROM       lab
  WHERE      labGUID = p_lookupGUID;

  IF ( count_lab = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT labID
    INTO   l_labID
    FROM   lab
    WHERE  labGUID = p_lookupGUID
    LIMIT  1;                           

    SELECT @OK AS status;

    SELECT l_labID AS labID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_lab_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_lab_info`( p_personGUID CHAR(36),
                                p_password   VARCHAR(80),
                                p_labID      INT )
    READS SQL DATA
BEGIN
  DECLARE count_labs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_labs
  FROM       lab
  WHERE      labID = p_labID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_labs = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   labGUID, name, building, room
      FROM     lab
      WHERE    labID = p_labID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_lab_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_lab_names`( p_personGUID CHAR(36),
                                 p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_labs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_labs
    FROM      lab;

    IF ( count_labs = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT labID, name
      FROM lab
      ORDER BY name;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_modelDescsIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_modelDescsIDs`( p_personGUID  CHAR(36),
                                  p_password  VARCHAR(80),
                                  p_editID    INT )
    READS SQL DATA
BEGIN
  DECLARE count_models INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_models
  FROM       model
  WHERE      editedDataID = p_editID;

  IF ( count_models = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;

    SELECT   description, modelID, lastUpdated
    FROM     model
    WHERE    editedDataID = p_editID;
    
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_modelID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_modelID`( p_personGUID    CHAR(36),
                               p_password    VARCHAR(80),
                               p_modelGUID   CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_models INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_models    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_models
    FROM      model
    WHERE     modelGUID = p_modelGUID;

    IF ( TRIM( p_modelGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The modelGUID parameter to the ',
                                    'get_modelID function cannot be empty' );

    ELSEIF ( count_models < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   modelID
      FROM     model
      WHERE    modelGUID = p_modelGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_model_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_model_desc`( p_personGUID CHAR(36),
                                  p_password VARCHAR(80),
                                  p_ID       INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_models( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
                 editGUID, m.editedDataID,
                 timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size
        FROM     modelPerson, model m, editedData
        WHERE    modelPerson.modelID = m.modelID
        AND      m.editedDataID = editedData.editedDataID
        AND      modelPerson.personID = p_ID
        ORDER BY m.modelID DESC;
   
      ELSE
        SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
                 editGUID, m.editedDataID,
                 timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size
        FROM     modelPerson, model m, editedData
        WHERE    modelPerson.modelID = m.modelID
        AND      m.editedDataID = editedData.editedDataID
        ORDER BY m.modelID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this model';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_models( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
               editGUID, m.editedDataID,
               timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM     modelPerson, model m, editedData
      WHERE    modelPerson.modelID = m.modelID
      AND      m.editedDataID = editedData.editedDataID
      AND      modelPerson.personID = @US3_ID
      ORDER BY m.modelID DESC;
      

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_model_desc_auto` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_model_desc_auto`( p_personGUID CHAR(36),
                                     p_password VARCHAR(80),
                                     p_ID       INT,
				     p_description VARCHAR(200) )
    READS SQL DATA
BEGIN

  DECLARE desc_pattern VARCHAR(254);	
  CALL config();
  SET desc_pattern = CONCAT( p_description, '%2DSA-FM%' );
  

  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_models( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
                 editGUID, m.editedDataID,
                 timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size
        FROM     modelPerson, model m, editedData
        WHERE    modelPerson.modelID = m.modelID
        AND      m.editedDataID = editedData.editedDataID
        AND      modelPerson.personID = p_ID
	AND	 m.description LIKE desc_pattern
        ORDER BY m.modelID DESC;
   
      ELSE
        SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
                 editGUID, m.editedDataID,
                 timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size
        FROM     modelPerson, model m, editedData
        WHERE    modelPerson.modelID = m.modelID
        AND      m.editedDataID = editedData.editedDataID
	AND	 m.description LIKE desc_pattern
        ORDER BY m.modelID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this model';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_models( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   m.modelID, modelGUID, description, m.variance, m.meniscus,
               editGUID, m.editedDataID,
               timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM     modelPerson, model m, editedData
      WHERE    modelPerson.modelID = m.modelID
      AND      m.editedDataID = editedData.editedDataID
      AND      modelPerson.personID = @US3_ID
      AND      m.description LIKE desc_pattern
      ORDER BY m.modelID DESC;
      

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_model_desc_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_model_desc_by_editID`( p_personGUID CHAR(36),
                                            p_password   VARCHAR(80),
                                            p_ID         INT,
                                            p_editID     INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( p_ID <= 0 ) THEN
    
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';

    SELECT @US3_LAST_ERRNO AS status;
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_models_by_editID( p_personGUID, p_password, p_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT m.modelID, modelGUID, description, m.variance, m.meniscus,
             editGUID, m.editedDataID,
             timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
             MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM   modelPerson, model m, editedData
      WHERE  personID = p_ID
      AND    modelPerson.modelID = m.modelID
      AND    m.editedDataID      = p_editID
      AND    m.editedDataID      = editedData.editedDataID
      ORDER BY m.modelID DESC;
   
    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this model';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_models_by_editID( p_personGUID, p_password, @US3_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT m.modelID, modelGUID, description, m.variance, m.meniscus,
             editGUID, m.editedDataID,
             timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
             MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM   modelPerson, model m, editedData
      WHERE  personID = @US3_ID
      AND    modelPerson.modelID = m.modelID
      AND    m.editedDataID      = p_editID
      AND    m.editedDataID      = editedData.editedDataID
      ORDER BY m.modelID DESC;
   
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_model_desc_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_model_desc_by_runID`( p_personGUID CHAR(36),
                                           p_password   VARCHAR(80),
                                           p_ID         INT,
                                           p_runID      VARCHAR(250) )
    READS SQL DATA
BEGIN

  DECLARE run_pattern VARCHAR(254);

  CALL config();
  SET run_pattern = CONCAT( p_runID, '.%' );
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( p_ID <= 0 ) THEN
    
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';

    SELECT @US3_LAST_ERRNO AS status;
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_models_by_runID( p_personGUID, p_password, p_ID, p_runID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT m.modelID, modelGUID, description, m.variance, m.meniscus,
             editGUID, m.editedDataID,
             timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
             MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM   modelPerson, model m, editedData
      WHERE  personID = p_ID
      AND    modelPerson.modelID = m.modelID
      AND    m.editedDataID      = editedData.editedDataID
      AND    description LIKE run_pattern
      ORDER BY m.modelID DESC;
   
    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this model';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_models_by_runID( p_personGUID, p_password, @US3_ID, p_runID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT m.modelID, modelGUID, description, m.variance, m.meniscus,
             editGUID, m.editedDataID,
             timestamp2UTC( m.lastUpdated ) AS UTC_lastUpdated,
             MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM   modelPerson, model m, editedData
      WHERE  personID = @US3_ID
      AND    modelPerson.modelID = m.modelID
      AND    m.editedDataID      = editedData.editedDataID
      AND    description LIKE run_pattern
      ORDER BY m.modelID DESC;
   
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_model_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_model_info`( p_personGUID  CHAR(36),
                                  p_password  VARCHAR(80),
                                  p_modelID   INT )
    READS SQL DATA
BEGIN
  DECLARE count_models INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_models
  FROM       model
  WHERE      modelID = p_modelID;

  IF ( verify_model_permission( p_personGUID, p_password, p_modelID ) = @OK ) THEN
    IF ( count_models = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   modelGUID, description, xml, variance, meniscus, personID,
               timestamp2UTC( lastUpdated ) AS UTC_lastUpdated,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size
      FROM     model m, modelPerson mp
      WHERE    m.modelID = mp.modelID
      AND      m.modelID = p_modelID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_mrecsID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_mrecsID`( p_personGUID    CHAR(36),
                               p_password      VARCHAR(80),
                               p_mrecsGUID     CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_mrecs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_mrecs    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT COUNT(*)
    INTO   count_mrecs
    FROM   pcsa_modelrecs
    WHERE  mrecsGUID = p_mrecsGUID;

    IF ( TRIM( p_mrecsGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The mrecsGUID parameter to the ',
                                    'get_mrecsID function cannot be empty' );

    ELSEIF ( count_mrecs < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT mrecsID
      FROM   pcsa_modelrecs
      WHERE  mrecsGUID = p_mrecsGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_mrecs_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_mrecs_desc`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_mrecs( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   mrecsID, mrecsGUID, c.editedDataID, e.editGUID,
                 c.modelID, m.modelGUID, c.description,
                 MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
                 timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated 
        FROM     pcsa_modelrecs c, editedData e, rawData r,
                 experimentPerson p, model m
        WHERE    p.personID     = p_ID
        AND      r.experimentID = p.experimentID
        AND      e.rawDataID    = r.rawDataID
        AND      e.editedDataID = c.editedDataID
        AND      m.modelID      = c.modelID
        ORDER BY mrecsID DESC;

      ELSE
        SELECT   mrecsID, mrecsGUID, c.editedDataID, e.editGUID,
                 c.modelID, m.modelGUID, c.description,
                 MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
                 timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated
        FROM     pcsa_modelrecs c, editedData e, model m
        WHERE    e.editedDataID = c.editedDataID
        AND      m.modelID      = c.modelID
        ORDER BY mrecsID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this mrecs';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_mrecs( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   mrecsID, mrecsGUID, c.editedDataID, e.editGUID,
               c.modelID, m.modelGUID, c.description,
               MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
               timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated 
      FROM     pcsa_modelrecs c, editedData e, rawData r,
               experimentPerson p, model m
      WHERE    p.personID     = @US3_ID
      AND      r.experimentID = p.experimentID
      AND      e.rawDataID    = r.rawDataID
      AND      e.editedDataID = c.editedDataID
      AND      m.modelID      = c.modelID
      ORDER BY mrecsID DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_mrecs_desc_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_mrecs_desc_by_editID`( p_personGUID CHAR(36),
                                            p_password   VARCHAR(80),
                                            p_ID         INT,
                                            p_editID     INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( p_ID <= 0 ) THEN
    
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';

    SELECT @US3_LAST_ERRNO AS status;
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_mrecs_by_editID( p_personGUID, p_password, p_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT   mrecsID, mrecsGUID, c.editedDataID, e.editGUID,
               c.modelID, m.modelGUID, c.description,
               MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
               timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated 
      FROM     pcsa_modelrecs c, editedData e, rawData r,
               experimentPerson p, model m
      WHERE    p.personID     = p_ID
      AND      r.experimentID = p.experimentID
      AND      e.rawDataID    = r.rawDataID
      AND      c.editedDataID = p_editID
      AND      e.editedDataID = p_editID
      AND      m.modelID      = c.modelID
      ORDER BY mrecsID DESC;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this mrecs';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_mrecs_by_editID( p_personGUID, p_password, @US3_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   mrecsID, mrecsGUID, c.editedDataID, e.editGUID,
               c.modelID, m.modelGUID, c.description,
               MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
               timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated 
      FROM     pcsa_modelrecs c, editedData e, rawData r,
               experimentPerson p, model m
      WHERE    p.personID     = @US3_ID
      AND      r.experimentID = p.experimentID
      AND      e.rawDataID    = r.rawDataID
      AND      c.editedDataID = p_editID
      AND      e.editedDataID = p_editID
      AND      m.modelID      = c.modelID
      ORDER BY mrecsID DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_mrecs_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_mrecs_info`( p_personGUID  CHAR(36),
                                  p_password    VARCHAR(80),
                                  p_mrecsID     INT )
    READS SQL DATA
BEGIN
  DECLARE count_mrecs INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_mrecs
  FROM       pcsa_modelrecs
  WHERE      mrecsID = p_mrecsID;

  IF ( verify_mrecs_permission( p_personGUID, p_password, p_mrecsID ) = @OK ) THEN
    IF ( count_mrecs = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   mrecsGUID, c.xml, c.editedDataID, e.editGUID,
               c.modelID, m.modelGUID, c.description,
               MD5( c.xml ) AS checksum, LENGTH( c.xml ) AS size,
               timestamp2UTC( c.lastUpdated ) AS UTC_lastUpdated 
      FROM     pcsa_modelrecs c, editedData e, model m
      WHERE    mrecsID        = p_mrecsID
      AND      e.editedDataID = c.editedDataID
      AND      m.modelID      = c.modelID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_noiseID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_noiseID`( p_personGUID    CHAR(36),
                               p_password      VARCHAR(80),
                               p_noiseGUID     CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_noise INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_noise    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_noise
    FROM      noise
    WHERE     noiseGUID = p_noiseGUID;

    IF ( TRIM( p_noiseGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The noiseGUID parameter to the ',
                                    'get_noiseID function cannot be empty' );

    ELSEIF ( count_noise < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   noiseID
      FROM     noise
      WHERE    noiseGUID = p_noiseGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_noiseTypesIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_noiseTypesIDs`( p_personGUID  CHAR(36),
                                  p_password    VARCHAR(80),
                                  p_modelID     INT )
    READS SQL DATA
BEGIN
  DECLARE count_noise INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_noise
  FROM       noise
  WHERE      modelID = p_modelID;

  IF ( count_noise = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT @OK AS status;

    SELECT   noiseID, noiseType, timeEntered
    FROM     noise 
    WHERE    modelID = p_modelID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_noise_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_noise_desc`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_noise( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   noiseID, noiseGUID, editedDataID, noise.modelID, noiseType, modelGUID,
                 timestamp2UTC( timeEntered ) AS UTC_timeEntered,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
        FROM     modelPerson, noise
        WHERE    modelPerson.modelID  = noise.modelID
        AND      modelPerson.personID = p_ID
        ORDER BY timeEntered DESC;

      ELSE
        SELECT   noiseID, noiseGUID, editedDataID, noise.modelID, noiseType, modelGUID,
                 timestamp2UTC( timeEntered ) AS UTC_timeEntered,
                 MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
        FROM     modelPerson, noise
        WHERE    modelPerson.modelID  = noise.modelID
        ORDER BY timeEntered DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this noise';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_noise( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   noiseID, noiseGUID, editedDataID, noise.modelID, noiseType, modelGUID,
               timestamp2UTC( timeEntered ) AS UTC_timeEntered,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
      FROM     modelPerson, noise
      WHERE    modelPerson.modelID  = noise.modelID
      AND      modelPerson.personID = @US3_ID
      ORDER BY timeEntered DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_noise_desc_by_editID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_noise_desc_by_editID`( p_personGUID CHAR(36),
                                            p_password   VARCHAR(80),
                                            p_ID         INT,
                                            p_editID     INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( p_ID <= 0 ) THEN
    
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';

    SELECT @US3_LAST_ERRNO AS status;
    
  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_noise_by_editID( p_personGUID, p_password, p_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT   noiseID, noiseGUID, editedDataID, noise.modelID, noiseType, modelGUID,
               timestamp2UTC( timeEntered ) AS UTC_timeEntered,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
      FROM     modelPerson, noise
      WHERE    modelPerson.personID = p_ID
      AND      modelPerson.modelID  = noise.modelID
      AND      editedDataID         = p_editID
      ORDER BY timeEntered DESC;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this noise';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_noise_by_editID( p_personGUID, p_password, @US3_ID, p_editID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   noiseID, noiseGUID, editedDataID, noise.modelID, noiseType, modelGUID,
               timestamp2UTC( timeEntered ) AS UTC_timeEntered,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
      FROM     modelPerson, noise
      WHERE    modelPerson.personID = @US3_ID
      AND      modelPerson.modelID  = noise.modelID
      AND      editedDataID         = p_editID
      ORDER BY timeEntered DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_noise_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_noise_info`( p_personGUID  CHAR(36),
                                  p_password    VARCHAR(80),
                                  p_noiseID     INT )
    READS SQL DATA
BEGIN
  DECLARE count_noise INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_noise
  FROM       noise
  WHERE      noiseID = p_noiseID;

  IF ( verify_noise_permission( p_personGUID, p_password, p_noiseID ) = @OK ) THEN
    IF ( count_noise = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   noiseGUID, editedDataID, modelID, modelGUID, noiseType, xml,
               timestamp2UTC( timeEntered ) AS UTC_timeEntered,
               MD5( xml ) AS checksum, LENGTH( xml ) AS size, description
      FROM     noise 
      WHERE    noiseID = p_noiseID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_nucleotide_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_nucleotide_info`( p_personGUID CHAR(36),
                                       p_password   VARCHAR(80),
                                       p_analyteID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_analytes INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_analytes
  FROM       analyte
  WHERE      analyteID = p_analyteID;

  IF ( verify_analyte_permission( p_personGUID, p_password, p_analyteID ) = @OK ) THEN
    IF ( count_analytes = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   doubleStranded, complement, _3prime, _5prime, 
               sodium, potassium, lithium, magnesium, calcium 
      FROM     analyte
      WHERE    analyteID = p_analyteID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_operator_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_operator_names`( p_personGUID   CHAR(36),
                                      p_password     VARCHAR(80),
                                      p_instrumentID INT )
    READS SQL DATA
BEGIN
  DECLARE count_operators INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_operators
    FROM      permits
    WHERE     instrumentID = p_instrumentID;

    IF ( count_operators = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   m.personID, p.personGUID, p.lname, p.fname
      FROM     permits m, people p
      WHERE    m.instrumentID = p_instrumentID 
      AND      m.personID = p.personID
      ORDER BY p.lname, p.fname;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_people` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_people`( p_personGUID CHAR(36),
                             p_password   VARCHAR(80),
                             p_template   VARCHAR(30) )
    READS SQL DATA
BEGIN
  DECLARE template VARCHAR(40);

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET p_template      = TRIM( p_template );

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_people( p_personGUID, p_password, p_template )  < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( LENGTH(p_template) = 0 ) THEN
      SELECT @OK AS status;

      SELECT   personID,
               lname AS lastName,
               fname AS firstName,
               organization
      FROM     people
      ORDER BY lname;

    ELSE
      SELECT @OK AS status;

      SET template = CONCAT('%', p_template, '%');

      SELECT   personID,
               lname AS lastName,
               fname AS firstName,
               organization
      FROM     people
      WHERE    lname LIKE template
      OR       fname LIKE template
      ORDER BY lname, fname;
    
    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_personID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_personID_from_GUID`( p_personGUID   CHAR(36),
                                          p_password     VARCHAR(80),
                                          p_lookupGUID   CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_person  INT;
  DECLARE l_personID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_person
  FROM       people
  WHERE      personGUID = p_lookupGUID;

  IF ( count_person = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT personID
    INTO   l_personID
    FROM   people
    WHERE  personGUID = p_lookupGUID
    LIMIT  1;                           

    SELECT @OK AS status;

    SELECT l_personID AS personID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_person_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_person_info`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_ID         INT )
    READS SQL DATA
BEGIN
  DECLARE count_person INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_person
  FROM       people
  WHERE      personID = p_ID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_person = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   fname AS firstName,
               lname AS lastName,
               address,
               city,
               state,
               zip,
               phone,
               organization,
               email,
               personGUID
      FROM     people
      WHERE    personID = p_ID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_projectID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_projectID_from_GUID`( p_personGUID   CHAR(36),
                                           p_password     VARCHAR(80),
                                           p_projectGUID  CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_project  INT;
  DECLARE l_projectID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_project
  FROM       project
  WHERE      projectGUID = p_projectGUID;

  IF ( count_project = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT projectID
    INTO   l_projectID
    FROM   project
    WHERE  projectGUID = p_projectGUID
    LIMIT  1;                           

    IF ( verify_project_permission( p_personGUID, p_password, l_projectID ) = @OK ) THEN
      SELECT @OK AS status;

      SELECT l_projectID AS projectID;

    ELSE
      SELECT @US3_LAST_ERRNO AS status;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_project_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_project_desc`( p_personGUID CHAR(36),
                                    p_password   VARCHAR(80),
                                    p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_projects( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   j.projectID, description, status,
                 timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
        FROM     project j, projectPerson p
        WHERE    j.projectID = p.projectID
        AND      p.personID = p_ID
        ORDER BY description;
   
      ELSE
        SELECT   j.projectID, description, status,
                 timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
        FROM     project j, projectPerson p
        WHERE    j.projectID = p.projectID
        ORDER BY description;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this project';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_projects( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   j.projectID, description, status,
               timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
      FROM     project j, projectPerson p
      WHERE    j.projectID = p.projectID
      AND      p.personID = @US3_ID
      ORDER BY description;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_project_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_project_info`( p_personGUID  CHAR(36),
                                    p_password    VARCHAR(80),
                                    p_projectID   INT )
    READS SQL DATA
BEGIN
  DECLARE count_projects INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_projects
  FROM       project
  WHERE      projectID = p_projectID;

  IF ( verify_project_permission( p_personGUID, p_password, p_projectID ) = @OK ) THEN
    IF ( count_projects = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   j.projectID, projectGUID, goals, molecules, purity, expense, bufferComponents,
               saltInformation, AUC_questions, notes, description, status, personID, expDesign,
               timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
      FROM     project j, projectPerson p
      WHERE    j.projectID = p.projectID
      AND      j.projectID = p_projectID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_protocol_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_protocol_desc`( p_personGUID CHAR(36),
                                     p_password VARCHAR(80),
                                     p_ID       INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_protocols( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT   p.protocolID, protocolGUID, description, xml, optimaHost,
                 timestamp2UTC( dateUpdated ) AS UTC_lastUpdated
        FROM     protocol p, protocolPerson
        WHERE    p.protocolID = protocolPerson.protocolID
        AND      protocolPerson.personID = p_ID
        ORDER BY p.protocolID DESC;
   
      ELSE
        SELECT   p.protocolID, protocolGUID, description, xml, optimaHost,
                 timestamp2UTC( dateUpdated ) AS UTC_lastUpdated,
                 personID
        FROM     protocol p, protocolPerson
        WHERE    p.protocolID = protocolPerson.protocolID
        ORDER BY p.protocolID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this protocol';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_protocols( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT   p.protocolID, protocolGUID, description, xml, optimaHost,
               timestamp2UTC( dateUpdated ) AS UTC_lastUpdated
      FROM     protocol p, protocolPerson
      WHERE    p.protocolID = protocolPerson.protocolID
      AND      p.personID = @US3_ID
      ORDER BY p.protocolID DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_protocol_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_protocol_info`( p_personGUID  CHAR(36),
                                     p_password    VARCHAR(80),
                                     p_protocolID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_protocols INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_protocols
  FROM       protocol
  WHERE      protocolID = p_protocolID;

  IF ( verify_protocol_permission( p_personGUID, p_password, p_protocolID ) = @OK ) THEN
    IF ( count_protocols = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_protocols > 1 ) THEN
      SET @US3_LAST_ERRNO = @MORE_THAN_SINGLE_ROW;
      SET @US3_LAST_ERROR = 'MySQL: more than a single row for a run protocol';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   pc.protocolID, protocolGUID, description, xml, optimaHost,
               timestamp2UTC( dateUpdated ) AS UTC_lastUpdated,
               rotorID, speed1, duration, usedcells, estscans,
               solution1, solution2, wavelengths, pp.personID
      FROM     protocol pc, protocolPerson pp
      WHERE    pc.protocolID = pp.protocolID
      AND      pc.protocolID = p_protocolID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_radialcal_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_radialcal_info`( p_personGUID  CHAR(36),
                                      p_password    VARCHAR(80),
                                      p_radialCalID INT )
    READS SQL DATA
BEGIN
  DECLARE count_profiles INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( p_radialCalID > 0 ) THEN
    SELECT     COUNT(*)
    INTO       count_profiles
    FROM       radialCalibration
    WHERE      radialCalID = p_radialCalID;
  ELSE
    SELECT     COUNT(*)
    INTO       count_profiles
    FROM       radialCalibration;
  END IF;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_profiles = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      IF ( p_radialCalID > 0 ) THEN
        SELECT   radialCalID, radialCalGUID, speed, rotorCalID, dateUpdated,
                 roc.coeff1, roc.coeff2
        FROM     radialCalibration, rotorCalibration roc
        WHERE    radialCalID = p_radialCalID
        AND      roc.rotorCalibrationID = rotorCalID ;
      ELSE
        SELECT   radialCalID, radialCalGUID, speed, rotorCalID, dateUpdated,
                 roc.coeff1, roc.coeff2
        FROM     radialCalibration, rotorCalibration roc
        WHERE    roc.rotorCalibrationID = rotorCalID ;
      END IF;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rawData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rawData`( p_personGUID   CHAR(36),
                               p_password     VARCHAR(80),
                               p_rawDataID    INT )
    READS SQL DATA
BEGIN

  DECLARE l_experimentID  INT;
  DECLARE count_rawData   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT experimentID
  INTO   l_experimentID
  FROM   rawData
  WHERE  rawDataID = p_rawDataID
  LIMIT  1;                         

  SELECT     COUNT(*)
  INTO       count_rawData
  FROM       rawData
  WHERE      rawDataID = p_rawDataID;

  IF ( count_rawData = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
    
    SELECT @OK as status;

    SELECT  rawDataGUID, label, filename, comment, experimentID, solutionID, channelID, 
            timestamp2UTC( lastUpdated ) AS UTC_lastUpdated, 
            MD5( data ) AS checksum, LENGTH( data ) AS size
    FROM    rawData
    WHERE   rawDataID = p_rawDataID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rawDataIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rawDataIDs`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_experimentID INT )
    READS SQL DATA
BEGIN
  DECLARE count_rawData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_rawData
  FROM       rawData
  WHERE      experimentID = p_experimentID;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    IF ( count_rawData = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   rawDataID, label, filename
      FROM     rawData
      WHERE    experimentID = p_experimentID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rawDataID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rawDataID_from_GUID`( p_personGUID   CHAR(36),
                                           p_password     VARCHAR(80),
                                           p_rawDataGUID  CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_rawData  INT;
  DECLARE l_rawDataID    INT;
  DECLARE l_experimentID INT;
  DECLARE l_solutionID   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_rawData
  FROM       rawData
  WHERE      rawDataGUID = p_rawDataGUID;

  IF ( count_rawData = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT rawDataID, experimentID, solutionID
    INTO   l_rawDataID, l_experimentID, l_solutionID
    FROM   rawData
    WHERE  rawDataGUID = p_rawDataGUID
    LIMIT  1;                           

    IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
      SELECT @OK AS status;

      SELECT l_rawDataID AS rawDataID, 
             l_experimentID AS experimentID,
             l_solutionID AS solutionID;

    ELSE
      SELECT @US3_LAST_ERRNO AS status;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rawData_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rawData_desc`( p_personGUID   CHAR(36),
                                    p_password     VARCHAR(80),
                                    p_ID           INT )
    READS SQL DATA
BEGIN
  DECLARE count_rawData INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_rawData( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT     rawDataID, rawData.label, rawData.filename,
                   rawData.experimentID, rawData.solutionID, 
                   timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                   experimentPerson.personID,
                   rawDataGUID, rawData.comment, experiment.experimentGUID
        FROM       rawData, experiment, experimentPerson
        WHERE      experimentPerson.personID = p_ID
        AND        experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        ORDER BY   rawData.lastUpdated DESC;

      ELSE
        SELECT     rawDataID, rawData.label, rawData.filename,
                   rawData.experimentID, rawData.solutionID,
                   timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                   experimentPerson.personID,
                   rawDataGUID, rawData.comment, experiment.experimentGUID
        FROM       rawData, experiment, experimentPerson
        WHERE      experiment.experimentID = experimentPerson.experimentID
        AND        rawData.experimentID = experiment.experimentID
        ORDER BY   rawData.lastUpdated DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this experiment';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_experiments( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT     rawDataID, rawData.label, rawData.filename,
                 rawData.experimentID, rawData.solutionID, 
                 timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                 experimentPerson.personID,
                 rawDataGUID, rawData.comment, experiment.experimentGUID
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      ORDER BY   rawData.lastUpdated DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_raw_desc_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_raw_desc_by_runID`( p_personGUID   CHAR(36),
                                         p_password     VARCHAR(80),
                                         p_ID           INT,
                                         p_runID        VARCHAR(250) )
    READS SQL DATA
BEGIN

  DECLARE count_rawData INT;
  DECLARE run_pattern VARCHAR(254);

  CALL config();
  SET run_pattern = CONCAT( p_runID, '.%' );
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_rawData
  FROM       rawData
  WHERE      filename LIKE run_pattern;

  IF ( p_ID <= 0 ) THEN
    
    SET @US3_LAST_ERRNO = @EMPTY;
    SET @US3_LAST_ERROR = 'MySQL: The ID cannot be 0';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_rawData < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      SELECT     rawDataID, rawData.label, rawData.filename,
                 rawData.experimentID, rawData.solutionID, 
                 timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                 experimentPerson.personID,
                 rawDataGUID, rawData.comment, experiment.experimentGUID
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = p_ID
      AND        rawData.filename LIKE run_pattern
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      ORDER BY   rawData.lastUpdated DESC;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    
    IF ( (p_ID != 0) && (p_ID != @US3_ID ) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this model';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_rawData < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT     rawDataID, rawData.label, rawData.filename,
                 rawData.experimentID, rawData.solutionID, 
                 timestamp2UTC( rawData.lastUpdated) AS UTC_lastUpdated, 
                 experimentPerson.personID,
                 rawDataGUID, rawData.comment, experiment.experimentGUID
      FROM       rawData, experiment, experimentPerson
      WHERE      experimentPerson.personID = @US3_ID
      AND        rawData.filename LIKE run_pattern
      AND        experiment.experimentID = experimentPerson.experimentID
      AND        rawData.experimentID = experiment.experimentID
      ORDER BY   rawData.lastUpdated DESC;

    END IF;
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportDocumentID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportDocumentID`( p_personGUID        CHAR(36),
                                        p_password          VARCHAR(80),
                                        p_reportDocumentGUID  CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_reports    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_reports
    FROM      reportDocument
    WHERE     reportDocumentGUID = p_reportDocumentGUID;

    IF ( TRIM( p_reportDocumentGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The reportDocumentGUID parameter to the ',
                                    'get_reportDocumentID function cannot be empty' );

    ELSEIF ( count_reports < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   reportDocumentID
      FROM     reportDocument
      WHERE    reportDocumentGUID = p_reportDocumentGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportDocument_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportDocument_desc`( p_personGUID      CHAR(36),
                                           p_password        VARCHAR(80),
                                           p_reportTripleID  INT )
    READS SQL DATA
BEGIN
  DECLARE l_reportID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
     SET @US3_LAST_ERRNO = @NOTPERMITTED;
     SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
     SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_reportDocument( p_personGUID, p_password, p_reportTripleID ) < 1 ) THEN
     SET @US3_LAST_ERRNO = @NOROWS;
     SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
     SELECT @US3_LAST_ERRNO AS status;

  ELSE
     SELECT @OK AS status;

     SELECT    reportDocument.reportDocumentID, reportDocumentGUID, editedDataID, 
               label, filename, analysis, subAnalysis, documentType
     FROM      documentLink, reportDocument
     WHERE     reportTripleID = p_reportTripleID
     AND       documentLink.reportDocumentID = reportDocument.reportDocumentID
     ORDER BY  label;
   
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportDocument_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportDocument_info`( p_personGUID      CHAR(36),
                                           p_password        VARCHAR(80),
                                           p_reportDocumentID  INT )
    READS SQL DATA
BEGIN
  DECLARE l_reportID       INT;
  DECLARE l_reportTripleID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT reportID, documentLink.reportTripleID
  INTO   l_reportID, l_reportTripleID
  FROM   documentLink, reportTriple
  WHERE  documentLink.reportDocumentID = p_reportDocumentID
  AND    documentLink.reportTripleID = reportTriple.reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
     SET @US3_LAST_ERRNO = @NOTPERMITTED;
     SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
     SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_reportDocument( p_personGUID, p_password, l_reportTripleID ) < 1 ) THEN
     SET @US3_LAST_ERRNO = @NOROWS;
     SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
     SELECT @US3_LAST_ERRNO AS status;

  ELSE
     SELECT @OK AS status;

     SELECT    reportDocumentGUID, editedDataID, label, filename, 
               analysis, subAnalysis, documentType
     FROM      reportDocument
     WHERE     reportDocumentID = p_reportDocumentID
     ORDER BY  label;
   
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportID`( p_personGUID  CHAR(36),
                                p_password    VARCHAR(80),
                                p_reportGUID  CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_reports    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_reports
    FROM      report
    WHERE     reportGUID = p_reportGUID;

    IF ( TRIM( p_reportGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The reportGUID parameter to the ',
                                    'get_reportID function cannot be empty' );

    ELSEIF ( count_reports < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   reportID
      FROM     report
      WHERE    reportGUID = p_reportGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportID_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportID_by_runID`( p_personGUID  CHAR(36),
                                         p_password    VARCHAR(80),
                                         p_ID          INT,
                                         p_runID       VARCHAR(255) )
    READS SQL DATA
BEGIN

  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_reports    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT COUNT(*)
    INTO   count_reports
    FROM   reportPerson p, report r
    WHERE  personID = p_ID
    AND    p.reportID = r.reportID
    AND    runID = p_runID;

    IF ( TRIM( p_runID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The runID parameter to the ',
                                    'get_reportID_by_runID function cannot be empty' );

    ELSEIF ( count_reports < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT p.reportID
      FROM   reportPerson p, report r
      WHERE  personID = p_ID
      AND    p.reportID = r.reportID
      AND    runID = p_runID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportTripleID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportTripleID`( p_personGUID        CHAR(36),
                                      p_password          VARCHAR(80),
                                      p_reportTripleGUID  CHAR(36) )
    READS SQL DATA
BEGIN

  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET count_reports    = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN

    SELECT    COUNT(*)
    INTO      count_reports
    FROM      reportTriple
    WHERE     reportTripleGUID = p_reportTripleGUID;

    IF ( TRIM( p_reportTripleGUID ) = '' ) THEN
      SET @US3_LAST_ERRNO = @EMPTY;
      SET @US3_LAST_ERROR = CONCAT( 'MySQL: The reportTripleGUID parameter to the ',
                                    'get_reportTripleID function cannot be empty' );

    ELSEIF ( count_reports < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   reportTripleID
      FROM     reportTriple
      WHERE    reportTripleGUID = p_reportTripleGUID;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportTriple_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportTriple_desc`( p_personGUID CHAR(36),
                                         p_password   VARCHAR(80),
                                         p_reportID   INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_report_permission( p_personGUID, p_password, p_reportID ) != @OK ) THEN
     SET @US3_LAST_ERRNO = @NOTPERMITTED;
     SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
     SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_reportTriple( p_personGUID, p_password, p_reportID ) < 1 ) THEN
     SET @US3_LAST_ERRNO = @NOROWS;
     SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
     SELECT @US3_LAST_ERRNO AS status;

  ELSE
     SELECT @OK AS status;

     SELECT    reportTripleID, reportTripleGUID, resultID, triple, dataDescription
     FROM      reportTriple
     WHERE     reportID = p_reportID
     ORDER BY  triple;
   
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_reportTriple_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reportTriple_info`( p_personGUID       CHAR(36),
                                         p_password         VARCHAR(80),
                                         p_reportTripleID   INT )
    READS SQL DATA
BEGIN
  DECLARE l_reportID    INT;
  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  SELECT COUNT(*)
  INTO   count_reports
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
     SET @US3_LAST_ERRNO = @NOTPERMITTED;
     SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
     SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_reports < 1 ) THEN
     SET @US3_LAST_ERRNO = @NOROWS;
     SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
     SELECT @US3_LAST_ERRNO AS status;

  ELSE
     SELECT @OK AS status;

     SELECT    reportTripleGUID, resultID, triple, dataDescription
     FROM      reportTriple
     WHERE     reportTripleID = p_reportTripleID;
   
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_desc` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_desc`( p_personGUID CHAR(36),
                                   p_password   VARCHAR(80),
                                   p_ID         INT )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_reports( p_personGUID, p_password, p_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT    report.reportID, reportGUID, title, experimentID, runID
        FROM      reportPerson, report
        WHERE     reportPerson.personID = p_ID
        AND       reportPerson.reportID = report.reportID
        ORDER BY  reportID DESC;
   
      ELSE
        SELECT    report.reportID, reportGUID, title, experimentID, runID
        FROM      reportPerson, report
        WHERE     reportPerson.reportID = report.reportID
        ORDER BY  reportID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_reports( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT    report.reportID, reportGUID, title, experimentID, runID
      FROM      reportPerson, report
      WHERE     reportPerson.personID = @US3_ID
      AND       reportPerson.reportID = report.reportID
      ORDER BY  reportID DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_desc_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_desc_by_runID`( p_personGUID CHAR(36),
                                            p_password   VARCHAR(80),
                                            p_ID         INT,
                                            p_runID      VARCHAR(80) )
    READS SQL DATA
BEGIN

  CALL config();

  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN
    
    IF ( count_reports_by_runID( p_personGUID, p_password, p_ID, p_runID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;
  
      IF ( p_ID > 0 ) THEN
        SELECT    r.reportID, reportGUID, title, experimentID
        FROM      reportPerson p, report r
        WHERE     p.personID = p_ID
        AND       p.reportID = r.reportID
        AND       runID    = p_runID
        ORDER BY  reportID DESC;
   
      ELSE
        SELECT    reportID, reportGUID, title, experimentID
        FROM      report
        WHERE     runID = p_runID
        ORDER BY  reportID DESC;

      END IF;

    END IF;

  ELSEIF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( (p_ID != 0) && (p_ID != @US3_ID) ) THEN
      
      SET @US3_LAST_ERRNO = @NOTPERMITTED;
      SET @US3_LAST_ERROR = 'MySQL: you do not have permission to view this report';
     
      SELECT @US3_LAST_ERRNO AS status;

    ELSEIF ( count_reports( p_personGUID, p_password, @US3_ID ) < 1 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
   
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      
      SELECT @OK AS status;

      SELECT    r.reportID, reportGUID, title, experimentID
      FROM      reportPerson p, report r
      WHERE     p.personID = @US3_ID
      AND       p.reportID = r.reportID
      AND       runID    = p_runID
      ORDER BY  reportID DESC;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_info`( p_personGUID  CHAR(36),
                                   p_password    VARCHAR(80),
                                   p_reportID    INT )
    READS SQL DATA
BEGIN
  DECLARE count_reports INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_reports
  FROM       report
  WHERE      reportID = p_reportID;

  IF ( verify_report_permission( p_personGUID, p_password, p_reportID ) = @OK ) THEN
    IF ( count_reports = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT    report.reportID, reportGUID, experimentID, runID, title, html
      FROM      reportPerson, report
      WHERE     report.reportID = p_reportID
      AND       report.reportID = reportPerson.reportID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_info_by_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_info_by_id`( p_personGUID    CHAR(36),
                                       	p_password      VARCHAR(80),
                                       	p_reportID      INT )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowReport
  WHERE      reportID = p_reportID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   channelName, totalConc, rmsdLimit, avIntensity, expDuration, wavelength, totalConcTol, expDurationTol
      FROM     autoflowReport 
      WHERE    reportID = p_reportID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_info_by_runID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_info_by_runID`( p_personGUID  CHAR(36),
                                            p_password    VARCHAR(80),
                                            p_ID          INT,
                                            p_runID       VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE count_reports INT;
  DECLARE l_reportID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   count_reports
  FROM   reportPerson p, report r
  WHERE  personID = p_ID
  AND    p.reportID = r.reportID
  AND    runID = p_runID;

  SELECT p.reportID
  INTO   l_reportID
  FROM   reportPerson p, report r
  WHERE  personID = p_ID
  AND    p.reportID = r.reportID
  AND    runID = p_runID;

  IF ( count_reports = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) THEN
    SELECT @OK AS status;

    SELECT    report.reportID, reportGUID, experimentID, runID, title, html
    FROM      reportPerson, report
    WHERE     report.reportID = l_reportID
    AND       report.reportID = reportPerson.reportID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_items_ids_by_report_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_items_ids_by_report_id`( p_personGUID    CHAR(36),
                                       	            p_password      VARCHAR(80),
                                       	            p_reportID      INT )
    READS SQL DATA
BEGIN
  DECLARE count_report_records INT;
  DECLARE count_report_item_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_report_records
  FROM       autoflowReport
  WHERE      reportID = p_reportID;

  SELECT     COUNT(*)
  INTO       count_report_item_records
  FROM       autoflowReportItem
  WHERE      reportID = p_reportID;


  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_report_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
       IF ( count_report_item_records = 0 ) THEN
          SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
          SET @US3_LAST_ERROR = 'MySQL: no rows returned';

          SELECT @US3_LAST_ERRNO AS status;

       ELSE
          SELECT @OK AS status;

          SELECT   reportItemID
          FROM     autoflowReportItem 
          WHERE    reportID = p_reportID;

       END IF;
    END IF;   

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_report_item_info_by_id` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_report_item_info_by_id`( p_personGUID    CHAR(36),
                                       	     p_password      VARCHAR(80),
                                       	     p_reportItemID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowReportItem
  WHERE      reportItemID  = p_reportItemID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   type, method, rangeLow, rangeHi, integration, tolerance, totalPercent
      FROM     autoflowReportItem 
      WHERE    reportItemID = p_reportItemID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotorCalibrationID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotorCalibrationID_from_GUID`( p_rotorGUID   CHAR(36),
                                                    p_password     VARCHAR(80),
                                                    p_lookupGUID   CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_profile INT;
  DECLARE l_rotorCalibrationID     INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_profile
  FROM       rotorCalibration
  WHERE      rotorCalibrationGUID = p_lookupGUID;

  IF ( count_profile = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT rotorCalibrationID
    INTO   l_rotorCalibrationID
    FROM   rotorCalibration
    WHERE  rotorCalibrationGUID = p_lookupGUID
    LIMIT  1;                           

    SELECT @OK AS status;

    SELECT l_rotorCalibrationID AS rotorCalibrationID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotorID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotorID_from_GUID`( p_rotorGUID   CHAR(36),
                                         p_password     VARCHAR(80),
                                         p_lookupGUID   CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_rotor  INT;
  DECLARE l_rotorID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_rotor
  FROM       rotor
  WHERE      rotorGUID = p_lookupGUID;

  IF ( count_rotor = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    SELECT rotorID
    INTO   l_rotorID
    FROM   rotor
    WHERE  rotorGUID = p_lookupGUID
    LIMIT  1;                           

    SELECT @OK AS status;

    SELECT l_rotorID AS rotorID;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotor_calibration_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotor_calibration_info`( p_personGUID CHAR(36),
                                              p_password   VARCHAR(80),
                                              p_calibrationID INT )
    READS SQL DATA
BEGIN
  DECLARE count_profiles INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_profiles
  FROM       rotorCalibration
  WHERE      rotorCalibrationID = p_calibrationID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_profiles = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   rotorCalibrationGUID, rotorCalibration.rotorID, rotorGUID,
               report, coeff1, coeff2, omega2_t, dateUpdated, 
               calibrationExperimentID, label 
      FROM     rotorCalibration, rotor
      WHERE    rotorCalibrationID = p_calibrationID
      AND      rotorCalibration.rotorID = rotor.rotorID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotor_calibration_profiles` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotor_calibration_profiles`( p_personGUID CHAR(36),
                                                  p_password   VARCHAR(80),
                                                  p_rotorID    INT )
    READS SQL DATA
BEGIN
  DECLARE count_profiles INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_profiles
    FROM      rotorCalibration
    WHERE     rotorID = p_rotorID;

    IF ( count_profiles = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   rotorCalibrationID, dateUpdated, label
      FROM     rotorCalibration
      WHERE    rotorID = p_rotorID
      ORDER BY dateUpdated DESC;
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotor_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotor_info`( p_personGUID CHAR(36),
                                  p_password   VARCHAR(80),
                                  p_rotorID    INT )
    READS SQL DATA
BEGIN
  DECLARE count_rotors INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_rotors
  FROM       rotor
  WHERE      rotorID = p_rotorID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_rotors = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   r.rotorGUID, r.name, serialNumber,  
               a.name, r.abstractRotorID, a.abstractRotorGUID, r.labID
      FROM     rotor r, abstractRotor a
      WHERE    r.abstractRotorID = a.abstractRotorID
      AND      rotorID = p_rotorID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_rotor_names` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_rotor_names`( p_personGUID CHAR(36),
                                   p_password   VARCHAR(80),
                                   p_labID      INT )
    READS SQL DATA
BEGIN
  DECLARE count_rotors INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT    COUNT(*)
    INTO      count_rotors
    FROM      rotor
    WHERE     labID = p_labID;

    IF ( count_rotors = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';
 
      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   rotorID, name
      FROM     rotor
      WHERE    labID = p_labID
      ORDER BY UPPER( name );
 
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_solution` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_solution`( p_personGUID   CHAR(36),
                                p_password     VARCHAR(80),
                                p_solutionID   INT )
    READS SQL DATA
BEGIN

  DECLARE count_solution   INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  SELECT     COUNT(*)
  INTO       count_solution
  FROM       solution
  WHERE      solutionID = p_solutionID;

  IF ( count_solution = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) = @OK ) THEN
    
    SELECT @OK as status;

    SELECT  solutionGUID, description, commonVbar20, storageTemp, notes
    FROM    solution
    WHERE   solutionID = p_solutionID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_solutionAnalyte` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_solutionAnalyte`( p_personGUID   CHAR(36),
                                      p_password     VARCHAR(80),
                                      p_solutionID   INT )
    READS SQL DATA
BEGIN
  DECLARE l_analyteCount     INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   l_analyteCount
  FROM   solutionAnalyte
  WHERE  solutionID = p_solutionID
  LIMIT  1;                         

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_analyteCount = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the analyte association was not found in the database";
    SET @US3_LAST_ERRNO = @NO_ANALYTE;

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT @OK AS status;

    SELECT   analyte.analyteID, analyteGUID, description, amount, 
             molecularWeight, vbar, analyte.type
    FROM     solutionAnalyte, analyte
    WHERE    solutionAnalyte.solutionID = p_solutionID
    AND      solutionAnalyte.analyteID   = analyte.analyteID
    ORDER BY description;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_solutionBuffer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_solutionBuffer`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_solutionID   INT )
    READS SQL DATA
BEGIN
  DECLARE l_bufferCount     INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   l_bufferCount
  FROM   solutionBuffer
  WHERE  solutionID = p_solutionID
  LIMIT  1;                         

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_bufferCount = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the buffer association was not found in the database";
    SET @US3_LAST_ERRNO = @NO_BUFFER;

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT @OK AS status;

    SELECT   buffer.bufferID, bufferGUID, description
    FROM     solutionBuffer, buffer
    WHERE    solutionBuffer.solutionID = p_solutionID
    AND      solutionBuffer.bufferID   = buffer.bufferID
    ORDER BY description
    LIMIT    1;                     

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_solutionIDs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_solutionIDs`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_experimentID INT )
    READS SQL DATA
BEGIN
  DECLARE count_solution INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_solution
  FROM       experimentSolutionChannel
  WHERE      experimentID = p_experimentID;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    IF ( count_solution = 0 ) THEN
      SET @US3_LAST_ERRNO = @NOROWS;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   esc.solutionID, s.description 
      FROM     experimentSolutionChannel esc, solution s
      WHERE    esc.experimentID = p_experimentID
      AND      esc.solutionID   = s.solutionID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_solutionID_from_GUID` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_solutionID_from_GUID`( p_personGUID   CHAR(36),
                                            p_password     VARCHAR(80),
                                            p_solutionGUID  CHAR(36) )
    READS SQL DATA
BEGIN
  DECLARE count_solution  INT;
  DECLARE l_solutionID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_solution
  FROM       solution
  WHERE      solutionGUID = p_solutionGUID;

  IF ( count_solution = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SELECT solutionID
    INTO   l_solutionID
    FROM   solution
    WHERE  solutionGUID = p_solutionGUID
    LIMIT  1;                           

    IF ( verify_solution_permission( p_personGUID, p_password, l_solutionID ) = @OK ) THEN
      SELECT @OK AS status;

      SELECT l_solutionID AS solutionID;

    ELSE
      SELECT @US3_LAST_ERRNO AS status;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_spectrum` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_spectrum`( p_personGUID    CHAR(36),
                               p_password      VARCHAR(80),
                               p_componentID   INT,
                               p_componentType enum( 'Buffer', 'Analyte' ),
                               p_opticsType    enum( 'Extinction', 'Refraction', 'Fluorescence' ) )
    READS SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( count_spectrum( p_personGUID, p_password, p_componentID, p_componentType, p_opticsType ) < 1 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';
  
    SELECT @US3_LAST_ERRNO AS status;
      
  ELSE
    
    SELECT @OK AS status;
  
    SELECT   spectrumID, lambda, molarCoefficient
    FROM     spectrum
    WHERE    componentID   = p_componentID
    AND      componentType = p_componentType
    AND      opticsType    = p_opticsType
    ORDER BY lambda;
  
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_timestate`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_timestateID  INT )
    READS SQL DATA
BEGIN

  DECLARE count_timestate   INT;
  DECLARE l_experimentID    INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT experimentID
  INTO   l_experimentID
  FROM   timestate
  WHERE  timestateID = p_timestateID;
 
  SELECT COUNT(*)
  INTO   count_timestate
  FROM   timestate
  WHERE  timestateID = p_timestateID;

  IF ( count_timestate = 0 ) THEN
    SET @US3_LAST_ERRNO = @NOROWS;
    SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
    
    SELECT @OK as status;

    SELECT  experimentID, filename, definitions,
            MD5( data ) AS checksum, LENGTH( data ) AS size,
            timestamp2UTC( lastUpdated ) AS UTC_lastUpdated
    FROM    timestate
    WHERE   timestateID = p_timestateID;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_user_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_user_info`( p_personGUID CHAR(36),
                                p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  CALL config();

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    SELECT @OK AS status;

    SELECT @US3_ID    AS ID, 
           @FNAME     AS firstName, 
           @LNAME     AS lastName, 
           @PHONE     AS phone, 
           @EMAIL     AS email,
           @USERLEVEL AS userLevel;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;


  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_analyte` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_analyte`( p_personGUID  CHAR(36),
                               p_password    VARCHAR(80),
                               p_analyteGUID CHAR(36),
                               p_type        VARCHAR(16),
                               p_sequence    TEXT,
                               p_vbar        FLOAT,
                               p_description TEXT,
                               p_spectrum    TEXT,
                               p_mweight     FLOAT,
                               p_gradform    TINYINT,
                               p_ownerID     INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_analyteID INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_analyteGUID ) = @OK ) ) THEN
    INSERT INTO analyte SET
      analyteGUID        = p_analyteGUID,
      type        = p_type,
      sequence    = p_sequence,
      vbar        = p_vbar,
      description = p_description,
      spectrum    = p_spectrum,
      molecularWeight = p_mweight,
      gradientForming = p_gradform ;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for analyteGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for analyteGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      INSERT INTO analytePerson SET
        analyteID   = @LAST_INSERT_ID,
        personID    = p_ownerID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_aprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_aprofile`( p_personGUID   CHAR(36),
                                p_password     VARCHAR(80),
                                p_aprofileGUID CHAR(36),
                                p_name         VARCHAR(160),
                                p_xml          LONGTEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE l_aprofileID INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = -1;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_aprofileGUID ) = @OK ) ) THEN
 
    INSERT INTO analysisprofile SET
      aprofileGUID   = p_aprofileGUID,
      name           = p_name,
      xml            = p_xml,
      dateUpdated    = NOW();
   
    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for aprofileGUID/name field(s)";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the analysisprofile table";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;
 
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_autoflow_analysis_stages_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_autoflow_analysis_stages_record`( p_personGUID CHAR(36),
                                      	            p_password   VARCHAR(80),
					            p_requestID  INT(11) )
    MODIFIES SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    INSERT INTO autoflowAnalysisStages SET
      requestID         = p_requestID;
    
  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_buffer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_buffer`( p_personGUID      CHAR(36),
                              p_password        VARCHAR(80),
                              p_bufferGUID      CHAR(36),
                              p_description     TEXT,
                              p_compressibility FLOAT,
                              p_pH              FLOAT,
                              p_density         FLOAT,
                              p_viscosity       FLOAT,
                              p_manual          TINYINT,
                              p_private         TINYINT,
                              p_ownerID         INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_bufferID INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_bufferGUID ) = @OK ) ) THEN
    INSERT INTO buffer SET
      bufferGUID      = p_bufferGUID,
      description     = p_description,
      compressibility = p_compressibility,
      pH              = p_pH,
      density         = p_density,
      viscosity       = p_viscosity,
      manual          = p_manual;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for bufferGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for bufferGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      INSERT INTO bufferPerson SET
        bufferID    = @LAST_INSERT_ID,
        personID    = p_ownerID,
        private     = p_private;
    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_cell_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_cell_experiment`( p_personGUID   CHAR(36),
                                       p_password     VARCHAR(80),
                                       p_cellGUID     CHAR(36),
                                       p_name         TEXT,
                                       p_holeNumber   INT(11),
                                       p_abstractCenterpieceID INT,
                                       p_experimentID INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
  SET @@FOREIGN_KEY_CHECKS=0;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    INSERT INTO cell SET
      cellGUID     = p_cellGUID,
      name         = p_name,
      holeNumber   = p_holeNumber,
      experimentID = p_experimentID,
      abstractCenterpieceID = p_abstractCenterpieceID,
      dateUpdated  = NOW();

  END IF;

  SET @@FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_editedData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_editedData`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_rawDataID    INT,
                                  p_editGUID     CHAR(36),
                                  p_label        VARCHAR(80),
                                  p_filename     VARCHAR(255),
                                  p_comment      TEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field     TINYINT DEFAULT 0;
  DECLARE l_experimentID INT;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SELECT     experimentID
  INTO       l_experimentID
  FROM       rawData
  WHERE      rawDataID = p_rawDataID;

  IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
 
    
    INSERT INTO editedData SET
      rawDataID     = p_rawDataID,
      editGUID      = p_editGUID,
      label         = p_label,
      filename      = p_filename,
      comment       = p_comment,
      lastUpdated   = NOW();
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the editedData table";

    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();

    END IF;
   
   END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_eprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_eprofile`( p_personGUID       CHAR(36),
                               p_password         VARCHAR(80),
                               p_componentID      INT,
                               p_componentType    enum( 'Buffer', 'Analyte', 'Sample' ),
                               p_valueType        enum( 'absorbance', 'molarExtinction', 'massExtinction' ),
                               p_xml              LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_componentID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) = @OK ) THEN
    INSERT INTO extinctionProfile SET
      componentID      = p_componentID,
      componentType    = p_componentType,
      valueType        = p_valueType,
      xml              = p_xml;
    SET @LAST_INSERT_ID = LAST_INSERT_ID();
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_experiment`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_expGUID      CHAR(36),
                                  p_projectID    INT,
                                  p_runID        VARCHAR(255),
                                  p_labID        INT,
                                  p_instrumentID INT,
                                  p_operatorID   INT,
                                  p_rotorID      INT,
                                  p_calibrationID INT,
                                  p_type         VARCHAR(30),
                                  p_runType      ENUM( 'RA', 'RI', 'IP', 'FI', 'WA', 'WI' ),
                                  p_RIProfile    LONGTEXT,
                                  p_runTemp      FLOAT,
                                  p_label        VARCHAR(80),
                                  p_comment      TEXT,
                                  p_centrifugeProtocol TEXT,
                                  p_ownerID      INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_experimentID INT;
  DECLARE l_count_runID  INT;
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK           )   &&
       ( verify_operator_permission( p_personGUID, p_password, 
          p_labID, p_instrumentID, p_operatorID ) = @OK          )   &&
       ( check_GUID( p_personGUID, p_password, p_expGUID ) = @OK ) ) THEN
    
    SELECT COUNT(*)
    INTO   l_count_runID
    FROM   experimentPerson p, experiment e
    WHERE  p.experimentID = e.experimentID
    AND    e.runID = p_runID
    AND    p.personID = @US3_ID;

    IF ( l_count_runID > 0 ) THEN
      SET @US3_LAST_ERRNO = @DUPFIELD;
      SET @US3_LAST_ERROR = "MySQL: Duplicate runID in experiment table";

    ELSE
      IF ( p_runType != 'RI' ) THEN
        SET p_RIProfile = '';
      END IF;

      INSERT INTO experiment SET
        projectID          = p_projectID,
        experimentGUID     = p_expGUID,
        runID              = p_runID,
        labID              = p_labID,
        instrumentID       = p_instrumentID,
        operatorID         = p_operatorID,
        rotorID            = p_rotorID,
        rotorCalibrationID = p_calibrationID,
        type               = p_type,
        runType            = p_runType,
        RIProfile          = p_RIProfile,
        runTemp            = p_runTemp,
        label              = p_label,
        comment            = p_comment,
        centrifugeProtocol = p_centrifugeProtocol,
        dateBegin          = NOW(),
        dateUpdated        = NOW() ;
  
      IF ( duplicate_key = 1 ) THEN
        SET @US3_LAST_ERRNO = @INSERTDUP;
        SET @US3_LAST_ERROR = "MySQL: Duplicate entry for experimentGUID field";

      ELSE
        SET @LAST_INSERT_ID  = LAST_INSERT_ID();
    
        INSERT INTO experimentPerson SET
          experimentID = @LAST_INSERT_ID,
          personID     = p_ownerID;

      END IF;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_experiment_solution` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_experiment_solution`( p_personGUID   CHAR(36),
                                           p_password     VARCHAR(80),
                                           p_experimentID INT,
                                           p_solutionID   INT,
                                           p_channelID    INT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) &&
       ( verify_solution_permission  ( p_personGUID, p_password, p_solutionID   ) = @OK ) ) THEN

    INSERT INTO experimentSolutionChannel SET
      experimentID  = p_experimentID,
      solutionID    = p_solutionID,
      channelID     = p_channelID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_model` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_model`( p_personGUID  CHAR(36),
                             p_password    VARCHAR(80),
                             p_modelGUID   CHAR(36),
                             p_description TEXT,
                             p_xml         LONGTEXT,
                             p_variance    DOUBLE,
                             p_meniscus    DOUBLE,
                             p_editGUID    CHAR(36),
                             p_ownerID     INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_modelID      INT;
  DECLARE l_count_editID INT;
  DECLARE l_editID       INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SET l_editID = 1;         
  SELECT COUNT(*) 
  INTO   l_count_editID
  FROM   editedData
  WHERE  editGUID = p_editGUID;

  IF ( l_count_editID > 0 ) THEN
    SELECT editedDataID
    INTO   l_editID
    FROM   editedData
    WHERE  editGUID = p_editGUID
    LIMIT  1;

  END IF;

  IF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_modelGUID ) = @OK ) ) THEN
    INSERT INTO model SET
      editedDataID = l_editID,
      modelGUID    = p_modelGUID,
      description  = p_description,
      xml          = p_xml,
      variance     = p_variance,
      meniscus     = p_meniscus,
      lastUpdated  = NOW();

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for modelGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for modelGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      INSERT INTO modelPerson SET
        modelID   = @LAST_INSERT_ID,
        personID  = p_ownerID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_mrecs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_mrecs`( p_personGUID    CHAR(36),
                             p_password      VARCHAR(80),
                             p_mrecsGUID     CHAR(36),
                             p_editedDataID  INT(11),
                             p_modelID       INT(11),
                             p_modelGUID     CHAR(36),
                             p_description   TEXT,
                             p_xml           LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_editID          INT;
  DECLARE l_editID_count    INT;
  DECLARE l_modelID         INT;
  DECLARE l_modelID_count   INT;
  DECLARE l_modelGUID_count INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SET l_editID = 1;         
  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    SET l_editID = p_editedDataID;

  END IF;

  SELECT COUNT(*)
  INTO   l_editID_count
  FROM   editedData
  WHERE  editedDataID = p_editedDataID
  LIMIT  1;                         

  
  SELECT COUNT(*)
  INTO   l_modelGUID_count
  FROM   model
  WHERE  modelGUID = p_modelGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_modelID_count
  FROM   model
  WHERE  modelID = p_modelID
  LIMIT  1;                         

  IF ( l_modelGUID_count = 1 ) THEN 
    SELECT modelID
    INTO   l_modelID
    FROM   model
    WHERE  modelGUID = p_modelGUID
    LIMIT  1;

  ELSEIF ( l_modelID_count = 1 ) THEN
    SET l_modelID = p_modelID;

  ELSE
    
    SET l_modelID   = 0;

  END IF;

  
  SET l_editID = 1;         
  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    SET l_editID = p_editedDataID;
  END IF;

  IF ( check_GUID ( p_personGUID, p_password, p_mrecsGUID ) = @OK ) THEN
    INSERT INTO pcsa_modelrecs SET
      mrecsGUID    = p_mrecsGUID,
      editedDataID = l_editID,
      modelID      = l_modelID,
      description  = p_description,
      xml          = p_xml,
      lastUpdated  = NOW();

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for mrecsGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for mrecsGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_noise` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_noise`( p_personGUID    CHAR(36),
                             p_password      VARCHAR(80),
                             p_noiseGUID     CHAR(36),
                             p_editedDataID  INT(11),
                             p_modelID       INT(11),
                             p_modelGUID     CHAR(36),
                             p_noiseType     ENUM( 'ri_noise', 'ti_noise' ),
                             p_description   TEXT,
                             p_xml           LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_editID          INT;
  DECLARE l_modelID         INT;
  DECLARE l_modelID_count   INT;
  DECLARE l_modelGUID       CHAR(36);
  DECLARE l_modelGUID_count INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SET l_editID = 1;         
  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    SET l_editID = p_editedDataID;

  END IF;

  
  SELECT COUNT(*)
  INTO   l_modelGUID_count
  FROM   model
  WHERE  modelGUID = p_modelGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_modelID_count
  FROM   model
  WHERE  modelID = p_modelID
  LIMIT  1;                         

  IF ( l_modelGUID_count = 1 ) THEN 
    SET l_modelGUID = p_modelGUID;

    SELECT modelID
    INTO   l_modelID
    FROM   model
    WHERE  modelGUID = p_modelGUID
    LIMIT  1;

  ELSEIF ( l_modelID_count = 1 ) THEN
    SET l_modelID = p_modelID;

    SELECT modelGUID
    INTO   l_modelGUID
    FROM   model
    WHERE  modelID = p_modelID;

  ELSE
    
    SET l_modelID   = 0;
    SET l_modelGUID = p_modelGUID;

  END IF;

  IF ( verify_model_permission( p_personGUID, p_password, l_modelID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_modelGUID_count = 0 && l_modelID_count = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the model was not found in the database";
    SET @US3_LAST_ERRNO = @NO_MODEL;

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( check_GUID ( p_personGUID, p_password, p_noiseGUID ) = @OK ) THEN
    INSERT INTO noise SET
      noiseGUID    = p_noiseGUID,
      editedDataID = l_editID,
      modelID      = l_modelID,
      modelGUID    = l_modelGUID,
      noiseType    = p_noiseType,
      description  = p_description,
      xml          = p_xml,
      timeEntered  = NOW();

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for noiseGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for noiseGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_person` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_person`( p_personGUID   CHAR(36),
                              p_password     VARCHAR(80),
                              p_fname        VARCHAR(30),
                              p_lname        VARCHAR(30),
                              p_address      VARCHAR(255),
                              p_city         VARCHAR(30),
                              p_state        CHAR(2),
                              p_zip          VARCHAR(10),
                              p_phone        VARCHAR(24),
                              p_new_email    VARCHAR(63),
                              p_new_guid     CHAR(36),
                              p_organization VARCHAR(45),
                              p_new_password VARCHAR(80) )
    MODIFIES SQL DATA
BEGIN
  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) &&
       ( check_GUID      ( p_personGUID, p_password, p_new_guid ) = @OK ) ) THEN

    INSERT INTO people SET
           fname        = p_fname,
           lname        = p_lname,
           address      = p_address,
           city         = p_city,
           state        = p_state,
           zip          = p_zip,
           phone        = p_phone,
           email        = p_new_email,
           personGUID   = p_new_guid,
           organization = p_organization,
           password     = MD5(p_new_password),
           activated    = true,
           signup       = NOW(),
           lastLogin    = NOW(),
           clusterAuthorizations = 'lonestar5:stampede2:comet:alamo:jetstream',
           userlevel    = 0;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for email or personGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for a field that cannot be NULL";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_project` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_project`( p_personGUID       CHAR(36),
                               p_password         VARCHAR(80),
                               p_projectGUID      CHAR(36),
                               p_goals            TEXT,
                               p_molecules        TEXT,
                               p_purity           TEXT,
                               p_expense          TEXT,
                               p_bufferComponents TEXT,
                               p_saltInformation  TEXT,
                               p_AUC_questions    TEXT,
                               p_notes            TEXT,
                               p_description      TEXT,
                               p_status           ENUM('submitted', 'designed', 
                                                       'scheduled', 'uploaded', 
                                                       'anlyzed',   'invoiced', 
                                                       'paid',      'other'),
                               p_ownerID          INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_projectID INT;
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK               )   &&
       ( check_GUID( p_personGUID, p_password, p_projectGUID ) = @OK ) ) THEN
    INSERT INTO project SET
      projectGUID      = p_projectGUID,
      goals            = p_goals,
      molecules        = p_molecules,
      purity           = p_purity,
      expense          = p_expense,
      bufferComponents = p_bufferComponents,
      saltInformation  = p_saltInformation,
      AUC_questions    = p_AUC_questions,
      notes            = p_notes,
      description      = p_description,
      status           = p_status ;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for projectGUID field";

    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();
    
      INSERT INTO projectPerson SET
        projectID = @LAST_INSERT_ID,
        personID  = p_ownerID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_project2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_project2`( p_personGUID       CHAR(36),
                               p_password         VARCHAR(80),
                               p_projectGUID      CHAR(36),
                               p_goals            TEXT,
                               p_molecules        TEXT,
                               p_purity           TEXT,
                               p_expense          TEXT,
                               p_bufferComponents TEXT,
                               p_saltInformation  TEXT,
                               p_AUC_questions    TEXT,
                               p_expDesign        TEXT,
                               p_notes            TEXT,
                               p_description      TEXT,
                               p_status           ENUM('submitted', 'designed', 
                                                       'scheduled', 'uploaded', 
                                                       'anlyzed',   'invoiced', 
                                                       'paid',      'other'),
                               p_ownerID          INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_projectID INT;
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK               )   &&
       ( check_GUID( p_personGUID, p_password, p_projectGUID ) = @OK ) ) THEN
    INSERT INTO project SET
      projectGUID      = p_projectGUID,
      goals            = p_goals,
      molecules        = p_molecules,
      purity           = p_purity,
      expense          = p_expense,
      bufferComponents = p_bufferComponents,
      saltInformation  = p_saltInformation,
      AUC_questions    = p_AUC_questions,
      expDesign        = p_expDesign,
      notes            = p_notes,
      description      = p_description,
      status           = p_status ;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for projectGUID field";

    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();
    
      INSERT INTO projectPerson SET
        projectID = @LAST_INSERT_ID,
        personID  = p_ownerID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_protocol` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_protocol`( p_personGUID   CHAR(36),
                                p_password     VARCHAR(80),
                                p_ownerID      INT,
                                p_protocolGUID CHAR(36),
                                p_description  VARCHAR(160),
                                p_xml          LONGTEXT,
                                p_optimaHost   VARCHAR(24),
                                p_rotorID      INT,
                                p_speed1       INT,
                                p_duration     FLOAT,
                                p_usedcells    INT,
                                p_estscans     INT,
                                p_solution1    VARCHAR(80),
                                p_solution2    VARCHAR(80),
                                p_wavelengths  INT )
    MODIFIES SQL DATA
BEGIN

  DECLARE l_protocolID INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = -1;
 
  IF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_protocolGUID ) = @OK ) ) THEN
 
    INSERT INTO protocol SET
      protocolGUID   = p_protocolGUID,
      description    = p_description,
      xml            = p_xml,
      optimaHost     = p_optimaHost,
      dateUpdated    = NOW(),
      rotorID        = p_rotorID,
      speed1         = p_speed1,
      duration       = p_duration,
      usedcells      = p_usedcells,
      estscans       = p_estscans,
      solution1      = p_solution1,
      solution2      = p_solution2,
      wavelengths    = p_wavelengths;
   
    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for protocolGUID/description field(s)";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the protocol table";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      INSERT INTO protocolPerson SET
        protocolID = @LAST_INSERT_ID,
        personID   = p_ownerID;

    END IF;
 
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_rawData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_rawData`( p_personGUID   CHAR(36),
                               p_password     VARCHAR(80),
                               p_rawDataGUID  CHAR(36),
                               p_label        VARCHAR(80),
                               p_filename     VARCHAR(255),
                               p_comment      TEXT,
                               p_experimentID INT,
                               p_solutionID   INT,
                               p_channelID    INT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field    TINYINT DEFAULT 0;
  DECLARE duplicate_key TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
 
    
    INSERT INTO rawData SET
      rawDataGUID   = p_rawDataGUID,    
      label         = p_label,
      filename      = p_filename,
      comment       = p_comment,
      experimentID  = p_experimentID,
      solutionID    = p_solutionID,
      channelID     = p_channelID,
      lastUpdated   = NOW();
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the rawData table";

    ELSEIF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @DUPFIELD;
      SET @US3_LAST_ERROR = CONCAT( "MySQL: The GUID ",
                                    p_rawDataGUID,
                                    " already exists in the rawData table" );
      
    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();

    END IF;
   
   END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_report`( p_personGUID  CHAR(36),
                              p_password    VARCHAR(80),
                              p_reportGUID  CHAR(36),
                              p_runID       VARCHAR(255),
                              p_title       VARCHAR(255),
                              p_html        LONGTEXT,
                              p_ownerID     INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;
  DECLARE count_experiment  INT DEFAULT 0;
  DECLARE count_reports     INT DEFAULT 0;
  DECLARE l_experimentID    INT DEFAULT -1;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SET @LAST_INSERT_ID = 0;
 
  
  SELECT COUNT(*)
  INTO   count_experiment
  FROM   experimentPerson p, experiment e
  WHERE  p.personID = p_ownerID
  AND    p.experimentID = e.experimentID
  AND    e.runID = p_runID;

  
  SELECT COUNT(*)
  INTO   count_reports
  FROM   reportPerson p, report r
  WHERE  personID = p_ownerID
  AND    p.reportID = r.reportID
  AND    runID = p_runID;

  SELECT e.experimentID
  INTO   l_experimentID
  FROM   experimentPerson p, experiment e
  WHERE  p.personID = p_ownerID
  AND    p.experimentID = e.experimentID
  AND    e.runID = p_runID;

  IF ( count_experiment = 0 ) THEN
    SET @US3_LAST_ERRNO = @NO_EXPERIMENT;
    SET @US3_LAST_ERROR = "MySQL: No experiment with that runID exists";

  ELSEIF ( count_reports > 0 ) THEN
    SET @US3_LAST_ERRNO = @INSERTDUP;
    SET @US3_LAST_ERROR = "MySQL: Duplicate entry for runID field";

  ELSEIF ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
           ( check_GUID ( p_personGUID, p_password, p_reportGUID ) = @OK ) ) THEN
    INSERT INTO report SET
      reportGUID   = p_reportGUID,
      experimentID = l_experimentID,
      runID        = p_runID,
      title        = p_title,
      html         = p_html;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for reportGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for reportGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      INSERT INTO reportPerson SET
        reportID  = @LAST_INSERT_ID,
        personID  = p_ownerID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_reportDocument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_reportDocument`( p_personGUID          CHAR(36),
                                      p_password            VARCHAR(80),
                                      p_reportTripleID      INT,
                                      p_reportDocumentGUID  CHAR(36),
                                      p_editedDataID        INT,
                                      p_label               VARCHAR(160),
                                      p_filename            VARCHAR(255),
                                      p_analysis            VARCHAR(20),
                                      p_subAnalysis         VARCHAR(20),
                                      p_documentType        VARCHAR(20) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_reportID    INT;
  DECLARE l_documentID  INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;
  DECLARE constraint_failed TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  DECLARE CONTINUE HANDLER FOR 1452
    SET constraint_failed = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SET @LAST_INSERT_ID = 0;
 
  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_reportDocumentGUID ) = @OK )    &&
       ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) ) THEN
    INSERT INTO reportDocument SET
      reportDocumentGUID  = p_reportDocumentGUID,
      editedDataID        = p_editedDataID,
      label               = p_label,
      filename            = p_filename,
      analysis            = p_analysis,
      subAnalysis         = p_subAnalysis,
      documentType        = p_documentType;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for reportDocumentGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for reportDocumentGUID field";

    ELSEIF ( constraint_failed = 1 ) THEN
      SET @US3_LAST_ERRNO = @CONSTRAINT_FAILED;
      SET @US3_LAST_ERROR = "MySQL: FK Constraint failed while inserting into reportDocument";

    ELSE
      SET @US3_LAST_ERRNO = @OK;
      SET @US3_LAST_ERROR = '';

      SET l_documentID = LAST_INSERT_ID();

      INSERT INTO documentLink SET
        reportTripleID   = p_reportTripleID,
        reportDocumentID = l_documentID;

      SET @LAST_INSERT_ID = l_documentID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_reportTriple` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_reportTriple`( p_personGUID        CHAR(36),
                                    p_password          VARCHAR(80),
                                    p_reportTripleGUID  CHAR(36),
                                    p_reportID          INT,
                                    p_resultID          INT,
                                    p_triple            VARCHAR(20),
                                    p_dataDescription   VARCHAR(255) )
    MODIFIES SQL DATA
BEGIN
  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;
  DECLARE constraint_failed TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  DECLARE CONTINUE HANDLER FOR 1452
    SET constraint_failed = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SET @LAST_INSERT_ID = 0;
 
  IF ( ( verify_report_permission( p_personGUID, p_password, p_reportID ) = @OK ) &&
       ( check_GUID ( p_personGUID, p_password, p_reportTripleGUID ) = @OK )      ) THEN
    INSERT INTO reportTriple SET
      reportTripleGUID  = p_reportTripleGUID,
      reportID          = p_reportID,
      resultID          = p_resultID,
      triple            = p_triple,
      dataDescription   = p_dataDescription ;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for reportTripleGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for reportTripleGUID field";

    ELSEIF ( constraint_failed = 1 ) THEN
      SET @US3_LAST_ERRNO = @CONSTRAINT_FAILED;
      SET @US3_LAST_ERROR = "MySQL: FK Constraint failed inserting into reportTriple";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_solution` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_solution`( p_personGUID   CHAR(36),
                                p_password     VARCHAR(80),
                                p_solutionGUID CHAR(36),
                                p_description  VARCHAR(80),
                                p_commonVbar20 DOUBLE,
                                p_storageTemp  FLOAT,
                                p_notes        TEXT,
                                p_experimentID INT,
                                p_channelID    INT,
                                p_ownerID      INT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
 
    
    INSERT INTO solution SET
      solutionGUID   = p_solutionGUID,    
      description    = p_description,
      commonVbar20   = p_commonVbar20,
      storageTemp    = p_storageTemp,
      notes          = p_notes;
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the solution table";

    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();

      
      INSERT INTO solutionPerson SET
        solutionID   = @LAST_INSERT_ID,
        personID     = p_ownerID;

      
      IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
        INSERT INTO experimentSolutionChannel SET
          experimentID = p_experimentID,
          solutionID   = @LAST_INSERT_ID,
          channelID    = p_channelID;

      ELSE
        
        INSERT INTO experimentSolutionChannel SET
          experimentID = 1,
          solutionID   = @LAST_INSERT_ID,
          channelID    = p_channelID;

      END IF;

      

    END IF;
   
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_solutionAnalyte` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_solutionAnalyte`( p_personGUID   CHAR(36),
                                      p_password     VARCHAR(80),
                                      p_solutionID   INT,
                                      p_analyteID    INT,
                                      p_analyteGUID  CHAR(36),
                                      p_amount       FLOAT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_analyteID        INT;
  DECLARE l_analyteCount     INT;
  DECLARE l_analyteGUIDCount INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   l_analyteGUIDCount
  FROM   analyte
  WHERE  analyteGUID = p_analyteGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_analyteCount
  FROM   analyte
  WHERE  analyteID = p_analyteID
  LIMIT  1;                         

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_analyteGUIDCount = 0 && l_analyteCount = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the analyte was not found in the database";
    SET @US3_LAST_ERRNO = @NO_ANALYTE;

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    IF ( l_analyteGUIDCount = 1 ) THEN
      
      SELECT analyteID
      INTO   l_analyteID
      FROM   analyte
      WHERE  analyteGUID = p_analyteGUID
      LIMIT  1;
  
    ELSE
      SET l_analyteID = p_analyteID;

    END IF;

    
    IF ( verify_analyte_permission( p_personGUID, p_password, l_analyteID ) = @OK ) THEN
      
      INSERT INTO solutionAnalyte SET
        solutionID = p_solutionID,
        analyteID  = l_analyteID,
        amount     = p_amount;
  
      SELECT @OK AS status;

    ELSE
      SET @US3_LAST_ERROR = "MySQL: you are not permitted to use this analyte";
      SET @US3_LAST_ERRNO = @NOTPERMITTED;

      SELECT @US3_LAST_ERRNO AS status;
      
    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_solutionBuffer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_solutionBuffer`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_solutionID   INT,
                                     p_bufferID     INT,
                                     p_bufferGUID   CHAR(36) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_bufferID        INT;
  DECLARE l_bufferCount     INT;
  DECLARE l_bufferGUIDCount INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT COUNT(*)
  INTO   l_bufferGUIDCount
  FROM   buffer
  WHERE  bufferGUID = p_bufferGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_bufferCount
  FROM   buffer
  WHERE  bufferID = p_bufferID
  LIMIT  1;                         

  IF ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_bufferGUIDCount = 0 && l_bufferCount = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the buffer was not found in the database";
    SET @US3_LAST_ERRNO = @NO_BUFFER;

    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    IF ( l_bufferGUIDCount = 1 ) THEN
      
      SELECT bufferID
      INTO   l_bufferID
      FROM   buffer
      WHERE  bufferGUID = p_bufferGUID
      LIMIT  1;
  
    ELSE
      SET l_bufferID = p_bufferID;

    END IF;

    
    DELETE FROM solutionBuffer
    WHERE       solutionID = p_solutionID;

    
    INSERT INTO solutionBuffer SET
      solutionID = p_solutionID,
      bufferID   = l_bufferID;

    SELECT @OK AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_spectrum` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_spectrum`( p_personGUID       CHAR(36),
                               p_password         VARCHAR(80),
                               p_componentID      INT,
                               p_componentType    enum( 'Buffer', 'Analyte' ),
                               p_opticsType       enum( 'Extinction', 'Refraction', 'Fluorescence' ),
                               p_lambda           FLOAT,
                               p_molarCoefficient FLOAT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_componentID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) = @OK ) THEN
    INSERT INTO spectrum SET
      componentID      = p_componentID,
      componentType    = p_componentType,
      opticsType       = p_opticsType,
      lambda           = p_lambda,
      molarCoefficient = p_molarCoefficient;
    SET @LAST_INSERT_ID = LAST_INSERT_ID();
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_speedstep` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_speedstep`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_experimentID INT(11),
                                 p_scans        INT(11),
                                 p_durationhrs  INT(11),
                                 p_durationmins DOUBLE,
                                 p_delayhrs     INT(11),
                                 p_delaymins    DOUBLE,
                                 p_rotorspeed   INT(11),
                                 p_acceleration INT(11),
                                 p_accelerflag  TINYINT(1),
                                 p_w2tfirst     FLOAT,
                                 p_w2tlast      FLOAT,
                                 p_timefirst    INT(11),
                                 p_timelast     INT(11),
                                 p_setspeed     INT(11),
                                 p_avgspeed     FLOAT,
                                 p_speedsdev    FLOAT )
    MODIFIES SQL DATA
BEGIN
  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
  SET @@FOREIGN_KEY_CHECKS=0;

  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
    INSERT INTO speedstep SET
      experimentID = p_experimentID,
      scans        = p_scans,
      durationhrs  = p_durationhrs,
      durationmins = p_durationmins,
      delayhrs     = p_delayhrs,
      delaymins    = p_delaymins,
      rotorspeed   = p_rotorspeed,
      acceleration = p_acceleration,
      accelerflag  = p_accelerflag,
      w2tfirst     = p_w2tfirst,
      w2tlast      = p_w2tlast,
      timefirst    = p_timefirst,
      timelast     = p_timelast,
      setspeed     = p_setspeed,
      avgspeed     = p_avgspeed,
      speedsdev    = p_speedsdev;

  END IF;

  SET @@FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `new_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `new_timestate`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_experimentID INT,
                                 p_filename     VARCHAR(255),
                                 p_definitions  LONGTEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
 
    
    
    
    INSERT INTO timestate SET
      experimentID   = p_experimentID,
      filename       = p_filename,
      definitions    = p_definitions,
      lastUpdated    = NOW();
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the timestate table";

    ELSE
      SET @LAST_INSERT_ID  = LAST_INSERT_ID();

      

    END IF;
 
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `read_autoflowAnalysisHistory_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `read_autoflowAnalysisHistory_record`( p_personGUID    CHAR(36),
                                      	       	     p_password      VARCHAR(80),
                                       		     p_requestID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowAnalysisHistory
  WHERE      requestID = p_requestID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   requestID, tripleName, clusterDefault, filename, aprofileGUID, invID, currentGfacID, 
      	       currentHPCARID, statusJson, status, statusMsg, createTime, updateTime, createUser, updateUser,
               nextWaitStatus, nextWaitStatusMsg
      FROM     autoflowAnalysisHistory 
      WHERE    requestID = p_requestID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `read_autoflowAnalysis_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `read_autoflowAnalysis_record`( p_personGUID    CHAR(36),
                                      	       	p_password      VARCHAR(80),
                                       		p_requestID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowAnalysis
  WHERE      requestID = p_requestID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   requestID, tripleName, clusterDefault, filename, aprofileGUID, invID, currentGfacID,
      	       currentHPCARID, statusJson, status, statusMsg, createTime, updateTime, createUser, updateUser,
               nextWaitStatus, nextWaitStatusMsg
      FROM     autoflowAnalysis 
      WHERE    requestID = p_requestID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `read_autoflow_record` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `read_autoflow_record`( p_personGUID    CHAR(36),
                                       	p_password      VARCHAR(80),
                                       	p_autoflowID  INT )
    READS SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      ID = p_autoflowID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

      SELECT @US3_LAST_ERRNO AS status;

    ELSE
      SELECT @OK AS status;

      SELECT   protName, cellChNum, tripleNum, duration, runName, expID, 
      	       runID, status, dataPath, optimaName, runStarted, invID, created, 
	       corrRadii, expAborted, label, gmpRun, filename, aprofileGUID, analysisIDs  
      FROM     autoflow 
      WHERE    ID = p_autoflowID;

    END IF;

  ELSE
    SELECT @US3_LAST_ERRNO AS status;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `replace_rotor_calibration` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `replace_rotor_calibration`( p_personGUID   CHAR(36),
                                             p_password     VARCHAR(80),
                                             p_old_calibrationID   INT,
                                             p_new_calibrationID   INT )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_experiments          INT;
  DECLARE count_calibrations         INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_experiments
  FROM       experiment
  WHERE      rotorCalibrationID = p_old_calibrationID;

  SELECT     COUNT(*)
  INTO       count_calibrations
  FROM       rotorCalibration
  WHERE      rotorCalibrationID = p_new_calibrationID;

  IF ( ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) ) THEN
    IF ( count_calibrations = 0 ) THEN
      
      SET @US3_LAST_ERRNO = @NO_CALIB;
      SET @US3_LAST_ERROR = "MySQL: The new calibration does not exist\n";

    ELSEIF ( count_experiments > 0 ) THEN
      
      UPDATE experiment SET
        rotorCalibrationID = p_new_calibrationID
      WHERE  rotorCalibrationID = p_old_calibrationID;

    
      
        
    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `set_nucleotide_info` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `set_nucleotide_info`( p_personGUID  CHAR(36),
                                       p_password    VARCHAR(80),
                                       p_analyteID   INT,
                                       p_doubleStranded TINYINT,
                                       p_complement  TINYINT,
                                       p__3prime     TINYINT,
                                       p__5prime     TINYINT,
                                       p_sodium      DOUBLE,
                                       p_potassium   DOUBLE,
                                       p_lithium     DOUBLE,
                                       p_magnesium   DOUBLE,
                                       p_calcium     DOUBLE )
    MODIFIES SQL DATA
BEGIN
  DECLARE not_found     TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_analyte_permission( p_personGUID, p_password, p_analyteID ) = @OK ) THEN
    UPDATE analyte SET
      doubleStranded = p_doubleStranded,
      complement     = p_complement,
      _3prime        = p__3prime,
      _5prime        = p__5prime,
      sodium         = p_sodium,
      potassium      = p_potassium, 
      lithium        = p_lithium, 
      magnesium      = p_magnesium, 
      calcium        = p_calcium 
    WHERE analyteID = p_analyteID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ANALYTE;
      SET @US3_LAST_ERROR = "MySQL: No analyte with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_analyte` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_analyte`( p_personGUID  CHAR(36),
                                  p_password    VARCHAR(80),
                                  p_analyteID   INT,
                                  p_type        VARCHAR(16),
                                  p_sequence    TEXT,
                                  p_vbar        FLOAT,
                                  p_description TEXT,
                                  p_spectrum    TEXT,
                                  p_mweight     FLOAT,
                                  p_gradform    TINYINT )
    MODIFIES SQL DATA
BEGIN
  DECLARE not_found     TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_analyte_permission( p_personGUID, p_password, p_analyteID ) = @OK ) THEN
    UPDATE analyte SET
      type        = p_type,
      sequence    = p_sequence,
      vbar        = p_vbar,
      description = p_description,
      spectrum    = p_spectrum,
      molecularWeight = p_mweight,
      gradientForming = p_gradform
    WHERE analyteID = p_analyteID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_ANALYTE;
      SET @US3_LAST_ERROR = "MySQL: No analyte with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_analysis_record_at_deletion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_analysis_record_at_deletion`( p_personGUID  CHAR(36),
                                             		     p_password      VARCHAR(80),
                                                             p_statusMsg     TEXT,
                                       	     		     p_requestID     INT  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE current_status TEXT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowAnalysis
  WHERE      requestID = p_requestID;

  SELECT     status
  INTO       current_status
  FROM	     autoflowAnalysis
  WHERE	     requestID = p_requestID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflowAnalysis
      SET      status = 'CANCELED', statusMsg = p_statusMsg
      WHERE    requestID = p_requestID;
	

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_analysis_record_at_deletion_other_wvl` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_analysis_record_at_deletion_other_wvl`( p_personGUID  CHAR(36),
                                             		               p_password      VARCHAR(80),
                                       	     		               p_requestID     INT  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE current_status TEXT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowAnalysis
  WHERE      requestID = p_requestID;

  SELECT     status
  INTO       current_status
  FROM	     autoflowAnalysis
  WHERE	     requestID = p_requestID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflowAnalysis
      SET      nextWaitStatus = 'CANCELED', nextWaitStatusMsg = 'Job has been scheduled for deletion'
      WHERE    requestID = p_requestID;
	

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_analysis_record_at_fitmen` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_analysis_record_at_fitmen`( p_personGUID  CHAR(36),
                                             		   p_password      VARCHAR(80),
                                       	     		   p_requestID     INT  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;
  DECLARE current_status TEXT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowAnalysis
  WHERE      requestID = p_requestID;

  SELECT     status
  INTO       current_status
  FROM	     autoflowAnalysis
  WHERE	     requestID = p_requestID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflowAnalysis
      SET      nextWaitStatus = 'COMPLETE', nextWaitStatusMsg = 'The manual stage has been completed'
      WHERE    requestID = p_requestID;
	

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_analysis` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_analysis`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	INT,
                                                p_optima        VARCHAR(300)  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      status = 'REPORT'
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_edit_data` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_edit_data`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	INT,
						p_analysisIDs   TEXT,
                                                p_optima        VARCHAR(300) )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      status = 'ANALYSIS', analysisIDs = p_analysisIDs
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_lims_import` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_lims_import`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	INT,
					  	p_filename      VARCHAR(300),
                                                p_optima        VARCHAR(300) )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      filename = p_filename, status = 'EDIT_DATA'
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_live_update` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_live_update`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	 INT,
					  	p_curDir        VARCHAR(300),
                                                p_optima        VARCHAR(300)  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      dataPath = p_curDir, status = 'EDITING'
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_live_update_expaborted` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_live_update_expaborted`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	 INT,
                                                p_optima        VARCHAR(300) )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      expAborted = 'YES'
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_at_live_update_radiicorr` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_at_live_update_radiicorr`( p_personGUID    CHAR(36),
                                             	p_password      VARCHAR(80),
                                       	     	p_runID    	 INT,
                                                p_optima         VARCHAR(300)   )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflow
  WHERE      runID = p_runID AND optimaName = p_optima;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflow
      SET      corrRadii = 'NO'
      WHERE    runID = p_runID AND optimaName = p_optima;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_report_at_import` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_report_at_import`( p_personGUID    CHAR(36),
                                             	  p_password      VARCHAR(80),
                                       	     	  p_reportID      INT,
					  	  p_dropped       TEXT  )
    MODIFIES SQL DATA
BEGIN
  DECLARE count_records INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     COUNT(*)
  INTO       count_records
  FROM       autoflowReport
  WHERE      reportID = p_reportID;

  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( count_records = 0 ) THEN
      SET @US3_LAST_ERRNO = @NO_AUTOFLOW_RECORD;
      SET @US3_LAST_ERROR = 'MySQL: no rows returned';

    ELSE
      UPDATE   autoflowReport
      SET      triplesDropped = p_dropped
      WHERE    reportID = p_reportID;

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_autoflow_runid_starttime` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_autoflow_runid_starttime`( p_personGUID    CHAR(36),
                                         	 p_password      VARCHAR(80),
                                       	 	 p_expID    	 INT,
					 	 p_runid    	 INT,
                                                 p_optima        VARCHAR(300) )
    MODIFIES SQL DATA
BEGIN
  DECLARE curr_runid INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SELECT     runID
  INTO       curr_runid
  FROM       autoflow
  WHERE      expID = p_expID AND optimaName = p_optima;


  IF ( verify_user( p_personGUID, p_password ) = @OK ) THEN
    IF ( curr_runid IS NULL ) THEN
      UPDATE   autoflow
      SET      runID = p_runid, runStarted = NOW()
      WHERE    expID = p_expID AND optimaName = p_optima;

    END IF;

  END IF;

 

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_buffer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_buffer`( p_personGUID      CHAR(36),
                                 p_password        VARCHAR(80),
                                 p_bufferID        INT,
                                 p_description     TEXT,
                                 p_compressibility FLOAT,
                                 p_pH              FLOAT,
                                 p_density         FLOAT,
                                 p_viscosity       FLOAT,
                                 p_manual          TINYINT,
                                 p_private         TINYINT )
    MODIFIES SQL DATA
BEGIN
  DECLARE not_found     TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_buffer_permission( p_personGUID, p_password, p_bufferID ) = @OK ) THEN
    UPDATE buffer SET
      description     = p_description,
      compressibility = p_compressibility,
      pH              = p_pH,
      density         = p_density,
      viscosity       = p_viscosity,
      manual          = p_manual
    WHERE bufferID    = p_bufferID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_BUFFER;
      SET @US3_LAST_ERROR = "MySQL: No buffer with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

      UPDATE bufferPerson SET
        private       = p_private
      WHERE bufferID  = p_bufferID;

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_editedData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_editedData`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_editedDataID INT,
                                     p_rawDataID    INT,
                                     p_editGUID     CHAR(36),
                                     p_label        VARCHAR(80),
                                     p_filename     VARCHAR(255),
                                     p_comment      TEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE not_found      TINYINT DEFAULT 0;
  DECLARE null_field     TINYINT DEFAULT 0;
  DECLARE l_experimentID INT;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SELECT     experimentID
  INTO       l_experimentID
  FROM       rawData
  WHERE      rawDataID = p_rawDataID;

  IF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
 
    
    UPDATE editedData SET
      rawDataID     = p_rawDataID,
      editGUID      = p_editGUID,
      label         = p_label,
      filename      = p_filename,
      comment       = p_comment,
      lastUpdated   = NOW()
    WHERE editedDataID = p_editedDataID;
   
    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_EDITDATA;
      SET @US3_LAST_ERROR = "MySQL: No edit profile with that ID exists";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to update with a NULL value in the editedData table";

    END IF;
   
   END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_eprofile` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_eprofile`( p_personGUID       CHAR(36),
                                  p_password         VARCHAR(80),
                                  p_profileID        INT,
                                  p_componentID      INT,
                                  p_componentType    enum( 'Buffer', 'Analyte', 'Sample' ),
                                  p_valueType        enum( 'absorbance', 'molarExtinction', 'massExtinction' ),
                                  p_xml              LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_componentID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) = @OK ) THEN
    UPDATE extinctionProfile SET
      componentID      = p_componentID,
      componentType    = p_componentType,
      valueType        = p_valueType,
      xml              = p_xml
      WHERE profileID  = p_profileID;
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_experiment` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_experiment`( p_personGUID   CHAR(36),
                                     p_password     VARCHAR(80),
                                     p_experimentID INT,
                                     p_expGUID      CHAR(36),
                                     p_projectID    INT,
                                     p_runID        VARCHAR(255),
                                     p_labID        INT,
                                     p_instrumentID INT,
                                     p_operatorID   INT,
                                     p_rotorID      INT,
                                     p_calibrationID INT,
                                     p_type         VARCHAR(30),
                                     p_runType      ENUM( 'RA', 'RI', 'IP', 'FI', 'WA', 'WI' ),
                                     p_RIProfile    LONGTEXT,
                                     p_runTemp      FLOAT,
                                     p_label        VARCHAR(80),
                                     p_comment      TEXT,
                                     p_centrifugeProtocol TEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_count_runID  INT;
  DECLARE l_expGUID      CHAR(36);
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) &&
       ( verify_operator_permission( p_personGUID, p_password, 
          p_labID, p_instrumentID, p_operatorID ) = @OK )                                 &&
       ( check_GUID( p_personGUID, p_password, p_expGUID ) = @OK ) ) THEN
    
    
    SELECT COUNT(*)
    INTO   l_count_runID
    FROM   experimentPerson p, experiment e
    WHERE  p.experimentID = e.experimentID
    AND    e.runID = p_runID
    AND    e.experimentID != p_experimentID
    AND    p.personID = @US3_ID;

    IF ( l_count_runID > 0 ) THEN
      SET @US3_LAST_ERRNO = @DUPFIELD;
      SET @US3_LAST_ERROR = "MySQL: Duplicate runID in experiment table";

    ELSE
      
      SELECT experimentGUID
      INTO   l_expGUID
      FROM   experiment
      WHERE  experimentID = p_experimentID;

      IF ( p_runType != 'RI' ) THEN
        SET p_RIProfile = '';
      END IF;

      IF ( STRCMP( l_expGUID, p_expGUID ) = 0 ) THEN
        UPDATE experiment SET
          
          projectID          = p_projectID,
          runID              = p_runID,
          labID              = p_labID,
          instrumentID       = p_instrumentID,
          operatorID         = p_operatorID,
          rotorID            = p_rotorID,
          rotorCalibrationID = p_calibrationID,
          type               = p_type,
          runType            = p_runType,
          RIProfile          = p_RIProfile,
          runTemp            = p_runTemp,
          label              = p_label,
          comment            = p_comment,
          centrifugeProtocol = p_centrifugeProtocol,
          dateUpdated        = NOW()
        WHERE experimentID   = p_experimentID;

      ELSE
        UPDATE experiment SET
          experimentGUID     = p_expGUID,
          projectID          = p_projectID,
          runID              = p_runID,
          labID              = p_labID,
          instrumentID       = p_instrumentID,
          operatorID         = p_operatorID,
          rotorID            = p_rotorID,
          rotorCalibrationID = p_calibrationID,
          type               = p_type,
          runType            = p_runType,
          RIProfile          = p_RIProfile,
          runTemp            = p_runTemp,
          label              = p_label,
          comment            = p_comment,
          centrifugeProtocol = p_centrifugeProtocol,
          dateUpdated        = NOW()
        WHERE experimentID   = p_experimentID;

        IF ( duplicate_key = 1 ) THEN
          SET @US3_LAST_ERRNO = @INSERTDUP;
          SET @US3_LAST_ERROR = "MySQL: Duplicate entry for experimentGUID field";
    
        END IF;

      END IF;

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_instrument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_instrument`( p_personGUID    CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_instrumentID  INT,
                                     p_radialCalID   INT(11) )
    MODIFIES SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    UPDATE instrument SET radialCalID = p_radialCalID
    WHERE instrumentID = p_instrumentID;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_instrument_new` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_instrument_new`( p_personGUID    CHAR(36),
                                       p_password      VARCHAR(80),
                                       p_instrumentID  INT,
                                       p_name          TEXT,
                                       p_serialNumber  TEXT,
                                       p_labID         INT,
                                       p_host          TEXT,
                                       p_port          INT,
                                       p_optimadbname  TEXT,
                                       p_optimadbuser  TEXT,
                                       p_optimadbpassw  VARCHAR(100),
				       p_opsys1        TEXT,
				       p_opsys2        TEXT,
				       p_opsys3        TEXT,
				       p_radcalwvl     INT,
				       p_chromoab      TEXT,
				       p_msgport       INT  )
    MODIFIES SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    UPDATE instrument SET 
      name              = p_name,
      serialNumber      = p_serialNumber,
      labID             = p_labID,
      optimaHost        = p_host,
      optimaPort        = p_port,
      optimaDBname      = p_optimadbname,
      optimaDBusername  = p_optimadbuser,
      optimaDBpassw     = ENCODE( p_optimadbpassw, 'secretOptimaDB' ),
      opsys1            = p_opsys1,
      opsys2            = p_opsys2,
      opsys3            = p_opsys3,	
      RadCalWvl         = p_radcalwvl,
      chromaticAB       = p_chromoab,
      dateUpdated       = NOW(),
      optimaPortMsg     = p_msgport 
    WHERE instrumentID = p_instrumentID;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_instrument_set_selected` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_instrument_set_selected`( p_personGUID    CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_name          TEXT )
    MODIFIES SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    UPDATE instrument SET selected = 1 
    WHERE name = p_name;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_instrument_set_unselected` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_instrument_set_unselected`( p_personGUID    CHAR(36),
                                     p_password      VARCHAR(80),
                                     p_name          TEXT )
    MODIFIES SQL DATA
BEGIN

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK ) THEN

    UPDATE instrument SET selected = 0  
    WHERE name = p_name;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_model` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_model`( p_personGUID  CHAR(36),
                                p_password    VARCHAR(80),
                                p_modelID     INT,
                                p_description TEXT,
                                p_xml         LONGTEXT,
                                p_variance    DOUBLE,
                                p_meniscus    DOUBLE,
                                p_editGUID    CHAR(36) )
    MODIFIES SQL DATA
BEGIN
  DECLARE not_found     TINYINT DEFAULT 0;
  DECLARE l_count_editID INT;
  DECLARE l_editID       INT;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  
  SET l_editID = 1;         

  SELECT COUNT(*)           
  INTO   l_count_editID
  FROM   model
  WHERE  modelID = p_modelID;

  IF ( l_count_editID > 0 ) THEN
    SELECT editedDataID     
    INTO   l_editID
    FROM   model
    WHERE  modelID = p_modelID;

  END IF;

  SELECT COUNT(*)           
  INTO   l_count_editID
  FROM   editedData
  WHERE  editGUID = p_editGUID;

  IF ( l_count_editID > 0 ) THEN
    SELECT editedDataID
    INTO   l_editID
    FROM   editedData
    WHERE  editGUID = p_editGUID
    LIMIT  1;

  END IF;

  IF ( verify_model_permission( p_personGUID, p_password, p_modelID ) = @OK ) THEN
    UPDATE model SET
      editedDataID = l_editID,
      description  = p_description,
      xml          = p_xml,
      variance     = p_variance,
      meniscus     = p_meniscus,
      lastUpdated  = NOW()
    WHERE modelID  = p_modelID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_MODEL;
      SET @US3_LAST_ERROR = "MySQL: No model with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_mrecs` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_mrecs`( p_personGUID    CHAR(36),
                                p_password      VARCHAR(80),
                                p_mrecsID       INT(11),
                                p_mrecsGUID     CHAR(36),
                                p_editedDataID  INT(11),
                                p_modelID       INT(11),
                                p_description   TEXT,
                                p_xml           LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_editID          INT;
  DECLARE l_editID_count    INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SET l_editID = 1;         
  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    SET l_editID = p_editedDataID;

  END IF;

  SELECT COUNT(*)
  INTO   l_editID_count 
  FROM   editedData
  WHERE  editedDataID = p_editedDataID
  LIMIT  1;

  IF ( verify_editData_permission( p_personGUID, p_password, l_editID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_editID_count = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the editedData was not found in the database";
    SET @US3_LAST_ERRNO = @NO_EDITDATA;

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( check_GUID ( p_personGUID, p_password, p_mrecsGUID ) = @OK ) THEN
    UPDATE pcsa_modelrecs SET
      mrecsGUID    = p_mrecsGUID,
      editedDataID = l_editID,
      modelID      = p_modelID,
      description  = p_description,
      xml          = p_xml,
      lastUpdated  = NOW()
    WHERE mrecsID  = p_mrecsID;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for mrecsGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for mrecsGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_noise` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_noise`( p_personGUID    CHAR(36),
                                p_password      VARCHAR(80),
                                p_noiseID       INT(11),
                                p_noiseGUID     CHAR(36),
                                p_editedDataID  INT(11),
                                p_modelID       INT(11),
                                p_modelGUID     CHAR(36),
                                p_noiseType     ENUM( 'ri_noise', 'ti_noise' ),
                                p_description   TEXT,
                                p_xml           LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_editID          INT;
  DECLARE l_modelID         INT;
  DECLARE l_modelID_count   INT;
  DECLARE l_modelGUID       CHAR(36);
  DECLARE l_modelGUID_count INT;

  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;
 
  
  SET l_editID = 1;         
  IF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
    SET l_editID = p_editedDataID;

  END IF;

  
  SELECT COUNT(*)
  INTO   l_modelGUID_count
  FROM   model
  WHERE  modelGUID = p_modelGUID
  LIMIT  1;                         

  SELECT COUNT(*)
  INTO   l_modelID_count
  FROM   model
  WHERE  modelID = p_modelID
  LIMIT  1;                         

  IF ( l_modelGUID_count = 1 ) THEN 
    SET l_modelGUID = p_modelGUID;

    SELECT modelID
    INTO   l_modelID
    FROM   model
    WHERE  modelGUID = p_modelGUID
    LIMIT  1;

  ELSEIF ( l_modelID_count = 1 ) THEN
    SET l_modelID = p_modelID;

    SELECT modelGUID
    INTO   l_modelGUID
    FROM   model
    WHERE  modelID = p_modelID;

  ELSE
    
    SET l_modelID   = 0;
    SET l_modelGUID = p_modelGUID;

  END IF;

  IF ( verify_model_permission( p_personGUID, p_password, l_modelID ) != @OK ) THEN
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( l_modelGUID_count = 0 && l_modelID_count = 0 ) THEN
    SET @US3_LAST_ERROR = "MySQL: the model was not found in the database";
    SET @US3_LAST_ERRNO = @NO_MODEL;

    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( check_GUID ( p_personGUID, p_password, p_noiseGUID ) = @OK ) THEN
    UPDATE noise SET
      noiseGUID    = p_noiseGUID,
      editedDataID = l_editID,
      modelID      = l_modelID,
      modelGUID    = l_modelGUID,
      noiseType    = p_noiseType,
      description  = p_description,
      xml          = p_xml,
      timeEntered  = NOW()
    WHERE noiseID  = p_noiseID;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for noiseGUID field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for noiseGUID field";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_person` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_person`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_ID           INT,
                                 p_fname        VARCHAR(30),
                                 p_lname        VARCHAR(30),
                                 p_address      VARCHAR(255),
                                 p_city         VARCHAR(30),
                                 p_state        CHAR(2),
                                 p_zip          VARCHAR(10),
                                 p_phone        VARCHAR(24),
                                 p_new_email    VARCHAR(63),
                                 p_organization VARCHAR(45),
                                 p_new_password VARCHAR(80) )
    MODIFIES SQL DATA
BEGIN
  DECLARE duplicate_key TINYINT DEFAULT 0;
  DECLARE null_field    TINYINT DEFAULT 0;
  DECLARE not_found     TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
  SET @LAST_INSERT_ID = 0;

  
  IF ( ( ( verify_user( p_personGUID, p_password ) = @OK ) &&
         ( p_ID = @US3_ID                                )              ) ||
       ( verify_userlevel( p_personGUID, p_password, @US3_ADMIN ) = @OK )  ) THEN

    UPDATE people SET
           fname        = p_fname,
           lname        = p_lname,
           address      = p_address,
           city         = p_city,
           state        = p_state,
           zip          = p_zip,
           phone        = p_phone,
           email        = p_new_email,
           organization = p_organization,
           password     = MD5(p_new_password),
           activated    = true,
           lastLogin    = NOW()
    WHERE  personID     = p_ID;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for email field";

    ELSEIF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: NULL value for email field is not allowed";

    ELSEIF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_PERSON;
      SET @US3_LAST_ERROR = "MySQL: No person with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_project` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_project`( p_personGUID       CHAR(36),
                                  p_password         VARCHAR(80),
                                  p_projectID        INT,
                                  p_projectGUID      CHAR(36),
                                  p_goals            TEXT,
                                  p_molecules        TEXT,
                                  p_purity           TEXT,
                                  p_expense          TEXT,
                                  p_bufferComponents TEXT, 
                                  p_saltInformation  TEXT,
                                  p_AUC_questions    TEXT,
                                  p_notes            TEXT,
                                  p_description      TEXT,
                                  p_status           ENUM('submitted', 'designed', 
                                                          'scheduled', 'uploaded', 
                                                          'anlyzed',   'invoiced', 
                                                          'paid',      'other') )
    MODIFIES SQL DATA
BEGIN                             
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;
                                  
  CALL config();                  
  SET @US3_LAST_ERRNO = @OK;      
  SET @US3_LAST_ERROR = '';

  IF ( ( verify_project_permission( p_personGUID, p_password, p_projectID ) = @OK ) &&
       ( check_GUID( p_personGUID, p_password, p_projectGUID )              = @OK ) ) THEN
    UPDATE project SET
           projectGUID      = p_projectGUID,
           goals            = p_goals,
           molecules        = p_molecules,
           purity           = p_purity,
           expense          = p_expense,
           bufferComponents = p_bufferComponents,
           saltInformation  = p_saltInformation,
           AUC_questions    = p_AUC_questions,
           notes            = p_notes,
           description      = p_description,
           status           = p_status 
    WHERE  projectID        = p_projectID;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for projectGUID field";

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_project2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_project2`( p_personGUID       CHAR(36),
                                  p_password         VARCHAR(80),
                                  p_projectID        INT,
                                  p_projectGUID      CHAR(36),
                                  p_goals            TEXT,
                                  p_molecules        TEXT,
                                  p_purity           TEXT,
                                  p_expense          TEXT,
                                  p_bufferComponents TEXT, 
                                  p_saltInformation  TEXT,
                                  p_AUC_questions    TEXT,
                                  p_expDesign        TEXT,
                                  p_notes            TEXT,
                                  p_description      TEXT,
                                  p_status           ENUM('submitted', 'designed', 
                                                          'scheduled', 'uploaded', 
                                                          'anlyzed',   'invoiced', 
                                                          'paid',      'other') )
    MODIFIES SQL DATA
BEGIN                             
  DECLARE duplicate_key  TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1062
    SET duplicate_key = 1;
                                  
  CALL config();                  
  SET @US3_LAST_ERRNO = @OK;      
  SET @US3_LAST_ERROR = '';

  IF ( ( verify_project_permission( p_personGUID, p_password, p_projectID ) = @OK ) &&
       ( check_GUID( p_personGUID, p_password, p_projectGUID )              = @OK ) ) THEN
    UPDATE project SET
           projectGUID      = p_projectGUID,
           goals            = p_goals,
           molecules        = p_molecules,
           purity           = p_purity,
           expense          = p_expense,
           bufferComponents = p_bufferComponents,
           saltInformation  = p_saltInformation,
           AUC_questions    = p_AUC_questions,
           expDesign        = p_expDesign,
           notes            = p_notes,
           description      = p_description,
           status           = p_status 
    WHERE  projectID        = p_projectID;

    IF ( duplicate_key = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTDUP;
      SET @US3_LAST_ERROR = "MySQL: Duplicate entry for projectGUID field";

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_report` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_report`( p_personGUID   CHAR(36),
                                 p_password     VARCHAR(80),
                                 p_reportID     INT,
                                 p_title        VARCHAR(255),
                                 p_html         LONGTEXT )
    MODIFIES SQL DATA
BEGIN
  DECLARE not_found     TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  IF ( verify_report_permission( p_personGUID, p_password, p_reportID ) = @OK ) THEN
    UPDATE report SET
      title       = p_title,
      html        = p_html
    WHERE reportID  = p_reportID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_REPORT;
      SET @US3_LAST_ERROR = "MySQL: No report with that ID exists";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;
      
  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_reportDocument` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_reportDocument`( p_personGUID          CHAR(36),
                                         p_password            VARCHAR(80),
                                         p_reportDocumentID    INT,
                                         p_editedDataID        INT,
                                         p_label               VARCHAR(160),
                                         p_filename            VARCHAR(255),
                                         p_analysis            VARCHAR(20),
                                         p_subAnalysis         VARCHAR(20),
                                         p_documentType        VARCHAR(20) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_reportID    INT;
  DECLARE l_countLinks  INT;

  DECLARE constraint_failed TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1452
    SET constraint_failed = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SET @LAST_INSERT_ID = 0;
 
  SELECT COUNT(*)
  INTO   l_countLinks
  FROM   documentLink
  WHERE  reportDocumentID = p_reportDocumentID;
    
  SELECT reportID
  INTO   l_reportID
  FROM   documentLink, reportTriple
  WHERE  documentLink.reportDocumentID = p_reportDocumentID
  AND    documentLink.reportTripleID = reportTriple.reportTripleID;

  IF ( l_countLinks < 1 ) THEN
    SET @US3_LAST_ERRNO = @NO_DOCUMENT_LINK;
    SET @US3_LAST_ERROR = CONCAT('MySQL: The link between the triple and the document is missing; ',
                                 'Report document ID = ', p_reportDocumentID, '; ',
                                 'Link count = ', l_countLinks );
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
    
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to edit this report';
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSEIF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) != @OK ) THEN
    
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to use that edit profile';
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    UPDATE reportDocument SET
      editedDataID         = p_editedDataID,
      label                = p_label,
      filename             = p_filename,
      analysis             = p_analysis,
      subAnalysis          = p_subAnalysis,
      documentType         = p_documentType
    WHERE reportDocumentID = p_reportDocumentID;

    IF ( constraint_failed = 1 ) THEN
      SET @US3_LAST_ERRNO = @CONSTRAINT_FAILED;
      SET @US3_LAST_ERROR = "MySQL: FK Constraint failed while updating reportDocument";

    ELSE
      SET @US3_LAST_ERRNO = @OK;
      SET @US3_LAST_ERROR = '';

      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_reportTriple` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_reportTriple`( p_personGUID        CHAR(36),
                                       p_password          VARCHAR(80),
                                       p_reportTripleID    INT,
                                       p_resultID          INT,
                                       p_triple            VARCHAR(20),
                                       p_dataDescription   VARCHAR(255) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_reportID    INT;
  DECLARE not_found     TINYINT DEFAULT 0;
  DECLARE constraint_failed TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  DECLARE CONTINUE HANDLER FOR 1452
    SET constraint_failed = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';

  SET @LAST_INSERT_ID = 0;

  
  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple
  WHERE  reportTripleID = p_reportTripleID;

  IF ( verify_report_permission( p_personGUID, p_password, l_reportID ) != @OK ) THEN
    
    SET @US3_LAST_ERRNO = @NOTPERMITTED;
    SET @US3_LAST_ERROR = 'MySQL: you do not have permission to edit this report';
    
    SELECT @US3_LAST_ERRNO AS status;

  ELSE
    
    SET p_dataDescription = TRIM( p_dataDescription );

    IF ( LENGTH( p_dataDescription ) < 1 ) THEN
      UPDATE reportTriple SET
        resultID          = p_resultID,
        triple            = p_triple
      WHERE reportTripleID = p_reportTripleID;

    ELSE
      UPDATE reportTriple SET
        resultID          = p_resultID,
        triple            = p_triple,
        dataDescription   = p_dataDescription 
      WHERE reportTripleID = p_reportTripleID;
    END IF;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_REPORT_TRIPLE;
      SET @US3_LAST_ERROR = "MySQL: No report triple record with that ID exists";

    ELSEIF ( constraint_failed = 1 ) THEN
      SET @US3_LAST_ERRNO = @CONSTRAINT_FAILED;
      SET @US3_LAST_ERROR = "MySQL: FK Constraint failed while updating reportTriple";

    ELSE
      SET @LAST_INSERT_ID = LAST_INSERT_ID();

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_solution` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_solution`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_solutionID   INT,
                                   p_solutionGUID CHAR(36),
                                   p_description  VARCHAR(80),
                                   p_commonVbar20 DOUBLE,
                                   p_storageTemp  FLOAT,
                                   p_notes        TEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  IF ( ( verify_solution_permission( p_personGUID, p_password, p_solutionID ) = @OK ) &&
       ( check_GUID( p_personGUID, p_password, p_solutionGUID ) = @OK               ) ) THEN
 
    
    UPDATE solution SET
      solutionGUID   = p_solutionGUID,    
      description    = p_description,
      commonVbar20   = p_commonVbar20,
      storageTemp    = p_storageTemp,
      notes          = p_notes
    WHERE solutionID = p_solutionID;
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the solution table";

    END IF;
   
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_spectrum` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_spectrum`( p_personGUID       CHAR(36),
                                  p_password         VARCHAR(80),
                                  p_spectrumID       INT,
                                  p_componentID      INT,
                                  p_componentType    enum( 'Buffer', 'Analyte' ),
                                  p_opticsType       enum( 'Extinction', 'Refraction', 'Fluorescence' ),
                                  p_lambda           FLOAT,
                                  p_molarCoefficient FLOAT )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_componentID INT;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  IF ( verify_componentID( p_personGUID, p_password, p_componentID, p_componentType ) = @OK ) THEN
    UPDATE spectrum SET
      componentID      = p_componentID,
      componentType    = p_componentType,
      opticsType       = p_opticsType,
      lambda           = p_lambda,
      molarCoefficient = p_molarCoefficient
    WHERE spectrumID   = p_spectrumID;
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_timestate`( p_personGUID   CHAR(36),
                                    p_password     VARCHAR(80),
                                    p_timestateID  INT,
                                    p_experimentID INT,
                                    p_filename     VARCHAR(255),
                                    p_definitions  LONGTEXT )
    MODIFIES SQL DATA
BEGIN

  DECLARE null_field    TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR 1048
    SET null_field = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  IF ( verify_experiment_permission( p_personGUID, p_password, p_experimentID ) = @OK ) THEN
 
    
    UPDATE timestate SET
      experimentID   = p_experimentID,
      filename       = p_filename,
      definitions    = p_definitions,
      lastUpdated    = NOW()
    WHERE timestateID = p_timestateID;
   
    IF ( null_field = 1 ) THEN
      SET @US3_LAST_ERRNO = @INSERTNULL;
      SET @US3_LAST_ERROR = "MySQL: Attempt to insert NULL value in the timestate table";

    END IF;
   
  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `upload_aucData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `upload_aucData`( p_personGUID   CHAR(36),
                                  p_password     VARCHAR(80),
                                  p_rawDataID    INT,
                                  p_data         LONGBLOB,
                                  p_checksum     CHAR(33) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_checksum     CHAR(33);
  DECLARE l_experimentID INT;
  DECLARE not_found      TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SET l_checksum = MD5( p_data );
  SET @DEBUG = CONCAT( l_checksum , ' ', p_checksum );

  
  SELECT experimentID
  INTO   l_experimentID
  FROM   rawData
  WHERE  rawDataID = p_rawDataID;

  IF ( l_checksum != p_checksum ) THEN

    
    SET @US3_LAST_ERRNO = @BAD_CHECKSUM;
    SET @US3_LAST_ERROR = "MySQL: Transmission error, bad checksum";

  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
 
    
    UPDATE rawData SET
      data           = p_data
    WHERE  rawDataID = p_rawDataID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_RAWDATA;
      SET @US3_LAST_ERROR = "MySQL: No raw data with that ID exists";

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `upload_editData` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `upload_editData`( p_personGUID   CHAR(36),
                                   p_password     VARCHAR(80),
                                   p_editedDataID INT,
                                   p_data         LONGBLOB,
                                   p_checksum     CHAR(33) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_checksum     CHAR(33);
  DECLARE l_experimentID INT;
  DECLARE not_found      TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SET l_checksum = MD5( p_data );
  SET @DEBUG = CONCAT( l_checksum , ' ', p_checksum );

  IF ( l_checksum != p_checksum ) THEN

    
    SET @US3_LAST_ERRNO = @BAD_CHECKSUM;
    SET @US3_LAST_ERROR = "MySQL: Transmission error, bad checksum";

  ELSEIF ( verify_editData_permission( p_personGUID, p_password, p_editedDataID ) = @OK ) THEN
 
    
    UPDATE editedData SET
           data         = p_data
    WHERE  editedDataID = p_editedDataID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_EDITDATA;
      SET @US3_LAST_ERROR = "MySQL: No edit profile with that ID exists";

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `upload_reportContents` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `upload_reportContents`( p_personGUID       CHAR(36),
                                         p_password         VARCHAR(80),
                                         p_reportDocumentID INT,
                                         p_contents         LONGBLOB,
                                         p_checksum         CHAR(33) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_checksum     CHAR(33);
  DECLARE l_reportID     INT;
  DECLARE not_found      TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SET l_checksum = MD5( p_contents );
  SET @DEBUG = CONCAT( l_checksum , ' ', p_checksum );

  
  SELECT reportID
  INTO   l_reportID
  FROM   reportTriple, documentLink
  WHERE  reportDocumentID = p_reportDocumentID
  AND    reportTriple.reportTripleID = documentLink.reportTripleID;

  IF ( l_checksum != p_checksum ) THEN

    
    SET @US3_LAST_ERRNO = @BAD_CHECKSUM;
    SET @US3_LAST_ERROR = "MySQL: Transmission error, bad checksum";

  ELSEIF ( verify_report_permission( p_personGUID, p_password, l_reportID ) = @OK ) THEN
 
    
    UPDATE reportDocument SET
           contents  = p_contents
    WHERE  reportDocumentID = p_reportDocumentID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_REPORT_DOCUMENT;
      SET @US3_LAST_ERROR = "MySQL: No report document with that ID exists";

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `upload_timestate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `upload_timestate`( p_personGUID   CHAR(36),
                                    p_password     VARCHAR(80),
                                    p_timestateID  INT,
                                    p_data         LONGBLOB,
                                    p_checksum     CHAR(33) )
    MODIFIES SQL DATA
BEGIN
  DECLARE l_checksum     CHAR(33);
  DECLARE l_experimentID INT;
  DECLARE not_found      TINYINT DEFAULT 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND
    SET not_found = 1;

  CALL config();
  SET @US3_LAST_ERRNO = @OK;
  SET @US3_LAST_ERROR = '';
 
  
  SET l_checksum = MD5( p_data );
  SET @DEBUG = CONCAT( l_checksum , ' ', p_checksum );

  
  SELECT experimentID
  INTO   l_experimentID
  FROM   timestate
  WHERE  timestateID = p_timestateID;

  IF ( l_checksum != p_checksum ) THEN

    
    SET @US3_LAST_ERRNO = @BAD_CHECKSUM;
    SET @US3_LAST_ERROR = "MySQL: Transmission error, bad checksum";

  ELSEIF ( verify_experiment_permission( p_personGUID, p_password, l_experimentID ) = @OK ) THEN
 
    
    UPDATE timestate SET
      data           = p_data
    WHERE  timestateID = p_timestateID;

    IF ( not_found = 1 ) THEN
      SET @US3_LAST_ERRNO = @NO_RAWDATA;
      SET @US3_LAST_ERROR = "MySQL: No raw data with that ID exists";

    END IF;

  END IF;

  SELECT @US3_LAST_ERRNO AS status;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `validate_user` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `validate_user`( p_personGUID CHAR(36),
                                p_email      VARCHAR(63),
                                p_password   VARCHAR(80) )
    READS SQL DATA
BEGIN
  DECLARE status       INT;

  call config();

  
  SET status = check_user( p_personGUID, p_password );
  IF ( @US3_ID IS NOT NULL ) THEN
    
    SELECT @OK AS status;
    SELECT @personGUID AS personGUID, @EMAIL AS email;

  ELSEIF ( TRIM( p_email ) = '' ) THEN
    
    SELECT @US3_LAST_ERRNO AS status;
    SELECT p_personGUID AS personGUID, p_email AS email;

  ELSE
    
    SET status = check_user_email( p_email, p_password );
    IF ( @US3_ID IS NOT NULL ) THEN
      
      SELECT @OK AS status;
      SELECT @personGUID AS personGUID, @EMAIL AS email;

    ELSE
      
      SELECT @US3_LAST_ERRNO AS status;
      SELECT p_personGUID AS personGUID, p_email AS email;

    END IF;

  END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

