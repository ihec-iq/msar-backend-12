<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\VoucherItemHistoryResource;
use App\Http\Resources\Voucher\VoucherItemHistoryResourceCollection;
use App\Models\VoucherItemHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherItemHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(VoucherItemHistoryResource::collection(VoucherItemHistory::all()));
    }

    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        // if (! $request->isNotFilled('name') && $request->name != '') {
        //     $filter_bill[] = ['name', 'like', '%'.$request->name.'%'];
        // }
        if (
            !$request->isNotFilled('inputVoucherItemId') &&
            $request->inputVoucherItemId != ''
            && $request->inputVoucherItemId != '0'
        ) {
            $filter_bill[] = ['input_voucher_item_id', $request->inputVoucherItemId];
        }
        if (!$request->isNotFilled('employeeId') && $request->employeeId != '' && $request->employeeId != '0') {
            $filter_bill[] = ['employee_id', $request->employeeId];
        }
        $filter_bill[] = ['voucher_item_historiable_type', '!=', 'App\\Models\\InputVoucherItem'];
        //return ($filter_bill);

        $data = VoucherItemHistory::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        //$data = VoucherItemHistory::orderBy('id', 'desc')->paginate($limit);
        //Log::alert($data->toArray());

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new VoucherItemHistoryResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
