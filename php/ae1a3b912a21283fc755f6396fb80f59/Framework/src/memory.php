<?php
    class Mem
    {
        function __construct()
        {
            // if($whose !== "amo")
            //     logger4Queue($whose."-".$max_task);
            $this->m_cache = new Memcache();
            $this->m_cache->addServer('localhost', 11211);
        }
        
        
        
        public  function setTask($who, $task)
        {
            // logger4Queue("WHO ".$who);
            $this->m_cache->set($who, $task,0 , 30);
            return "ok";
        }
        
       
        
        public  function getTask($name)
        {
            if($this->m_cache->get(($name)) != false)
            {
                return $this->m_cache->get(($name));
            }
            else
            {
                return null;
            }
        }
    }

?>