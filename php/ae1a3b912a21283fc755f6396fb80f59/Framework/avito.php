<?php
    
    require_once __DIR__."/src/baseClass.php";
    require_once __DIR__."/amocrm.php";
    require_once '/requires.php';
    
    function loggerAvito($msg, $flag=false)
    {
        $params = http_build_query(array(
            "text"=> ($flag)?  urldecode(urldecode(urldecode($msg))): urldecode(urldecode(urldecode(json_encode($msg)))),
            "parse_mode"=>"html",
            "chat_id"=>"-1001522485409"
            ));
        file_get_contents('https://api.telegram.org/<bot:token>/sendMessage?'.$params);
    }
    
    class Avito extends Request
    {
        function __construct($whose)
        {
            R::selectDatabase("access");
            $this->whose = $whose;
            $this->info = R::findOne("avito24", " whose = ? ", [''.$this->whose]);
            $this->auth = 'Authorization: Bearer '.$this->info->access_token;
            parent::__construct(null, array('Content-Type: application/json' , $this->auth));    
        }
        
        public function AntySpam($message, $chat_id)
        {
            $file_path = __DIR__."/log/".$this->whose.".txt";
            file_put_contents($file_path, "", FILE_APPEND);
            $searched = $message."--".$chat_id;
            if(strpos(file_get_contents($file_path),$searched) !== false)
            {
                return true;
            }
            else
            {   
                file_put_contents($file_path, $searched, FILE_APPEND);
                return false;   
            }
        }
        
        
        
        public function getToken()
        {
            R::selectDatabase("access");
            $this->info = R::findOne("avito24", " whose = ? ", [''.$this->whose]);
            
            $client_secret = ($this->info)['client_secret'];
            $client_id = ($this->info)['client_id'];
            
            $link = 'https://api.avito.ru/token?grant_type=client_credentials&client_id='.$client_id.'&client_secret='.$client_secret.'&scope=messenger:write';
            $result = json_decode(file_get_contents($link),true);
            echo $link."<br>";
            $access_token = $result['access_token'];
            $this->auth = $access_token;
            $sql_search = R::findOne('avito24', 'whose = ?', [''.$this->whose]);
            
            if($sql_search)
            {
                $sql_write = R::load('avito24', $sql_search);
                $sql_write->access_token = $access_token;
                R::store($sql_write);
            }
            
            loggerAvito(array("TAG"=>"#NEWTOKEN", "ANSWER"=>$result));
            
            return $access_token;
        }
        
        public function write2avito($chat_id, $message)
        {
            
        
            $link = 'https://api.avito.ru/messenger/v1/accounts/'.$this->info->avito_id.'/chats/'.$chat_id.'/messages';
            $link_to_read = 'https://api.avito.ru/messenger/v1/accounts/'.$this->info->avito_id.'/chats/'.$chat_id.'/read';
            // $request_params = '{"type": "text","message": {"text": "'.urldecode(str_replace("%0A","%5Cn",urlencode(str_replace('"',"'",$message)))).'"}}';
            $request_params = array("type" => "text",
                "message" => array(
                    "text" => $message
                )
            );
            var_dump($this->auth);
            // exit();
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $link,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $this->auth),
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($request_params),
            ));
            $exec = json_decode(curl_exec($curl),true);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $link_to_read,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $this->auth),
                CURLOPT_POST => true
            ));
            json_decode(curl_exec($curl),true);
            curl_close($curl);
            
            return $exec;
        }
        
        public static function readHookAvito($data)
        {
            echo json_encode(array("ok"=>1));
            $response = array();
            if($data['payload']['value']['type'] == 'system')
            {
                $response = array_merge($response, array('type'=>'system'));
            }
            else
            {
                R::selectDatabase("access");
                $u_id = $data['payload']['value']['author_id'];
                $search = R::findOne("avito24", " avito_id = ? ", [$u_id]);
                if($search != null)
                {
                    $response = array_merge($response, array("from"=>$search['whose']));   
                }
                else
                {
                    $response = array_merge($response, array("from"=>"client"));
                }
                $response = array_merge($response, array('chat_id'=>$data['payload']['value']['chat_id']));
                $response = array_merge($response, array("message"=>$data['payload']['value']['content']['text']));
                $response = array_merge($response, array("ad_url"=>$data['context']['value']['url']));
                if($data['payload']['value']['type'] == "image")
                {
                    $response = array_merge($response, array('type'=>"image"));
                    $imgs = $data['payload']['value']['content']["image"]['sizes'];
                    $link = $imgs[array_keys($imgs)[0]];
                    if($link != null)
                    {
                        $response = array_merge($response, array('link_img'=>$link));
                    }
                }
                else if($data['payload']['value']['type'] == "video")
                {
                    $response = array_merge($response, array('type'=>"video"));
                }
                else
                {
                    $response = array_merge($response, array('type'=>"text"));
                }
            }
            
            return $response;
        }
        
        public function get_info_chat($chat_id)
        {
            $myCurl = curl_init();
            curl_setopt_array($myCurl, array(
                CURLOPT_URL => 'https://api.avito.ru/messenger/v1/accounts/'.$this->info->avito_id.'/chats/'.$chat_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $this->auth)
            ));
            $out = json_decode(curl_exec($myCurl), true);
            return $out;
        }
        
        public function get_all_chats($chat_id)
        {
            $myCurl = curl_init();
            curl_setopt_array($myCurl, array(
                CURLOPT_URL => 'https://api.avito.ru/messenger/v1/accounts/'.$this->info->avito_id.'/chats/'.$chat_id.'/messages/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $this->auth)
            ));
            $out = json_decode(curl_exec($myCurl), true);
            return $out;
        }
        
        public function __subscribe_webhook($url)
        {
             
            $ch = curl_init("https://api.avito.ru/messenger/v2/webhook");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", $this->auth]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["url" => "".$url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch), true);
            
            return array_merge($response, array("accaunt_info"=>json_encode($this)));
        }
        
        public function __unsubscribe_webhook($url)
        {
            $ch = curl_init("https://api.avito.ru/messenger/v1/webhook/unsubscribe");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", $this->auth]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["url" => $url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch), true);
            
            return array_merge($response, array("accaunt_info"=>json_encode($this)));
        }
    }
    
    function helpSend($chat_id, $message)
    {
        R::selectDatabase("access");
        $avitos = R::findAll("avito24");
        $result = array();
        foreach($avitos as $avt)
        {
            $avito = new Avito($avt['whose']);
            $result[] = array($avito->write2avito($chat_id, $message), $avt['whose']);
            usleep(1000000);
        }
        return array("debug_mode"=>"#DEBUG","from"=>$avito->info->whose, "result"=>$result);
    }
    
   
?>