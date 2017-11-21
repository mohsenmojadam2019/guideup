<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Response;

class UserRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'phone' => 'max:20',
        ];

        switch ($this->method()) {
            case 'POST':
                array_push($rules, [
                    'name' => 'required|max:100',
                    'email' => 'required|email|max:50|unique:users,email',
                    'password' => 'required|min:6'
                ]);
                break;
            
            case 'PUT':
            case 'PATCH':
                array_push($rules, [
                    'name' => 'sometimes|required|max:100',
                    'email' => 'sometimes|required|email|max:50|unique:users,email,'.$this->get('id'),
                    'password' => 'sometimes|min:6'
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