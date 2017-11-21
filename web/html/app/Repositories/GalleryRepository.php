<?php

namespace App\Repositories;

use App\Repositories\Repository;
use App\Models\Gallery;
use App\Models\User;
use DB;
use Exception;

class GalleryRepository extends Repository {

    use \App\Traits\StorageTrait;

    protected $modelClass = Gallery::class;

    public function search(array $array = []) {
        $galleries = $this->newQuery();
        if(isset($array['author_id'])) {
            $galleries = $galleries->where('author_id', '=', $array['author_id']);
        }
        if(isset($array['place_id'])) {
            $galleries = $galleries->where('place_id', '=', $array['place_id']);
        }
        if(isset($array['guide_id'])) {
            $galleries = $galleries->where('guide_id', '=', $array['guide_id']);
        }

        $take = 15;
        if(isset($array['total'])) {
            $take = $array['total'];
        }
        
        if(isset($array['count'])) {
            return ["total" => $galleries->count()];
        }
        
        $galleries = $galleries->orderBy('id')->orderBy('position');

        return $this->doQuery($galleries, $take);
    }
    
    public function create(array $data = []) {
        if (isset($data['file']) && $data['file'] != null) {
            $data['image'] = $this->saveToGallery($data['file'], null);
        }
        else {
            throw new \Exception("No file was informed");
        }

        unset($data['file']);

        $model = $this->factory($data);
        $this->save($model);
        return $model;
    }
    
    public function update($id, array $data = []) {
        $model = $this->findByID($id);

        if (isset($data['file']) && $data['file'] != null) {
            $data['image'] = $this->saveToGallery($data['file'], $model->image);
        }
        unset($data['file']);

        $this->setModelData($model, $data);
        $this->save($model);
        return $model;
    }

    public function delete($model) {
        if($this->deleteFromGallery($model->image)) {
            return $model->forceDelete();
        }
        return false;
    }
}