<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupSettingsRequest;
use App\Models\BackupSetting;

class BackupSettingsController extends Controller
{
    public function show()
    {
        return BackupSetting::firstOrFail();
    }

    public function update(BackupSettingsRequest $request)
    {
        $s = BackupSetting::firstOrFail();
        $s->fill($request->validated())->save();
        return $s->refresh();
    }
}
