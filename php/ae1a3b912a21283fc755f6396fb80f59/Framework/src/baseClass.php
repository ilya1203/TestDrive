<?php

require_once '/home/m/matveever/crm.yasdelayu.ru/public_html/queue/Queue.php';
require_once '/home/m/matveever/crm.yasdelayu.ru/public_html/queue/redis/worker.php';
require_once __DIR__."/memory.php";

if(!function_exists('logger4test'))
{
    function logger4test($message, $chat_id="chat_id", $mark=null)
    {
        $file_name = str_replace(".php", "",explode("/",$_SERVER["PHP_SELF"])[count(explode("/",$_SERVER["PHP_SELF"]))-1]);
        $file_name = $_SERVER["PHP_SELF"];
        $params = str_replace("amp;", "", http_build_query(array(
                        "chat_id"=> "".$chat_id, //TEST
                        "text"=> mb_convert_encoding("FROM FILE: ".$file_name."\n".$message, "UTF8"),
                        "reply_markup"=>$mark,
                        "parse_mode"=>"html")
                    )
                );
        $result = json_decode(file_get_contents('https://api.telegram.org/<toketn:tg_bot>/sendMessage?'.$params), true);
        return $result;
    }
}


if(!class_exists("Request"))
{
    class Request
    {
           
           function __construct($user_agent=null, $headers=null, $limit=null, $whose="amo", $max=7)
           {
               
               $this->mem = new Mem();
               
               $this->redis = new Worker($whose);
               $this->thread = new Queue($whose, $max);
               $this->user_agent = $user_agent;
               $this->headers = $headers;
               $this->limit = $limit;
               if($limit != null)
               {
                    $this->stopListName = explode("-", $limit)[0];
                    $this->stopListValue = intval(explode("-", $limit)[1]);
               }
           }
           
           function sendPost($link=null, $data=null, $with_code=false)
           {
                $body = json_encode($data);
                $curl=curl_init();
                
                curl_setopt($curl, CURLOPT_URL, $link);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                
                if( $this->user_agent != null)
                {
                    curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
                }
                
                if( $this->headers != null)
                {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
                }
                
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                
              
                $uid = $this->redis->push();
                
                while($this->redis->get_current() != $uid && $this->redis->get_current() != "" && $this->redis->get_current() !== null)
                {
                    usleep(350000);
                }
                
                set_error_handler(function(){;});
                $out = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                restore_error_handler();
                // ниже был -0, но тогда получается деление на 0. Поставил +1, чтобы не было такого
                usleep(round(1000000/(intval($this->limit)+1)));
                $this->redis->pop_forward();
                // $total_threading = intval(file_get_contents($this->stopListName));
                // file_put_contents($this->stopListName , ''.($total_threading -  1));
                
                
                $response = json_decode($out, true);
                if($with_code)
                {
                    return array("response"=>$response, "code"=>$code);   
                }
                else
                {
                    return $response;
                }
           }
           
           function sendGet($link=null, $with_code=false)
           {
                $curl=curl_init();
                curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
                if($this->user_agent != null)
                    curl_setopt($curl,CURLOPT_USERAGENT,$this->user_agent);
                curl_setopt($curl,CURLOPT_URL,$link);
                curl_setopt($curl,CURLOPT_HEADER,false);
                curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
                curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
                if($this->headers != null)
                    curl_setopt($curl,CURLOPT_HTTPHEADER,$this->headers);
                
                // if($this->mem->getTask($link) != null)
                //     return ($this->mem->getTask($link));
                
                $uid = $this->redis->push();
                
                while($this->redis->get_current() != $uid && $this->redis->get_current() != "" && $this->redis->get_current() !== null)
                {
                    if(!in_array($uid,$this->redis->get()['values']))
                    {
                        break;
                    }
                    usleep(350000);
                }
                
                set_error_handler(function(){;});
                $out = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                restore_error_handler();
                // ниже был -0, но тогда получается деление на 0. Поставил +1, чтобы не было такого
                usleep(round(1000000/(intval($this->limit)+1)));
                $this->redis->pop_forward();
                // $total_threading = intval(file_get_contents($this->stopListName));
                // file_put_contents($this->stopListName , ''.($total_threading -  1));
                
                
                $response = json_decode($out, true);
                
                
                // ($this->mem->setTask($link, $response));
                
                
                if($with_code)
                {
                    return array("response"=>$response, "code"=>$code);   
                }
                else
                {
                    return $response;
                }
           }
           
           function sendPatch($link, $data, $with_code=false)
           {
                $data = json_encode($data);
                $curl=curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                
                if( $this->user_agent != null)
                {
                    curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
                }
                
                if( $this->headers != null)
                {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
                }
                
                curl_setopt($curl, CURLOPT_URL, $link);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                $out = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                $uid = $this->redis->push();
                
                while($this->redis->get_current() != $uid && $this->redis->get_current() != "" && $this->redis->get_current() !== null)
                {
                    usleep(350000);
                }
                
                set_error_handler(function(){;});
                $out = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                restore_error_handler();
                
                usleep(round(1000000/(intval($this->limit)-0)));
                $this->redis->pop_forward();
                // $total_threading = intval(file_get_contents($this->stopListName));
                // file_put_contents($this->stopListName , ''.($total_threading -  1));
                
                
                $response = json_decode($out, true);
                if($with_code)
                {
                    return array("response"=>$response, "code"=>$code);   
                }
                else
                {
                    return $response;
                }
           }
           
           function sendDelete($link,  $with_code=false)
           {
                $curl=curl_init();
                
                if( $this->user_agent != null)
                {
                    curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
                }
                
                if( $this->headers != null)
                {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
                }
                
                curl_setopt($curl, CURLOPT_URL, $link);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                
                
                $uid = $this->redis->push();
                
                while($this->redis->get_current() != $uid && $this->redis->get_current() != "" && $this->redis->get_current() !== null)
                {
                    usleep(350000);
                }
                
                set_error_handler(function(){;});
                $out = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                restore_error_handler();
                usleep(round(1000000/(intval($this->limit)+1)));
                $this->redis->pop_forward();
                // $total_threading = intval(file_get_contents($this->stopListName));
                // file_put_contents($this->stopListName , ''.($total_threading -  1));
                
                
                $response = json_decode($out, true);
                if($with_code)
                {
                    return array("response"=>$response, "code"=>$code);   
                }
                else
                {
                    return $response;
                }
           }
    }
}