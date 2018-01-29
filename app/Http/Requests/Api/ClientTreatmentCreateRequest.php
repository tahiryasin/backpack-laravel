<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientTreatmentCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'treatment_date' => 'sometimes|nullable|date',
            'client_image_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('client_images', 'id')->where('user_id', Auth::id()),
            ],
            'subjectable_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('subjectables', 'id'),
            ],
            'needle_module_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('needle_modules', 'id'),
            ],
            'mask_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('masks', 'id'),
            ],
            'home_care_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('home_cares', 'id'),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'title' => trans('models/client/treatment.title'),
            'description' => trans('models/client/treatment.description'),
            'treatment_date' => trans('models/client/treatment.treatment_date'),
            'client_image_id' => trans('models/client/image.model_name.singular'),
            'subjectable_id' => trans('models/subjectable.model_name.singular'),
            'needle_module_id' => trans('models/needle_module.model_name.singular'),
            'mask_id' => trans('models/mask.model_name.singular'),
            'home_care_id' => trans('models/home_care.model_name.singular'),
        ];
    }
}
