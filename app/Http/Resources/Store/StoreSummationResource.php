<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Expr\Cast\Double;

class StoreSummationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {


        return [
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'description' => $this->description,
            'code' => $this->code,
            'descriptionItem' => $this->itemDescription,
            'stockName' => $this->stockName,
            'categoryName' => $this->itemCategoryName,
            'price' => $this->price / 100,
            'count' => ((float) $this->countIn - (float) $this->countOut ) + ((float) $this->countReIn - (float) $this->countReOut) ,
            'countIn' => (float)$this->countIn,
            'countOut' => (float)$this->countOut,
            'countReIn' => (float)$this->countReIn,
            'countReOut' => (float)$this->countReOut
        ];
    }
}
