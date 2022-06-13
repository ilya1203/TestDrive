<?php

    require_once "/Framework/amocrm.php";
    
    
    class Lead extends Amo
    {
        function __construct($data_lead, $subdomain="", $id_base="1")
        {
            $self->data_lead = $data_lead;
            parent::__construct($subdomain, $id_base);
        }
        
        public function jsn()
        {
            return $this->data_lead;
        }
        
    }


?>