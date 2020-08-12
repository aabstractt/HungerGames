<?php

namespace hungergames\command\listener;

use Exception;
use Kits\PlayerKitSelectEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player as pocketPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use hungergames\arena\Arena;
use hungergames\player\Player;
use hungergames\HungerGames;
use hungergames\utils\Utils;

class HungerGamesEvents implements Listener {

    /**
     * HungerGamesEvents constructor.
     */
    public function __construct() {
		Server::getInstance()->getPluginManager()->registerEvents($this, HungerGames::getInstance());
	}

    /**
     * @param EntityLevelChangeEvent $ev
     * @throws Exception
     */
    public function onEntityLevelChange(EntityLevelChangeEvent $ev) {
        $entity = $ev->getEntity();

        if($entity instanceof pocketPlayer) {
            $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($entity->getName());

            if($arena instanceof Arena) {
                if($ev->getTarget()->getFolderName() != $arena->getName()) {
                    $arena->disconnect($entity->getName());
                }
            }
        }
    }

    /**
     * @param PlayerQuitEvent $ev
     * @throws Exception
     */
    public function onQuit(PlayerQuitEvent $ev) {
        $player = $ev->getPlayer();

        Utils::removeFromConfigurator($player->getName());

        HungerGames::getInstance()->getArenaManager()->removeFromArena($player->getName());
    }

    /**
     * @param PlayerMoveEvent $ev
     */
    public function onMove(PlayerMoveEvent $ev) {
        $player = $ev->getPlayer();

        if(($arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName())) instanceof Arena) {
            if($arena->getStatus() == Arena::DEATH_MATCH) {
                $posStart = $arena->getLevel()->getMatchStart();

                $posStop = $arena->getLevel()->getMatchStop();

                $x = floor($player->x);

                $z = floor($player->z);

                if(!(((min($posStart->x, $posStop->x) <= $x) && (max($posStart->x, $posStop->x) >= $x)) && ((min($posStart->z, $posStop->z) <= $z) && (max($posStart->z, $posStop->z) >= $z)))) {
                    $target = $arena->getPlayerByName($player->getName());

                    $target->teleport($arena->getLevel()->getSpawnVector($target->slot)->asVector3());
                }

                unset($posStop, $posStop, $arena, $x, $z);
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $ev
     */
    public function onDropItem(PlayerDropItemEvent $ev) {
        $player = $ev->getPlayer();

        $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

        if($arena instanceof Arena) {
            if($arena->getStatus() != Arena::IN_GAME and $arena->getStatus() != Arena::DEATH_MATCH) {
                $ev->setCancelled(true);
            } else if($arena->getPlayerByName($player->getName())->isSpectating()) {
                $ev->setCancelled(true);
            }
        }
    }

    /**
     * @param InventoryOpenEvent $ev
     * @throws Exception
     */
    public function onInventoryOpen(InventoryOpenEvent $ev) {
        $player = $ev->getPlayer();

        $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

        $inv = $ev->getInventory();

        if($arena instanceof Arena and ($inv instanceof ChestInventory)) {
            if($arena->getStatus() != Arena::IN_GAME and $arena->getStatus() != Arena::DEATH_MATCH) {
                $ev->setCancelled(true);
            } else if(!$arena->getPlayerByName($player->getName())->isChestRegistered($inv->getHolder())) {
                Utils::loadChests($arena, $arena->chests);

                $arena->getPlayerByName($player->getName())->addChest($inv->getHolder());
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $ev
     */
    public function onInventoryTransaction(InventoryTransactionEvent $ev) {
        $player = $ev->getTransaction()->getSource();

        $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

        if($arena instanceof Arena) {
            if(($arena->getStatus() != Arena::IN_GAME and $arena->getStatus() != Arena::DEATH_MATCH) or ($arena->getPlayerByName($player->getName())->isSpectating())) {
                foreach($ev->getTransaction()->getActions() as $action) {
                    if($action instanceof SlotChangeAction and ($action->getInventory() === $player->getInventory())) {
                        $ev->setCancelled();
                    }
                }
            }
        }
    }

    /**
     * @param BlockBreakEvent $ev
     */
    public function onBreak(BlockBreakEvent $ev): void {
		$player = $ev->getPlayer();

		$arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

		if($arena instanceof Arena) {
		    if($ev->getBlock()->getId() == 18) {
		        $ev->setDrops([Item::get(Item::APPLE)]);
            } else {
		        $ev->setCancelled();
            }
        }
	}

    /**
     * @param BlockPlaceEvent $ev
     */
    public function onPlace(BlockPlaceEvent $ev): void {
        $player = $ev->getPlayer();

        $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName());

        if($arena instanceof Arena) {
            $ev->setCancelled();
        }
	}

    /**
     * @param EntityDamageEvent $ev
     * @throws Exception
     */
    public function onDamage(EntityDamageEvent $ev) {
        $entity = $ev->getEntity();

        if($entity instanceof pocketPlayer) {
            $arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($entity->getName());

            if($arena instanceof Arena) {
                if($arena->status != Arena::IN_GAME and $arena->getStatus() != Arena::DEATH_MATCH) {
                    $ev->setCancelled();
                } else if(($arena->getInvincibleTime() > 0 and $arena->getStatus() == Arena::IN_GAME) or ($arena->getStatus() == Arena::DEATH_MATCH and $arena->getDeathmatchInvincibleTime() > 0)) {
                    $ev->setCancelled();
                } else if(($player = $arena->getPlayerByName($entity->getName())) instanceof Player) {
                    if($ev instanceof EntityDamageByEntityEvent) {
                        $damager = $ev->getDamager();

                        if($damager instanceof pocketPlayer) {
                            $target = $arena->getPlayerByName($damager->getName());

                            if($target instanceof Player) {
                                $player->attack($target->getName());
                            }
                        }
                    }

                    if(($ev->getFinalDamage() + 1.4) >= $entity->getHealth()) {
                        $ev->setCancelled();

                        $arena->handleDeath($player, $ev->getCause(), $player->getKiller());
                    }
                }
            }
        }
    }

    /**
     * @param DataPacketReceiveEvent $ev
     * @throws Exception
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $ev) {
        $player = $ev->getPlayer();

        $pk = $ev->getPacket();

        if(HungerGames::getInstance()->getArenaManager()->hasArenaByPlayer($player->getName())) {
            if($player instanceof pocketPlayer) {
                if($pk instanceof PlayerActionPacket) {
                    if($pk->action == 25) {
                        foreach(Utils::getItemsSpectator() as $item) {
                            if(TextFormat::colorize($item['item-name']) == $player->getInventory()->getItemInHand()->getCustomName()) {
                                Server::getInstance()->dispatchCommand($player, $item['item-action']['command']);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param PlayerKitSelectEvent $ev
     */
    public function onKitSelect(PlayerKitSelectEvent $ev) {
        $player = $ev->getPlayer();

        if(HungerGames::getInstance()->getArenaManager()->hasArenaByPlayer($player->getName())) {
            if(!Utils::canUseThisKit($ev->getKitName())) {
                $ev->setCancelled();
            } else {
                HungerGames::getInstance()->getArenaManager()->getPlayerByName($player->getName())->getTargetOffline()->data['kit'] = $ev->getKitName();
            }
        }
    }
}