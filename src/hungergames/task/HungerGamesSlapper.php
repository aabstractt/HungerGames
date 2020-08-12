<?php

namespace hungergames\task;

use pocketmine\block\Block;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use hungergames\HungerGames;
use hungergames\utils\HungerGamesJoin;

class HungerGamesSlapper extends Task {

    /**
     * HungerGamesSlapper constructor.
     */
    public function __construct() {
        $this->setHandler(HungerGames::getInstance()->getScheduler()->scheduleRepeatingTask($this, 20));
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     * @return void
     */
    public function onRun(int $currentTick) {
        foreach(Server::getInstance()->getDefaultLevel()->getEntities() as $entity) {
            if($entity instanceof HungerGamesJoin) {
                $entity->setNameTag(HungerGames::getInstance()->getTranslationManager()->translateString('NAMEDTAG', [HungerGames::getInstance()->getArenaManager()->getOnlinePlayers()]));

                $entity->getLevel()->addParticle(new DestroyBlockParticle($entity->getPosition(), Block::get(Block::SNOW_BLOCK)));
            }
        }
    }
}