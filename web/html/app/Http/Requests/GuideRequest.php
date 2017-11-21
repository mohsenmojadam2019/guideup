<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Response;

class GuideRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
                'company' => 'max:50'
        ];

        switch ($this->method()) {
            case 'POST':
                array_push($rules, [
                    'user_id' => 'required|unique:users,id',
                    'number_consil' => 'required|max:25',
                    'phone' => 'required|number|max:20',
                    'password' => 'required|min:6',
                    'address.id' =>'required|',
                ]);
                break;
            
            case 'PUT':
            case 'PATCH':
                array_push($rules, [
                    'user_id' => 'sometimes|required|unique:users,id,'.$this->get('id') == '' ? '0' : $this->get('id'),
                    'number_consil' => 'sometimes|required|max:25',
                    'password' => 'sometimes|required|min:6',
                    'phone' => 'sometimes|required|number|max:20',
                ]);
                break;
        }
        return $rules;
    }

    public function response(array $errors)
    {
        return Response::json($errors, 422);
    }

    public function authorize()
    {
        // Only allow logged in users
        // return \Auth::check();
        // Allows all users in
        return true;
    }

    // OPTIONAL OVERRIDE
    public function forbiddenResponse()
    {
        // Optionally, send a custom response on authorize failure 
        // (default is to just redirect to initial page with errors)
        // 
        // Can return a response, a view, a redirect, or whatever else
        return Response::json('Permission denied!', 403);
    }
}