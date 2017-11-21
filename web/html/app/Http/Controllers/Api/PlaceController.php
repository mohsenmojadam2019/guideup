<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Response;
use App\Http\Requests;
use Auth;

use App\Http\Requests\PlaceRequest;
use App\Repositories\PlaceRepository;
use App\Repositories\GuideRepository;
use App\Repositories\GalleryRepository;
use App\Repositories\ReviewRepository;

class PlaceController extends Controller {
    use \App\Traits\StorageTrait;

    protected $places;
    protected $guides;
    protected $galleries;
    protected $reviews;

    protected $fields = ['name', 'description', 'city_id', 'state_id', 'country_id', 'latitude', 'longitude', 'address', 'type', 'galleries'];

    public function __construct(PlaceRepository $placeRepository,
                                GuideRepository $guideRepository,
                                GalleryRepository $galleryRepository,
                                ReviewRepository $reviewRepository) {
        $this->places = $placeRepository;
        $this->guides = $guideRepository;
        $this->galleries = $galleryRepository;
        $this->reviews = $reviewRepository;

        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }

    public function index(Request $request)  {
        try {
            $places = $this->places->search($request->only(['term', 'type', 'city_id', 'state_id', 'country_id', 'latitude', 'longitude', 'distance', 'total', 'count', 'all']));
			
            $user = Auth::guard('api')->user();
            if($user)
            {
				foreach($places as $place) {
					$place->favorite = $place->users()->wherePivot('user_id','=', $user->id)->count() > 0;
				}
            }
            return Response::json($places, 200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'place_index_error',
                    'message' => $e->getMessage(),
                    ]
                ], 500);
        }
    }

    public function show(Request $request, $id) {
        try {

            $place = $this->places->findByID($id);
            
            $user = Auth::guard('api')->user();
            if($user)
            {
                $has_review = $place->reviews()->where('user_id','=',$user->id)->count() > 0;
                $place->has_review = $has_review;
                $place->favorite = $place->users()->wherePivot('user_id','=', $user->id)->count() > 0;
            }

            return Response::json($place, 200);
        }
        catch (ModelNotFoundException $ex) {            
            return Response::json(['error' => [
                'name' => 'place_not_found',
                'message' => 'Place not found' 
                ]
            ], 404);
        }
        catch(Exception $e) {
            return Response::json(['error' => [
                'name' => 'place_show_error',
                'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
    
    public function store(PlaceRequest $request) {
        try {
                $values = $request->only($this->fields);
                
                if($this->places->exists(['name' => $values['name'], 
                                          'country_id' => $values['country_id'], 
                                          'state_id' => $values['state_id'], 
                                          'city_id' => $values['city_id']])) {
                    return Response::json(['error' => [
                        'name' => 'place_store_error',
                        'message' => 'A place with this name already exists in this location' 
                        ]
                    ], 403);
                }
                if ($request->hasFile('file') && $request->file('file')->isValid()) {
                    $values['cover'] = $this->saveToGallery($request->file('file'), null);
                } 
				else if($request->has('cover') && trim($request->cover) !== '') {
					$values['cover'] = trim($request->cover);
				}

                $values['created_by'] = Auth::user()->id;
                $place = $this->places->create($values);
                return Response::json($place, 201)
                    ->header('location', route('place.store').'/'.$place->id);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                    'name' => 'place_store_error',
                    'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function update(PlaceRequest $request, $id) {
        try {           
            $place = $this->places->findByID($id);
            
            if(!Auth::user()->can('update', $place)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            $values = $request->only($this->fields);
            
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $values['cover'] = $this->saveToGallery($request->file('file'), $place->cover);
            }
			else if($request->has('cover') && trim($request->cover) !== '') {
				$values['cover'] = trim($request->cover);
			}

            $values['created_by'] = Auth::user()->id;
            $place = $this->places->update($id, array_filter($values));
            return Response::json($place, 200);
        } 
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Place not found' 
                    ]
                ], 404);
        } 
        catch(Exception $e)
        {
            return Response::json(['error' => [
                    'name' => 'place_update_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
    
    public function destroy($id) {
        try {
            $place = $this->places->findByID($id);
            
            if(!Auth::user()->can('delete', $place)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ], 403);
            }
            $this->places->delete($place);
            return Response::json(["ok" => true], 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Place not found' 
                    ]
                ], 404);
        }
        catch (\Exception $e)
        {
            return Response::json([
                'error' => [
                    'name' => 'place_delete_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function guideIndex(Request $request, $place_id) {
        try {
            $guides = $this->guides->search(['place_id' => $place_id]);
            return Response::json($guides,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'place_guide_index_error',
				'message' => $e->getMessage(),
                    'stack' => $e->getTraceAsString()
				]
			],500);
        }
    }

    public function guideShow($place_id, $guide_id) {
        try {
            $guide = $this->guides->findByID($guide_id);
            if($guide == null) {
                return Response::json(['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found'
                    ]
                ],404);
            }
            return Response::json($guide,200);
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
				'name' => 'place_guide_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function galleryIndex(Request $request, $place_id) {
        try {
            $values = $request->only('page', 'total');
            $values['place_id'] = $place_id;
            $galleries = $this->galleries->search($values);
            return Response::json($galleries,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'place_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function galleryShow($place_id, $gallery_id) {
        try {
            $gallery = $this->galleries->findByID($gallery_id);
            if($gallery == null || $gallery->place_id != $place_id) {
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
				'name' => 'place_gallery_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewIndex(Request $request, $place_id) {
        try {
            $values = $request->only('page', 'total');
            $values['place_id'] = $place_id;
            $reviews = $this->reviews->search($values);
            return Response::json($reviews,200);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
				'name' => 'place_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

    public function reviewShow($place_id, $review_id) {
        try {
            $review = $this->reviews->findByID($review_id);
            if($review == null || $review->place_id != $place_id) {
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
				'name' => 'place_review_index_error',
				'message' => $e->getMessage()
				]
			],500);
        }
    }

/**
    // FUNÇÕES ANTIGAS 
    protected function persistPlace(Request $request, $id = null)
    { 
        if($id == null)
        {
            $newPlace = true;
            $place = new Place();
        }
        else
        {
            $place = Place::find($id);
            if($place == null)
            {
                throw new Exception("Place not found");
            }
            $newPlace = false;
        }

        if($id == null)
        {
            // Save The Place 
            $place->name = $request->name;
            $place->description = $request->description;
            $place->address = $request->address;
            $place->city_id = $request->city['id'];
            $place->state_id = $request->state['id'];
            $place->country_id = $request->country['id'];
            //$place->latitude = $request->latitude;
            //$place->longitude = $request->longitude;
            
            if ($request->hasFile('file') && $request->file('file')->isValid())
            {
                $place->cover = $this->saveToGallery($request->file('file'), null);
            }
            else if($request->file != null && $request->file != '') {
                $place->cover = $this->saveToGallery($request->file, null);
            }
            else {
                throw new Exception("cover file is missing");
            }
        }
        else
        {
            if($request->has('name'))
                $place->name = $request->name;
            if($request->has('description'))
                $place->description = $request->description;
            if($request->has('address'))
                $place->address = $request->address;
            if($request->has('city'))
                $place->city_id = $request->city['id'];
            if($request->has('state'))
                $place->state_id = $request->state['id'];
            if($request->has('country'))
                $place->country_id = $request->country['id'];
            if($request->has('latitude'))
                $place->latitude = $request->latitude;
            if($request->has('longitude'))
                $place->longitude = $request->longitude;
            
            if ($request->hasFile('file') && $request->file('file')->isValid())
            {
                $place->cover = $this->saveToGallery($request->file('file'), $place->cover);
            }
        }
        
        $place->save();

        if($request->has('images'))
        {
            foreach(explode(',',$request->images) as $image_id)
            {
                $imageGallery = Gallery::find($image_id);
                if($imageGallery != null)
                {
                    $imageGallery->place()->associate($place);
                    //$imageGallery->temp = false;
                    $imageGallery->save();
                }
            }
        }
        else if($request->has('galleries'))
        {
            foreach($request->galleries as $image)
            {
                $imageGallery = Gallery::find($image['id']);
                if($imageGallery != null)
                {
                    $imageGallery->place()->associate($place);
                    //$imageGallery->temp = false;
                    $imageGallery->save();
                }
            }
        }
            
        return $place;
    }

 //Colocar o Favorito no Usuário porque ai pode salvar 1 ou vários ex. POST api/user/123/places/favorite adiciona ou remove vários [{ place: id, favorite: true|false }]
    public function favoriteMany(Request $request)
    {
        $statusCode = 200;
        $response = [];
        try
        { 
            $array = $request->json()->all();
            if(!is_array($request->json()->all()))
            {
                throw new Exception('Favorite not informed.');
            }

            $user = $this->getLoggedUser();
            if(!$user)
            {
                $response = ['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ];
                $statusCode = 403;
            }
            else
            {
                if($user == null)
                {
                    $response = ['error' => [
                        'name' => 'user_not_found',
                        'message' => 'User not found' 
                        ]
                    ];
                    $statusCode = 404;
                }
                else
                {    
                    $user->places()->sync($array);
                    $response = [
                        'ok' => true
                    ];
                }
            }
        }
        catch(Exception $e)
        {
            $response = [
                'error' => [
                    'name' => 'place_favorite_error',
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

    public function favorite(Request $request, $id)
    {
        $statusCode = 200;
        $response = [];
        try
        { 
            if(!$request->has('favorite'))
            {
                throw new Exception('Favorite not informed.');
            }

            $user = $this->getLoggedUser();
            if(!$user)
            {
                $response = ['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ];
                $statusCode = 403;
            }
            else
            {
                if($user == null)
                {
                    $response = ['error' => [
                        'name' => 'user_not_found',
                        'message' => 'User not found' 
                        ]
                    ];
                    $statusCode = 404;
                }
                else
                {           
                    $place = Place::find($id);      
                    if($place == null)
                    {
                        $response = ['error' => [
                            'name' => 'place_not_found',
                            'message' => 'Place not found' 
                            ]
                        ];
                        $statusCode = 404;
                    }
                    if($request->favorite == 'true')
                    {
                        if($place->users()->count() < 1)
                        {
                            $place->users()->attach($user->id);
                            $place->save();
                        }
                    }
                    else
                    {
                        $place->users()->detach($user->id);
                        $place->save();
                    }

                    $response = [
                        'ok' => true,
                        'id' => $place->id
                    ];
                }
            }
        }
        catch(Exception $e)
        {
            $response = [
                'error' => [
                    'name' => 'place_favorite_error',
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
 
    public function search(Request $request)
    {        
        try
        {
            $statusCode = 200;
            $response = [];
            
            if($request->term == null || $request->term == 'null' || strlen($request->term) < 1)
            {
                $places = new Place();
                if($request->type != null && $request->type !== 'all') {
                    $places = $places->where('type','=', $request->type);
                }
                if($request->parent != null) {
                        $place = $place->where('city_id','=',$request->parent)
                                       ->where('state_id','=',$request->parent)
                                       ->where('country_id','=',$request->parent);
                }
            }
            else if(strlen($request->term) < 3)
            {
                $response = ['error' => [
                    'name' => 'search_minimal_size',
                    'message' => 'The minimal size for search is 3 caracters' 
                    ]
                ];
                $statusCode = 404;
                return Response::json($response,$statusCode);
            }
            else
            {
                $places = Place::where('name','like','%'.$request->term.'%');
                if($request->type != null && $request->type !== 'all') {
                    $places = $places->where('type','=', $request->type);
                }
            }
            $totalPlaces = $places->count();
            $selectArray = ['id', 
                            'name', 
                            'city_id', 
                            'address', 
                            'state_id', 
                            'country_id', 
                            'cover', 
                            'latitude', 
                            'longitude',
                            'created_at',
                            'type'];
            $total = 50;
            if($request->total != null && $request->total > 0) {
                $total = $request->total;
            }
            $offset = 0;
            if($request->page != null && $request->page > 0) {
                $offset = $total * $request->page;
            }
            $places = $places->offset($offset)->limit($total);

            if($request->latitude != null && $request->latitude != 0 && $request->longitude != null && $request->longitude != 0)
            {
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $selectArray[] = DB::raw("ACOS(SIN(latitude*$this->earthScaleFactor)*SIN($latitude*$this->earthScaleFactor) + COS(latitude*$this->earthScaleFactor)*COS($latitude*$this->earthScaleFactor)*COS((longitude-$longitude)*$this->earthScaleFactor)) * $this->earthRadiusKm as distance"); 
                $places = $places->orderBy('distance');
            }
            else
            {
                $places = $places->orderBy('name');
            }

            $places = $places->select($selectArray)->get();
            
            if($places == null)
            {
                $response = ['error' => [
                    'name' => 'place_not_found',
                    'message' => 'Places not found' 
                    ]
                ];
                $statusCode = 404;
            }
            else
            {
                $response = [
                    'total' => $totalPlaces,
                    'list' => $places
                ];
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'place_search_error',
                    'message' => $e->getMessage(),
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
            if($request->latitude != null && $request->latitude != 0 && $request->longitude != null && $request->longitude != 0 && $request->distance != null) {
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $distance = $request->distance;
                $type = 1;

                if($request->type != null) {
                    $type = $request->type;
                }

                $bound = $this->getBoundsByRadius($latitude, $longitude, $distance);
                $latitudeN = $bound['N']['lat'];
                $latitudeS = $bound['S']['lat'];
                $longitudeE = $bound['E']['lng'];
                $longitudeW = $bound['W']['lng'];

                //DB::EnableQueryLog();
                $count = Place::where('type','=',$type)
                ->whereBetween('latitude', [$latitudeS, $latitudeN])
                    ->whereBetween('longitude', [$longitudeW, $longitudeE])
                    ->count();
                    $response = array('count' => $count);
            } else {

                $response = ['error' => [
                        'name' => 'place_count_location_error',
                        'message' => "Must inform latitude, longitude and distance"
                        ]
                    ];
                $statusCode = 500;
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'place_count_location_error',
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

    public function getByLocation(Request $request)
    {
        $statusCode = 200;
        $response = [];
        try
        {
            if($request->latitude != null && $request->latitude != 0 && $request->longitude != null && $request->longitude != 0 && $request->distance != null) {
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $distance = $request->distance;
                $type = 1;

                if($request->type != null) {
                    $type = $request->type;
                }

                $bound = $this->getBoundsByRadius($latitude, $longitude, $distance);
                $latitudeN = $bound['N']['lat'];
                $latitudeS = $bound['S']['lat'];
                $longitudeE = $bound['E']['lng'];
                $longitudeW = $bound['W']['lng'];

                $places = Place::where('type','=',$type)->whereBetween('latitude', [$latitudeS, $latitudeN])
                    ->whereBetween('longitude', [$longitudeW, $longitudeE])
                    ->get();
                foreach ($places as $place) 
                {
                    array_push($response, $place);
                }
            } else {

                $response = ['error' => [
                        'name' => 'places_by_location_error',
                        'message' => "Must inform latitude, longitude and distance"
                        ]
                    ];
                $statusCode = 500;
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'places_by_location_error',
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

    public function getByGuide(Request $request, $id = 0)
    {
        $statusCode = 200;
        $response = [];       
        try
        {
            if($id == null || $id < 1)
            {
                $response = ['error' => [
                    'name' => 'guide_not_found',
                    'message' => 'Guide not found' 
                    ]
                ];
                $statusCode = 404;
                return Response::json($response,$statusCode);
            }
            else
            {
                $guide = Guide::find($id);
                $places = $guide->places()->orderBy('name')->get();
                foreach($places as $place)
                {
                    unset($place->description);
                    $response[] = $place;
                }
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'place_by_guide_error',
                    'message' => $e->getMessage(),
                    ]
                ];
            $statusCode = 500;
        }
        finally
        {
            return Response::json($response,$statusCode);
        }
    }
    
    //Colocar o Favorito no Usuário porque ai pode salvar 1 ou vários ex. POST api/user/123/places adiciona vários [{ place: id }]
    public function saveGuidePlace(Request $request)
    {
        $statusCode = 200;
        $response = [];       
        try
        {
            $user = $this->getLoggedUser();
            if(!$user)
            {
                $response = ['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ];
                $statusCode = 403;
            }
            else if($user == null)
            {
                $response = ['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ];
                $statusCode = 404;
            }
            else
            {              
                $guide = Guide::where('user_id','=',$user->id)->first();
                if($guide != null)
                {
                    if($request->has('data'))
                    {
                        DB::beginTransaction();
                        $places = [];
                        foreach($request->data as $data)
                        {
                            $places[] = $data['id'];
                            $response[] = ['id' => $data['id']];
                        }

                        $guide->places()->sync($places);
                    }
                }
            }
        }
        catch(Exception $e)
        {
            DB::rollback();
            $response = ['error' => [
                    'name' => 'place_save_guide_error',
                    'message' => $e->getMessage(),
                    ]
                ];
            $statusCode = 500;
        }
        finally
        {
            DB::commit();
            return Response::json($response,$statusCode);
        }
    }
    
    //Colocar o Favorito no Usuário porque ai pode salvar 1 ou vários ex. DELETE api/user/123/places remove vários [{ place: id }]
    public function removeGuidePlace(Request $request, $id)
    {
        $statusCode = 200;
        $response = [];       
        try
        {
            $user = $this->getLoggedUser();
            if(!$user)
            {
                $response = ['error' => [
                    'name' => 'not_authorized',
                    'message' => 'Not Authorized' 
                    ]
                ];
                $statusCode = 403;
            }
            else if($user == null)
            {
                $response = ['error' => [
                    'name' => 'user_not_found',
                    'message' => 'User not found' 
                    ]
                ];
                $statusCode = 404;
            }
            else
            {              
                $guide = Guide::where('user_id','=',$user->id)->first();
                if($guide != null)
                {
                    if($id > 0)
                    {
                        $guide->places()->detach($id);
                        $response = ['ok' => true, 'id' => $id];
                    }
                }
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'place_delete_guide_error',
                    'message' => $e->getMessage(),
                    ]
                ];
            $statusCode = 500;
        }
        finally
        {
            return Response::json($response,$statusCode);
        }
    }
    

    public function listType(Request $request, $type, $parent_id = 0)
    {
        $statusCode = 200;
        $response = [];       
        try
        {    
            $place = Place::where('type','=',$type);
            if($parent_id > 0)
            {
                switch ($type) {
                    case 1: //Lugar
                        $place = $place->where('city_id','=',$parent_id);
                        break;
                    case 2: //Cidade
                        $place = $place->where('state_id','=',$parent_id);
                        break;
                    case 3: //Estado
                        $place = $place->where('country_id','=',$parent_id);
                        break;
                }
            }
            $places = $place->get();
            foreach($places as $p)
            {
                $newPlace =  (object) [
                    'id' => $p->id,
                    'name' => $p->name,
                    'cover_thumbnail_url' => $p->cover_thumbnail_url
                ];
                array_push($response,$newPlace);
            }
        }
        catch(Exception $e)
        {
            $response = ['error' => [
                    'name' => 'place_list_type_error',
                    'message' => $e->getMessage(),
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