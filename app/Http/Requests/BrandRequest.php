<?php

namespace App\Http\Requests;

use App\Models\Song;
use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            "name" => "required|string|max:255|unique:Brands",
            "logo" => "required|file|mimetypes:image/jpeg,image/png",
            "location" => "required|string|max:255",
        ];
    }
}
