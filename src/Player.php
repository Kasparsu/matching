<?php

namespace Kaspa\Matching;


class Player {
    public $srs = [];
    public $name;

    public function __construct($data)
    {
        foreach($data->roles as $role){
            $this->srs[$role] = $data->sr->$role;
        }
        $this->name = $data->name;
    }

    public function getRoles(){
        return array_keys($this->srs);
    }
    
    public function getAvgSr(){
        return array_sum($this->srs) / count($this->srs);
    }

    public function isTank(){
        return in_array('tank', $this->getRoles());
    }

    public function isSup(){
        return in_array('sup', $this->getRoles());
    }

    public function isDps(){
        return in_array('dps', $this->getRoles());
    }
}