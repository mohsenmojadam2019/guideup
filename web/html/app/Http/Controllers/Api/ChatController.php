<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Exception;
use Response;
use App\Models\User;
use DB;

class ChatController extends Controller
{
    function delete(Request $request) 
    {
        if($request->has('username') && $request->has("message"))
        {
            $deleted = DB::connection("ejabberd")->table("spool")->where("username", "=",$request->username)->where("xml", "like", "%<body>$request->message</body>%")->limit(1)->delete();
            return response('{"ok":true}', 200)->header('Content-Type', 'application/json');
        }
        return response('{"ok":false}', 200)->header('Content-Type', 'application/json');
    }

    function received(Request $request) 
    {
        try
        {
            $post = file_get_contents('php://input');
            $data = json_decode(str_replace("'","\"",$post));
            if($data != null && isset($data->access_token) && $data->access_token == "123-token")
            {
                $fromUser = User::whereEmail(str_replace("[at]","@",$data->from))->first();

                $toUser = str_replace("[at]","@",$data->to);
                $user = User::whereEmail($toUser)->first();
                if($fromUser != null && $user != null)
                {
                    //Send message to Firebase
                    $ch = curl_init();
                    $data = '{"to":"'.$user->fcm_token.'","data":{"from_user":"'.$data->from.'","to_user":"'.$data->to.'","title":"'.$fromUser->name.'","message": "'.$data->body.'", "image":"'.$fromUser->avatar_url.'"}}';
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: key=AAAAJ7zi8cU:APA91bEgrJpixvWUdpmPTq2YFltCLjCi89zY-d-EKJKsQJrQl9ZJHn0bjqUmWlT-qDGcDKFaXTGNSlrJaKfv6FkZWcz_H1yq2VLEv2qfUDyuqbuftnRXN2hZYspb0FSLzPGKQALkQ2q-pZQT63q0KHqPwsg3ogkYLg',
                    'Content-Type: application/json'
                    ));
                    $output = curl_exec($ch);
                    curl_close($ch);
                    error_log("response server: ".$output);
                }
            }
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }
        catch(Exception $e)
        {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            return response('ERROR', 200)->header('Content-Type', 'text/plain');
        }
    }
}
