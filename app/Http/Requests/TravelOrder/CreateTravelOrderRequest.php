<?php

namespace App\Http\Requests\TravelOrder;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination'    => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date'    => ['required', 'date', 'after:departure_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_date.after_or_equal' => 'A data de ida não pode ser no passado.',
            'return_date.after'              => 'A data de volta deve ser posterior à data de ida.',
        ];
    }
}
