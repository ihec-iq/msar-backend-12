<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Document\DocumentResource;
use App\Models\Archive;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DocumentResource::collection(Document::get());

        return $this->ok($data);
    }
    public function last()
    {
        $data = new DocumentResource(Document::latest()->first());

        return $this->ok($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!isset($request->file)) {
            return null;
        }

        //$url = Storage::put('public/documents', $request->file);
        $file = $request->file;
        $path = "public/archives/$request->archive_id";
        $url = Storage::putFileAs($path, $file, date('YmdHis') . $file->getClientOriginalName());
        $extension = $file->getClientOriginalExtension();
        $url = Storage::put('public/archives', $request->file);
        //$fullPath = Storage::path($url);
        $Document = Document::create([
            'path' => $url,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'extension' => $extension,
            'size' => Storage::size($url),
            'archive_id' => $request->archive_id,
            'user_id' => Auth::user()->id,
        ]);

        //Storage::delete($url);
        if (!$Document) {
            return null;
        }

        return $Document;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store_multi(Request $request, $documentable_id, $documentable_type, $pathFolder)
    {
        $files = $request->file('FilesDocument'); // Convert to array of files
        if (!$files || !is_array($files)) {
            return null;
        }

        $result = [];
        foreach ($files as $file) {
            // Add validation for file existence
            if (!$file->isValid()) {
                continue;
            }

            $path = "public/" . $pathFolder . "/$documentable_id";
            $filename = date('YmdHis') . $file->getClientOriginalName();

            $url = Storage::putFileAs($path, $file, $filename);

            $result[] = Document::create([
                'path' => $url,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(), // More reliable than Storage::size()
                'documentable_id' => $documentable_id,
                'documentable_type' => $documentable_type,
                'document_type_id' => 1,
                'user_id' => Auth::user()->id,
            ]);
        }

        return $result;
    }
    public function store_multi_hr(Request $request, $documentable_id, $documentable_type, $pathFolder)
    {
        //region "Delete log files"
        // $logFilePath = storage_path('logs/laravel.log');
        // file_put_contents($logFilePath, '');
        //endregion
        if ($request->file('FilesDocument') == false) {
            return null;
        }
        $result = [];
        foreach ($request->file('FilesDocument') as $file) {
            $path = "public/hr/" . $pathFolder . "";
            $url = Storage::putFileAs($path, $file, $documentable_id.date('YmdHis') . $file->getClientOriginalName());
            $extension = $file->getClientOriginalExtension();
            $result[] = Document::create([
                'path' => $url,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $extension,
                'size' => Storage::size($url),
                'documentable_id' => $documentable_id,
                'documentable_type' => $documentable_type,
                'document_type_id' => 1,
                'user_id' => Auth::user()->id,
            ]);
        }

        return $result;
    }
    public function store_one(Request $request, $documentable_id, $documentable_type, $pathFolder)
    {
        //region "Delete log files"
        // $logFilePath = storage_path('logs/laravel.log');
        // file_put_contents($logFilePath, '');
        //endregion
        if ($request->file('file') == false) {
            return null;
        }
        $file = $request->file('file');
        $path = "public/" . $pathFolder . "/$documentable_id";
        $url = Storage::putFileAs($path, $file, date('YmdHis') . $file->getClientOriginalName());
        $extension = $file->getClientOriginalExtension();
        $document = Document::create([
            'path' => $url,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'extension' => $extension,
            'size' => Storage::size($url),
            'documentable_id' => $documentable_id,
            'documentable_type' => $documentable_type,
            'document_type_id'=>1,
            'user_id' => Auth::user()->id,
        ]);
        return $document;
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Document::find($id);

        return $this->ok(new DocumentResource($data));
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
        $Document = Document::find($id);
        //$url = Storage::put('documents', $Document->path);
        //$result = Storage::delete($Document->path);
        $Document->delete();
        return true;
        //return $this->deleteFile($Document->path);
    }

    public function deleteFile($filePath)
    {
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);

            return response()->json(['message' => 'File deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'File not found'], 404);
        }
    }
}
