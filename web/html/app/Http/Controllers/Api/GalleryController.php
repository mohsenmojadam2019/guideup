<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Response;

use App\Http\Requests\GalleryRequest;
use App\Repositories\GalleryRepository;

class GalleryController extends Controller
{
    protected $galleries;

    protected $fields = ['file', 'description', 'position', 'author_id', 'guide_id', 'place_id'];

    public function __construct(GalleryRepository $galleryRepository) {
        $this->galleries = $galleryRepository;
        
        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }
    
    public function index(Request $request) {
        try {   
            return Response::json($this->galleries->search($request->only(['guide_id', 'place_id', 'author_id'])));
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                'name' => 'gallery_search_error',
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }

    public function show($id)  {
        try {
            $gallery = $this->galleries->findByID($id);
            return Response::json($gallery, 200);
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
				'name' => 'gallery_show_error',
				'message' => $e->getMessage()
				]
			], 500);
        }
    }

    public function store(GalleryRequest $request) {
        try {
            $user = Auth::user();
            $values = $request->only($this->fields);

            if($values['guide_id'] != null && $user->guide_id != null && $values['guide_id'] != $user->guide_id) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'The guide informed doesn\'t belongs to this user'
                    ]
                ], 403);
            }
            else {
                $values['author_id'] = $user->id;
            }

            $gallery = $this->galleries->create($values);
            return Response::json($gallery, 201)
                ->header('location', route('gallery.store').'/'.$gallery->id);
        }
        catch(\Exception $e) {
            return Response::json(['error' => [
                'name' => 'gallery_store_error',
                'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function update(GalleryRequest $request, $id) {
        try {     
            $user = Auth::user();
            $gallery = $this->galleries->findByID($id);
            if(!$user->can('update', $gallery)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                'message' => 'The image informed doesn\'t belongs to this user'
                    ]
                ], 403);
            }
            $values = $request->only($this->fields);

            if($values['guide_id'] != null && ($user->guide_id == null || $values['guide_id'] != $user->guide_id)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                    'message' => 'This image doesn\'t belongs to this user'
                    ]
                ], 403);
            } 
            else {
                $values['author_id'] = $user->id;
            }

            $gallery = $this->galleries->update($id, array_filter($values));
            return Response::json($gallery, 200);
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
                    'name' => 'gallery_update_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        } 
    }

    public function destroy(Request $request, $id) {
        try {
            $gallery = $this->galleries->findByID($id);

            $user = Auth::user();
            if(!$user->can('delete', $gallery)) {
                return Response::json(['error' => [
                    'name' => 'not_authorized',
                'message' => 'The image informed doesn\'t belongs to this user'
                    ]
                ], 403);
            }

            if(!$this->galleries->delete($gallery)) {
                throw new \Exception('Error deleting the image');
            }
            return Response::json(["ok" => true], 200);
        }
        catch (ModelNotFoundException $ex) {
                return Response::json(['error' => [
                    'name' => 'gallery_not_found',
                    'message' => 'Gallery not found' 
                    ]
                ], 404);
        }
        catch (\Exception $e)
        {
            return Response::json([
                'error' => [
                    'name' => 'gallery_delete_error',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function generateThumbnails() {
        $galleries = Gallery::all();
        foreach($galleries as $gallery) {
            try {
                $this->deleteFile('images/gallery/thumbnail/'.$gallery->image);
                $thumbnail = Image::make($this->getFile('images/gallery/'.$gallery->image))->fit(200);            
                $this->putFile('images/gallery/thumbnail',$thumbnail->stream()->__toString(),$gallery->image);

                $response[$gallery->image] = 'OK';
            }
            catch(\Exception $e) {
                $response['error'][$gallery->image] = $e->getMessage();
            }
        }
        return Response::json($response,$statusCode);
    }

}
