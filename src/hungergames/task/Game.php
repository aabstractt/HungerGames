<?php

namespace hungergames\task;

use pocketmine\scheduler\Task;
use hungergames\HungerGames;

class Game extends Task {

    /**
     * Game constructor.
     */
    public function __construct() {
        $this->setHandler(HungerGames::getInstance()->getScheduler()->scheduleRepeatingTask($this, 20));
    }

    /**
     * Actions to execute when run
     *
     * @param $currentTick
     *
     * @return void
     * @throws \Exception
     */
    public function onRun($currentTick) {
        foreach(HungerGames::getInstance()->getArenaManager()->arenas as $arena) {
            $arena->tick();
        }
    }
}