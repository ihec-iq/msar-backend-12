<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackupAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:100'],
            'email' => ['nullable','email','max:150'],
            'telegram_id' => ['nullable','string','max:50'],
            'active' => ['boolean'],
            'notify_via' => ['array'],
            'notify_via.*' => ['in:telegram,email'],
        ];
    }
}
