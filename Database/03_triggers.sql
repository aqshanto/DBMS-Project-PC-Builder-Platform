DELIMITER //

CREATE TRIGGER after_product_price_update
AFTER UPDATE ON PRODUCTS
FOR EACH ROW
BEGIN
    -- Shudhu matro jodi dam (price) poriborton hoy, tobei log save hobe
    IF OLD.price <> NEW.price THEN
        INSERT INTO PRICE_HISTORY_LOG (product_id, old_price, new_price, change_date)
        VALUES (OLD.product_id, OLD.price, NEW.price, NOW());
    END IF;
END //

DELIMITER ;
