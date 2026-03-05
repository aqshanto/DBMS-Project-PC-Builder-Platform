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

INSERT INTO PRODUCTS (product_id, name, brand, price, stock_quantity, category_id) VALUES
-- 20 CPUs
('CPU001', 'Core i9-14900K', 'Intel', 600.00, 15, 'CAT01'),
('CPU002', 'Core i7-14700K', 'Intel', 400.00, 20, 'CAT01'),
('CPU003', 'Core i5-14600K', 'Intel', 300.00, 25, 'CAT01'),
('CPU004', 'Core i9-13900K', 'Intel', 550.00, 10, 'CAT01'),
('CPU005', 'Core i7-13700K', 'Intel', 380.00, 18, 'CAT01'),
('CPU006', 'Core i5-13600K', 'Intel', 280.00, 30, 'CAT01'),
('CPU007', 'Core i5-13400F', 'Intel', 200.00, 40, 'CAT01'),
('CPU008', 'Core i3-13100F', 'Intel', 110.00, 50, 'CAT01'),
('CPU009', 'Core i9-12900K', 'Intel', 400.00, 12, 'CAT01'),
('CPU010', 'Core i7-12700K', 'Intel', 290.00, 22, 'CAT01'),
('CPU011', 'Ryzen 9 7950X', 'AMD', 600.00, 15, 'CAT01'),
('CPU012', 'Ryzen 9 7900X', 'AMD', 450.00, 20, 'CAT01'),
('CPU013', 'Ryzen 7 7800X3D', 'AMD', 400.00, 25, 'CAT01'),
('CPU014', 'Ryzen 5 7600X', 'AMD', 230.00, 35, 'CAT01'),
('CPU015', 'Ryzen 5 7600', 'AMD', 200.00, 40, 'CAT01'),
('CPU016', 'Ryzen 9 5950X', 'AMD', 450.00, 10, 'CAT01'),
('CPU017', 'Ryzen 7 5800X3D', 'AMD', 320.00, 15, 'CAT01'),
('CPU018', 'Ryzen 7 5700X', 'AMD', 190.00, 20, 'CAT01'),
('CPU019', 'Ryzen 5 5600X', 'AMD', 160.00, 45, 'CAT01'),
('CPU020', 'Ryzen 5 5600G', 'AMD', 140.00, 50, 'CAT01'),

-- 20 Motherboards
('MB001', 'ROG Maximus Z790 Hero', 'ASUS', 600.00, 5, 'CAT02'),
('MB002', 'Z790 AORUS ELITE AX', 'Gigabyte', 250.00, 15, 'CAT02'),
('MB003', 'MAG B760 TOMAHAWK WIFI', 'MSI', 200.00, 20, 'CAT02'),
('MB004', 'PRIME B760M-A', 'ASUS', 140.00, 30, 'CAT02'),
('MB005', 'Z690 UD AX', 'Gigabyte', 180.00, 10, 'CAT02'),
('MB006', 'PRO B660M-A WIFI', 'MSI', 150.00, 25, 'CAT02'),
('MB007', 'ROG STRIX Z790-E', 'ASUS', 450.00, 8, 'CAT02'),
('MB008', 'B760M DS3H', 'Gigabyte', 120.00, 35, 'CAT02'),
('MB009', 'H610M-K', 'ASUS', 90.00, 50, 'CAT02'),
('MB010', 'PRO H610M-G', 'MSI', 85.00, 55, 'CAT02'),
('MB011', 'ROG CROSSHAIR X670E HERO', 'ASUS', 650.00, 5, 'CAT02'),
('MB012', 'X670E AORUS MASTER', 'Gigabyte', 480.00, 8, 'CAT02'),
('MB013', 'MAG B650 TOMAHAWK WIFI', 'MSI', 220.00, 20, 'CAT02'),
('MB014', 'TUF GAMING B650-PLUS', 'ASUS', 210.00, 22, 'CAT02'),
('MB015', 'B650M AORUS ELITE', 'Gigabyte', 180.00, 25, 'CAT02'),
('MB016', 'PRO B650M-A', 'MSI', 160.00, 30, 'CAT02'),
('MB017', 'ROG STRIX B550-F', 'ASUS', 180.00, 15, 'CAT02'),
('MB018', 'B550 AORUS ELITE V2', 'Gigabyte', 150.00, 25, 'CAT02'),
('MB019', 'MAG B550 TOMAHAWK', 'MSI', 170.00, 20, 'CAT02'),
('MB020', 'PRIME B450M-A II', 'ASUS', 80.00, 40, 'CAT02');


INSERT INTO CPUS (product_id, socket_type, core_count, tdp_watt, passmark_score) VALUES
('CPU001', 'LGA1700', 24, 125.00, 60000), ('CPU002', 'LGA1700', 20, 125.00, 53000),
('CPU003', 'LGA1700', 14, 125.00, 40000), ('CPU004', 'LGA1700', 24, 125.00, 59000),
('CPU005', 'LGA1700', 16, 125.00, 45000), ('CPU006', 'LGA1700', 14, 125.00, 38000),
('CPU007', 'LGA1700', 10, 65.00, 25000),  ('CPU008', 'LGA1700', 4, 58.00, 15000),
('CPU009', 'LGA1700', 16, 125.00, 41000), ('CPU010', 'LGA1700', 12, 125.00, 34000),
('CPU011', 'AM5', 16, 170.00, 63000),     ('CPU012', 'AM5', 12, 170.00, 52000),
('CPU013', 'AM5', 8, 120.00, 35000),      ('CPU014', 'AM5', 6, 105.00, 28000),
('CPU015', 'AM5', 6, 65.00, 26000),       ('CPU016', 'AM4', 16, 105.00, 46000),
('CPU017', 'AM4', 8, 105.00, 28000),      ('CPU018', 'AM4', 8, 65.00, 26000),
('CPU019', 'AM4', 6, 65.00, 22000),       ('CPU020', 'AM4', 6, 65.00, 19000);


INSERT INTO MOTHERBOARDS (product_id, socket_type, form_factor, supported_ram_type, m2_slots) VALUES
('MB001', 'LGA1700', 'ATX', 'DDR5', 5), ('MB002', 'LGA1700', 'ATX', 'DDR5', 4),
('MB003', 'LGA1700', 'ATX', 'DDR5', 3), ('MB004', 'LGA1700', 'Micro-ATX', 'DDR5', 2),
('MB005', 'LGA1700', 'ATX', 'DDR4', 3), ('MB006', 'LGA1700', 'Micro-ATX', 'DDR4', 2),
('MB007', 'LGA1700', 'ATX', 'DDR5', 4), ('MB008', 'LGA1700', 'Micro-ATX', 'DDR4', 2),
('MB009', 'LGA1700', 'Micro-ATX', 'DDR4', 1), ('MB010', 'LGA1700', 'Micro-ATX', 'DDR4', 1),
('MB011', 'AM5', 'E-ATX', 'DDR5', 5),   ('MB012', 'AM5', 'E-ATX', 'DDR5', 4),
('MB013', 'AM5', 'ATX', 'DDR5', 3),     ('MB014', 'AM5', 'ATX', 'DDR5', 3),
('MB015', 'AM5', 'Micro-ATX', 'DDR5', 2), ('MB016', 'AM5', 'Micro-ATX', 'DDR5', 2),
('MB017', 'AM4', 'ATX', 'DDR4', 2),     ('MB018', 'AM4', 'ATX', 'DDR4', 2),
('MB019', 'AM4', 'ATX', 'DDR4', 2),     ('MB020', 'AM4', 'Micro-ATX', 'DDR4', 1);
