-- Scenario 1: Basic One-to-One Filtering (CPU ➡️ Motherboard)
-- Logic: Dhoro ekjon user prothome 'Core i7-14700K' (Product ID: CPU002) select koreche. Ebar se jokhon Motherboard er list dekhbe, database ke shudhu shei motherboard guloi dekhate hobe jader socket_type ei CPU er socket (LGA1700) er sathe match kore.
-- SQL Query:
SELECT 
    p.name AS 'Compatible Motherboard', 
    p.brand AS 'Brand', 
    p.price AS 'Price ($)', 
    m.socket_type AS 'Socket', 
    m.supported_ram_type AS 'RAM Support'
FROM MOTHERBOARDS m
JOIN PRODUCTS p ON m.product_id = p.product_id
JOIN CPUS c ON m.socket_type = c.socket_type
WHERE c.product_id = 'CPU002';

-- (Eta run korle dekhbe shudhu LGA1700 socket er ASUS, Gigabyte, MSI er motherboard gulo asche, kono AM4 ba AM5 socket er motherboard asbe na!)
-- Scenario 2: Omni-directional / Multi-Constraint Filtering (CPU + RAM ➡️ Motherboard)
-- Logic (The CEP Level): Eita hocche ashol Star Tech ba PCPartPicker er level er logic! Dhoro user non-linear bhabe age 'Core i7-14700K' (CPU002) ebang 'Corsair Vengeance DDR5' (RAM001) select kore feleche.
-- Ekhon tomader system ke emon Motherboard khujte hobe jeta eki sathe LGA1700 socket ebang DDR5 RAM support kore. Kono LGA1700 motherboard jodi DDR4 support kore, tobe sheta ei list theke filter out (bad) hoye jabe.
-- SQL Query:
SELECT 
    p_mobo.name AS 'Perfect Match Motherboard',
    p_mobo.brand AS 'Brand',
    m.socket_type AS 'CPU Socket',
    m.supported_ram_type AS 'RAM Type',
    p_mobo.price AS 'Price ($)'
FROM MOTHERBOARDS m
JOIN PRODUCTS p_mobo ON m.product_id = p_mobo.product_id
JOIN CPUS c ON m.socket_type = c.socket_type
JOIN RAMS r ON m.supported_ram_type = r.ram_type
WHERE c.product_id = 'CPU002' 
  AND r.product_id = 'RAM001';

-- (Eta run kore dekho, ager query te jekhane DDR4 motherboard gulo (jemon 'Z690 UD AX') eshechilo, ebar segulo automatically bad pore shudhu DDR5 gulo ashbe!)


-- Ebar Cholo Magic Ta Test Kori!
-- Tumi prothome kono ekta product er dam update korbe, ebang tarpor log table ta check korbe. Nicher query duto ekta ekta kore run kore dekho:
-- Step 1: Dam Update koro (Admin action):
-- Dhoro 'Core i9-14900K' (CPU001) er dam ekhon $600 ase. Amra dam komiye $550 kore dicchi.
UPDATE PRODUCTS 
SET price = 550.00 
WHERE product_id = 'CPU001';

-- Step 2: Log Table Check koro (Automatic result):
-- Ebar eita run kore dekho, tumi kintu log table e kichu insert koro nai, kintu trigger auto kaj koreche!

SELECT 
    p.name AS 'Product Name',
    l.old_price AS 'Old Price',
    l.new_price AS 'New Price',
    l.change_date AS 'Date Changed'
FROM PRICE_HISTORY_LOG l
JOIN PRODUCTS p ON l.product_id = p.product_id;
-- (Eta run korle dekhbe magic er moto log table e record toiri hoye geche je CPU001 er dam 600 theke 550 hoyeche!)

-- Ei code gulo run kore view toiri korar por, tumi just nicher ei ek line er query gulo run kore dekho. Dekhbe khub sundor table akare pura PC build ta show korche!
-- Budget Gaming PC dekhar jonno:

SELECT * FROM View_Budget_Gaming_PC;
-- Office PC dekhar jonno:
SELECT * FROM View_Office_PC;
-- (Eta jokhon sir ke dekhabe, tokhon explain korbe je "Sir, frontend e bar bar complex join query na chaliye amra ekta Virtual Table (View) toiri kore rekhechi, jate loading time onek fast hoy.")
