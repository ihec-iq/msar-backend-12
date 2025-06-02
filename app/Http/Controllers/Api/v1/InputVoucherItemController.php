<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\InputVoucherItemRequest;
use App\Http\Resources\Voucher\InputVoucherItemHistoryResource;
use App\Http\Resources\Voucher\InputVoucherItemResource;
use App\Http\Resources\Voucher\InputVoucherItemResourceCollection;
use App\Http\Resources\Voucher\InputVoucherItemVSelectResource;
use App\Models\InputVoucherItem;
use App\Models\ItemStoreView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputVoucherItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return $this->ok(InputVoucherItemResource::collection(InputVoucherItem::all()));
    }

    /**
     * Display a Items For VSelect that show only available items.
     */
    public function getAvailableItemsVSelect(Request $request)
    {

        $results = ItemStoreView::whereRaw('countIn-countOut countReIn-countReOut+ >0')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut',
                'countReIn',
                'countReOut'
            );
        if (isset($request->storeId) && $request->storeId != "0")
            $results = $results->whereRaw('stockId=' . $request->storeId);
        if (isset($request->employeeId) && $request->employeeId != "0")
            $results = $results->whereRaw('employeeId=' . $request->employeeId);
        $results = $results->get();
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }
    public function getAvailableItemsVSelectByEmployeeId($employeeId)
    {
        $results = ItemStoreView::whereRaw('countIn-`countOut` + countReIn-countReOut>0')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut',
                'countReIn',
                'countReOut'
            );
        if (isset($employeeId) && $employeeId != "0")
            $results = $results->whereRaw('employeeId=' . $employeeId);
        $results = $results->get();
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }
    public function getAllItemsVSelect($storeId = "0")
    {
        Log::info($storeId);
        $results = ItemStoreView::whereRaw('countIn-countOut>0 || (countOut>0 && countIn)')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut'
            );
        if ($storeId <> "0")
            $results = $results->whereRaw('stockId=' . $storeId);
        $results = $results->get();
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }

    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('stockId') && $request->stockId != -1) {
            $filter_bill[] = ['stock_id', $request->stockId];
        }
        if (!$request->isNotFilled('itemId') && $request->itemId != -1) {
            $filter_bill[] = ['item_id', $request->itemId];
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_bill[] = ['description', 'like', '%' . $request->description . '%'];
        }
        if (!$request->isNotFilled('notes') && $request->notes != '') {
            $filter_bill[] = ['notes', 'like', '%' . $request->notes . '%'];
        }

        $data = InputVoucherItem::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new InputVoucherItemResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     * InputVoucherItemRequest
     */
    public function store(Request $request)
    {
        $data = InputVoucherItem::create([
            'input_voucher_id' => $request->inputVoucherId,
            'item_id' => $request->itemId,
            'description' => $request->description,
            'count' => $request->count,
            'price' => $request->price * 100,
            'value' => $request->price * $request->count * 100,
            'notes' => $request->notes,
        ]);

        return $this->ok(new InputVoucherItemResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(InputVoucherItem $inputVoucherItem)
    {
        return $this->ok(new InputVoucherItemHistoryResource($inputVoucherItem));
    }

    /**
     * Update the specified resource in storage.
     * InputVoucherItemRequest
     */
    public function update(Request $request, InputVoucherItem $inputVoucherItem)
    {

        $inputVoucherItem->input_voucher_id = $request->inputVoucherId;
        $inputVoucherItem->item_id = $request->itemId;
        $inputVoucherItem->description = $request->description;
        $inputVoucherItem->count = $request->count;
        $inputVoucherItem->price = $request->price * 100;
        $inputVoucherItem->value = $request->price * $request->count * 100;
        $inputVoucherItem->notes = $request->notes;
        $inputVoucherItem->save();

        return $this->ok(new InputVoucherItemResource($inputVoucherItem));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = InputVoucherItem::find($id);
        $data->delete();
        return $this->ok(null);
    }
}
