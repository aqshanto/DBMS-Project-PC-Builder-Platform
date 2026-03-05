-- View 1: Budget Gaming PC Build (AMD AM5 Platform)
CREATE VIEW View_Budget_Gaming_PC AS
SELECT 
    c.category_name AS 'Component', 
    p.name AS 'Product Name', 
    p.brand AS 'Brand', 
    p.price AS 'Price ($)'
FROM PRODUCTS p
JOIN CATEGORIES c ON p.category_id = c.category_id
WHERE p.product_id IN (
    'CPU015', -- Ryzen 5 7600 (AM5)
    'MB015',  -- B650M AORUS ELITE (AM5)
    'RAM004', -- Corsair Vengeance 32GB DDR5
    'GPU012', -- GeForce RTX 3060 12GB
    'CAS005', -- Fractal Design Meshify C
    'PSU009', -- Gigabyte P650B 650W
    'STO003', -- WD Blue 1TB NVMe
    'COL003'  -- Cooler Master Hyper 212
);

-- View 2: Office & Home PC Build (No Graphics Card needed, uses Integrated Graphics)
CREATE VIEW View_Office_PC AS
SELECT 
    c.category_name AS 'Component', 
    p.name AS 'Product Name', 
    p.brand AS 'Brand', 
    p.price AS 'Price ($)'
FROM PRODUCTS p
JOIN CATEGORIES c ON p.category_id = c.category_id
WHERE p.product_id IN (
    'CPU020', -- Ryzen 5 5600G (AM4 with integrated graphics)
    'MB020',  -- PRIME B450M-A II (AM4)
    'RAM020', -- Corsair Vengeance 16GB DDR4
    'CAS018', -- Cougar MX330-G
    'PSU020', -- Corsair CV550 550W
    'STO012', -- PNY CS900 500GB SATA
    'COL016'  -- ID-Cooling SE-224-XT
);
