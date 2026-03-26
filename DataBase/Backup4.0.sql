-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql103.infinityfree.com
-- Generation Time: Mar 25, 2026 at 12:17 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41395736_pc_builder`
--

-- --------------------------------------------------------

--
-- Table structure for table `builds`
--

CREATE TABLE `builds` (
  `build_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `build_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `builds`
--

INSERT INTO `builds` (`build_id`, `user_id`, `build_name`, `created_at`) VALUES
(2, 1, 'Max Price Range', '2026-03-16 02:34:39'),
(3, 2, 'Fggf', '2026-03-20 01:45:44'),
(4, 3, 'new 1', '2026-03-20 09:35:51');

-- --------------------------------------------------------

--
-- Table structure for table `build_components`
--

CREATE TABLE `build_components` (
  `build_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `slot_type` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `build_components`
--

INSERT INTO `build_components` (`build_id`, `component_id`, `slot_type`) VALUES
(2, 1, 'cpu'),
(2, 3, 'motherboard'),
(2, 16, 'gpu'),
(2, 69, 'ram'),
(2, 102, 'powersupply'),
(2, 114, 'storage'),
(2, 139, 'case'),
(3, 1, 'cpu'),
(3, 5, 'ram'),
(3, 12, 'motherboard'),
(3, 15, 'case'),
(3, 16, 'gpu'),
(3, 102, 'powersupply'),
(3, 109, 'storage'),
(4, 16, 'gpu'),
(4, 28, 'cpu');

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `component_id` int(11) NOT NULL,
  `Form_Factor` enum('ATX','Micro-ATX','Mini-ITX') DEFAULT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `Max_GPU_Length` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`component_id`, `Form_Factor`, `Color`, `Max_GPU_Length`) VALUES
(9, 'ATX', 'White', 420),
(10, 'ATX', 'Black', 360),
(15, 'ATX', 'Black', 360),
(23, 'Micro-ATX', 'White', 315),
(125, 'Micro-ATX', 'Black', 360),
(126, 'ATX', 'Black', 360),
(127, 'ATX', 'Black', 315),
(128, 'ATX', 'Black', 420),
(129, 'ATX', 'Black', 369),
(130, 'ATX', 'Black', 420),
(131, 'ATX', 'Black', 410),
(132, 'ATX', 'Black', 380),
(133, 'ATX', 'Black', 400),
(134, 'Micro-ATX', 'Black', 330),
(135, 'Micro-ATX', 'Black', 320),
(136, 'ATX', 'Black', 360),
(137, 'ATX', 'White', 395),
(138, 'ATX', 'White', 341),
(139, 'ATX', 'Black', 435),
(140, 'ATX', 'Black', 430);

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `component_id` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Brand` varchar(100) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `components`
--

INSERT INTO `components` (`component_id`, `Name`, `Brand`, `Type`, `Price`, `stock_quantity`, `image_url`) VALUES
(1, 'Core i9-14900K', 'Intel', 'CPU', '76500.00', 14, 'https://www.intel.com/content/dam/www/central-libraries/us/en/images/processors/core/core-i9-14900k-pdp-hero.png.rendition.intel.web.480.270.png'),
(2, 'Ryzen 7 7800X3D', 'AMD', 'CPU', '54000.00', 25, 'https://www.amd.com/system/files/2023-01/1259841-amd-ryzen-7800x3d-pib-left-facing-1260x709.png'),
(3, 'ROG Maximus Z790 Hero', 'ASUS', 'Motherboard', '82000.00', 5, 'https://dlcdnwebimgs.asus.com/gain/E8FBD2DF-6D9D-4A3F-A4A9-9B0A37E0DB9C/'),
(4, 'MAG B650 TOMAHAWK WIFI', 'MSI', 'Motherboard', '30000.00', 20, 'https://asset.msi.com/resize/image/global/product/product_1668408247bb12d1a5c1eba7b3d3c4cdf5e1ae2c53.png62405b38d601f2ce8c7316831b3f81e6/1024.png'),
(5, 'Corsair Vengeance 32GB (2x16GB) DDR5', 'Corsair', 'RAM', '19500.00', 30, 'https://www.corsair.com/medias/sys_master/images/images/hd7/h47/9617310269470/CMK32GX5M2B5600C36-Gallery-CMK32GX5M2B5600C36-01.png'),
(6, 'Kingston FURY Beast 16GB DDR4', 'Kingston', 'RAM', '7000.00', 50, 'https://media.kingston.com/kingston/product/ktc-product-flash-kf432c16bb-16-1-zm.jpg'),
(7, 'Corsair RM1000x 1000W 80+ Gold', 'Corsair', 'Power Supply', '24000.00', 15, 'https://www.corsair.com/medias/sys_master/images/images/hbf/hce/9617383186462/CP-9020203-NA-Gallery-RM1000x-SHIFT-01.png'),
(8, 'Cooler Master MWE 650W 80+ Bronze', 'Cooler Master', 'Power Supply', '8000.00', 40, 'https://www.coolermaster.com/uploads/2022/07/MPE-6501-AFAAG-photo_1024.png'),
(9, 'Lian Li PC-O11 Dynamic', 'Lian Li', 'Case', '20000.00', 15, 'https://www.lian-li.com/wp-content/uploads/2020/09/O11DXLSIDE.png'),
(10, 'NZXT H510 Flow', 'NZXT', 'Case', '13000.00', 20, 'https://nzxt.com/assets/cms/34299/1612453641-h510-flow-black-front.png'),
(11, 'Core i5-13600K', 'Intel', 'CPU', '36500.00', 30, 'https://www.intel.com/content/dam/www/central-libraries/us/en/images/processors/core/core-i5-boxed-processor-pdp-hero.png.rendition.intel.web.480.270.png'),
(12, 'B760 AORUS ELITE AX', 'Gigabyte', 'Motherboard', '23500.00', 15, 'https://www.gigabyte.com/FileUpload/Global/KeyFeature/3330/images/kv.png'),
(13, 'Trident Z5 RGB 32GB', 'G.Skill', 'RAM', '20000.00', 25, 'https://www.gskill.com/img/products/ram/Trident-Z5-RGB/F5-6000J3040G16GX2-TZ5RK/F5-6000J3040G16GX2-TZ5RK-1.jpg'),
(14, 'EVGA SuperNOVA 850W', 'EVGA', 'Power Supply', '17500.00', 20, 'https://images.evga.com/products/gallery/lg/220-G6-0850-X1_LG_1.png'),
(15, 'Corsair 4000D Airflow', 'Corsair', 'Case', '14000.00', 40, 'https://www.corsair.com/medias/sys_master/images/images/h37/h22/9617368178718/CC-9011200-WW-Gallery-4000D-AIRFLOW-Tempered-Glass-Mid-Tower-ATX-PC-Case-01.png'),
(16, 'GeForce RTX 4090', 'NVIDIA', 'GPU', '195000.00', 3, 'https://www.nvidia.com/content/dam/en-zz/Solutions/geforce/ada/rtx-4090/geforce-ada-4090-web-oc-1200x680.jpg'),
(17, 'GeForce RTX 4070 Super', 'NVIDIA', 'GPU', '80000.00', 12, 'https://www.nvidia.com/content/dam/en-zz/Solutions/geforce/ada/rtx-4070-super/geforce-rtx-4070-super-web-1200x680.jpg'),
(18, 'Radeon RX 7900 XTX', 'AMD', 'GPU', '120000.00', 8, 'https://www.amd.com/system/files/2023-01/1262902-amd-radeon-rx-7900-xtx-pib-left-facing-1260x709.png'),
(19, 'Samsung 990 Pro 1TB NVMe', 'Samsung', 'Storage', '14000.00', 30, 'https://image-us.samsung.com/SamsungUS/home/computing/memory-storage/all-ssds/06222022/990-pro/Samsung_990_PRO_NVMe_SSD_Product_Image.jpg'),
(20, 'WD Black SN850X 2TB NVMe', 'Western Digital', 'Storage', '24000.00', 20, 'https://shop.westerndigital.com/content/dam/store/en-us/assets/products/internal-storage/wd-black-sn850x-nvme-ssd/gallery/wd-black-sn850x-nvme-ssd-front.png.thumb.1280.1280.png'),
(21, 'Seagate Barracuda 2TB HDD', 'Seagate', 'Storage', '5000.00', 50, 'https://www.seagate.com/content/dam/seagate/migrated-assets/www-content/product-content/barracuda-fam/barracuda-2021/_shared/images/desktop-barracuda-pdp-hero-600x600.png'),
(22, 'H610M H DDR4', 'ASUS', 'Motherboard', '10500.00', 25, 'https://dlcdnwebimgs.asus.com/gain/5B873F1B-6E19-4B75-B1B4-B3E77A0EBBEA/'),
(23, 'Fractal Design Pop Mini', 'Fractal Design', 'Case', '10000.00', 18, 'https://www.fractal-design.com/app/uploads/2022/05/FD-C-POM1A-02_front-1-1024x1024.png'),
(24, 'Core i3-12100F', 'Intel', 'CPU', '10500.00', 40, 'https://www.startech.com.bd/image/cache/catalog/processor/intel/i3-12100f/i3-12100f-500x500.jpg'),
(25, 'Core i3-13100F', 'Intel', 'CPU', '12500.00', 35, NULL),
(26, 'Core i5-12400F', 'Intel', 'CPU', '17500.00', 30, NULL),
(27, 'Core i5-13400F', 'Intel', 'CPU', '21000.00', 28, NULL),
(28, 'Core i5-14600K', 'Intel', 'CPU', '33500.00', 20, NULL),
(29, 'Core i7-13700K', 'Intel', 'CPU', '48000.00', 15, NULL),
(30, 'Core i7-14700K', 'Intel', 'CPU', '56000.00', 12, NULL),
(31, 'Core i9-12900K', 'Intel', 'CPU', '52000.00', 10, NULL),
(32, 'Core i9-13900K', 'Intel', 'CPU', '65000.00', 8, NULL),
(33, 'Ryzen 5 5500', 'AMD', 'CPU', '11000.00', 35, NULL),
(34, 'Ryzen 5 5600X', 'AMD', 'CPU', '16500.00', 30, NULL),
(35, 'Ryzen 5 7600X', 'AMD', 'CPU', '27000.00', 25, NULL),
(36, 'Ryzen 7 5800X', 'AMD', 'CPU', '26500.00', 20, NULL),
(37, 'Ryzen 7 7700X', 'AMD', 'CPU', '37500.00', 18, NULL),
(38, 'Ryzen 9 5900X', 'AMD', 'CPU', '40000.00', 12, NULL),
(39, 'Ryzen 9 7900X', 'AMD', 'CPU', '55000.00', 8, NULL),
(40, 'Ryzen 9 7950X', 'AMD', 'CPU', '75000.00', 5, NULL),
(41, 'PRIME B660M-A DDR4', 'ASUS', 'Motherboard', '12000.00', 25, NULL),
(42, 'PRO B660M-A DDR4', 'MSI', 'Motherboard', '13000.00', 22, NULL),
(43, 'B660M DS3H DDR4', 'Gigabyte', 'Motherboard', '11500.00', 28, NULL),
(44, 'ROG STRIX B660-F DDR5', 'ASUS', 'Motherboard', '26500.00', 15, NULL),
(45, 'MAG Z790 TOMAHAWK DDR5', 'MSI', 'Motherboard', '38000.00', 10, NULL),
(46, 'Z790 AORUS MASTER', 'Gigabyte', 'Motherboard', '65000.00', 5, NULL),
(47, 'TUF GAMING B550-PLUS', 'ASUS', 'Motherboard', '17500.00', 20, NULL),
(48, 'MAG B550 TOMAHAWK', 'MSI', 'Motherboard', '18500.00', 18, NULL),
(49, 'B550 AORUS PRO AC', 'Gigabyte', 'Motherboard', '21000.00', 15, NULL),
(50, 'ROG CROSSHAIR X670E HERO', 'ASUS', 'Motherboard', '76000.00', 4, NULL),
(51, 'MEG X670E ACE', 'MSI', 'Motherboard', '85000.00', 3, NULL),
(52, 'X670E AORUS MASTER', 'Gigabyte', 'Motherboard', '72000.00', 4, NULL),
(53, 'PRIME A520M-K', 'ASUS', 'Motherboard', '9000.00', 35, NULL),
(54, 'B450M PRO-VDH MAX', 'MSI', 'Motherboard', '10000.00', 30, NULL),
(55, 'B550M Pro4', 'ASRock', 'Motherboard', '13500.00', 22, NULL),
(56, 'TUF GAMING Z790-PLUS D4', 'ASUS', 'Motherboard', '32000.00', 12, NULL),
(57, 'Vengeance LPX 16GB DDR4 3200', 'Corsair', 'RAM', '6000.00', 50, NULL),
(58, 'FURY Beast 32GB DDR4 3200', 'Kingston', 'RAM', '11500.00', 40, NULL),
(59, 'Ripjaws V 16GB DDR4 3600', 'G.Skill', 'RAM', '7000.00', 45, NULL),
(60, 'Vengeance 16GB DDR5 5200', 'Corsair', 'RAM', '9500.00', 35, NULL),
(61, 'FURY Beast 32GB DDR5 5200', 'Kingston', 'RAM', '18500.00', 25, NULL),
(62, 'Trident Z5 64GB DDR5 6000', 'G.Skill', 'RAM', '35000.00', 10, NULL),
(63, 'T-Force Vulcan 16GB DDR4 3200', 'TeamGroup', 'RAM', '5500.00', 55, NULL),
(64, 'Crucial 16GB DDR4 3200', 'Crucial', 'RAM', '6000.00', 60, NULL),
(65, 'ValueRAM 8GB DDR4 2666', 'Kingston', 'RAM', '3200.00', 80, NULL),
(66, 'Crucial 8GB DDR4 3200', 'Crucial', 'RAM', '3000.00', 90, NULL),
(67, 'Aegis 16GB DDR4 3000', 'G.Skill', 'RAM', '5500.00', 45, NULL),
(68, 'Dominator Platinum 32GB DDR5 5600', 'Corsair', 'RAM', '23000.00', 15, NULL),
(69, 'Vengeance 64GB DDR5 5600', 'Corsair', 'RAM', '38000.00', 7, NULL),
(70, 'FURY Renegade 32GB DDR5 6000', 'Kingston', 'RAM', '24000.00', 12, NULL),
(71, 'T-Force Delta 16GB DDR5 5600', 'TeamGroup', 'RAM', '10500.00', 30, NULL),
(72, 'Crucial Pro 32GB DDR5 5600', 'Crucial', 'RAM', '17500.00', 20, NULL),
(73, 'Trident Z Neo 32GB DDR4 3600', 'G.Skill', 'RAM', '13500.00', 25, NULL),
(74, 'GeForce RTX 3060 12GB', 'NVIDIA', 'GPU', '30000.00', 15, NULL),
(75, 'GeForce RTX 3060 Ti 8GB', 'NVIDIA', 'GPU', '37000.00', 12, NULL),
(76, 'GeForce RTX 3070 8GB', 'NVIDIA', 'GPU', '45000.00', 10, NULL),
(77, 'GeForce RTX 3080 10GB', 'NVIDIA', 'GPU', '65000.00', 8, NULL),
(78, 'GeForce RTX 4060 8GB', 'NVIDIA', 'GPU', '40000.00', 18, NULL),
(79, 'GeForce RTX 4060 Ti 16GB', 'NVIDIA', 'GPU', '55000.00', 10, NULL),
(80, 'GeForce RTX 4070 16GB', 'NVIDIA', 'GPU', '68000.00', 8, NULL),
(81, 'GeForce RTX 4080 Super 16GB', 'NVIDIA', 'GPU', '130000.00', 4, NULL),
(82, 'GeForce GTX 1660 Super 6GB', 'NVIDIA', 'GPU', '20000.00', 20, NULL),
(83, 'Radeon RX 6600 8GB', 'AMD', 'GPU', '24000.00', 18, NULL),
(84, 'Radeon RX 6650 XT 8GB', 'AMD', 'GPU', '27500.00', 15, NULL),
(85, 'Radeon RX 6700 XT 12GB', 'AMD', 'GPU', '38000.00', 12, NULL),
(86, 'Radeon RX 6800 XT 16GB', 'AMD', 'GPU', '55000.00', 8, NULL),
(87, 'Radeon RX 7600 8GB', 'AMD', 'GPU', '30000.00', 16, NULL),
(88, 'Radeon RX 7700 XT 12GB', 'AMD', 'GPU', '45000.00', 10, NULL),
(89, 'Radeon RX 7800 XT 16GB', 'AMD', 'GPU', '53000.00', 8, NULL),
(90, 'Radeon RX 7900 GRE 16GB', 'AMD', 'GPU', '68000.00', 6, NULL),
(91, 'CX550 550W 80+ Bronze', 'Corsair', 'Power Supply', '7000.00', 40, NULL),
(92, 'CV550 550W 80+ Bronze', 'Corsair', 'Power Supply', '6000.00', 45, NULL),
(93, 'MWE 750W 80+ Bronze', 'Cooler Master', 'Power Supply', '9500.00', 35, NULL),
(94, 'Focus GX-750 750W 80+ Gold', 'Seasonic', 'Power Supply', '15000.00', 20, NULL),
(95, 'RM750x 750W 80+ Gold', 'Corsair', 'Power Supply', '15500.00', 18, NULL),
(96, 'SuperNOVA 650W 80+ Gold', 'EVGA', 'Power Supply', '11000.00', 25, NULL),
(97, 'C850 850W 80+ Gold', 'NZXT', 'Power Supply', '17000.00', 15, NULL),
(98, 'MPG A850GF 850W 80+ Gold', 'MSI', 'Power Supply', '18000.00', 12, NULL),
(99, 'Toughpower GF1 750W 80+ Gold', 'Thermaltake', 'Power Supply', '14000.00', 18, NULL),
(100, 'Straight Power 11 750W Platinum', 'be quiet!', 'Power Supply', '25000.00', 8, NULL),
(101, 'Prime TX-1000 1000W Titanium', 'Seasonic', 'Power Supply', '38000.00', 6, NULL),
(102, 'AX1600i 1600W Titanium', 'Corsair', 'Power Supply', '80000.00', 2, NULL),
(103, 'Hydro PRO 600W 80+ Bronze', 'FSP', 'Power Supply', '6000.00', 50, NULL),
(104, 'MWE 550W White', 'Cooler Master', 'Power Supply', '5500.00', 55, NULL),
(105, 'C650 650W 80+ Gold', 'NZXT', 'Power Supply', '12000.00', 22, NULL),
(106, 'P650B 650W 80+ Bronze', 'Gigabyte', 'Power Supply', '6500.00', 38, NULL),
(107, 'RM1000x 1000W 80+ Gold', 'Corsair', 'Power Supply', '24000.00', 8, NULL),
(108, '870 EVO 500GB SATA SSD', 'Samsung', 'Storage', '7000.00', 40, NULL),
(109, '870 EVO 1TB SATA SSD', 'Samsung', 'Storage', '12500.00', 35, NULL),
(110, 'WD Blue 1TB SATA SSD', 'Western Digital', 'Storage', '9500.00', 38, NULL),
(111, 'MX500 500GB SATA SSD', 'Crucial', 'Storage', '6500.00', 45, NULL),
(112, 'A400 480GB SATA SSD', 'Kingston', 'Storage', '5000.00', 60, NULL),
(113, '980 Pro 500GB NVMe PCIe 4.0', 'Samsung', 'Storage', '9000.00', 30, NULL),
(114, '980 Pro 2TB NVMe PCIe 4.0', 'Samsung', 'Storage', '26000.00', 14, NULL),
(115, 'WD Black SN770 1TB NVMe', 'Western Digital', 'Storage', '11000.00', 28, NULL),
(116, 'Barracuda 1TB HDD 7200RPM', 'Seagate', 'Storage', '4200.00', 70, NULL),
(117, 'Barracuda 4TB HDD 5400RPM', 'Seagate', 'Storage', '9500.00', 35, NULL),
(118, 'WD Blue 2TB HDD 5400RPM', 'Western Digital', 'Storage', '6000.00', 50, NULL),
(119, 'WD Blue 4TB HDD 5400RPM', 'Western Digital', 'Storage', '10500.00', 28, NULL),
(120, 'P300 2TB HDD 7200RPM', 'Toshiba', 'Storage', '5500.00', 40, NULL),
(121, 'NV2 1TB NVMe PCIe 4.0', 'Kingston', 'Storage', '6500.00', 35, NULL),
(122, 'P3 1TB NVMe PCIe 3.0', 'Crucial', 'Storage', '7000.00', 32, NULL),
(123, 'Platinum P41 1TB NVMe PCIe 4.0', 'SK Hynix', 'Storage', '12000.00', 20, NULL),
(124, 'IronWolf 4TB NAS HDD', 'Seagate', 'Storage', '15000.00', 18, NULL),
(125, 'Q300L Micro-ATX Tower', 'Cooler Master', 'Case', '5000.00', 30, NULL),
(126, 'H510 Elite ATX', 'NZXT', 'Case', '17500.00', 15, NULL),
(127, 'Meshify C ATX', 'Fractal Design', 'Case', '13000.00', 18, NULL),
(128, 'Eclipse P400A ATX', 'Phanteks', 'Case', '11000.00', 22, NULL),
(129, 'Pure Base 500 ATX', 'be quiet!', 'Case', '12000.00', 16, NULL),
(130, '5000D Airflow ATX', 'Corsair', 'Case', '22000.00', 12, NULL),
(131, 'MasterBox TD500 Mesh ATX', 'Cooler Master', 'Case', '8500.00', 25, NULL),
(132, 'CC560 ATX Mid Tower', 'DeepCool', 'Case', '6500.00', 35, NULL),
(133, 'P120 Crystal ATX', 'Antec', 'Case', '8000.00', 20, NULL),
(134, 'MAG PANO M100R Micro-ATX', 'MSI', 'Case', '7500.00', 22, NULL),
(135, 'S100 Tempered Glass mATX', 'Thermaltake', 'Case', '5000.00', 38, NULL),
(136, 'MasterBox Q500L ATX', 'Cooler Master', 'Case', '5500.00', 30, NULL),
(137, 'H7 Flow ATX', 'NZXT', 'Case', '19000.00', 10, NULL),
(138, 'North ATX Mesh', 'Fractal Design', 'Case', '17000.00', 12, NULL),
(139, 'NV7 Full Tower ATX', 'Phanteks', 'Case', '24000.00', 7, NULL),
(140, 'Dark Base 700 ATX', 'be quiet!', 'Case', '20000.00', 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cpus`
--

CREATE TABLE `cpus` (
  `component_id` int(11) NOT NULL,
  `Socket` varchar(100) DEFAULT NULL,
  `Cores` int(11) DEFAULT NULL,
  `Clock_Speed` decimal(3,1) DEFAULT NULL,
  `tdp_watt` decimal(5,2) DEFAULT NULL,
  `passmark_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cpus`
--

INSERT INTO `cpus` (`component_id`, `Socket`, `Cores`, `Clock_Speed`, `tdp_watt`, `passmark_score`) VALUES
(1, 'LGA1700', 24, '6.0', '125.00', 60000),
(2, 'AM5', 8, '4.2', '120.00', 35000),
(11, 'LGA1700', 14, '5.1', '125.00', 38000),
(24, 'LGA1700', 4, '3.3', '58.00', 18000),
(25, 'LGA1700', 4, '3.4', '58.00', 19500),
(26, 'LGA1700', 6, '2.5', '65.00', 24000),
(27, 'LGA1700', 10, '2.5', '65.00', 28000),
(28, 'LGA1700', 14, '3.5', '125.00', 36000),
(29, 'LGA1700', 16, '3.4', '125.00', 43000),
(30, 'LGA1700', 20, '3.4', '125.00', 50000),
(31, 'LGA1700', 16, '5.2', '125.00', 45000),
(32, 'LGA1700', 24, '3.0', '125.00', 58000),
(33, 'AM4', 6, '3.6', '65.00', 22000),
(34, 'AM4', 6, '3.7', '65.00', 26000),
(35, 'AM5', 6, '4.7', '105.00', 30000),
(36, 'AM4', 8, '3.8', '105.00', 32000),
(37, 'AM5', 8, '4.5', '105.00', 36000),
(38, 'AM4', 12, '3.7', '105.00', 40000),
(39, 'AM5', 12, '4.7', '170.00', 50000),
(40, 'AM5', 16, '4.5', '170.00', 62000);

-- --------------------------------------------------------

--
-- Table structure for table `gpus`
--

CREATE TABLE `gpus` (
  `component_id` int(11) NOT NULL,
  `VRAM_GB` int(11) DEFAULT NULL,
  `TDP_Watt` int(11) DEFAULT NULL,
  `GPU_Length_mm` int(11) DEFAULT NULL,
  `Memory_Type` varchar(20) DEFAULT NULL,
  `perf_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gpus`
--

INSERT INTO `gpus` (`component_id`, `VRAM_GB`, `TDP_Watt`, `GPU_Length_mm`, `Memory_Type`, `perf_score`) VALUES
(16, 24, 450, 336, 'GDDR6X', 38000),
(17, 12, 220, 285, 'GDDR6X', 27000),
(18, 24, 355, 287, 'GDDR6', 31000),
(74, 12, 170, 242, 'GDDR6', 17000),
(75, 8, 200, 242, 'GDDR6X', 19500),
(76, 8, 220, 240, 'GDDR6X', 22000),
(77, 10, 320, 285, 'GDDR6X', 26000),
(78, 8, 115, 240, 'GDDR6', 18500),
(79, 16, 165, 246, 'GDDR6', 22000),
(80, 16, 200, 285, 'GDDR6X', 25500),
(81, 16, 320, 336, 'GDDR6X', 33000),
(82, 6, 125, 229, 'GDDR6', 12500),
(83, 8, 132, 240, 'GDDR6', 15000),
(84, 8, 176, 267, 'GDDR6', 17500),
(85, 12, 230, 267, 'GDDR6', 20500),
(86, 16, 300, 267, 'GDDR6', 25000),
(87, 8, 165, 230, 'GDDR6', 17000),
(88, 12, 245, 267, 'GDDR6', 22500),
(89, 16, 263, 267, 'GDDR6', 24500),
(90, 16, 260, 267, 'GDDR6', 28000);

-- --------------------------------------------------------

--
-- Table structure for table `motherboards`
--

CREATE TABLE `motherboards` (
  `component_id` int(11) NOT NULL,
  `Socket` varchar(100) DEFAULT NULL,
  `Form_Factor` enum('ATX','Micro-ATX','Mini-ITX') DEFAULT NULL,
  `Max_Ram_Capacity` int(11) DEFAULT NULL,
  `Max_Ram_Slots` int(11) DEFAULT NULL,
  `supported_ram_type` varchar(10) DEFAULT NULL,
  `m2_slots` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motherboards`
--

INSERT INTO `motherboards` (`component_id`, `Socket`, `Form_Factor`, `Max_Ram_Capacity`, `Max_Ram_Slots`, `supported_ram_type`, `m2_slots`) VALUES
(3, 'LGA1700', 'ATX', NULL, 4, 'DDR5', 5),
(4, 'AM5', 'ATX', NULL, 4, 'DDR5', 3),
(12, 'LGA1700', 'ATX', NULL, 4, 'DDR5', 3),
(22, 'LGA1700', 'Micro-ATX', 64, 2, 'DDR4', 1),
(41, 'LGA1700', 'Micro-ATX', 64, 2, 'DDR4', 1),
(42, 'LGA1700', 'Micro-ATX', 64, 2, 'DDR4', 2),
(43, 'LGA1700', 'Micro-ATX', 64, 2, 'DDR4', 1),
(44, 'LGA1700', 'ATX', 128, 4, 'DDR5', 3),
(45, 'LGA1700', 'ATX', 128, 4, 'DDR5', 4),
(46, 'LGA1700', 'ATX', 128, 4, 'DDR5', 5),
(47, 'AM4', 'ATX', 128, 4, 'DDR4', 2),
(48, 'AM4', 'ATX', 128, 4, 'DDR4', 2),
(49, 'AM4', 'ATX', 128, 4, 'DDR4', 3),
(50, 'AM5', 'ATX', 128, 4, 'DDR5', 5),
(51, 'AM5', 'ATX', 128, 4, 'DDR5', 5),
(52, 'AM5', 'ATX', 128, 4, 'DDR5', 5),
(53, 'AM4', 'Micro-ATX', 64, 2, 'DDR4', 1),
(54, 'AM4', 'Micro-ATX', 64, 4, 'DDR4', 1),
(55, 'AM4', 'Micro-ATX', 128, 4, 'DDR4', 2),
(56, 'LGA1700', 'ATX', 128, 4, 'DDR5', 4);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_type` enum('build','single') NOT NULL DEFAULT 'single',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cod','bkash','nagad','card') NOT NULL DEFAULT 'cod',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `txn_id` varchar(100) DEFAULT NULL,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(60) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_type`, `status`, `payment_method`, `payment_status`, `txn_id`, `total_price`, `full_name`, `phone`, `address`, `city`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 'build', 'pending', 'nagad', 'paid', '515548628', '521500.00', 'aqshanto', '01712345678', 'dhaka', 'Dhaka', '', '2026-03-20 10:15:13', '2026-03-20 10:15:13'),
(2, 1, 'single', 'cancelled', 'cod', 'unpaid', '', '6500.00', 'aqshanto', '545646545', 'hj gjhn', 'Rajshahi', '', '2026-03-25 00:21:05', '2026-03-25 00:21:33');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `slot_type` varchar(30) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `component_id`, `slot_type`, `name`, `brand`, `price`) VALUES
(1, 1, 1, 'cpu', 'Core i9-14900K', 'Intel', '76500.00'),
(2, 1, 3, 'motherboard', 'ROG Maximus Z790 Hero', 'ASUS', '82000.00'),
(3, 1, 16, 'gpu', 'GeForce RTX 4090', 'NVIDIA', '195000.00'),
(4, 1, 69, 'ram', 'Vengeance 64GB DDR5 5600', 'Corsair', '38000.00'),
(5, 1, 102, 'powersupply', 'AX1600i 1600W Titanium', 'Corsair', '80000.00'),
(6, 1, 114, 'storage', '980 Pro 2TB NVMe PCIe 4.0', 'Samsung', '26000.00'),
(7, 1, 139, 'case', 'NV7 Full Tower ATX', 'Phanteks', '24000.00'),
(8, 2, 132, 'case', 'CC560 ATX Mid Tower', 'DeepCool', '6500.00');

-- --------------------------------------------------------

--
-- Table structure for table `powersupplies`
--

CREATE TABLE `powersupplies` (
  `component_id` int(11) NOT NULL,
  `Wattage` int(11) DEFAULT NULL,
  `Efficiency_Rating` enum('Gold','Bronze','Platinum') DEFAULT NULL,
  `Modularity` enum('Full','Semi','Non') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `powersupplies`
--

INSERT INTO `powersupplies` (`component_id`, `Wattage`, `Efficiency_Rating`, `Modularity`) VALUES
(7, 1000, 'Gold', 'Full'),
(8, 650, 'Bronze', 'Non'),
(14, 850, 'Gold', 'Full'),
(91, 550, 'Bronze', 'Non'),
(92, 550, 'Bronze', 'Non'),
(93, 750, 'Bronze', 'Non'),
(94, 750, 'Gold', 'Full'),
(95, 750, 'Gold', 'Full'),
(96, 650, 'Gold', 'Semi'),
(97, 850, 'Gold', 'Full'),
(98, 850, 'Gold', 'Full'),
(99, 750, 'Gold', 'Semi'),
(100, 750, 'Platinum', 'Full'),
(101, 1000, 'Gold', 'Full'),
(102, 1600, 'Gold', 'Full'),
(103, 600, 'Bronze', 'Non'),
(104, 550, 'Bronze', 'Non'),
(105, 650, 'Gold', 'Full'),
(106, 650, 'Bronze', 'Non'),
(107, 1000, 'Gold', 'Full');

-- --------------------------------------------------------

--
-- Table structure for table `rams`
--

CREATE TABLE `rams` (
  `component_id` int(11) NOT NULL,
  `Capacity_GB` int(11) DEFAULT NULL,
  `DDR_Version` enum('DDR3','DDR4','DDR5') DEFAULT NULL,
  `Speed_MHz` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rams`
--

INSERT INTO `rams` (`component_id`, `Capacity_GB`, `DDR_Version`, `Speed_MHz`) VALUES
(5, 32, 'DDR5', 6000),
(6, 16, 'DDR4', 3200),
(13, 32, 'DDR5', 6000),
(57, 16, 'DDR4', 3200),
(58, 32, 'DDR4', 3200),
(59, 16, 'DDR4', 3600),
(60, 16, 'DDR5', 5200),
(61, 32, 'DDR5', 5200),
(62, 64, 'DDR5', 6000),
(63, 16, 'DDR4', 3200),
(64, 16, 'DDR4', 3200),
(65, 8, 'DDR4', 2666),
(66, 8, 'DDR4', 3200),
(67, 16, 'DDR4', 3000),
(68, 32, 'DDR5', 5600),
(69, 64, 'DDR5', 5600),
(70, 32, 'DDR5', 6000),
(71, 16, 'DDR5', 5600),
(72, 32, 'DDR5', 5600),
(73, 32, 'DDR4', 3600);

-- --------------------------------------------------------

--
-- Table structure for table `storages`
--

CREATE TABLE `storages` (
  `component_id` int(11) NOT NULL,
  `Capacity_GB` int(11) DEFAULT NULL,
  `Storage_Type` varchar(20) DEFAULT NULL,
  `Interface` varchar(20) DEFAULT NULL,
  `Read_Speed_MBps` int(11) DEFAULT NULL,
  `Write_Speed_MBps` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storages`
--

INSERT INTO `storages` (`component_id`, `Capacity_GB`, `Storage_Type`, `Interface`, `Read_Speed_MBps`, `Write_Speed_MBps`) VALUES
(19, 1000, 'NVMe SSD', 'PCIe 4.0 x4', 7450, 6900),
(20, 2000, 'NVMe SSD', 'PCIe 4.0 x4', 7300, 6600),
(21, 2000, 'HDD', 'SATA III', 180, 180),
(108, 500, 'SATA SSD', 'SATA III', 560, 530),
(109, 1000, 'SATA SSD', 'SATA III', 560, 530),
(110, 1000, 'SATA SSD', 'SATA III', 560, 520),
(111, 500, 'SATA SSD', 'SATA III', 560, 510),
(112, 480, 'SATA SSD', 'SATA III', 500, 450),
(113, 500, 'NVMe SSD', 'PCIe 4.0 x4', 6900, 5000),
(114, 2000, 'NVMe SSD', 'PCIe 4.0 x4', 7300, 6900),
(115, 1000, 'NVMe SSD', 'PCIe 4.0 x4', 5150, 4900),
(116, 1000, 'HDD', 'SATA III', 190, 190),
(117, 4000, 'HDD', 'SATA III', 180, 180),
(118, 2000, 'HDD', 'SATA III', 175, 175),
(119, 4000, 'HDD', 'SATA III', 175, 175),
(120, 2000, 'HDD', 'SATA III', 185, 185),
(121, 1000, 'NVMe SSD', 'PCIe 4.0 x4', 3500, 2800),
(122, 1000, 'NVMe SSD', 'PCIe 3.0 x4', 3500, 3000),
(123, 1000, 'NVMe SSD', 'PCIe 4.0 x4', 7000, 6500),
(124, 4000, 'HDD', 'SATA III', 210, 210);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `user_mail` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `user_mail`, `password_hash`, `role`) VALUES
(1, 'aqshanto', 'aqshanto@pcbuilder.com', '$2y$10$Gb.UEdBnrs2YH2UNsjSZwuP2hoeoSP09L8TbGQzUfvCrag5xBpXea', 'admin'),
(2, 'AminoAcid', 'sabbirgunda1@gmail.com', '$2y$10$DR10pvpRAV.fd.RBVBY7WOZxHBEquuQMENS/.C14G2zsmqMmdnrUS', 'user'),
(3, 'user', 'user@gmail.com', '$2y$10$pUbuCLTeWibFvwZI2htJi.JGvR9mrl8O2UjQVxQk6E.RTxoOWPxXK', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `builds`
--
ALTER TABLE `builds`
  ADD PRIMARY KEY (`build_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `build_components`
--
ALTER TABLE `build_components`
  ADD PRIMARY KEY (`build_id`,`component_id`),
  ADD KEY `component_id` (`component_id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `cpus`
--
ALTER TABLE `cpus`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `gpus`
--
ALTER TABLE `gpus`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `motherboards`
--
ALTER TABLE `motherboards`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `powersupplies`
--
ALTER TABLE `powersupplies`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `rams`
--
ALTER TABLE `rams`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `storages`
--
ALTER TABLE `storages`
  ADD PRIMARY KEY (`component_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `user_mail` (`user_mail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `builds`
--
ALTER TABLE `builds`
  MODIFY `build_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `builds`
--
ALTER TABLE `builds`
  ADD CONSTRAINT `builds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `build_components`
--
ALTER TABLE `build_components`
  ADD CONSTRAINT `build_components_ibfk_1` FOREIGN KEY (`build_id`) REFERENCES `builds` (`build_id`),
  ADD CONSTRAINT `build_components_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `cpus`
--
ALTER TABLE `cpus`
  ADD CONSTRAINT `cpus_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `gpus`
--
ALTER TABLE `gpus`
  ADD CONSTRAINT `gpus_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `motherboards`
--
ALTER TABLE `motherboards`
  ADD CONSTRAINT `motherboards_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `powersupplies`
--
ALTER TABLE `powersupplies`
  ADD CONSTRAINT `powersupplies_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `rams`
--
ALTER TABLE `rams`
  ADD CONSTRAINT `rams_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);

--
-- Constraints for table `storages`
--
ALTER TABLE `storages`
  ADD CONSTRAINT `storages_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`component_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
