<?php

    require_once __DIR__."/avito.php";
    
    class AvitoChats extends Avito
    {
        function __construct($whose)
        {
            R::selectDatabase("access");
            $this->chat_info = R::findOne("amochats", " id = ? ", ['1']);
            parent::__construct($whose);
        }
        
        public function SendMessage2Amo($chat_id, $message, $name, $avatar=null)
        {
            $body = json_encode(array(
                'event_type' => 'new_message',//тип сообщения
                'payload' => array(
                    'timestamp' => time()+7200,//Время сообщения
                    'msgid' => uniqid(),//Уникальное id сообщения
                    "conversation_id"=>"".$chat_id,//уникальное id чата
                    'sender' => array(
                        'id' => ''.$chat_id, //Уникальное id клиента
                        'name' => $name,
                        'avatar'=>$avatar,
                        'profile' => array(
                        ),
                        // 'profile_link' => 'http://example.com',//Ссылка на профиль
                    ),
                    'message' => array(
                        'type' => 'text',
                        'text' => $message
                    )
                )
            ));
            $signature = hash_hmac('sha1', $body, $this->chat_info->secret);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://amojo.amocrm.ru/v2/origin/custom/{$this->chat_info->scope_id}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "x-signature: {$signature}"
                ),
            ));
            $response = json_decode(curl_exec($curl), true);
            $err = curl_error($curl);
            return $response;
        }
        
        public function SendStatus2Amo($msg_id, $status, $code=0, $text="All Right")
        {
            $body = json_encode(array(
                      "msgid"=> "".$msg_id,
                      "delivery_status"=> intval($status),
                      "error_code"=> intval($code),
                      "error"=> $text
            ));
            $signature = hash_hmac('sha1', $body, $this->chat_info->secret);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://amojo.amocrm.ru/v2/origin/custom/{$this->chat_info->scope_id}/".$msg_id."/delivery_status",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "x-signature: {$signature}"
                ),
            ));
            $response = json_decode(curl_exec($curl), true);
            $err = curl_error($curl);
            return $response;
        }
        
        public static function Amo2Amo($chat_id, $message, $name)
        {
            R::selectDatabase("access");
            $chat_info = R::findOne("amochats", " id = ? ", ['1']);
            $body = json_encode(array(
                'event_type' => 'new_message',//тип сообщения
                'payload' => array(
                    'timestamp' => time()+7200,//Время сообщения
                    'msgid' => uniqid(),//Уникальное id сообщения
                    "conversation_id"=>"".$chat_id,//уникальное id чата
                    'sender' => array(
                        'id' => ''.$chat_id, //Уникальное id клиента
                        'name' => $name,
                        
                        'profile' => array(
                        ),
                        // 'profile_link' => 'http://example.com',//Ссылка на профиль
                    ),
                    'message' => array(
                        'type' => 'text',
                        'text' => $message
                    )
                )
            ));
            $signature = hash_hmac('sha1', $body, $chat_info->secret);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://amojo.amocrm.ru/v2/origin/custom/{$chat_info->scope_id}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "x-signature: {$signature}"
                ),
            ));
            $response = json_decode(curl_exec($curl), true);
            $err = curl_error($curl);
            // var_dump($response);
            return $response;
        }
        
        public function SendPicture2Amo($chat_id, $link, $name, $avatar=null)
        {
            
            $body = json_encode(array(
                'event_type' => 'new_message',//тип сообщения
                'payload' => array(
                    'timestamp' => time()+7200,//Время сообщения
                    'msgid' => uniqid(),//Уникальное id сообщения
                    "conversation_id"=>"".$chat_id,//уникальное id чата
                    'sender' => array(
                        'id' => ''.$chat_id, //Уникальное id клиента
                        'name' => $name,
                        'avatar'=>$avatar,
                        'profile' => array(
                        ),
                        // 'profile_link' => 'http://example.com',//Ссылка на профиль
                    ),
                    'message' => array(
                        'type' => 'picture',
                        'file_name' => "PHOTO FROM AVITO",
                        "media"=>"".$link,
                        'file_size'=>10200
                    )
                )
            ));
            $signature = hash_hmac('sha1', $body, $this->chat_info->secret);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://amojo.amocrm.ru/v2/origin/custom/{$this->chat_info->scope_id}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "x-signature: {$signature}"
                ),
            ));
            $response = json_decode(curl_exec($curl), true);
            $err = curl_error($curl);
            return $response;
            
        }
    }
    
    
?>