<?php

namespace hungergames\utils;

use hungergames\arena\Arena;
use hungergames\HungerGames;
use hungergames\player\Player as HungerGamesPlayer;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class HungerGamesJoin extends Human {

    /**
     * @return string
     */
    public function getName(): string {
        return 'HungerGames Join';
    }

    /**
     * @param EntityDamageEvent $source
     * @throws \Exception
     */
    public function attack(EntityDamageEvent $source): void {
        $source->setCancelled();

        if($source instanceof EntityDamageByEntityEvent) {
            $target = $source->getDamager();

            if($target instanceof Player) {
                if($target->isSneaking()) {
                    Server::getInstance()->dispatchCommand($target, 'hg levels');
                } else if(!($currentArena = HungerGames::getInstance()->getArenaManager()->getArenaAvailable()) instanceof Arena) {
                    $target->sendMessage(TextFormat::RED . 'Games not available');
                } else {
                    $currentArena->join(new HungerGamesPlayer($target->getName(), $currentArena));
                }
            }
        }
    }
}