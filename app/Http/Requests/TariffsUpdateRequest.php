<?php

namespace App\Http\Requests;

use App\Models\UserTariff;
use Illuminate\Foundation\Http\FormRequest;

class TariffsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('user')->role->name;
        $actions = UserTariff::getActionsForRole($role);

        $rules = [
            'fixed_salary_per_day' => 'nullable|numeric|min:0',
        ];

        foreach ($actions as $action) {
            if ($action === 'Оклад') {
                continue;
            }

            $rules["tariffs.{$action}.type"] = 'nullable|in:fixed,per_meter,per_piece';
            $rules["tariffs.{$action}.per_meter"] = 'array';
            $rules["tariffs.{$action}.per_piece"] = 'array';
        }

        return $rules;
    }
}
