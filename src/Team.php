<?php

namespace Kaspa\Matching;

class Team {
    public $tank;
    public $dps1;
    public $dps2;
    public $sup1;
    public $sup2;
    public $avgSr;

    public function getAvgSr(){
        $this->avgSr = ($this->tank->srs['tank'] + $this->dps1->srs['dps'] + $this->dps2->srs['dps'] + $this->sup1->srs['sup'] + $this->sup2->srs['sup']) /5;
        return $this->avgSr;
    }

    public function getAvgRoleSr($role){
        switch ($role){
            case 'tank':
                return $this->tank->srs['tank'];
            case 'dps':
                return ($this->dps1->srs['dps'] + $this->dps2->srs['dps']) / 2;
            case 'sup':
                return ($this->sup1->srs['sup'] + $this->sup2->srs['sup']) / 2;
        }
    }
    public function getMembersArray(){
        return [
            ['role' => 'tank', 'player' => $this->tank],
            ['role' => 'dps', 'player' => $this->dps1],
            ['role' => 'dps', 'player' => $this->dps2],
            ['role' => 'sup', 'player' => $this->sup1],
            ['role' => 'sup', 'player' => $this->sup2],
        ];
    }
    public function getMembersSortedBySr(){
        $players = $this->getMembersArray();
        usort($players, function($a, $b) {
            return $a['player']->srs[$a['role']] - $b['player']->srs[$b['role']];
        });
        return $players;
    }

    public function getSwappableMembersSortedBySr($player, $role){
        $players = array_filter($this->getMembersArray(), function($p) use($player, $role) {
            return in_array($p['role'], $player->getRoles()) && in_array($role, $p['player']->getRoles()) && $player->srs[$role]<$p['player']->srs[$role];
        });
        usort($players, function($a, $b) {
            return $a['player']->srs[$a['role']] - $b['player']->srs[$b['role']] ;
        });
        return $players;
    }

    public function swap($player, $swap){

        if($this->tank->name == $player['player']->name){
            $this->tank = $swap['player'];
        }
        if($this->dps1->name == $player['player']->name){
           
            $this->dps1 = $swap['player'];
        }
        if($this->dps2->name == $player['player']->name){
            
            $this->dps2 = $swap['player'];
        }
        if($this->sup1->name == $player['player']->name){
            $this->sup1 = $swap['player'];
        }
        if($this->sup2->name == $player['player']->name){
            $this->sup2 = $swap['player'];
        }
    }
}
