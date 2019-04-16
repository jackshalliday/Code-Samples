<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

        'title_id' => 'required',
        'first_name' => 'required',
        'surname' => 'required',
        'telephone' => 'required',
        'category_id' => 'required',
        'user_id' => 'required',
        'address_1' => 'required',
        'address_2' => 'required',
        'address_town' => 'required',
        'address_county' => 'required',
        'address_postcode' => 'required',
        'franchise_id' => 'required',
        'area_id' => 'required',
        'delivery_day' => 'required',
        'round_id' => 'required',
        'timeslot_id' => 'required',
        'billing_1' => 'required',
        'billing_2' => 'required',
        'billing_town' => 'required',
        'billing_county' => 'required',
        'billing_postcode' => 'required',
        'payterm_id' => 'required',
        'primarysource_id'=> 'required',
        'created_by'=> 'required',
  
        ];
    }
}
