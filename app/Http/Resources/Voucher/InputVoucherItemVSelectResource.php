<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputVoucherItemVSelectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inputVoucherId' => 0,
            'Item' => [
                'id' => $this->itemId,
                'name' => $this->itemName,
                'code' => $this->code,
                'description' => $this->ItemDescription,
                'Category' => [
                    'id' => $this->itemCategoryId,
                    'name' => $this->itemCategoryName,
                ],
                'measuringUnit' => '',
            ],
            'count' => $this->countIn - $this->countOut + $this->countReIn - $this->countReOut,
            'countIn' => $this->countIn,
            'countOut' => $this->countOut,
            'countReIn' => $this->countReIn,
            'countReOut' => $this->countReOut,
            'Stock' => [
                'id' => $this->stockId,
                'name' => $this->stockName,
            ],
            'description' => $this->description,
            'price' => $this->price / 100,
            'value' => ($this->price * ($this->countIn - $this->countOut + $this->countReIn - $this->countReOut)) / 100,
            'notes' => $this->notes,
        ];
    }
}
