<?php

namespace App\Http\Requests;

use App\Models\Roll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveCollectMovementMaterialToWorkshopRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'material_id.*' => 'required|exists:materials,id',
            'quantity.*' => 'required|numeric|min:0',
            'roll_code.*' => 'required|string',
        ];
    }

    /**
     * Customize the error messages used by the validator.
     */
    public function messages(): array
    {
        return [
            'material_id.*.required' => 'Материал обязателен.',
            'material_id.*.exists' => 'Материал не найден.',
            'quantity.*.required' => 'Количество обязательно.',
            'quantity.*.min' => 'Количество должно быть больше нуля.',
            'roll_code.*.required' => 'ШК-рулона обязательно.',
            'roll_code.*.exists' => 'ШК-рулона не найден.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            $materialIds = $this->input('material_id');
            $rollCodes = $this->input('roll_code');

            foreach ($rollCodes as $index => $code) {

                $materialId = $materialIds[$index];

                $roll = Roll::where('roll_code', $code)
                    ->where('material_id', $materialId)
                    ->where('status', 'in_storage')
                    ->first();

                if (! $roll) {
                    $validator->errors()->add(
                        "roll_code.$index",
                        'Рулон "'.$code.'" не принадлежит данному материалу или не находится на складе'
                    );

                    continue;
                }

                $order = $roll->movementMaterials()->first()?->order;

                if ($order && $order->status != 3) {
                    $validator->errors()->add(
                        "roll_code.$index",
                        'Поставка с рулоном "'.$code.'" еще не принята админом.'
                    );
                }
            }
        });
    }
}
