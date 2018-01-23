<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Response;
use App\Http\Requests;

use App\Http\Requests\CategoryRequest;
use App\Repositories\CategoryRepository;

class CategoryController extends Controller {

    use \App\Traits\UserTrait;

    protected $categories;

    public function __construct(CategoryRepository $categoryRepository) {
        $this->categories = $categoryRepository;
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

            return Response::json($this->categories->search(
                $request->only(['name'])));
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                'name' => 'category_search_error',
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function show($id) {
        try {
            $category = $this->categories->findByID($id);
            return Response::json($category, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'category_not_found',
                    'message' => 'Category not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'category_show_error',
				'message' => $e->getMessage()
				]
			], 500);
        }
    }

    public function store(CategoryRequest $request) {
        try {
            $values = $request->only(['name']);
            $category = $this->categories->create($values);
            return Response::json($category, 201)
                ->header('location', route('category.store').'/'.$category->id);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'category_store_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function update(CategoryRequest $request, $id) {
        try {
            $category = $this->categories->findByID($id);
            $values = $request->only(['name']);
            $category = $this->categories->update($id, array_filter($values));
            return Response::json($category, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'category_not_found',
                    'message' => 'Category not found' 
                    ]
                ], 404);
        } 
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'category_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }
}
    