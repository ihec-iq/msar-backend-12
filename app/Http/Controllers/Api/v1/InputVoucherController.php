<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\InputVoucherStoreRequest;
use App\Http\Resources\Document\DocumentResource;
use App\Http\Resources\Voucher\InputVoucherResource;
use App\Http\Resources\Voucher\InputVoucherResourceCollection;
use App\Models\InputVoucher;
use App\Models\InputVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InputVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = InputVoucherResource::collection(InputVoucher::with('Items')->get());

        return $this->ok($data);
    }

    public function filter(Request $request)
    {

        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        // if (! $request->isNotFilled('sectionId') && $request->sectionId != -1) {
        //     $filter_bill[] = ['section_id', $request->sectionId];
        // }
        $data = InputVoucher::orderBy('id', 'desc');

        if (! $request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('number', 'like', '%'.$request->name.'%');
        }
        if (! $request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('signature_person', 'like', '%'.$request->name.'%');
        }
        if (! $request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('notes', 'like', '%'.$request->name.'%');

        }
        if (! $request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
            $data = $data->where('date', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        }
        $data = $data->paginate($limit);

        //return $data->toSql();
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new InputVoucherResourceCollection($data));
        }
    }

    public function store(InputVoucherStoreRequest $request)
    {
        $State = json_decode($request->State, true);
        $Stock = json_decode($request->Stock, true);
        $data = InputVoucher::create([
            'number' => $request->number,
            'date' => $request->date,
            'date_bill' => $request->dateBill,
            'number_bill' => $request->numberBill,
            'date_receive' => $request->dateReceive,
            'input_voucher_state_id' => $State['id'],
            'requested_by' => $request->requestedBy,
            'signature_person' => $request->signaturePerson,
            'notes' => $request->notes,
            'stock_id' => $Stock['id'],
            'user_create_id' => auth()->user()->id,
            'user_update_id' => auth()->user()->id,
        ]);
        $arrayItems = json_decode($request->Items, true);
        $arrayItemInsert = [];
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi(
                request: $request,
                documentable_id: $data->id,
                documentable_type: InputVoucher::class,
                pathFolder: InputVoucher::class
            );
        }
        foreach ($arrayItems as $key => $item) {
            //{"id":0,"input_voucher_id":0,"item":{"name":"","id":0,"code":"","description":"","itemCategory":{"id":0,"name":""},"measuringUnit":""},"stock":{"name":"","id":1},"description":"66666666","count":0,"price":0,"value":0,"notes":""}
            $newItem = new InputVoucherItem();
            $newItem->item_id = $item['Item']['id'];
            $newItem->description = $item['description'];
            $newItem->count = $item['count'];
            $newItem->notes = $item['notes'];
            $newItem->price = $item['price'] * 100;
            $newItem->value = $newItem->count * $newItem->price * 100;
            array_push($arrayItemInsert, $newItem);
        }
        $data->Items()->saveMany($arrayItemInsert);

        return $this->ok(new InputVoucherResource($data));
    }

    public function show(InputVoucher $inputVoucher)
    {
        //return  $inputVoucher;
        return $this->ok(new InputVoucherResource($inputVoucher));
    }

    public function update(InputVoucherStoreRequest $request, InputVoucher $inputVoucher)
    {
        $inputVoucher->number = $request->number;
        $inputVoucher->date = $request->date;
        $inputVoucher->date_receive = $request->dateReceive;
        $inputVoucher->date_bill = $request->dateBill;
        $inputVoucher->number_bill = $request->numberBill;
        $State = json_decode($request->State, true);
        $inputVoucher->input_voucher_state_id = $State['id'];
        $Stock = json_decode($request->Stock, true);
        $inputVoucher->stock_id = $Stock['id'];
        $inputVoucher->requested_by = $request->requestedBy;
        $inputVoucher->signature_person = $request->signaturePerson;
        $inputVoucher->notes = $request->notes;
        $inputVoucher->user_update_id = auth()->user()->id;

        $arrayItems = json_decode($request->Items, true);
        $arrayNewItemInsert = [];
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi(
                request: $request,
                documentable_id: $inputVoucher->id,
                documentable_type: InputVoucher::class,
                pathFolder: InputVoucher::class
            );
        }
        foreach ($arrayItems as $key => $item) {
            // item schema {"id":0,"input_voucher_id":0,"item":{"name":"","id":0,"code":"","description":"","itemCategory":{"id":0,"name":""},"measuringUnit":""},"stock":{"name":"","id":1},"description":"66666666","count":0,"price":0,"value":0,"notes":""}
            if ($item['id'] > 0) {
                // update already item immediately
                $newItem = InputVoucherItem::find($item['id']);
                $newItem->item_id = $item['Item']['id'];
                $newItem->description = $item['description'];
                $newItem->notes = $item['notes'];
                $newItem->count = $item['count'];
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price * 100;
                $newItem->save();
            } else {
                // for collect new items
                $newItem = new InputVoucherItem();
                $newItem->item_id = $item['Item']['id'];
                $newItem->description = $item['description'];
                $newItem->notes = $item['notes'];
                $newItem->count = $item['count'];
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price;
                array_push($arrayNewItemInsert, $newItem);
            }
        }
        // for save new items at once
        if (count($arrayNewItemInsert) > 0) {
            $inputVoucher->Items()->saveMany($arrayNewItemInsert);
        }

        $inputVoucher->save();

        return $this->ok(new InputVoucherResource($inputVoucher));
    }
    public function show_documents(string $id)
    {
        $data = InputVoucher::find($id);

        return $this->ok(DocumentResource::collection($data->Documents));
    }
    public function destroy(string $id)
    {
        $data = InputVoucher::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
