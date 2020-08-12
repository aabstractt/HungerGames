<?php

namespace hungergames\arena;

use advancedmanagement\AdvancedManagement;
use Exception;
use hungergames\utils\GameChest;
use Kits\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level as pocketLevel;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Scoreboards\Scoreboards;
use hungergames\player\Player;
use hungergames\HungerGames;
use hungergames\utils\API;
use hungergames\utils\Utils;

class Arena {

    /** @var int */
    public const LOBBY = 0, IN_GAME = 1, RESTARTING = 2, DEATH_MATCH = 3, LEAVE_DEATH_TYPE = 300;

    /** @var int */
    public $status = Arena::LOBBY;

    /** @var int */
    private $lobbytime;

    /** @var int */
    private $gametime;

    /** @var int */
    private $endtime;

    /** @var int */
    public $refilltime;

    /** @var int */
    private $deathmatchtime;

    /** @var int */
    private $invincibletime;

    /** @var int */
    private $deathmatchInvincibleTime;

    /** @var Player[] */
    public $players = [];

    /** @var Level */
    private $level;

    /** @var string */
    private $name;

    /** @var int */
    public $bossid;

    /** @var string */
    private $chestType = 'Normal';

    /** @var bool */
    private $refilled = false;

    /** @var GameChest[] */
    public $chests = [];

    /**
     * Arena constructor.
     * @param string $name
     * @param Level $level
     */
    public function __construct(string $name, Level $level) {
        $this->level = $level;

        $this->name = $name;

        $level->arena = $this;

        $this->level->load();

        $this->lobbytime = HungerGames::getInstance()->getConfig()->get('defaultLobbytime');

        $this->gametime = HungerGames::getInstance()->getConfig()->get('defaultGametime');

        $this->endtime = HungerGames::getInstance()->getConfig()->get('defaultEndtime');

        $this->refilltime = HungerGames::getInstance()->getConfig()->get('defaultRefilltime');

        $this->deathmatchtime = HungerGames::getInstance()->getConfig()->get('defaultDeathmatchtime');

        $this->invincibletime = HungerGames::getInstance()->getConfig()->get('defaultInvincibleTime');

        $this->deathmatchInvincibleTime = $this->invincibletime;

        $this->bossid = Entity::$entityCount++;
    }

    /**
     * @return bool
     */
    public function isFull(): bool {
        return count($this->getPlayers()) >= $this->getLevel()->getMaxSlots();
    }

    /**
     * @return Level
     */
    public function getLevel(): Level {
        return $this->level;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return str_replace('hungergames_', '', $this->name);
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @param int $value
     */
    public function setLobbyTime(int $value) {
        $this->lobbytime = $value;
    }

    /**
     * @param int $value
     */
    public function setGameTime(int $value) {
        $this->gametime = $value;
    }

    /**
     * @param int $value
     */
    public function setEndTime(int $value) {
        $this->endtime = $value;
    }

    /**
     * @return int
     */
    public function getLobbyTime(): int {
        return $this->lobbytime;
    }

    /**
     * @return int
     */
    public function getInvincibleTime(): int {
        return $this->invincibletime;
    }

    /**
     * @return int
     */
    public function getDeathmatchInvincibleTime(): int {
        return $this->deathmatchInvincibleTime;
    }

    /**
     * @return string
     */
    public function getChestType(): string {
        return $this->chestType;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array {
        $players = [];

        foreach($this->players as $k => $p) {
            if($p instanceof Player) {
                if($p->isOnline()) {
                    $players[($this->status == Arena::LOBBY) ? $k : strtolower($p->getName())] = $p;
                }
            }
        }

        $this->players = $players;

        return $players;
    }

    /**
     * @return Player[]
     */
    public function getPlayersAlive(): array {
        $players = [];

        foreach($this->getPlayers() as $k => $p) {
            if(!$p->isSpectating()) {
                $players[($this->status == Arena::LOBBY) ? $k : strtolower($p->getName())] = $p;
            }
        }

        return $players;
    }

    /**
     * @return Player|null
     */
    public function getTarget(): ?Player {
        return array_values($this->getPlayersAlive())[0] ?? null;
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getPlayerByName(string $name): ?Player {
        foreach($this->getPlayers() as $p) {
            if($p->getUsernameToLower() == strtolower($name)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function inArenaAsPlayerByName(string $name): bool {
        return $this->getPlayerByName($name) != null;
    }

    /**
     * @param string $name
     */
    public function removePlayerByName(string $name) {
        foreach($this->getPlayers() as $k => $p) {
            if($p->getUsernameToLower() == strtolower($name)) {
                unset($this->players[$k]);
            }
        }
    }

    /**
     * @param Player $player
     * @throws Exception
     */
    public function join(Player $player) {
        if(($arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($player->getName())) instanceof Arena and ($arena->getName() == $player->getArena()->getName())) {
            $player->getInstance()->sendMessage(TextFormat::RED . 'You already have an arena');
        } else if(count($this->players) >= $this->level->getMaxSlots()) {
            $player->getInstance()->sendMessage(TextFormat::RED . 'The game is full');
        } else if(!Server::getInstance()->isLevelGenerated($this->getName())) {
            $player->getInstance()->sendMessage(TextFormat::RED . 'Level ' . $this->getLevel()->getCustomName() . ' not is available.');
        } else {

            for($i = 1; $i <= count($this->level->data['spawn']); $i++) {
                if(!isset($this->players[$i])) {
                    $this->players[$i] = $player;

                    $player->slot = $i;

                    $player->teleport($this->level->getSpawnPosition($i)->add(0.5, 0, 0.5)->asPosition());

                    $player->setFlying(false);

                    $player->clearAll();

                    $player->getInstance()->setImmobile(true);

                    $player->sendMessage(TextFormat::GREEN . 'Game found, sending you to ' . TextFormat::YELLOW . $this->getCustomName() . TextFormat::GREEN . '!');

                    $this->sendMessage('PLAYER_JOIN_GAME', [TextFormat::DARK_GRAY . $player->getName(), count($this->getPlayers()), $this->level->getMaxSlots()]);

                    API::sendBossBarToPlayer($player->getInstance(), $this->bossid, HungerGames::getInstance()->getTranslationManager()->translateString('ADDRESS'));

                    $this->level->addSound($player->getVector3(), 'random.levelup', $this->level->getLevel()->getPlayers());

                    foreach(Utils::getItemsSpectator() as $item) {
                        if(!$item['available']) {
                            $id = explode(':', $item['item-id']);

                            $player->getInventory()->setItem($item['slot'], (Item::get($id[0] ?? 1, $id[1] ?? 0, 1))->setCustomName(TextFormat::colorize($item['item-name'])));
                        }
                    }

                    break;
                }
            }
        }
    }

    /**
     * @param string|null $name
     * @throws Exception
     */
    public function disconnect(string $name = null) {
        $player = $this->getPlayerByName($name);

        if($player == null) {
            Server::getInstance()->getLogger()->error('{$name} not found in ' . $this->name . ' but is intent disconnect');

            return;
        } else if($this->status == Arena::LOBBY) {
            $this->sendMessage('PLAYER_LEFT_GAME', [TextFormat::DARK_GRAY . $player->getName(), count($this->getPlayers()) - 1, $this->level->getMaxSlots()], $player);
        } else if($this->status == Arena::IN_GAME || $this->status == Arena::DEATH_MATCH) {
            if(!$player->isSpectating()) {
                $this->handleDeath($player, Arena::LEAVE_DEATH_TYPE, $player->getKiller());
            }
        }

        HungerGames::getInstance()->getProvider()->setTargetOffline($player->getTargetOffline());

        if($player->isOnline()) {
            $player->getInstance()->setGamemode(0);

            if(Scoreboards::getInstance()->getObjectiveName($player->getInstance()) != null) {
                Scoreboards::getInstance()->remove($player->getInstance());
            }

            API::removeBossBar([$player->getInstance()], $this->bossid);

            $player->getInstance()->removeAllEffects();
        }

        $this->removePlayerByName($name);
    }

    /**
     * @param string $identifier
     * @param array $parameters
     * @param Player|null $except
     */
    public function sendMessage(string $identifier, array $parameters = [], ?Player $except = null) {
        foreach($this->getPlayers() as $player) {
            if($player->isOnline() and ($except == null or ($except instanceof Player && $except->getName() != $player->getName()))) {
                $player->sendTranslatedMessage($identifier, $parameters);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function tick() {
        if($this->status == Arena::LOBBY) {
            if(count($this->getPlayers()) < 2) {
                if($this->lobbytime != HungerGames::getInstance()->getConfig()->get('defaultLobbytime')) {
                    $this->lobbytime = HungerGames::getInstance()->getConfig()->get('defaultLobbytime');

                    $this->sendMessage(TextFormat::RED . 'We don\'t have enough players! Start cancelled.');
                }

                if((time() % 20) == 0) {
                    $this->sendMessage(TextFormat::RED . 'Waiting more players');
                }
            } else {
                if(($this->isFull() and $this->getLobbyTime() > 15) and Utils::canStartWhenIsFull()) {
                    $this->sendMessage(TextFormat::GOLD . '¡Server is now ' . TextFormat::BOLD . 'FULL' . TextFormat::RESET . TextFormat::GOLD . '! ¡Starting game in ' . TextFormat::YELLOW . '15 seconds' . TextFormat::GOLD . '!');

                    $this->lobbytime = 15;
                }

                if($this->lobbytime == 60 || $this->lobbytime == 50 || $this->lobbytime == 40 || $this->lobbytime == 30 || $this->lobbytime == 20 || $this->lobbytime == 10 || ($this->lobbytime > 0 and $this->lobbytime < 6)) {
                    $this->sendMessage(TextFormat::GREEN . 'HungerGames is starting in ' . $this->lobbytime . ' second' . ($this->lobbytime > 1 ? 's' : '') . '.');

                    foreach($this->getPlayers() as $player) {
                        $this->level->addSound($player->getVector3(), 'note.pling');
                    }
                }

                if($this->lobbytime >= 0) {
                    $this->setLobbytime(($this->getLobbytime() - 1));

                    foreach($this->getPlayers() as $p) {
                        $p->getInstance()->sendPopup(TextFormat::GREEN . 'Starting in ' . TextFormat::BOLD . $this->lobbytime . TextFormat::RESET . TextFormat::GREEN . ' second' . ($this->lobbytime > 1 ? 's' : '') . PHP_EOL . PHP_EOL);
                    }
                }

                if($this->lobbytime == 0) {
                    foreach($this->getPlayers() as $player) {
                        $instance = $player->getInstance();

                        $instance->setImmobile(false);

                        $player->getTargetOffline()->data['gamesplayed']++;

                        $player->clearAll();

                        $kit = Server::getInstance()->getPluginManager()->getPlugin('Kits');

                        if($kit instanceof Main) {
                            $kitName = HungerGames::isAuthorized($instance->getName()) ? $player->getTargetOffline()->getKit() : $kit->getPlayer($instance->getName())['defaultKit'];

                            if($kitName != null and Utils::canUseThisKit($kitName)) $kit->setKit($instance, $kit->getKit($kitName));
                        }

                        $this->level->addSound($player->getVector3(), 'mob.enderdragon.growl');
                    }

                    $this->sendMessage(TextFormat::BLUE . 'Game started!');

                    Utils::loadChests($this,  $this->chests);

                    $this->status = Arena::IN_GAME;
                }
            }
        } else if($this->status == Arena::IN_GAME || $this->status == Arena::DEATH_MATCH) {
            $this->setGameTime($this->gametime - 1);

            if($this->status == Arena::IN_GAME and $this->refilltime > 0) $this->refilltime--;

            if($this->refilltime == 0 and !$this->refilled) {
                Utils::loadChests($this, $this->chests);

                $this->sendMessage('REFILL_CHESTS');

                $this->refilled = true;
            }

            if($this->getInvincibleTime() > 0) $this->invincibletime--;

            if($this->getInvincibleTime() == 0) {
                $this->sendMessage(TextFormat::GOLD . 'Damage has been enabled, good luck!');

                $this->invincibletime = -5;
            }

            if($this->getInvincibleTime() <= 0 and $this->deathmatchtime > 0 and (count($this->getPlayersAlive()) <= 4 and $this->status == Arena::IN_GAME)) $this->deathmatchtime--;

            if($this->deathmatchtime == 15 || $this->deathmatchtime == 10 || ($this->deathmatchtime > 0 and $this->deathmatchtime < 6)) {
                $this->sendMessage(TextFormat::YELLOW . 'Deathmatch is starting in ' . $this->deathmatchtime . ' second' . ($this->deathmatchtime > 1 ? 's' : '') . '.');

                foreach($this->getPlayers() as $player) {
                    $this->level->addSound($player->getVector3(), 'note.pling');
                }
            }

            if($this->deathmatchtime == 0 and $this->status == Arena::IN_GAME) {
                $this->sendMessage('DEATHMATCH_STARTED');

                foreach($this->getPlayers() as $p) {
                    if(!$p->isSpectating()) {
                        $p->getInstance()->setImmobile(true);

                        $p->teleport($this->level->getSpawnVector($p->slot)->asVector3());
                    } else {
                        $p->teleport($this->getLevel()->getLevel()->getSpawnLocation());
                    }

                    $this->level->addSound($p->getVector3(), 'ambient.weather.thunder', $this->getPlayers());
                }

                $this->status = Arena::DEATH_MATCH;

            } else if($this->status == Arena::DEATH_MATCH and $this->deathmatchInvincibleTime > 0) {
                $this->deathmatchInvincibleTime--;

                if($this->getDeathmatchInvincibleTime() <= 0) {
                    foreach($this->getPlayers() as $p) $p->getInstance()->setImmobile(false);
                }
            }

            $api = Scoreboards::getInstance();

            if($api instanceof Scoreboards) {
                foreach($this->getPlayers() as $p) {
                    $instance = $p->getInstance();

                    $api->new($instance, 'HungerGames', TextFormat::YELLOW . TextFormat::BOLD . 'HungerGames');

                    $line = null;

                    if($this->getInvincibleTime() > 0 or ($this->getDeathmatchInvincibleTime() > 0 and $this->status == Arena::DEATH_MATCH)) {
                        $line = '&fDaños habilitados en: &a' . HungerGames::timeString($this->getInvincibleTime() > 0 ? $this->getInvincibleTime() : $this->getDeathmatchInvincibleTime());
                    } else if($this->status == Arena::IN_GAME and $this->refilltime > 0) {
                        $line = 'General refill: &a' . HungerGames::timeString($this->refilltime);
                    } else if($this->deathmatchtime > 0) {
                        $line = '&fDeathmatch en: &a' . HungerGames::timeString($this->deathmatchtime);
                    } else {
                        $line = '&fTermina en: &a' . HungerGames::timeString($this->gametime);
                    }

                    if($line != null) {
                        $api->setLine($instance, 5, TextFormat::colorize($line));

                        $api->setLine($instance, 4, TextFormat::WHITE);
                    }

                    $api->setLine($instance, 3, TextFormat::colorize('&fMapa: &a' . $this->level->getCustomName()));

                    $api->setLine($instance, 2, TextFormat::colorize('&fJugadores: &a' . count($this->getPlayersAlive()) . '/&c' . $this->level->getMaxSlots()));

                    $api->setLine($instance, 1, TextFormat::BLUE);

                    $api->setLine($instance, 0, HungerGames::getInstance()->getTranslationManager()->translateString('ADDRESS'));
                }
            }

            if(($this->gametime <= 0) or (count($this->getPlayersAlive()) <= 0 or count($this->getPlayers()) <= 0)) {
                Server::getInstance()->broadcastMessage(TextFormat::colorize('&0&lHungerGames &r&6There are no winners in the &e' . $this->level->getCustomName() . ' &6arena'));

                $this->status = Arena::RESTARTING;
            } else if(count($this->getPlayersAlive()) == 1) {
                $target = $this->getTarget();

                $this->gamestatus($target);

                Server::getInstance()->broadcastMessage(HungerGames::getInstance()->getTranslationManager()->translateString('WON_MESSAGE', [$target->getName(), $this->level->getCustomName()]));

                $target->getTargetOffline()->data['wins']++;

                HungerGames::getInstance()->getProvider()->setTargetOffline($target->getTargetOffline());

                $target->getInventory()->setItemInHand(Item::get(Item::TOTEM));

                $target->getInstance()->broadcastEntityEvent(65);

                $target->getInventory()->setItemInHand(Item::get(0));

                $target->getInstance()->addTitle(TextFormat::GOLD . TextFormat::BOLD . '#1' . TextFormat::GREEN . ' YOU WON', TextFormat::AQUA . $target->kills . ' Kills', 50, 50, 50);

                $this->level->addSound($target->getVector3(), 'random.totem', $this->level->getLevel()->getPlayers());

                $this->status = Arena::RESTARTING;
                
                EconomyAPI::getInstance()->addMoney($target->getName(), 30);

                $target->sendMessage(TextFormat::GREE . '+30 coins');

                foreach($this->level->getLevel()->getPlayers() as $player) {
                    if(!$this->inArenaAsPlayerByName($player->getName())) Server::getInstance()->dispatchCommand($player, 'hub');
                }

                foreach($this->getPlayers() as $p) {
                    if($p->getTargetOffline()->inAutoMode()) Utils::schedulerAutomode($p->getInstance());
                }
            }
        } else if($this->status == Arena::RESTARTING) {
            $this->setEndtime(($this->endtime - 1));

            if($this->endtime == 15) {
                foreach($this->getPlayers() as $player) {
                    Server::getInstance()->dispatchCommand($player->getInstance(), 'hub');
                }
            } else if($this->endtime == 10) {
                $level = $this->level->getLevel();

                if($level instanceof pocketLevel) {
                    Server::getInstance()->unloadLevel($level);
                }
            } else if($this->endtime == 5) {
                Utils::deleteDir(Server::getInstance()->getDataPath() . DIRECTORY_SEPARATOR . 'worlds' . DIRECTORY_SEPARATOR . $this->getName() . '/');
            } else if($this->endtime <= 0) {
                if(is_dir(Server::getInstance()->getDataPath() . 'worlds/' . $this->name . '/')) {
                    Utils::deleteDir(Server::getInstance()->getDataPath() . DIRECTORY_SEPARATOR . 'worlds' . DIRECTORY_SEPARATOR . $this->getName() . '/');
                }

                HungerGames::getInstance()->getArenaManager()->deleteFromArenas($this);
            }
        }
    }

    /**
     * @param Player|null $player
     * @param int $cause
     * @param Player|null $target
     * @throws Exception
     */
    public function handleDeath(?Player $player, int $cause, ?Player $target) {
        $instance = $player->getInstance();

        if($player->isSpectating()) return;

        $this->gamestatus($player);

        $player->getTargetOffline()->data['deaths']++;

        if($target instanceof Player) {
            $target->getTargetOffline()->data['kills']++;

            $target->kills++;
        }

        $instance->addTitle(TextFormat::YELLOW . TextFormat::BOLD . '#' . count($this->getPlayersAlive()) . TextFormat::RED . ' GAME OVER', TextFormat::AQUA . 'MaxCraft Hunger Games', 50, 50, 50);

        foreach($instance->getDrops() as $drop) {
            $instance->getLevel()->dropItem($instance, $drop);
        }

        $this->level->addSound($player->getVector3(), 'random.screenshot', $this->getPlayers());

        if($target instanceof Player) {
            $targetData = AdvancedManagement::getInstance()->getProvider()->getTargetData($target->getName());

            if($cause != EntityDamageByEntityEvent::CAUSE_VOID) AdvancedManagement::getInstance()->getEffectsManager()->getEffect($targetData['effect'])->execute($instance, $targetData['title'], $target->getName(), $target->getTargetOffline()->getKills());
        }

        if($player->slot <= 0) {
            $instance->teleport($this->level->getLevel()->getSafeSpawn());
        } else if($cause < Arena::LEAVE_DEATH_TYPE) {
            if($target instanceof Player) {
                $instance->teleport($target->getVector3()->asVector3());
            }

            $player->clearAll();

            $instance->setGamemode(3);

            $instance->addEffect((new EffectInstance(Effect::getEffect(Effect::BLINDNESS)))->setDuration(40)->setAmplifier(1)->setVisible(false));

            foreach(Utils::getItemsSpectator() as $item) {
                if($item['available']) {
                    $id = explode(':', $item['item-id']);

                    $instance->getInventory()->setItem($item['slot'], (Item::get($id[0] ?? 1, $id[1] ?? 0, 1))->setCustomName(TextFormat::colorize($item['item-name'])));
                }
            }
        }

        $player->spectating = true;

        $message = null;

        $debug = 'arena_id:' . $this->getCustomName() . '_folder:' . $this->level->getFolderName() . '_customName:' . $this->level->getCustomName() . '_cause:';

        switch($cause) {
            case EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::GOLD . $target->getName();
                }
                break;

            case EntityDamageEvent::CAUSE_FIRE:
            case EntityDamageEvent::CAUSE_FIRE_TICK:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_FIRE';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was burn by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_FIRE';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was burn';
                }
                break;

            case EntityDamageEvent::CAUSE_LAVA:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_LAVA';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_LAVA';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' tried to swim in lava';
                }
                break;

            case EntityDamageEvent::CAUSE_FALL:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_FALL';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_FALL';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' hit the ground too hard';
                }
                break;

            case EntityDamageEvent::CAUSE_SUFFOCATION:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_SUFFOCATION';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_SUFFOCATION';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was suffocated';
                }
                break;

            case EntityDamageEvent::CAUSE_PROJECTILE:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_PROJECTILE';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was shot by ' . TextFormat::GOLD . $target->getName();
                }
                break;

            case EntityDamageEvent::CAUSE_VOID:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_VOID';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was thrown into the void by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_VOID';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' fell into the void';
                }
                break;

            default:
                if($target instanceof Player) {
                    $debug .= 'ENTITY_ATTACK_UNKNOWN';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' was slain by ' . TextFormat::GOLD . $target->getName();
                } else {
                    $debug .= 'ENTITY_UNKNOWN';

                    $message = TextFormat::GOLD . $player->getName() . TextFormat::YELLOW . ' died';
                }
                break;
        }

        if($player->game_debug) $player->sendMessage($debug);

        $assistance = $player->getAssistance();

        if($message != null) {
            if($assistance instanceof Player) {
                $assistance->sendMessage($message . TextFormat::YELLOW . '.' . TextFormat::GRAY . ' (' . TextFormat::GOLD . '+1 assist' . TextFormat::GRAY . ')');
            }

            $this->sendMessage($message, [], $assistance);
        }

        if($player->getTargetOffline()->inAutoMode()) Utils::schedulerAutomode($player->getInstance());
    }

    /**
     * @param Player $player
     */
    private final function gamestatus(Player $player) {
        $player->sendTranslatedMessage('GAME_STATUS', [count($this->getPlayersAlive()), HungerGames::timeString((HungerGames::getInstance()->getConfig()->get('defaultGametime') - $this->gametime), true), $player->kills, count($player->chestsOpened)]);
    }
}