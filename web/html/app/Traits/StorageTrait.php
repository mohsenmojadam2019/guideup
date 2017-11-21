<?php

namespace App\Traits;

use Storage;
use Image;

trait StorageTrait
{

    public function saveToGallery($file, $oldFile=null, $filename=null)
    {
        if($oldFile != null && trim($oldFile) != "")
        {
            $this->deleteFile('images/gallery/'.$oldFile);
            $this->deleteFile('images/gallery/thumbnail/'.$oldFile);
        }

        if(gettype($file)=="string") {
            if($filename == null || trim($filename) == "") {
                $filename = md5($file).'.jpg';
            }
            $file = Image::make($file)->encode('jpg')->fit(200)->stream()->__toString();
        }
        
        $filename = $this->putFile('images/gallery', $file, $filename);
        $thumbnail = Image::make($file)->fit(200);
        $this->putFile('images/gallery/thumbnail',$thumbnail->stream()->__toString(),$filename);

        return $filename;
    }

    public function deleteFromGallery($filename)
    {
        if($filename == null || trim($filename) == '') {
            return false;
        }
        $gallery = $this->deleteFile('images/gallery/'.$filename);
        $thumbnail = $this->deleteFile('images/gallery/thumbnail/'.$filename);
        return $gallery;
    }

    public function getGalleryUrl($filename)
    {
        if($filename == null || trim($filename) == '') {
            return null;
        }
        return asset("/assets/images/gallery/".$filename);
    }

    public function saveAvatar($file, $oldFile)
    {
        if($oldFile != null && trim($oldFile) != "")
        {
            $this->deleteFile('images/avatar/'.$oldFile);
        }

        if(gettype($file)=="string") {
		    $avatar = Image::make($file)->fit(512);
            $filename = md5($file);
            $filename = md5($file).'.jpg';
        }
        else {
            $avatar = Image::make($file)->fit(512);
            $filePath = $file->getRealPath();
            $filename = md5_file($filePath);
            $filename = $filename.'.'.$file->extension();
        }


        $filename = $this->putFile('images/avatar',$avatar->stream()->__toString(), $filename);

        return $filename;
    }
    
    public function saveFacebookAvatar($imageUrl, $oldFile)
    {
        if($oldFile != null && trim($oldFile) != "")
        {
            $this->deleteFile('images/avatar/'.$oldFile);
        }

		$avatar = Image::make($imageUrl)->fit(512);
        $filename = md5($imageUrl);
        $filename = $filename.'.jpg';
        $filename = $this->putFile('images/avatar',$avatar->stream()->__toString(), $filename);
        return $filename;
    }

    public function putFile($path, $file, $filename=null)
    {
        //Is the content
        if(gettype($file)=="string")
        {
            if($filename == null)
            {
                return;
            }
            $contents = $file;
        }
        else
        {
            $filePath = $file->getRealPath();
            if($filename == null)
            {
                $filename = md5_file($filePath);
                $filename = $filename.'.'.$file->extension();
            }
            $contents = file_get_contents($filePath);

        }
        $fullPath = $path.'/'.$filename;

        Storage::drive('public')->put($fullPath, $contents);
        return $filename;
    }

    public function getFile($filename)
    {
        if(Storage::disk('public')->exists($filename))
        {
            return Storage::drive('public')->get($filename);
        }
        return null;
    }

    public function deleteFile($filename)
    {
        if(Storage::disk('public')->exists($filename)) {
            return Storage::drive('public')->delete($filename);
        }
        return true;
    }
}