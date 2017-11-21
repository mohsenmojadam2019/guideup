<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Response;

class GalleryRequest extends FormRequest
{
    public function rules()
    {
        $rules = [];

        switch ($this->method()) {
            case 'POST':
                array_push($rules, [
                    'description' => 'required|max:250',
                    'position' => 'required|',
                    'file' => 'required|'
                ]);
                break;
            
            case 'PUT':
            case 'PATCH':
                array_push($rules, [
                'description' => 'sometimes|required|max:250',
                'position' => 'sometimes|required',
                'file' => 'sometimes|required'
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