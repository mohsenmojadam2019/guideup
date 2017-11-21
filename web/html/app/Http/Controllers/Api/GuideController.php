<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Response;

use App\Http\Requests\GuideRequest;
use App\Repositories\GuideRepository;
use App\Repositories\PlaceRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\ReviewRepository;

class GuideController extends Controller {    
    use \App\Traits\UserTrait;

    protected $guides;
    protected $places;
    protected $galleries;
    protected $reviews;

    protected $fields = ['phone', 'email', 'file', 'company', 'number_consil', 'address', 'busy', 'description', 'latitude', 'longitude', 'user_id', 'places', 'languages'];

    public function __construct(GuideRepository $guideRepository,
                                PlaceRepository $placeRepository,
                                GalleryRepository $galleryRepository,
                                ReviewRepository $reviewRepository) {
        $this->guides = $guideRepository;
        $this->places = $placeRepository;
        $this->galleries = $galleryRepository;
        $this->reviews = $reviewRepository;
        
        $this->middleware('auth:api', ['only' => ['delete', 'update']]);
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
			
			$fields = ['id', 'number_consil', 'avatar', 'company', 'latitude', 'longitude', 'busy', 'user_id', 'address_id'];
			$guard = Auth::guard('api');
			
			$is_admin = ($guard->check() && $guard->user()->is_admin == 1);
			
			if($is_admin) {
				$fields = ['*'];
			}
			
            $guide = Response::json($this->guides->search(
                $request->only(['term', 'latitude', 'longitude', 'distance', 'total', 'place_id', 'city_id', 'count']),
                $fields));

            return $guide;
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                'name' => 'guide_search_error',
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function show($id)  {
        try {
            $guide = $this->guides->findByID($id, ['places' => function($query) {
                $query
                ->select('id', 'name', 'latitude', 'longitude', 'cover', 'city_id', 'state_id', 'country_id', 'address')
                ->with(['city' => function($q1) {
                    $q1->select(['id', 'name']);
                },
                'state' => function($q2) {
                    $q2->select(['id', 'name']);
                }, 
                'country' => function($q3) {
                    $q3->select(['id', 'name']);
                }]);
            },
            'galleries' => function($query) {
                $query->select('id', 'image', 'guide_id');
            },
            'reviews']);
            return Response::json($guide, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_show_error',
				'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
				]
			], 500);
        }
    }

    public function store(GuideRequest $request) {
        try {
            if($this->guides->existsForUser($request->user_id)) {
                return Response::json(['error' => [
                    'name' => 'guide_store_error',
                    'message' => 'This user already has a guide profile' 
                    ]
                ], 403);
            }
            $values = $request->only($this->fields);
            $guide = $this->guides->create($values);
            return Response::json($guide, 201)
                ->header('location', route('guide.store').'/'.$guide->id);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'guide_store_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function update(GuideRequest $request, $id) {
        try {
            $guide = $this->guides->findByID($id);
            if(!Auth::user()->can('update', $guide)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }

            $values = $request->only($this->fields);
            $guide = $this->guides->update($id, array_filter($values));
            return Response::json($guide, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ], 404);
        } 
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'guide_update_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function destroy($id) {
        try {
            $guide = $this->guides->findByID($id);
            if(!Auth::user()->can('delete', $guide)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }

            $this->guides->delete($guide);
            return Response::json(["ok" => true], 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ], 404);
        }
        catch (\Exception $e)
        {
            return Response::json([
                'error' => [
                    'name' => 'guide_delete_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
    
    public function placeIndex(Request $request, $guide_id) {
        try {
            $values = $request->only(['page', 'total', 'term', 'city_id', 'state_id', 'country_id', 'latitude', 'longitude', 'distance']);
            $places = $this->places->search(['guide_id' => $guide_id]);
            return Response::json($places,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_place_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function placeShow($guide_id, $place_id) {
        try {
            $place = $this->places->findByID($place_id);
            if($place == null || $place->guide_id != $guide_id) {
                return Response::json(['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Place not found'
                    ]
                ],404);
            }
            return Response::json($place,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Place not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_place_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function galleryIndex(Request $request, $guide_id) {
        try {
            $galleries = $this->galleries->search(['guide_id' => $guide_id]);
            return Response::json($galleries,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function galleryShow($guide_id, $gallery_id) {
        try {
            $gallery = $this->galleries->findByID($gallery_id);
            if($gallery == null || $gallery->guide_id != $guide_id) {
                return Response::json(['error' => [
                    'name' => 'gallery_not_found',
                    'message' => 'Gallery not found'
                    ]
                ],404);
            }
            return Response::json($gallery,200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'gallery_not_found',
                    'message' => 'Gallery not found' 
                    ]
                ], 404);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewIndex(Request $request, $guide_id) {
        try {
            $reviews = $this->reviews->search(['guide_id' => $guide_id]);
            return Response::json($reviews,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'guide_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewShow($guide_id, $review_id) {
        try {
            $review = $this->reviews->findByID($review_id);
            if($review == null || $review->guide_id != $guide_id) {
                return Response::json(['error' => [
                    'name' => 'review_not_found',
                    'message' => 'Review not found'
                    ]
                ],404);
            }
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

    /*public function getByLocation(Request $request) 
    {
        $statusCode = 200;
        $response = [];
        try
        {
            if($request->latitude != null && $request->latitude != 0 && $request->longitude != null && $request->longitude != 0) {
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                
                $bound = $this->getBoundsByRadius($latitude,$longitude, $distance);
                $guides = Guide::whereBetween('latitude',[$bound['S']['lat'], $bound['N']['lat']])
                                ->whereBetween('longitude',[$bound['W']['lng'], $bound['E']['lng']])
                                ->get();

                foreach($guides as $guide) {
                    $dist = $this->distance($latitude, $longitude, $guide->latitude, $guide->longitude);
                        if ($dist <= $distance) {
                        array_push($response, $guide);
                    }
                }
            } else {
                $response = [
                    'error' => [
                        'name' => 'guide_by_location_error',
                        'message' => "Latitude and longitude not informed"
                    ]
                ];
                $statusCode = 400;
            }
        }
        catch(Exception $e)
        {
            $response = [
                'error' => [
                    'name' => 'guide_by_location_error',
                    'message' => $e->getMessage()
                ]
            ];
            $statusCode = 500;
        }
        finally
        {
            return Response::json($response,$statusCode);
        }
    }

    public function countLocation(Request $request) 
    {
        $statusCode = 200;
        $response = [];
        try
        {
            if($request->latitude != null && $request->latitude != 0 && $request->longitude != null && $request->longitude != 0) {
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                
                $bound = $this->getBoundsByRadius($latitude,$longitude, $distance);
                $count = Guide::whereBetween('latitude',[$bound['S']['lat'], $bound['N']['lat']])
                                ->whereBetween('longitude',[$bound['W']['lng'], $bound['E']['lng']])
                                ->count();
                $response['count'] = $count;
             } else {
                $response = [
                    'error' => [
                        'name' => 'guide_by_location_error',
                        'message' => "Latitude and longitude not informed"
                    ]
                ];
                $statusCode = 400;
            }
        }
        catch(Exception $e)
        {
            $response = [
                'error' => [
                    'name' => 'guide_count_location_error',
                    'message' => $e->getMessage()
                ]
            ];
            $statusCode = 500;
        }
        finally
        {
            return Response::json($response,$statusCode);
        }
    }
    */
}
