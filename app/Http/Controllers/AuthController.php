<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use  App\Services\S3Upload;
use App\Services\customMail;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required | string',
            'lastname' => 'required | string',
            'email' => 'email | required | string | unique:users',
            'mobile' => 'required | string',
            'password' => 'required | string | min:8',
            'image' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        } else {


            $s3 = new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);



            $getImage=new S3Upload;
            $path=$getImage->uploadFile($request,$request->firstname."-".$request->lastname,'image');
            $validatedData = [
                'first_name' => $request->firstname,
                'last_name' => $request->lastname,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'image' => $path
            ];
            $user=User::create($validatedData);
            $mailTrigger=new customMail;
            $receipients=['to'=>$request->email];
            $userInfo=['name'=>$request->firstname];
            $mailTrigger->sendMail($receipients,'emails.registerEmail',"Registered Sucessfully",$userInfo);
            if($user){
                return response()->json(["status" => true, "message" => "User Registered","user" => $validatedData], 201);
            }
            else{
                return response()->json(["status" => false, "message" => "Failed To Register"],422);

            }
        }
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email | required | string ',
            'password' => 'required | string'
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        else{
            if (Auth::attempt($request->all())) {
                $user = Auth::user();
                $token = JWTAuth::fromUser($user);
            
                return response()->json(['status'=>true,"message"=>"Logged In","token"=>$token]);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }
    public function getDetails(){
        $user = Auth::user();
        return response()->json(['status'=>true,"details"=>$user]);
    }
    public function logout()
    {
    }
    public function refreshToken(Request $request)
    {
    }
    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'email | required | string',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        else{
            
        }
    }
}
