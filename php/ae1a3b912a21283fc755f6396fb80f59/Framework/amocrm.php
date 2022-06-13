<?php
    
    require_once '/requires.php'; // For connect to databases
   
    require_once __DIR__."/src/baseClass.php";
    
   
   
if(!class_exists("Amo"))
{
    class Amo extends Request
    {
        public $auth, $subdomain;
        
        
        #  ---------------Systems functions-------------------------------
        
        function __construct($subdomain="", $id_base="1")
        {
            R::selectDatabase("salers");
            $this->subdomain = $subdomain;
            $this->id_base = $id_base;
            $this->auth = 'Authorization: Bearer '.R::findOne('amotoken', ' id = ? ', [$id_base])['access'];
                    
            parent::__construct('amoCRM-API-client/1.0', array('Content-Type: application/json' , $this->auth), "AMO-5",  $whose="amo", $max=5);
        }
        
        
        public static function readHook($input)
        {
            $input = explode("&", $input);//Разбиваем строчку хука
            $request = array();
            foreach($input as $element)
            {
                $tm_data = explode("=", $element);//Разбиваем параметр на имя и значение
                if(mb_strpos("account[id]", $tm_data[0]) !== false)//Минуем коллизию id сделки и аккаунта
                    $request = array_merge($request, array(
                            "account_id" => "".$tm_data[1]
                            )); 
                else
                {
                   $tm_data[0] = explode( "[", $tm_data[0]);
                    $tm_data[0] = $tm_data[0][count($tm_data[0])-1];
                    $request = array_merge($request, array(
                        "".explode( "]", $tm_data[0])[0] => "".$tm_data[1]//Загоняем в массивчик данные
                        )); 
                }
            }
            
            return $request;
        }
        
        public function moreThan($link, $method="GET", $post_data=array())
        {
            if($method == "GET")
            {
                return parent::sendGet($link);
            }
            else if($method == "POST")
            {
               return parent::sendPost($link, $post_data); 
            }
            else
            {
                return array("data"=>"0", "msg"=>"IDK this method");
            }
        }
        
        
        public function iterablePage($link, $for='leads')
        {
            $leads = array();
            $page = 1;
            $search = parent::sendGet($link."&page=".$page."&limit=250");
            while($search !== null)
            {
                if($page > 25)
                {
                    break;
                }
                if($search['_embedded'][$for] !== null)
                    $leads = array_merge($leads, $search['_embedded'][$for]);
                // logger4test(count($leads));
                $page++;
                $search = parent::sendGet($link."&page=".$page."&limit=250");
                // logger4test($link."&page=".$page."&limit=250");
            }
            
            return $leads;
        }
        
        
        static public function refreshToken($whose)
        {
            return false;
        }
        
        #  ---------------Getters-------------------------------
        
        public function get_custom_field($data_lead, $name_or_id)
        {
            if($data_lead['custom_fields_values'] !== null)
            {
                foreach($data_lead['custom_fields_values'] as $field)
                {
                    if($field['field_name'] == $name_or_id)
                        return $field;
                    
                    if($field['field_id'] == $name_or_id)
                        return $field;
                }
            }
            return null;
        }
        
        public function get_user()
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/users';
            return parent::sendGet($link)['_embedded']['users'];
        }
        
        
        public function get_events_by_filter($filter)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/events?'.$filter;
            // echo $link."<br>";
            // return parent::sendGet($link);
            return $this->iterablePage($link, "events");
        }
        
        public function search_by_filter($filter, $entity_type="leads")
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v3/'.$entity_type.'?'.$filter;
            return parent::sendGet($link);
        }
        
        public function get_pipeline($id)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v2/pipelines?id='.$id;
            return parent::sendGet($link)['_embedded']['items']["".$id];
        }
        
        public function search_by_filter_v4($filter , $entity_type="leads")
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/'.$entity_type.'?'.$filter;
            
            return $this->iterablePage($link, $entity_type);
        }
        
        
        public function check_contact($phone)
        {
            $nums = "1234567890";
            $cleared_phone = "";
            // logger4test("NUM^".$phone);
            foreach(str_split("".$phone) as $char)
            {
                // if()
                if(strpos($nums, $char) != false)
                {
                    $cleared_phone .= $char;
                }
            }
            $cleared_phone = substr($cleared_phone, 1);
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/contacts?query='.$cleared_phone."&with=leads";
            $contacts = parent::sendGet($link);
            $filter = "";
            
            if($contacts !== null)
            {
                foreach($contacts['_embedded']['contacts'] as $contact)
                {
                    if($contact['_embedded']['leads'] !== null)
                    {
                        foreach($contact['_embedded']['leads'] as $lead)
                        {
                            $filter.= "filter[id][]=".$lead['id']."&";
                        }
                        
                        $mo = new self();
                        $leads = $mo->search_by_filter_v4($filter , $entity_type="leads");
                        if($leads != null)
                        {
                            foreach($leads as $lead)
                            {
                                if(!in_array($lead['status_id'], array(142, 143)))
                                {
                                    return $lead;
                                }
                            }
                        }
                    }
                }
            }
            return null;
        }
        
        
        
        public function check_contact_more($phone)
        {
            $nums = " 1234567890";
            $cleared_phone = "";
            // logger4test("NUM^".$phone);
            foreach(str_split("".$phone) as $char)
            {
                // if()
                if(strpos($nums, $char) != false)
                {
                    $cleared_phone .= $char;
                }
            }
            $cleared_phone = substr($cleared_phone, 1);
            // logger4test("FINCD".json_encode($cleared_phone));
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/contacts?query='.$cleared_phone."&with=leads";
            // logger4test($link);
            $contacts = parent::sendGet($link);
            $filter = "";
            
            $leadss = array();
            
            if($contacts !== null)
            {
                foreach($contacts['_embedded']['contacts'] as $contact)
                {
                    if($contact['_embedded']['leads'] !== null)
                    {
                        foreach($contact['_embedded']['leads'] as $lead)
                        {
                            $filter.= "filter[id][]=".$lead['id']."&";
                        }
                        
                       
                    }
                }
                // echo $filter;
                if($filter == "")
                    return null;
                $omo = new self();
                $leads = $omo->search_by_filter_v4($filter);
                
                if($leads != null)
                {
                    foreach($leads as $lead)
                    {
                        if(!in_array($lead, $leadss))
                            $leadss[] = $lead;
                        
                    }
                }
            }
            // logger4test($cleared_phone."  ".json_encode($leads));
            return $leads;
        }
        
        
        public function get_notes($amo_id)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/leads/'.$amo_id."/notes?limit=100";
            return (parent::sendGet($link))["_embedded"]['notes'];
        }
        
        
        public function get_lead($id, $with="")
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/".$id."?with=".$with;
            $result = parent::sendGet($link);
            // logger4test($link."\n".json_encode($result));
            return $result;
        }
        
        public function get_lead_obj($id, $with="")
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/".$id."?with=".$with;
            $result = parent::sendGet($link);
            // logger4test($link."\n".json_encode($result));
         
            return new Lead($result, $this->subdomain, $this->id_base);
        }
        
        public function get_contact($id, $with="")
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/contacts/".$id."?with=".$with;
            return parent::sendGet($link);
        }
        
        
        public function SearchByQuery($entity_type="leads", $query, $with="")
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/".$entity_type."?query=".urlencode($query)."&with=".$with;
            return parent::sendGet($link)['_embedded'][$entity_type];
        }
        
        
        public function get_tasks_from_lead($id)
        {
            // $link = "https://".$subdomain.".amocrm.ru/api/v4/tasks?filter[entity_type][]=".$entity_type."&filter[entity_id][]=".$id."&limit=".$limit;
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/tasks?filter[entity_type][]=leads&filter[entity_id][]='.$id.'&limit=250';//смотрим только незавершенные
           
            $tasks_array = (parent::sendGet($link))["_embedded"]['tasks'];
                
            return $tasks_array;
        }
        
        public function get_tasks_by_filter($filter)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/tasks?'.$filter;//смотрим только незавершенные
            $tasks_array = (parent::sendGet($link))["_embedded"]['tasks'];
                
            return $tasks_array;
        }
         
        
        public function get_tasks_by_user($id)
        {
            // return null;
            // $link = "https://".$subdomain.".amocrm.ru/api/v4/tasks?filter[entity_type][]=".$entity_type."&filter[entity_id][]=".$id."&limit=".$limit;
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/tasks?filter[entity_type][]=leads&order[complete_till]=desc&filter[responsible_user_id][]='.$id.'&limit=250';//смотрим только незавершенные
           
            
            $tasks_array = $this->iterablePage($link, $for='tasks');
                
            return $tasks_array;
        }
        
        
        public function get_values_fields($field_id)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/custom_fields/".$field_id;
            return parent::sendGet($link)['enums'];
        }
        
        
        public function get_unsorted()
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/unsorted";
            return parent::sendGet($link)['_embedded']['unsorted'];
        }
        
        public function search_contacts($phone)
        {
            $nums = " 1234567890";
            $cleared_phone = "";
            // logger4test("NUM^".$phone);
            foreach(str_split("".$phone) as $char)
            {
                // if()
                if(mb_strpos($nums, $char) != false)
                {
                    $cleared_phone .= $char;
                }
            }
            $cleared_phone = substr($cleared_phone, 1);
            // logger4test("FINCD".json_encode($cleared_phone));
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/contacts?query='.$cleared_phone."&with=leads";
            // logger4test($link);
            $contacts = parent::sendGet($link);
            return ($contacts !== null)? $contacts['_embedded']['contacts'] : null;
            
        }
        #  ---------------Setters-------------------------------
        
        public function add_product($name, $price)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/catalogs/12033/elements";   
            $data = array(
                array(
                    "name"=>$name,
                    // "type"=>"products",
                    "price"=>$price,
                    "custom_fields_values"=>array(
                        array(
                            "field_id"=>1404497,
                            "values"=>array(
                                array(
                                    "value"=>$price
                                    )
                                )
                            )
                        )
                    )
                );
            return parent::sendPost($link, $data);
        }
        
        public function del_unsorted($uid)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/unsorted/".$uid."/decline";   
            return parent::sendDelete($link);
        }
        
        
        public function change_lead($id="", $fields)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v2/leads";
            $data = array(
                "update"=>array(array_merge(array("id"=>"".$id, 'updated_at'=>time()+7200),$fields))
                );
            return parent::sendPost($link, $data);
        }
        
        public function change_lead_v4($id="", $fields)
        {
            
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads" ;
            /*
            $data = $fields;
            return array("result"=>parent::sendPatch($link, $data, true), "data"=>$data);
            */
            
            $curl=curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($curl, CURLOPT_URL, $link);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',$this->auth));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
          
            //ОТВЕТ ОБ УСПЕХЕ
            $response = json_decode($out, true);
            return $response;
        }
        
        
        public function change_contact($id="", $phone)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v2/contacts";
            $data = array(
                       "update"=> array(
                           array(
                                'id'=> "".intval($id),
                                "updated_at"=> time()+7200,
                                'custom_fields'=>array(
                                    array(
                                       "id"=> "1223023",
                                       "values"=> array(
                                              array(
                                                 "value"=> "".$phone,
                                                 "enum"=>"WORK"
                                              )
                                       )
                                    )
                                )
                            )
                        )
                    );
            
            return parent::sendPost($link, $data);
        }
        
        public function change_pakage($data_to_change)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v2/leads";
            $data = array(
                "update"=>$data_to_change
                );
            return parent::sendPost($link, $data);
        }
        
        public function createComplex($data)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/leads/complex';
            return parent::sendPost($link, $data);
        }
        
        public function linked_to_lead($lead_id, $contact_id)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/".$lead_id."/link";
            // echo $link;
            $data =  array(array(
                        "to_entity_id"=> intval($contact_id),
                        "to_entity_type"=> "contacts",
                        
                    ));
            return parent::sendPost($link, $data);
        }
        
        
        public function link_chat_to_contact($contact_id, $chat_id)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/contacts/chats";
            // echo $link;
            $data =  array(
                        array(
                            "contact_id"=> intval($contact_id),
                            "chat_id"=> "".$chat_id,
                            
                        )
                    );
                   
            return parent::sendPost($link, $data);
        }
        
        /**
         [{
                "id": 167353,
                "_embedded": {
                    "tags": [
                        {
                            "name": "Tag"
                        }
                    ]
                }
            }
        ]
         **/
        public function tags($data)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/leads';
            $curl=curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
            curl_setopt($curl, CURLOPT_URL, $link);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',$this->auth));
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            $out = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
          
            //ОТВЕТ ОБ УСПЕХЕ
            $response = json_decode($out, true);
            return $response;
        }
        
        public function sendComments($data)
        {
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/leads/notes';
            return parent::sendPost($link, $data);
        }
        
        public function send_notes($amo_id, $text)
        {
            $data = array(
                array(
                "entity_id"=> intval($amo_id),
                "note_type"=> "common",
                "params"=> array(
                            "text"=>"".$text
                        )
                    )
                );
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/leads/notes';
            return parent::sendPost($link, $data);
        }
        
        public function sendCall2amo($entity_id, $uuid, $duration, $type, $client, $to="leads")
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/".$to."/notes";
            $data = array(
                array(
                    "entity_id"=> intval($entity_id),
                    "note_type"=> "".$type,
                    "params"=> array(
                        "uniq"=> "".$uuid,
                        "duration"=> intval($duration),
                        // "duration"=> "inbound",
                        "source"=> "onlinePBX",
                        "link"=> $uuid,
                        "phone"=> "".$client
                        )
                    )
                );
            return parent::sendPost($link, $data);
            
        }
        
        
        public function sendCall2amoPack($data)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/notes";
            
            return parent::sendPost($link, $data);
            
        }
        
        
        
        public function create_lead($name, $contact_id, $tags, $custom_fields)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/lead";
            $data = array(
                array(
                    "name"=>$name,
                    "created_at"=>time()
                    )
                );
            
            return parent::sendPost($link, $data)['_embedded']['lead'][0];
        }
        
        
        public function create_contact($name)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/contacts";
            $data = array(
                array(
                    "name"=>$name,
                    "created_at"=>time()
                    )
                );
            
            return parent::sendPost($link, $data)['_embedded']['contacts'][0];
        }
        
        
        public function task2amo($amo_id, $responsible, $text, $time, $task_type_id=1)
        {
            $tasks =array(
                        array(
                            'entity_id'=>intval($amo_id),
                            'entity_type' => 'leads',
                            'responsible_user_id' => intval($responsible),
                            'text' => $text,
                            "task_type_id"=>$task_type_id,
                            'complete_till' => $time
                        )
                    );
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/tasks';
            
            return parent::sendPost($link, $tasks);
        }
        
        
        public function closeTask($id, $result="Новый статус")
        {
            $data = array(
                    "is_completed"=> true,
                    "result"=>array(
                            "text"=> $result
                        )
            );
            $link = 'https://'.$this->subdomain.'.amocrm.ru/api/v4/tasks/'.$id ;
            // echo $link;
            return parent::sendPatch($link, $data);
        }
        
        public function unsorted_send($pipeline_id, $data_lead, $data_contact, $meta_data)
        {
            $link = "https://".$this->subdomain.".amocrm.ru/api/v4/leads/unsorted/forms";
            $data = array(
                array(
                        "source_name" => "Сайт",
                        "source_uid" => "a1fee7c0fc436088e64ba2e8822ba2b3",
                        "pipeline_id"=> $pipeline_id,
                        "_embedded" => array(
                            "leads" => array(
                                $data_lead
                            ),
                            "contacts" => array(
                                $data_contact
                            )
                        ),
                        "metadata" => array_merge(array(
                            "ip"=> "0.0.0.0",
                            "form_id"=> uniqid(),
                            "form_sent_at"=> time()+7200
                        ), $meta_data)
                    )
                );
            return parent::sendPost($link, $data);
        }
    }
}
    

    // $amo = new Amo();
    
    //Получение лида
    // var_dump($amo->get_lead("13408933"));
    
    
    
    // $fields = array(
    //     "status_id"=>"42777226",
    //     'pipeline_id'=>"4651465"
    //     );
    // var_dump($amo->change_lead("13707467", $fields));
?>