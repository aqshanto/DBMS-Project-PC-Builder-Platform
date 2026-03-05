-- 1. Categories
INSERT INTO CATEGORIES (category_id, category_name) VALUES
('CAT01', 'Processor (CPU)'), ('CAT02', 'Motherboard'), ('CAT03', 'Desktop RAM'),
('CAT04', 'Graphics Card (GPU)'), ('CAT05', 'Casing'), ('CAT06', 'Power Supply (PSU)'),
('CAT07', 'NVMe SSD'), ('CAT08', 'SATA SSD'), ('CAT09', 'Hard Drive (HDD)'),
('CAT10', 'CPU Cooler'), ('CAT11', 'Casing Cooler'), ('CAT12', 'Monitor'),
('CAT13', 'Keyboard'), ('CAT14', 'Mouse'), ('CAT15', 'Headphone'),
('CAT16', 'UPS'), ('CAT17', 'Webcam'), ('CAT18', 'Microphone'),
('CAT19', 'Thermal Paste'), ('CAT20', 'Optical Drive'),
('CAT21', 'Speaker & Home Theater'), ('CAT22', 'Wifi Adapter / LAN Card'), 
('CAT23', 'Anti Virus');

-- 2. ALL PRODUCTS (CPU to Cooler)
INSERT INTO PRODUCTS (product_id, name, brand, price, stock_quantity, category_id) VALUES
-- CPUs
('CPU001', 'Core i9-14900K', 'Intel', 600.00, 15, 'CAT01'), ('CPU002', 'Core i7-14700K', 'Intel', 400.00, 20, 'CAT01'),
('CPU003', 'Core i5-14600K', 'Intel', 300.00, 25, 'CAT01'), ('CPU004', 'Core i9-13900K', 'Intel', 550.00, 10, 'CAT01'),
('CPU005', 'Core i7-13700K', 'Intel', 380.00, 18, 'CAT01'), ('CPU006', 'Core i5-13600K', 'Intel', 280.00, 30, 'CAT01'),
('CPU007', 'Core i5-13400F', 'Intel', 200.00, 40, 'CAT01'), ('CPU008', 'Core i3-13100F', 'Intel', 110.00, 50, 'CAT01'),
('CPU009', 'Core i9-12900K', 'Intel', 400.00, 12, 'CAT01'), ('CPU010', 'Core i7-12700K', 'Intel', 290.00, 22, 'CAT01'),
('CPU011', 'Ryzen 9 7950X', 'AMD', 600.00, 15, 'CAT01'), ('CPU012', 'Ryzen 9 7900X', 'AMD', 450.00, 20, 'CAT01'),
('CPU013', 'Ryzen 7 7800X3D', 'AMD', 400.00, 25, 'CAT01'), ('CPU014', 'Ryzen 5 7600X', 'AMD', 230.00, 35, 'CAT01'),
('CPU015', 'Ryzen 5 7600', 'AMD', 200.00, 40, 'CAT01'), ('CPU016', 'Ryzen 9 5950X', 'AMD', 450.00, 10, 'CAT01'),
('CPU017', 'Ryzen 7 5800X3D', 'AMD', 320.00, 15, 'CAT01'), ('CPU018', 'Ryzen 7 5700X', 'AMD', 190.00, 20, 'CAT01'),
('CPU019', 'Ryzen 5 5600X', 'AMD', 160.00, 45, 'CAT01'), ('CPU020', 'Ryzen 5 5600G', 'AMD', 140.00, 50, 'CAT01'),

-- Motherboards
('MB001', 'ROG Maximus Z790 Hero', 'ASUS', 600.00, 5, 'CAT02'), ('MB002', 'Z790 AORUS ELITE AX', 'Gigabyte', 250.00, 15, 'CAT02'),
('MB003', 'MAG B760 TOMAHAWK WIFI', 'MSI', 200.00, 20, 'CAT02'), ('MB004', 'PRIME B760M-A', 'ASUS', 140.00, 30, 'CAT02'),
('MB005', 'Z690 UD AX', 'Gigabyte', 180.00, 10, 'CAT02'), ('MB006', 'PRO B660M-A WIFI', 'MSI', 150.00, 25, 'CAT02'),
('MB007', 'ROG STRIX Z790-E', 'ASUS', 450.00, 8, 'CAT02'), ('MB008', 'B760M DS3H', 'Gigabyte', 120.00, 35, 'CAT02'),
('MB009', 'H610M-K', 'ASUS', 90.00, 50, 'CAT02'), ('MB010', 'PRO H610M-G', 'MSI', 85.00, 55, 'CAT02'),
('MB011', 'ROG CROSSHAIR X670E HERO', 'ASUS', 650.00, 5, 'CAT02'), ('MB012', 'X670E AORUS MASTER', 'Gigabyte', 480.00, 8, 'CAT02'),
('MB013', 'MAG B650 TOMAHAWK WIFI', 'MSI', 220.00, 20, 'CAT02'), ('MB014', 'TUF GAMING B650-PLUS', 'ASUS', 210.00, 22, 'CAT02'),
('MB015', 'B650M AORUS ELITE', 'Gigabyte', 180.00, 25, 'CAT02'), ('MB016', 'PRO B650M-A', 'MSI', 160.00, 30, 'CAT02'),
('MB017', 'ROG STRIX B550-F', 'ASUS', 180.00, 15, 'CAT02'), ('MB018', 'B550 AORUS ELITE V2', 'Gigabyte', 150.00, 25, 'CAT02'),
('MB019', 'MAG B550 TOMAHAWK', 'MSI', 170.00, 20, 'CAT02'), ('MB020', 'PRIME B450M-A II', 'ASUS', 80.00, 40, 'CAT02'),

-- RAM
('RAM001', 'Corsair Vengeance 32GB (2x16GB) DDR5', 'Corsair', 150.00, 30, 'CAT03'), ('RAM002', 'G.Skill Trident Z5 32GB DDR5', 'G.Skill', 160.00, 25, 'CAT03'),
('RAM003', 'Kingston FURY Beast 16GB DDR4', 'Kingston', 60.00, 50, 'CAT03'), ('RAM004', 'Corsair Vengeance RGB 32GB DDR5', 'Corsair', 160.00, 20, 'CAT03'), 
('RAM005', 'G.Skill Ripjaws V 16GB DDR4', 'G.Skill', 45.00, 50, 'CAT03'), ('RAM006', 'Crucial Ballistix 16GB DDR4', 'Crucial', 55.00, 40, 'CAT03'), 
('RAM007', 'TeamGroup T-Force 32GB DDR5', 'TeamGroup', 140.00, 30, 'CAT03'), ('RAM008', 'Kingston FURY Beast 32GB DDR5', 'Kingston', 150.00, 25, 'CAT03'), 
('RAM009', 'Corsair Dominator Platinum 32GB DDR5', 'Corsair', 190.00, 15, 'CAT03'), ('RAM010', 'AORUS RGB Memory 16GB DDR4', 'Gigabyte', 70.00, 20, 'CAT03'), 
('RAM011', 'Patriot Viper Steel 16GB DDR4', 'Patriot', 50.00, 35, 'CAT03'), ('RAM012', 'XPG Spectrix D41 16GB DDR4', 'ADATA', 60.00, 40, 'CAT03'), 
('RAM013', 'G.Skill Trident Z Neo 32GB DDR4', 'G.Skill', 120.00, 25, 'CAT03'), ('RAM014', 'Thermaltake TOUGHRAM 16GB DDR4', 'Thermaltake', 85.00, 10, 'CAT03'), 
('RAM015', 'Lexar Hades 32GB DDR4', 'Lexar', 110.00, 20, 'CAT03'), ('RAM016', 'Silicon Power XPOWER 16GB DDR4', 'Silicon Power', 40.00, 50, 'CAT03'), 
('RAM017', 'PNY XLR8 32GB DDR5', 'PNY', 135.00, 15, 'CAT03'), ('RAM018', 'Klevv Cras X RGB 16GB DDR4', 'Klevv', 65.00, 20, 'CAT03'), 
('RAM019', 'Crucial Pro 32GB DDR5', 'Crucial', 125.00, 30, 'CAT03'), ('RAM020', 'Corsair Vengeance LPX 16GB DDR4', 'Corsair', 40.00, 100, 'CAT03'),

-- GPUs
('GPU001', 'GeForce RTX 4090 24GB', 'NVIDIA', 1600.00, 5, 'CAT04'), ('GPU002', 'Radeon RX 7900 XTX 24GB', 'AMD', 999.00, 10, 'CAT04'),
('GPU003', 'GeForce RTX 4070 12GB', 'NVIDIA', 600.00, 15, 'CAT04'), ('GPU004', 'GeForce RTX 4080 16GB', 'NVIDIA', 1200.00, 10, 'CAT04'), 
('GPU005', 'Radeon RX 7900 XT 20GB', 'AMD', 800.00, 12, 'CAT04'), ('GPU006', 'GeForce RTX 4070 Ti 12GB', 'NVIDIA', 800.00, 15, 'CAT04'), 
('GPU007', 'GeForce RTX 4060 Ti 8GB', 'NVIDIA', 400.00, 25, 'CAT04'), ('GPU008', 'GeForce RTX 4060 8GB', 'NVIDIA', 300.00, 30, 'CAT04'), 
('GPU009', 'Radeon RX 7800 XT 16GB', 'AMD', 500.00, 20, 'CAT04'), ('GPU010', 'Radeon RX 7700 XT 12GB', 'AMD', 450.00, 18, 'CAT04'), 
('GPU011', 'Radeon RX 7600 8GB', 'AMD', 270.00, 30, 'CAT04'), ('GPU012', 'GeForce RTX 3060 12GB', 'NVIDIA', 280.00, 40, 'CAT04'), 
('GPU013', 'GeForce RTX 3060 Ti 8GB', 'NVIDIA', 350.00, 15, 'CAT04'), ('GPU014', 'Radeon RX 6700 XT 12GB', 'AMD', 330.00, 20, 'CAT04'), 
('GPU015', 'GeForce RTX 3050 8GB', 'NVIDIA', 220.00, 35, 'CAT04'), ('GPU016', 'Radeon RX 6600 8GB', 'AMD', 200.00, 40, 'CAT04'), 
('GPU017', 'Arc A770 16GB', 'Intel', 300.00, 10, 'CAT04'), ('GPU018', 'Arc A750 8GB', 'Intel', 200.00, 15, 'CAT04'), 
('GPU019', 'GeForce GTX 1660 Super 6GB', 'NVIDIA', 180.00, 20, 'CAT04'), ('GPU020', 'Radeon RX 6500 XT 4GB', 'AMD', 150.00, 25, 'CAT04'),

-- Casings
('CAS001', 'NZXT H510 Flow', 'NZXT', 90.00, 20, 'CAT05'), ('CAS002', 'Lian Li PC-O11 Dynamic', 'Lian Li', 150.00, 15, 'CAT05'),
('CAS003', 'Cooler Master MasterBox Q300L', 'Cooler Master', 50.00, 30, 'CAT05'), ('CAS004', 'Corsair 4000D Airflow', 'Corsair', 100.00, 25, 'CAT05'), 
('CAS005', 'Fractal Design Meshify C', 'Fractal', 110.00, 20, 'CAT05'), ('CAS006', 'Phanteks Eclipse P400A', 'Phanteks', 90.00, 15, 'CAT05'), 
('CAS007', 'Montech AIR 903 MAX', 'Montech', 75.00, 30, 'CAT05'), ('CAS008', 'Thermaltake Tower 500', 'Thermaltake', 150.00, 10, 'CAT05'), 
('CAS009', 'Be Quiet! Pure Base 500DX', 'Be Quiet!', 110.00, 15, 'CAT05'), ('CAS010', 'NZXT H7 Flow', 'NZXT', 130.00, 20, 'CAT05'), 
('CAS011', 'Lian Li Lancool 216', 'Lian Li', 100.00, 25, 'CAT05'), ('CAS012', 'Corsair 5000D Airflow', 'Corsair', 150.00, 15, 'CAT05'), 
('CAS013', 'DeepCool CH510', 'DeepCool', 60.00, 35, 'CAT05'), ('CAS014', 'Antec DF700 Flux', 'Antec', 85.00, 20, 'CAT05'), 
('CAS015', 'SilverStone FARA R1', 'SilverStone', 65.00, 30, 'CAT05'), ('CAS016', 'MSI MAG Forge 100R', 'MSI', 70.00, 25, 'CAT05'), 
('CAS017', 'Gigabyte AORUS C300', 'Gigabyte', 120.00, 10, 'CAT05'), ('CAS018', 'Cougar MX330-G', 'Cougar', 50.00, 40, 'CAT05'), 
('CAS019', 'Aerocool Cylon RGB', 'Aerocool', 45.00, 45, 'CAT05'), ('CAS020', 'Gamemax Spark', 'Gamemax', 55.00, 20, 'CAT05'),

-- PSUs
('PSU001', 'Corsair RM1000x 1000W 80+ Gold', 'Corsair', 180.00, 15, 'CAT06'), ('PSU002', 'EVGA SuperNOVA 850W 80+ Gold', 'EVGA', 140.00, 20, 'CAT06'),
('PSU003', 'Cooler Master MWE 650W 80+ Bronze', 'Cooler Master', 70.00, 40, 'CAT06'), ('PSU004', 'Corsair RM850x 850W Gold', 'Corsair', 130.00, 25, 'CAT06'), 
('PSU005', 'EVGA 600 W1 600W White', 'EVGA', 50.00, 50, 'CAT06'), ('PSU006', 'Seasonic Focus GX-750 750W Gold', 'Seasonic', 120.00, 20, 'CAT06'), 
('PSU007', 'Thermaltake Toughpower 750W', 'Thermaltake', 100.00, 30, 'CAT06'), ('PSU008', 'Be Quiet! Straight Power 850W', 'Be Quiet!', 150.00, 15, 'CAT06'), 
('PSU009', 'Gigabyte P650B 650W Bronze', 'Gigabyte', 60.00, 40, 'CAT06'), ('PSU010', 'MSI MPG A850GF 850W Gold', 'MSI', 125.00, 20, 'CAT06'), 
('PSU011', 'NZXT C750 750W Gold', 'NZXT', 110.00, 25, 'CAT06'), ('PSU012', 'ASUS ROG Thor 1000W Platinum', 'ASUS', 250.00, 5, 'CAT06'), 
('PSU013', 'FSP Hydro G Pro 850W Gold', 'FSP', 115.00, 15, 'CAT06'), ('PSU014', 'DeepCool DQ750-M 750W Gold', 'DeepCool', 105.00, 20, 'CAT06'), 
('PSU015', 'SilverStone Strider 650W', 'SilverStone', 75.00, 30, 'CAT06'), ('PSU016', 'Antec NeoECO 850W Gold', 'Antec', 120.00, 18, 'CAT06'), 
('PSU017', 'Cougar GEX 750W Gold', 'Cougar', 95.00, 22, 'CAT06'), ('PSU018', 'XPG Core Reactor 850W Gold', 'ADATA', 130.00, 15, 'CAT06'), 
('PSU019', 'Zalman GigaMax 600W Bronze', 'Zalman', 55.00, 35, 'CAT06'), ('PSU020', 'Corsair CV550 550W Bronze', 'Corsair', 45.00, 45, 'CAT06'),

-- Storage
('STO001', 'Samsung 990 PRO 2TB NVMe', 'Samsung', 170.00, 25, 'CAT07'), ('STO002', 'WD Black SN850X 1TB NVMe', 'Western Digital', 100.00, 30, 'CAT07'),
('STO003', 'WD Blue SN570 1TB NVMe', 'Western Digital', 60.00, 50, 'CAT07'), ('STO004', 'Crucial P3 Plus 2TB NVMe', 'Crucial', 120.00, 30, 'CAT07'),
('STO005', 'Kingston NV2 1TB NVMe', 'Kingston', 55.00, 60, 'CAT07'), ('STO006', 'Seagate FireCuda 530 2TB', 'Seagate', 180.00, 15, 'CAT07'),
('STO007', 'Corsair MP600 Pro 1TB', 'Corsair', 110.00, 20, 'CAT07'), ('STO008', 'Samsung 870 EVO 1TB SATA', 'Samsung', 90.00, 40, 'CAT08'),
('STO009', 'Crucial MX500 1TB SATA', 'Crucial', 85.00, 35, 'CAT08'), ('STO010', 'WD Blue 1TB SATA SSD', 'Western Digital', 80.00, 45, 'CAT08'),
('STO011', 'Kingston A400 480GB SATA', 'Kingston', 35.00, 80, 'CAT08'), ('STO012', 'PNY CS900 500GB SATA', 'PNY', 30.00, 70, 'CAT08'),
('STO013', 'Seagate Barracuda 2TB HDD', 'Seagate', 50.00, 60, 'CAT09'), ('STO014', 'WD Blue 2TB HDD', 'Western Digital', 55.00, 50, 'CAT09'),
('STO015', 'Toshiba P300 1TB HDD', 'Toshiba', 40.00, 40, 'CAT09'), ('STO016', 'WD Black 4TB HDD', 'Western Digital', 120.00, 20, 'CAT09'),
('STO017', 'Seagate IronWolf 4TB HDD', 'Seagate', 110.00, 25, 'CAT09'), ('STO018', 'AORUS Gen4 1TB NVMe', 'Gigabyte', 105.00, 15, 'CAT07'),
('STO019', 'TeamGroup MP33 512GB NVMe', 'TeamGroup', 35.00, 50, 'CAT07'), ('STO020', 'Lexar NM620 1TB NVMe', 'Lexar', 50.00, 45, 'CAT07'),

-- Coolers
('COL001', 'Noctua NH-D15', 'Noctua', 110.00, 15, 'CAT10'), ('COL002', 'Corsair iCUE H150i Elite', 'Corsair', 190.00, 10, 'CAT10'),
('COL003', 'Cooler Master Hyper 212', 'Cooler Master', 40.00, 50, 'CAT10'), ('COL004', 'DeepCool AK620', 'DeepCool', 65.00, 30, 'CAT10'),
('COL005', 'NZXT Kraken X63', 'NZXT', 140.00, 15, 'CAT10'), ('COL006', 'Arctic Liquid Freezer II 280', 'Arctic', 110.00, 20, 'CAT10'),
('COL007', 'Be Quiet! Dark Rock Pro 4', 'Be Quiet!', 90.00, 25, 'CAT10'), ('COL008', 'Thermaltake Peerless Assassin', 'Thermaltake', 45.00, 40, 'CAT10'),
('COL009', 'Lian Li Galahad 240', 'Lian Li', 120.00, 15, 'CAT10'), ('COL010', 'MSI MAG CoreLiquid 240R', 'MSI', 100.00, 20, 'CAT10'),
('COL011', 'ASUS ROG Strix LC II 240', 'ASUS', 150.00, 10, 'CAT10'), ('COL012', 'Corsair iCUE H100i', 'Corsair', 130.00, 18, 'CAT10'),
('COL013', 'Scythe Fuma 2', 'Scythe', 60.00, 25, 'CAT10'), ('COL014', 'Noctua NH-U12S', 'Noctua', 70.00, 30, 'CAT10'),
('COL015', 'DeepCool Gammaxx GT', 'DeepCool', 35.00, 45, 'CAT10'), ('COL016', 'ID-Cooling SE-224-XT', 'ID-Cooling', 30.00, 60, 'CAT10'),
('COL017', 'Zalman CNPS10X', 'Zalman', 45.00, 35, 'CAT10'), ('COL018', 'Enermax Liqmax III 240', 'Enermax', 80.00, 20, 'CAT10'),
('COL019', 'Phanteks Glacier One 240MPH', 'Phanteks', 140.00, 10, 'CAT10'), ('COL020', 'SilverStone PermaFrost 240', 'SilverStone', 95.00, 15, 'CAT10');

-- 3. SPECIFIC SUB-TABLES (Full 20 for all)
INSERT INTO CPUS (product_id, socket_type, core_count, tdp_watt, passmark_score) VALUES
('CPU001', 'LGA1700', 24, 125.00, 60000), ('CPU002', 'LGA1700', 20, 125.00, 53000), ('CPU003', 'LGA1700', 14, 125.00, 40000), 
('CPU004', 'LGA1700', 24, 125.00, 59000), ('CPU005', 'LGA1700', 16, 125.00, 45000), ('CPU006', 'LGA1700', 14, 125.00, 38000),
('CPU007', 'LGA1700', 10, 65.00, 25000),  ('CPU008', 'LGA1700', 4, 58.00, 15000), ('CPU009', 'LGA1700', 16, 125.00, 41000), 
('CPU010', 'LGA1700', 12, 125.00, 34000), ('CPU011', 'AM5', 16, 170.00, 63000),     ('CPU012', 'AM5', 12, 170.00, 52000),
('CPU013', 'AM5', 8, 120.00, 35000),      ('CPU014', 'AM5', 6, 105.00, 28000), ('CPU015', 'AM5', 6, 65.00, 26000),       
('CPU016', 'AM4', 16, 105.00, 46000), ('CPU017', 'AM4', 8, 105.00, 28000),      ('CPU018', 'AM4', 8, 65.00, 26000),
('CPU019', 'AM4', 6, 65.00, 22000),       ('CPU020', 'AM4', 6, 65.00, 19000);

INSERT INTO MOTHERBOARDS (product_id, socket_type, form_factor, supported_ram_type, m2_slots) VALUES
('MB001', 'LGA1700', 'ATX', 'DDR5', 5), ('MB002', 'LGA1700', 'ATX', 'DDR5', 4), ('MB003', 'LGA1700', 'ATX', 'DDR5', 3), 
('MB004', 'LGA1700', 'Micro-ATX', 'DDR5', 2), ('MB005', 'LGA1700', 'ATX', 'DDR4', 3), ('MB006', 'LGA1700', 'Micro-ATX', 'DDR4', 2),
('MB007', 'LGA1700', 'ATX', 'DDR5', 4), ('MB008', 'LGA1700', 'Micro-ATX', 'DDR4', 2), ('MB009', 'LGA1700', 'Micro-ATX', 'DDR4', 1), 
('MB010', 'LGA1700', 'Micro-ATX', 'DDR4', 1), ('MB011', 'AM5', 'E-ATX', 'DDR5', 5),   ('MB012', 'AM5', 'E-ATX', 'DDR5', 4),
('MB013', 'AM5', 'ATX', 'DDR5', 3),     ('MB014', 'AM5', 'ATX', 'DDR5', 3), ('MB015', 'AM5', 'Micro-ATX', 'DDR5', 2), 
('MB016', 'AM5', 'Micro-ATX', 'DDR5', 2), ('MB017', 'AM4', 'ATX', 'DDR4', 2),     ('MB018', 'AM4', 'ATX', 'DDR4', 2),
('MB019', 'AM4', 'ATX', 'DDR4', 2),     ('MB020', 'AM4', 'Micro-ATX', 'DDR4', 1);

INSERT INTO RAMS (product_id, ram_type, capacity_gb, speed_mhz) VALUES
('RAM001', 'DDR5', 32, 6000), ('RAM002', 'DDR5', 32, 6400), ('RAM003', 'DDR4', 16, 3200), ('RAM004', 'DDR5', 32, 5600), 
('RAM005', 'DDR4', 16, 3200), ('RAM006', 'DDR4', 16, 3600), ('RAM007', 'DDR5', 32, 6000), ('RAM008', 'DDR5', 32, 5200), 
('RAM009', 'DDR5', 32, 6200), ('RAM010', 'DDR4', 16, 3200), ('RAM011', 'DDR4', 16, 4000), ('RAM012', 'DDR4', 16, 3200), 
('RAM013', 'DDR4', 32, 3600), ('RAM014', 'DDR4', 16, 3200), ('RAM015', 'DDR4', 32, 3200), ('RAM016', 'DDR4', 16, 3200), 
('RAM017', 'DDR5', 32, 6000), ('RAM018', 'DDR4', 16, 3600), ('RAM019', 'DDR5', 32, 5600), ('RAM020', 'DDR4', 16, 2666);

INSERT INTO GPUS (product_id, length_mm, tdp_watt, passmark_score) VALUES
('GPU001', 336.00, 450.00, 39000), ('GPU002', 287.00, 355.00, 32000), ('GPU003', 242.00, 200.00, 27000), ('GPU004', 310.00, 320.00, 34000), 
('GPU005', 276.00, 315.00, 30000), ('GPU006', 285.00, 285.00, 28000), ('GPU007', 240.00, 160.00, 22000), ('GPU008', 240.00, 115.00, 19000), 
('GPU009', 267.00, 263.00, 25000), ('GPU010', 267.00, 245.00, 21000), ('GPU011', 240.00, 165.00, 17000), ('GPU012', 242.00, 170.00, 16000), 
('GPU013', 242.00, 200.00, 19500), ('GPU014', 267.00, 230.00, 18500), ('GPU015', 242.00, 130.00, 12000), ('GPU016', 240.00, 132.00, 14000), 
('GPU017', 267.00, 225.00, 15000), ('GPU018', 267.00, 225.00, 13000), ('GPU019', 235.00, 125.00, 12500), ('GPU020', 200.00, 107.00, 9000);

INSERT INTO CASINGS (product_id, supported_form_factors, max_gpu_length_mm) VALUES
('CAS001', 'ATX, Micro-ATX, Mini-ITX', 360.00), ('CAS002', 'E-ATX, ATX, Micro-ATX, Mini-ITX', 420.00), ('CAS003', 'Micro-ATX, Mini-ITX', 360.00), 
('CAS004', 'ATX, Micro-ATX, Mini-ITX', 360.00), ('CAS005', 'ATX, Micro-ATX, Mini-ITX', 315.00), ('CAS006', 'ATX, Micro-ATX, Mini-ITX', 380.00),
('CAS007', 'E-ATX, ATX, Micro-ATX, Mini-ITX', 400.00), ('CAS008', 'E-ATX, ATX, Micro-ATX', 355.00), ('CAS009', 'ATX, Micro-ATX, Mini-ITX', 369.00),
('CAS010', 'E-ATX, ATX, Micro-ATX, Mini-ITX', 400.00), ('CAS011', 'E-ATX, ATX, Micro-ATX, Mini-ITX', 392.00), ('CAS012', 'E-ATX, ATX, Micro-ATX, Mini-ITX', 400.00),
('CAS013', 'ATX, Micro-ATX, Mini-ITX', 380.00), ('CAS014', 'ATX, Micro-ATX, Mini-ITX', 405.00), ('CAS015', 'ATX, Micro-ATX, Mini-ITX', 322.00),
('CAS016', 'ATX, Micro-ATX, Mini-ITX', 330.00), ('CAS017', 'ATX, Micro-ATX, Mini-ITX', 400.00), ('CAS018', 'ATX, Micro-ATX, Mini-ITX', 350.00),
('CAS019', 'ATX, Micro-ATX, Mini-ITX', 371.00), ('CAS020', 'Micro-ATX, Mini-ITX', 355.00);

INSERT INTO PSUS (product_id, wattage, form_factor) VALUES
('PSU001', 1000.00, 'ATX'), ('PSU002', 850.00, 'ATX'), ('PSU003', 650.00, 'ATX'), ('PSU004', 850.00, 'ATX'), 
('PSU005', 600.00, 'ATX'), ('PSU006', 750.00, 'ATX'), ('PSU007', 750.00, 'ATX'), ('PSU008', 850.00, 'ATX'), 
('PSU009', 650.00, 'ATX'), ('PSU010', 850.00, 'ATX'), ('PSU011', 750.00, 'ATX'), ('PSU012', 1000.00, 'ATX'), 
('PSU013', 850.00, 'ATX'), ('PSU014', 750.00, 'ATX'), ('PSU015', 650.00, 'ATX'), ('PSU016', 850.00, 'ATX'), 
('PSU017', 750.00, 'ATX'), ('PSU018', 850.00, 'ATX'), ('PSU019', 600.00, 'ATX'), ('PSU020', 550.00, 'ATX');

INSERT INTO STORAGE (product_id, form_factor, interface_type, capacity_gb) VALUES
('STO001', 'M.2 2280', 'NVMe PCIe 4.0', 2000), ('STO002', 'M.2 2280', 'NVMe PCIe 4.0', 1000), ('STO003', 'M.2 2280', 'NVMe PCIe 3.0', 1000), 
('STO004', 'M.2 2280', 'NVMe PCIe 4.0', 2000), ('STO005', 'M.2 2280', 'NVMe PCIe 4.0', 1000), ('STO006', 'M.2 2280', 'NVMe PCIe 4.0', 2000), 
('STO007', 'M.2 2280', 'NVMe PCIe 4.0', 1000), ('STO008', '2.5 inch', 'SATA III', 1000), ('STO009', '2.5 inch', 'SATA III', 1000), 
('STO010', '2.5 inch', 'SATA III', 1000), ('STO011', '2.5 inch', 'SATA III', 480), ('STO012', '2.5 inch', 'SATA III', 500), 
('STO013', '3.5 inch', 'SATA III', 2000), ('STO014', '3.5 inch', 'SATA III', 2000), ('STO015', '3.5 inch', 'SATA III', 1000), 
('STO016', '3.5 inch', 'SATA III', 4000), ('STO017', '3.5 inch', 'SATA III', 4000), ('STO018', 'M.2 2280', 'NVMe PCIe 4.0', 1000), 
('STO019', 'M.2 2280', 'NVMe PCIe 3.0', 512), ('STO020', 'M.2 2280', 'NVMe PCIe 3.0', 1000);

INSERT INTO COOLERS (product_id, cooler_type, supported_sockets) VALUES
('COL001', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL002', 'AIO Liquid Cooler', 'LGA1700, AM5, AM4'), ('COL003', 'Air Cooler', 'LGA1700, AM5, AM4'), 
('COL004', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL005', 'AIO Liquid', 'LGA1700, AM5, AM4'), ('COL006', 'AIO Liquid', 'LGA1700, AM5, AM4'),
('COL007', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL008', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL009', 'AIO Liquid', 'LGA1700, AM5, AM4'), 
('COL010', 'AIO Liquid', 'LGA1700, AM5, AM4'), ('COL011', 'AIO Liquid', 'LGA1700, AM5, AM4'), ('COL012', 'AIO Liquid', 'LGA1700, AM5, AM4'),
('COL013', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL014', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL015', 'Air Cooler', 'LGA1700, AM5, AM4'), 
('COL016', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL017', 'Air Cooler', 'LGA1700, AM5, AM4'), ('COL018', 'AIO Liquid', 'LGA1700, AM5, AM4'),
('COL019', 'AIO Liquid', 'LGA1700, AM5, AM4'), ('COL020', 'AIO Liquid', 'LGA1700, AM5, AM4');

-- 4. USERS & BUILDS
INSERT INTO USERS (user_id, name, email, password_hash, role) VALUES
('USR001', 'Admin Rahman', 'admin1@pcbuilder.com', 'hash123', 'Admin'), ('USR002', 'Admin Karim', 'admin2@pcbuilder.com', 'hash123', 'Admin'),
('USR003', 'Shanto', 'shanto@example.com', 'hash123', 'Customer'), ('USR004', 'Rahim', 'rahim@example.com', 'hash123', 'Customer'),
('USR005', 'Kamal', 'kamal@example.com', 'hash123', 'Customer'), ('USR006', 'Jamal', 'jamal@example.com', 'hash123', 'Customer'),
('USR007', 'Tarek', 'tarek@example.com', 'hash123', 'Customer'), ('USR008', 'Fahim', 'fahim@example.com', 'hash123', 'Customer'),
('USR009', 'Arafat', 'arafat@example.com', 'hash123', 'Customer'), ('USR010', 'Rafi', 'rafi@example.com', 'hash123', 'Customer'),
('USR011', 'Sabbir', 'sabbir@example.com', 'hash123', 'Customer'), ('USR012', 'Rakib', 'rakib@example.com', 'hash123', 'Customer'),
('USR013', 'Sajid', 'sajid@example.com', 'hash123', 'Customer'), ('USR014', 'Sakib', 'sakib@example.com', 'hash123', 'Customer'),
('USR015', 'Mehedi', 'mehedi@example.com', 'hash123', 'Customer'), ('USR016', 'Hasan', 'hasan@example.com', 'hash123', 'Customer'),
('USR017', 'Hossain', 'hossain@example.com', 'hash123', 'Customer'), ('USR018', 'Arif', 'arif@example.com', 'hash123', 'Customer'),
('USR019', 'Naim', 'naim@example.com', 'hash123', 'Customer'), ('USR020', 'Sumon', 'sumon@example.com', 'hash123', 'Customer');

INSERT INTO BUILDS (build_id, user_id, build_name, total_price, total_wattage) VALUES
('BLD001', 'USR003', 'Shanto Dream Build', 2500.00, 750.00), ('BLD002', 'USR004', 'Budget Gaming', 800.00, 400.00),
('BLD003', 'USR005', 'Video Editing Rig', 1800.00, 600.00), ('BLD004', 'USR006', 'Office PC', 500.00, 300.00),
('BLD005', 'USR007', 'Ultimate 4K Gaming', 3500.00, 850.00), ('BLD006', 'USR008', 'Mid-Range Beast', 1200.00, 500.00),
('BLD007', 'USR009', 'AMD Fanboy Build', 1500.00, 550.00), ('BLD008', 'USR010', 'Intel Workstation', 2200.00, 700.00),
('BLD009', 'USR011', 'Esports Machine', 900.00, 450.00), ('BLD010', 'USR012', 'Silent PC', 1100.00, 450.00),
('BLD011', 'USR013', 'RGB Everything', 1400.00, 600.00), ('BLD012', 'USR014', 'Mini-ITX Console Killer', 1300.00, 500.00),
('BLD013', 'USR015', 'Home Server', 700.00, 350.00), ('BLD014', 'USR016', 'Student Laptop Replacement', 600.00, 350.00),
('BLD015', 'USR017', '3D Modeling PC', 2800.00, 800.00), ('BLD016', 'USR018', 'Casual Gamer', 850.00, 450.00),
('BLD017', 'USR019', 'Streaming Setup', 1900.00, 650.00), ('BLD018', 'USR020', 'Cheap 1080p Build', 750.00, 400.00),
('BLD019', 'USR003', 'Shanto Second PC', 1000.00, 450.00), ('BLD020', 'USR004', 'Brother PC', 950.00, 450.00);

INSERT INTO BUILD_ITEMS (build_id, product_id, quantity) VALUES
('BLD001', 'CPU001', 1), ('BLD001', 'MB001', 1), ('BLD001', 'GPU001', 1), ('BLD001', 'RAM001', 2),
('BLD002', 'CPU015', 1), ('BLD002', 'MB015', 1), ('BLD002', 'GPU016', 1), ('BLD002', 'RAM003', 1),
('BLD003', 'CPU005', 1), ('BLD003', 'MB005', 1), ('BLD003', 'GPU012', 1), ('BLD003', 'RAM002', 2),
('BLD004', 'CPU008', 1), ('BLD004', 'MB009', 1), ('BLD004', 'STO010', 1), ('BLD004', 'RAM003', 1),
('BLD005', 'CPU011', 1), ('BLD005', 'MB011', 1), ('BLD005', 'GPU002', 1), ('BLD005', 'RAM001', 2);
