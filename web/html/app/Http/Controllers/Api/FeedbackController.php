<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Response;
use App\Http\Requests;

use App\Http\Requests\FeedbackRequest;
use App\Repositories\FeedbackRepository;

class FeedbackController extends Controller {

    use \App\Traits\UserTrait;

    protected $feedbacks;

    protected $fields = ['name','email','description'/*,'latitude','longitude'*/];

    public function __construct(FeedbackRepository $feedbackRepository) {
        $this->feedbacks = $feedbackRepository;
    }

    public function index(Request $request) {
        try {   
            if($request->term != null && strlen($request->term) < 3) {
                return Response::json(['error' => [
                    'name' => 'search_minimal_size',
                    'message' => 'The minimal size for search is 3 caracters' 
                    ]
                ], 404);
            }

            return Response::json($this->feedbacks->search(
                $request->only(['term', 'email', 'count'])));
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                'name' => 'feedback_search_error',
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function show($id) {
        try {
            $feedback = $this->feedbacks->findByID($id);
            return Response::json($feedback, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'feedback_not_found',
                    'message' => 'Feedback not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'feedback_show_error',
				'message' => $e->getMessage()
				]
			], 500);
        }
    }

    public function store(FeedbackRequest $request) {
        try {
            $values = $request->only($this->fields);
            $feedback = $this->feedbacks->create($values);
            return Response::json($feedback, 201)
                ->header('location', route('feedback.store').'/'.$feedback->id);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'feedback_store_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function update(FeedbackRequest $request, $id) {
        try {
            $feedback = $this->feedbacks->findByID($id);
            $user = $this->getLoggedUser();
            if($user == null) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            $values = $request->only(['response']);
            //$values['user_id'] = $user->id;
            $feedback = $this->feedbacks->update($id, array_filter($values));
            return Response::json($feedback, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'feedback_not_found',
                    'message' => 'Feedback not found' 
                    ]
                ], 404);
        } 
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'feedback_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }
}
    