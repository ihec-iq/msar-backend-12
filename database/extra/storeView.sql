SELECT
    `input_voucher_items`.`id` AS `id`,
    `items`.`name` AS `itemName`,
    `items`.`id` AS `itemId`,
    `items`.`code` AS `code`,
    `items`.`description` AS `itemDescription`,
    `input_voucher_items`.`description` AS `description`,
    `input_voucher_items`.`notes` AS `notes`,
    `input_voucher_items`.`price` AS `price`,
    `item_categories`.`id` AS `itemCategoryId`,
    `item_categories`.`name` AS `itemCategoryName`,
    `stocks`.`id` AS `stockId`,
    `stocks`.`name` AS `stockName`,
    IFNULL(
        SUM(input_voucher_items.count),
        0
    ) AS inValue,
    IFNULL(
        SUM(output_voucher_items.count),
        0
    ) AS outValue
FROM
    `input_voucher_items`
LEFT JOIN `items` ON `input_voucher_items`.`item_id` = `items`.`id`
LEFT JOIN `item_categories` ON `items`.`item_category_id` = `item_categories`.`id`
LEFT JOIN `input_vouchers` ON `input_voucher_items`.`input_voucher_id` = `input_vouchers`.`id`
LEFT JOIN `stocks` ON `input_vouchers`.`stock_id` = `stocks`.`id`
LEFT JOIN `output_voucher_items` ON `input_voucher_items`.`id` = `output_voucher_items`.`input_voucher_item_id`
GROUP BY
    `input_voucher_items`.`id`,
    `items`.`name`,
    `items`.`id`,
    `items`.`code`,
    `items`.`description`,
    `input_voucher_items`.`description`,
    `input_voucher_items`.`notes`,
    `input_voucher_items`.`price`,
    `item_categories`.`id`,
    `item_categories`.`name`,
    `stocks`.`id`,
    `stocks`.`name`;
