<?php 

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class customMail{
    public function sendMail($recipient,$template,$subject,$data=""){
        try {
            Mail::send($template, ['data'=>$data], function ($message) use ($recipient,$subject) {
                if($recipient['to']){
                    $message->to($recipient['to']);
                }
                if(array_key_exists('cc',$recipient)){
                    $message->cc($recipient['cc']);
                }
                if(array_key_exists('bcc',$recipient)){
                    $message->bcc($recipient['bcc']);
                }
                $message->subject($subject);
            });
        } catch (\Throwable $th) {
            Log:info("Failed To send the Mail ".print_r($th->getMessage(),1));
        }
    }
}