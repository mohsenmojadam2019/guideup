<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

use Response;

use App\Http\Requests;
use App\Http\Requests\ReviewRequest;
use App\Repositories\ReviewRepository;

class ReviewController extends Controller {

    protected $fields = ['title', 'description', 'score', 'reply', 'guide_id', 'place_id'];
    protected $reviews;

    public function __construct(ReviewRepository $reviewRepository) {
        $this->reviews = $reviewRepository;
        
        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }
    
    public function index(Request $request) {
        try {
            $values = $request->only(['user_id', 'guide_id', 'place_id']);

            $reviews = $this->reviews->search($values);
            return Response::json($reviews,200);
        }
        catch(\Exception $e)
        {
            return Response::json(['error' => [
                    'name' => 'review_index_error',
                    'message' => $e->getMessage(),
                    ]
                ], 500);
        }
    }

    public function show($id)  {
        try {
            $review = $this->reviews->findByID($id);
            return Response::json($review,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'review_not_found',
                    'message' => 'Review not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function store(ReviewRequest $request) {
        try {
            if(!Auth::check()) 
            {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }

            $values = $request->only($this->fields);
            $values['user_id'] = Auth::user()->id;
            $review = $this->reviews->create($values);
            return Response::json($review, 201)
                        ->header('location', route('review.store').'/'.$review->id);
        }
        catch(\Exception $e)
        {
            return Response::json(['error' => [
				'name' => 'review_store_error',
				'message' => $e->getMessage()
				]
			], 500);
        }
    }

    public function update(ReviewRequest $request, $id) {
        try {
            $review = $this->reviews->findByID($id);
            if(!Auth::user()->can('update', $review)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            $values = $request->only($this->fields);
            $values['user_id'] = Auth::user()->id;
            $review = $this->reviews->update($id, array_filter($values));
            return Response::json($review, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'review_not_found',
                    'message' => 'Review not found' 
                    ]
                ], 404);
        } 
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'review_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }
    
    public function destroy(Request $request, $id) {
        try {
            $review = $this->reviews->findByID($id);
            if(!Auth::user()->can('delete', $review)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            $this->reviews->delete($review);
            return Response::json(["ok" => true], 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'review_not_found',
                    'message' => 'Review not found' 
                    ]
                ], 404);
        }
        catch (\Exception $e)
        {
            return Response::json([
                'error' => [
                    'name' => 'review_delete_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }     
    }
}
