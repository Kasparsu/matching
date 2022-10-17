<?php

namespace Kaspa\Matching;

use Exception;

class Roster {
    private $players = [];
    private $teams = [];
    public function __construct($file) {
        $json = file_get_contents($file);
        $players = json_decode($json);
        foreach($players as $player){
            $p = new Player($player);
            $this->players[] = $p;
        }
        
    }

    public function createInitialMatchup(){
        
        if($this->matchupsPossible()){
            $available = $this->players;
            $tanks = $this->sortByClosnessToAvgSr($this->getTanks(),$this->getAvgRoleSr('tank'), 'tank', []);
            $teams = [];
            for($i=0; $i<$this->matchupsCount();$i++){
                $team = new Team();
                $team->tank = $tanks[0];
                $available = $this->removeFromPlayerArray($available, $team->tank);
                $tanks = $this->sortByClosnessToAvgSr($this->getTanksFromPlayers($available),$team->tank->srs['tank'], 'tank', []);

                $this->teams[] = $team;
            }
            
            $dps = $this->sortByClosnessToAvgSr($this->getDpsFromPlayers($available),$this->getAvgRoleSr('dps'), 'dps', ['tank']);
            
            for($i=0; $i<$this->matchupsCount();$i++){
                $this->teams[$i]->dps1 = $dps[0];
                $available = $this->removeFromPlayerArray($available, $this->teams[$i]->dps1);
                $dps = $this->sortByClosnessToAvgSr($this->getDpsFromPlayers($available),$this->teams[$i]->dps1->srs['dps'], 'dps', ['tank']);

            }
            $dps = $this->sortByClosnessToAvgSr($this->getDpsFromPlayers($available),$this->getAvgRoleSr('dps'), 'dps', ['tank']);
            
            for($i=0; $i<$this->matchupsCount();$i++){
                $this->teams[$this->matchupsCount()-1-$i]->dps2 = $dps[0];
                $available = $this->removeFromPlayerArray($available, $this->teams[$this->matchupsCount()-1-$i]->dps2);
                $dps = $this->sortByClosnessToAvgSr($this->getDpsFromPlayers($available),$this->teams[$this->matchupsCount()-1-$i]->dps2->srs['dps'], 'dps', ['tank']);

            }
            $sup = $this->sortByClosnessToAvgSr($this->getSupFromPlayers($available),$this->getAvgRoleSr('sup'), 'sup', ['tank', 'dps']);
            
            for($i=0; $i<$this->matchupsCount();$i++){
                $this->teams[$i]->sup1 = $sup[0];

                $available = $this->removeFromPlayerArray($available, $this->teams[$i]->sup1);
                $sup = $this->sortByClosnessToAvgSr($this->getSupFromPlayers($available),$this->teams[$i]->sup1->srs['sup'], 'sup', ['tank', 'dps']);

            }
            $sup = $this->sortByClosnessToAvgSr($this->getSupFromPlayers($available),$this->getAvgRoleSr('sup'), 'sup', ['tank', 'dps']);
            for($i=0; $i<$this->matchupsCount();$i++){
                $this->teams[$this->matchupsCount()-1-$i]->sup2 = $sup[0];
                $available = $this->removeFromPlayerArray($available, $this->teams[$this->matchupsCount()-1-$i]->sup2);
                $sup = $this->sortByClosnessToAvgSr($this->getSupFromPlayers($available),$this->teams[$this->matchupsCount()-1-$i]->sup1->srs['sup'], 'sup', ['tank', 'dps']);

            }
            foreach($this->teams as $team){
                $team->getAvgSr();
            }
            return $this->teams;
        }
    }
    public function balance(){

        $roles = ['tank', 'dps', 'dps', 'sup', 'sup'];
        for($j=1;$j<count($this->teams);$j++){
            for($i=0; $i<5;$i++){
                usort($this->teams, function($teamA,$teamB) use($roles, $i) {
                    return $teamB->getAvgRoleSr($roles[$i]) - $teamA->getAvgRoleSr($roles[$i]);
                });
                
                $lowest = $this->teams[count($this->teams)-$j]->getMembersSortedBySr()[0];
                $swaps = $this->teams[0]->getSwappableMembersSortedBySr($lowest['player'], $lowest['role']);
                if($i==4){
                    dump($lowest);
                    dump($swaps);
                }
                foreach($swaps as $swap){
                    $team1 = clone $this->teams[0];
                    $team2 = clone $this->teams[count($this->teams)-1];
                    $team2->swap($lowest, $swap);
                    $team1->swap($swap, $lowest);
                    $team1->getAvgSr();
                    $team2->getAvgSr();
                    if(abs($team1->getAvgRoleSr($lowest['role'])-$team2->getAvgRoleSr($lowest['role']))<abs($this->teams[0]->getAvgRoleSr($lowest['role'])-$this->teams[count($this->teams)-1]->getAvgRoleSr($lowest['role'])) &&
                    abs($team1->getAvgRoleSr($swap['role'])-$team2->getAvgRoleSr($swap['role']))<abs($this->teams[0]->getAvgRoleSr($swap['role'])-$this->teams[count($this->teams)-1]->getAvgRoleSr($swap['role']))){
                        $this->teams[0] = $team1;
                        $this->teams[count($this->teams)-1] = $team2;
                        $i=-1;
                        break;
                    }
                }
            }
        }
        return $this->teams;
    }
    public function getAvgSr(){
        return array_sum(array_map(function($player){
            return $player->getAvgSr();
        }, $this->players)) / count($this->players);
    }
    public function getAvgRoleSr($role){
        return array_sum(array_map(function($player) use($role){
            return $player->srs[$role];
        }, $this->getRole($role))) / count($this->getRole($role));
    }

    public function getRole($role){
        switch($role){
            case 'tank':
                return $this->getTanks();
            case 'dps':
                return $this->getDps();
            case 'sup':
                return $this->getSup();
        }
    }
    public function getTanks(){
       return array_filter($this->players, fn($player) => $player->isTank());
    }

    public function getSup(){
        return array_filter($this->players, fn($player) => $player->isSup());
    }

    public function getDps(){
        return array_filter($this->players, fn($player) => $player->isDps());
    }
    public function getTanksFromPlayers($players){
        return array_filter($players, fn($player) => $player->isTank());
     }
 
     public function getSupFromPlayers($players){
         return array_filter($players, fn($player) => $player->isSup());
     }
 
     public function getDpsFromPlayers($players){
         return array_filter($players, fn($player) => $player->isDps());
     }
    public function sortByClosnessToAvgSr($players, $sr, $role, $selected){
        usort($players, function($a, $b) use($role, $selected, $sr) {
            $aRoles = array_values(array_filter($a->getRoles(), function($r) use($selected) {
                return !in_array($r, $selected);
            }));
            $bRoles = array_values(array_filter($b->getRoles(), function($r) use($selected) {
                return !in_array($r, $selected);
            }));
            
            if(count($aRoles)==1 && count($bRoles)>1 && $aRoles[0] == $role){
                return -1;
            }
            elseif(count($bRoles)==1 && count($aRoles)>1 && $bRoles[0] == $role){
                return 1;
            }
            return abs($sr - $a->srs[$role]) - abs($sr - $b->srs[$role]);
                
        });
        return $players;
    }

    public function matchupsPossible(){
        if(count($this->players)<10){
            dump('too few players');
            return false;
        }
        if(count($this->getTanks())<2){
            dump('too few tanks');
            return false;
        }
        if(count($this->getSup())<4){
            dump('too few supports');
            return false;
        }
        if(count($this->getDps())<4){
            dump('too few supports');
            return false;
        }
        return true;
    }

    public function matchupsCount(){
       
        return floor(count($this->players)/5);
    }

    public function removeFromPlayerArray($array, $player){
        return array_filter($array, fn($p) => $p->name != $player->name);
    }

    public function output(){
        $output = '';
        foreach($this->teams as $key => $team){
            $nr = $key+1;
            $output.= "Team $nr\n";
            $output.= "---------------------\n";
            $output.= str_pad($team->tank->name, 17) . $team->tank->srs['tank'] . "\n";
            $output.= str_pad($team->dps1->name, 17) . $team->dps1->srs['dps'] . "\n";
            $output.= str_pad($team->dps2->name, 17) . $team->dps2->srs['dps'] . "\n";
            $output.= str_pad($team->sup1->name, 17) . $team->sup1->srs['sup'] . "\n";
            $output.= str_pad($team->sup2->name, 17) . $team->sup2->srs['sup'] . "\n";
            $output.= "---------------------\n";
            $output.= str_pad("Average Sr", 16) . $team->avgSr . "\n";
            $output.= str_pad("Avg Tank Sr", 16) . $team->getAvgRoleSr('tank') . "\n";
            $output.= str_pad("Avg Dps Sr", 16) . $team->getAvgRoleSr('dps') . "\n";
            $output.= str_pad("Avg Sup Sr", 16) . $team->getAvgRoleSr('sup') . "\n";
            $output.= "\n";
            $output.= "*********************\n";

        }
        return $output;
    }
}