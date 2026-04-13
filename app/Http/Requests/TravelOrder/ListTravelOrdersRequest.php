<?php

namespace App\Http\Requests\TravelOrder;

use App\Domain\TravelOrder\ValueObjects\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTravelOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = array_column(TravelOrderStatus::cases(), 'value');

        return [
            'status'          => ['sometimes', 'string', Rule::in($validStatuses)],
            'destination'     => ['sometimes', 'string', 'max:255'],
            'departure_from'  => ['sometimes', 'date'],
            'departure_until' => ['sometimes', 'date', 'after_or_equal:departure_from'],
            'created_from'    => ['sometimes', 'date'],
            'created_until'   => ['sometimes', 'date', 'after_or_equal:created_from'],
        ];
    }
}
