<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use  App\Services\S3Upload;

class BlogController extends Controller
{
    public function createBlog(Request $request){
        $validator = Validator::make($request->all(), [
            'Title' => 'string ',
            'content' => 'required | string',
            'img' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => $validator->errors()], 422);
        }
        else{
            $getImage=new S3Upload;
            $imgLink=$getImage->uploadFile($request,Auth::user()->firstname."-".Auth::user()->lastname,'img');
            $validatedPostData=[
                'creator_id'=>Auth::user()->id,
                'Title'=>$request->title,
                'img'=>$imgLink,
                'content'=>$request->content,
            ];
            $blog=Blog::create($validatedPostData);
            if($blog){
                return response()->json(["status" => true, "message" => 'Vlog Created'], 201);
            }
            else{
                return response()->json(["status" => false, "message" => 'Failed to create the blog'], 422);
            }
        }
    }
    public function getAllBlog(){
        $id=Auth::user()->id;
        $allBlog=Blog::find($id);
        if($allBlog){
            return response()->json(["status"=>true,"messsage"=>"Blogs Returned Successfully","blogs"=>$allBlog,"Total-blog"=>$allBlog->count()]);
        }
        else{
            return response()->json(["status"=>false,"messsage"=>"Failed to returns the blogs"]);
        }
    }
    public function deleteBlog($BlogId){
        //Delete Function
        $user_id=Auth::user()->id;
        $blog=Blog::where('id',$BlogId)->where('creator_id',$user_id);
        if($blog){
            Blog::destroy($BlogId);
            return response()->json(["staus"=>true,"message"=>"Blog Deleted Successfully"],200);
        }
        else{
            return response()->json(["staus"=>false,"message"=>"You can't Delete this post"],422);
        }
    }
}
