<?php

namespace App\Http\Controllers;

use App\Models\IPInfo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IPInfoController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api');
        auth()->setDefaultDriver('api');
    }

    public function getIpInfo(){
        $iPdata = IPInfo::with('user')->get();
        return $iPdata;
    }

    public function submitIPData(Request $request){
        Log::info($request->all());

        $validator = Validator::make($request->all(),[
            'ip' => 'required',
            'description' => 'required'
        ]);
        if($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first(),
                'success' => false,
                'statusCode' => 422
            ]);
        }
        $user = new IPInfo();
        $user->userId =  $request->userId;
        $user->ip =  $request->ip;
        $user->description = $request->description;
        $user->save();

        return [
            'success' => true,
            'statusCode' => 200,
            'message' => 'Successfully registered !',
        ];
    }
}
