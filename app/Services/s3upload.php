<?php 
namespace App\Services;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
class S3Upload{
    private $s3;

    /* This function helps to upload image in s3 */
    public function uploadFile(Request $request,$filename,$name){ //$name specifies the name which is given in the input tag
        $image = $request->file($name);
        try {
            $this->s3=new S3Client([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);
            $filename =$filename. time() . '.' . $image->getClientOriginalExtension();
            $result = $this->s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key' => 'Mmagic/' .  $filename, // Adjust the S3 key as needed
                'Body' => fopen($image->getRealPath(), 'rb'),
                'ACL' => 'public-read',
            ]);
            $path=$result['ObjectURL'];
            
        } catch (\Throwable $th) {
            $path="";
        }
        return $path;
    }
}