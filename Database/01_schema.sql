-- DROP DATABASE pc_builder_db;

CREATE DATABASE pc_builder_db;

USE pc_builder_db;

CREATE TABLE CATEGORIES (
    category_id VARCHAR(20) PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE PRODUCTS (
    product_id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    category_id VARCHAR(20),
    FOREIGN KEY (category_id) REFERENCES CATEGORIES(category_id) 
        ON UPDATE CASCADE 
        ON DELETE SET NULL
);

-- Note: ON DELETE SET NULL deya hoyeche jate kono category delete hoye geleo product ta database theke harie na jay, shudhu category faka hoye jay.

CREATE TABLE CPUS (
    product_id VARCHAR(30) PRIMARY KEY,
    socket_type VARCHAR(50) NOT NULL,
    core_count INT NOT NULL,
    tdp_watt DECIMAL(6, 2) NOT NULL,
    passmark_score INT,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

-- Logic Check: Ekhane ON DELETE CASCADE bebohar kora hoyeche. Er mane holo, admin jodi PRODUCTS table theke kono CPU delete kore dey, tahole ei CPUS table thekeo automatically shei row ta delete hoye jabe. Eta data redundancy ebang orphan data thekate khub kaje dey.

CREATE TABLE MOTHERBOARDS (
    product_id VARCHAR(30) PRIMARY KEY,
    socket_type VARCHAR(50) NOT NULL,
    form_factor VARCHAR(50) NOT NULL,
    supported_ram_type VARCHAR(20) NOT NULL,
    m2_slots INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE RAMS (
    product_id VARCHAR(30) PRIMARY KEY,
    ram_type VARCHAR(20) NOT NULL, -- e.g., DDR4, DDR5
    capacity_gb INT NOT NULL,
    speed_mhz INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE GPUS (
    product_id VARCHAR(30) PRIMARY KEY,
    length_mm DECIMAL(6, 2) NOT NULL, -- Casing clearance check korar jonno
    tdp_watt DECIMAL(6, 2) NOT NULL,  -- Total wattage hisab korar jonno
    passmark_score INT,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE CASINGS (
    product_id VARCHAR(30) PRIMARY KEY,
    supported_form_factors VARCHAR(255) NOT NULL, -- e.g., 'ATX, Micro-ATX, Mini-ITX'
    max_gpu_length_mm DECIMAL(6, 2) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE PSUS (
    product_id VARCHAR(30) PRIMARY KEY,
    wattage DECIMAL(6, 2) NOT NULL,
    form_factor VARCHAR(50) DEFAULT 'ATX',
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE STORAGE (
    product_id VARCHAR(30) PRIMARY KEY,
    form_factor VARCHAR(50) NOT NULL,    -- e.g., 'M.2 2280', '2.5 inch', '3.5 inch'
    interface_type VARCHAR(50) NOT NULL, -- e.g., 'NVMe PCIe 4.0', 'SATA III'
    capacity_gb INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

CREATE TABLE COOLERS (
    product_id VARCHAR(30) PRIMARY KEY,
    cooler_type VARCHAR(50) NOT NULL,        -- e.g., 'Air Cooler', 'AIO Liquid Cooler'
    supported_sockets VARCHAR(255) NOT NULL, -- e.g., 'LGA1700, AM5, AM4'
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

-- 11. USERS Table (Customer and Admin)
CREATE TABLE USERS (
    user_id VARCHAR(30) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Customer') DEFAULT 'Customer'
);

-- 12. BUILDS Table (User saved PC builds)
CREATE TABLE BUILDS (
    build_id VARCHAR(30) PRIMARY KEY,
    user_id VARCHAR(30) NOT NULL,
    build_name VARCHAR(100) NOT NULL,
    total_price DECIMAL(10, 2) DEFAULT 0.00,
    total_wattage DECIMAL(6, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

-- 13. BUILD_ITEMS Table (Junction table to store items in a build)
CREATE TABLE BUILD_ITEMS (
    build_item_id INT AUTO_INCREMENT PRIMARY KEY,
    build_id VARCHAR(30) NOT NULL,
    product_id VARCHAR(30) NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (build_id) REFERENCES BUILDS(build_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);

-- 14. PRICE_HISTORY_LOG Table (For Triggers)
CREATE TABLE PRICE_HISTORY_LOG (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(30) NOT NULL,
    old_price DECIMAL(10, 2) NOT NULL,
    new_price DECIMAL(10, 2) NOT NULL,
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(product_id) 
        ON UPDATE CASCADE 
        ON DELETE CASCADE
);
