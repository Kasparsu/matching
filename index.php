<?php

use Kaspa\Matching\Player;
use Kaspa\Matching\Roster;

require 'vendor/autoload.php';



$roster = new Roster('players.json');
$roster->createInitialMatchup();
$roster->balance();
echo $roster->output();
