<?php

namespace App\Http\Controllers;

use App\Models\forgotPasswordTokens;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use  App\Services\S3Upload;
use App\Services\customMail;
use Illuminate\Support\Str;
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

            try {

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
            } catch (\Exception $e) {
                Log::info("Error is ".$e->getMessage());
                $path = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQa5Hfgzc60D5cgiQQX-nj-j7_eFHxqpwQmVw&s";
            }
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
        Auth::logout();
        return response()->json(['status'=>true,"message"=>"Logged out successfully"]);
    }
    public function refreshToken(Request $request)
    {
        // Refresh Token for Git changes Test
    }
    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'email | required | string',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        else{
            

            $user=User::where('email', $request->email)->first();
            if($user){
                $mailTrigger=new customMail;
                $gen_Link= uniqid().time().Str::random(10);
                //Status -> 0 means not visited
                //Status -> 1 means  visited
                //Status -> 2 means  expired
                Log::info("Link ".$gen_Link);
                forgotPasswordTokens::create([
                    'user_id' => $user->id,
                    'link' => $gen_Link,
                    'status' => 0
                ]);
                $receipients=['to'=>$request->email];
                $info=$gen_Link;
                $mailTrigger->sendMail($receipients,'emails.resetPassword',"Reset Password Link",$info);

                return response()->json(["status" => true, "message" => "We have sent a Reset Password Link to your Mail. You can Reset it from there."], 200);
            }
            else{
                return response()->json(["status" => false, "message" => "Email does not exists in our database.Register first"], 422);
            }
        }
    }
    public function resetPassword(Request $request,$link){
        $validator = Validator::make($request->all(), [
            'password' => 'string | required',
        ]);
        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        $token=forgotPasswordTokens::where('link',$link)->where('status',0)->first();
        forgotPasswordTokens::where('link',$link)->update(['status' =>1]);
        if($token){
            User::where('id',$token->user_id)->update(['password' => bcrypt($request->password)]);
            return response()->json(["status" => true, "message" => "Password updated successfully"], 200);
        }
        else{
            return response()->json(["status" => false, "message" => "Invalid link"], 422);
        }
    }
}
