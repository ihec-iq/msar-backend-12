<?php

namespace App\Observers;

use App\Models\InputVoucherItem;
use App\Models\VoucherItemHistory;

class InputVoucherItemObserver
{
    /**
     * Handle the InputVoucherItem "created" event.
     */
    public function created(InputVoucherItem $inputVoucherItem): void
    {
        VoucherItemHistory::create([
            'input_voucher_item_id' => $inputVoucherItem->id,
            'item_id' => $inputVoucherItem->item_id,
            'voucher_item_historiable_id' => $inputVoucherItem->id,
            'voucher_item_historiable_type' => InputVoucherItem::class,
            'employee_id' => 1,
            'price' => $inputVoucherItem->price,
            'count' => $inputVoucherItem->count,
            'notes' => $inputVoucherItem->notes,
        ]);
    }

    /**
     * Handle the InputVoucherItem "updated" event.
     */
    public function updated(InputVoucherItem $inputVoucherItem): void
    {
        //
        $voucher = VoucherItemHistory::where([
            'input_voucher_item_id' => $inputVoucherItem->input_voucher_item_id,
            'voucher_item_historiable_id' => $inputVoucherItem->id,
            'item_id' => $inputVoucherItem->item_id,
            'voucher_item_historiable_type' => InputVoucherItem::class,
        ])->first();
        if ($voucher) {
            $voucher->employee_id = $inputVoucherItem->employee_id;
            $voucher->price = $inputVoucherItem->price;
            $voucher->count = $inputVoucherItem->count;
            $voucher->item_id = $inputVoucherItem->item_id;
            $voucher->save();
        }
    }

    /**
     * Handle the InputVoucherItem "deleted" event.
     */
    public function deleted(InputVoucherItem $inputVoucherItem): void
    {
        VoucherItemHistory::where([
            'input_voucher_item_id' => $inputVoucherItem->id,
            'item_id' => $inputVoucherItem->item_id,
            'voucher_item_historiable_id' => $inputVoucherItem->id,
            'voucher_item_historiable_type' => InputVoucherItem::class,
        ])->delete();
    }

    /**
     * Handle the InputVoucherItem "restored" event.
     */
    public function restored(InputVoucherItem $inputVoucherItem): void
    {
        VoucherItemHistory::create([
            'input_voucher_item_id' => $inputVoucherItem->id,
            'item_id' => $inputVoucherItem->item_id,
            'voucher_item_historiable_id' => $inputVoucherItem->id,
            'voucher_item_historiable_type' => InputVoucherItem::class,
            'employee_id' => $inputVoucherItem->employee_id,
            'price' => $inputVoucherItem->price,
            'count' => $inputVoucherItem->count  ,
            'notes' => $inputVoucherItem->notes,
        ]);
    }

    /**
     * Handle the InputVoucherItem "force deleted" event.
     */
    public function forceDeleted(InputVoucherItem $inputVoucherItem): void
    {
        //
    }
}
