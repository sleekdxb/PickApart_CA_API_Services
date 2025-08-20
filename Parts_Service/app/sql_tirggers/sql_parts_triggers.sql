DELIMITER $$

CREATE TRIGGER update_total_clicks
AFTER UPDATE ON impressions
FOR EACH ROW
BEGIN
    DECLARE totalClicks INT;
    
    -- Get the sum of clicks from the impression table for the corresponding part_id
    SELECT SUM(clicks) INTO totalClicks
    FROM impressions
    WHERE parts.part_id = impressions.part_id;  -- Matching part_id between impression and parts
    
    -- Update the parts table with the total clicks for the same part_id
    UPDATE parts
    SET total_clicks = totalClicks
    WHERE parts.part_id = impressions.part_id;  -- Ensuring part_id matches
END $$

DELIMITER ;
