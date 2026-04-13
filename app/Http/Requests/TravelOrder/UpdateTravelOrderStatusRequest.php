<?php

namespace App\Http\Requests\TravelOrder;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([TravelOrderStatus::Approved->value, TravelOrderStatus::Cancelled->value]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'O status deve ser "aprovado" ou "cancelado".',
        ];
    }
}
