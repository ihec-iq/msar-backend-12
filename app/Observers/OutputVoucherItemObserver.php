<?php

namespace App\Observers;

use App\Models\OutputVoucherItem;
use App\Models\VoucherItemHistory;

class OutputVoucherItemObserver
{
    /**
     * Handle the OutputVoucherItem "created" event.
     */
    public function created(OutputVoucherItem $outputVoucherItem): void
    {
        VoucherItemHistory::create([
            'input_voucher_item_id' => $outputVoucherItem->input_voucher_item_id,
            'item_id' => $outputVoucherItem->item_id,
            'voucher_item_historiable_id' => $outputVoucherItem->id,
            'voucher_item_historiable_type' => OutputVoucherItem::class,
            'employee_id' => $outputVoucherItem->employee_id,
            'price' => $outputVoucherItem->price,
            'count' => $outputVoucherItem->count * -1,
            'notes' => $outputVoucherItem->notes,
        ]);
    }

    /**
     * Handle the OutputVoucherItem "updated" event.
     */
    public function updated(OutputVoucherItem $outputVoucherItem): void
    {
        //
        $voucher = VoucherItemHistory::where([
            'input_voucher_item_id' => $outputVoucherItem->input_voucher_item_id,
            'item_id' => $outputVoucherItem->item_id,
            'voucher_item_historiable_id' => $outputVoucherItem->id,
            'voucher_item_historiable_type' => OutputVoucherItem::class,
        ])->first();
        if ($voucher) {
            $voucher->employee_id = $outputVoucherItem->employee_id;
            $voucher->price = $outputVoucherItem->price;
            $voucher->count = $outputVoucherItem->count * -1;
            $voucher->notes = $outputVoucherItem->notes;
            $voucher->item_id = $outputVoucherItem->item_id;
            $voucher->save();
        }
    }

    /**
     * Handle the OutputVoucherItem "deleted" event.
     */
    public function deleted(OutputVoucherItem $outputVoucherItem): void
    {
        VoucherItemHistory::where([
            'input_voucher_item_id' => $outputVoucherItem->input_voucher_item_id,
            'item_id' => $outputVoucherItem->item_id,
            'voucher_item_historiable_id' => $outputVoucherItem->id,
            'voucher_item_historiable_type' => OutputVoucherItem::class,
        ])->delete();
    }

    /**
     * Handle the OutputVoucherItem "restored" event.
     */
    public function restored(OutputVoucherItem $outputVoucherItem): void
    {
        VoucherItemHistory::create([
            'input_voucher_item_id' => $outputVoucherItem->input_voucher_item_id,
            'item_id' => $outputVoucherItem->item_id,
            'voucher_item_historiable_id' => $outputVoucherItem->id,
            'voucher_item_historiable_type' => OutputVoucherItem::class,
            'employee_id' => $outputVoucherItem->employee_id,
            'price' => $outputVoucherItem->price,
            'count' => $outputVoucherItem->count * -1,
            'notes' => $outputVoucherItem->notes,
        ]);
    }

    /**
     * Handle the OutputVoucherItem "force deleted" event.
     */
    public function forceDeleted(OutputVoucherItem $outputVoucherItem): void
    {
        //
    }
}
