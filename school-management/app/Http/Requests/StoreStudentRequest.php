<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('students.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'monthly_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'registration_payment' => ['nullable', 'in:none,full,remaining'],
            'registration_remaining_amount' => ['nullable', 'numeric', 'min:0'],
            'registration_due_date' => ['nullable', 'date'],
            'monthly_fee_months' => ['nullable', 'array'],
            'monthly_fee_months.*' => ['date_format:Y-m'],
            'guardian.first_name' => ['required', 'string', 'max:100'],
            'guardian.last_name' => ['required', 'string', 'max:100'],
            'guardian.phone' => ['required', 'string', 'max:30'],
            'guardian.email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
