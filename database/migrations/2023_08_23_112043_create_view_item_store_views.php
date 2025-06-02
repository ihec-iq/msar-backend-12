<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //DB::statement('drop view item_store_views');
        DB::statement('DROP VIEW IF EXISTS item_store_views');

        DB::statement('
        CREATE  view item_store_views as
        select
        `input_voucher_items`.`id` as `id`,
        `items`.`name` as `itemName`,
        `items`.`id` as `itemId`,
        `items`.`code` as `code`,
        `items`.`description`as `itemDescription`,
        `input_voucher_items`.`description` as `description`,
        `input_voucher_items`.`notes` as `notes`,
        `input_voucher_items`.`price` as `price`,
        `item_categories`.`id` as `itemCategoryId`,
        `item_categories`.`name` as `itemCategoryName`,
        `stocks`.`id` as `stockId`,
        `stocks`.`name` as `stockName`,
        employees.id as `employeeId`,employees.name as `employeeName`,
        IFNULL(SUM(input_voucher_items.count),0) as `countIn`,
        IFNULL(SUM(output_voucher_items.count),0) as `countOut`,
        IFNULL(SUM(ReIn.count),0) as `countReIn`,
        IFNULL(SUM(ReOut.count),0) as `countReOut`
        from `input_voucher_items`
        inner join `input_vouchers` on `input_voucher_items`.`input_voucher_id` = `input_vouchers`.`id`
        inner join `stocks` on `input_vouchers`.`stock_id` = `stocks`.`id`
        inner join `items` on `input_voucher_items`.`item_id` = `items`.`id`
        inner join `item_categories` on `items`.`item_category_id` = `item_categories`.`id`
        left join `output_voucher_items` on `input_voucher_items`.`id`=`output_voucher_items`.`input_voucher_item_id`
        left join `employees` on `employees`.`id`=`output_voucher_items`.`employee_id`
        left join `retrieval_voucher_items` ReIn on `input_voucher_items`.`id`=ReIn.`input_voucher_item_id` and  ReIn.retrieval_voucher_item_type_id in (1)
        left join `retrieval_voucher_items` ReOut on `input_voucher_items`.`id`=ReOut.`input_voucher_item_id` and  ReOut.retrieval_voucher_item_type_id not in (1)
        group by
        `input_voucher_items`.`id`,
        `items`.`name`,`items`.`id`,
        `items`.`code`,
        `items`.`description`,
        `input_voucher_items`.`description`,
        `input_voucher_items`.`notes`,
        `input_voucher_items`.`price`,
        `item_categories`.`id`,
        `item_categories`.`name`,
        `stocks`.`id`,
        `stocks`.`name`,
        employees.id,employees.name
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS item_store_views');
    }
};
