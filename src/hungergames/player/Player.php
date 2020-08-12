<?php

namespace hungergames\player;

use hungergames\math\HungerGamesPosition;
use hungergames\math\HungerGamesVector3;
use pocketmine\level\Position;
use pocketmine\Player as pocketPlayer;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\math\Vector3;
use pocketmine\Server;
use hungergames\arena\Arena;
use hungergames\provider\target\TargetOffline;
use hungergames\HungerGames;

class Player {

    /** @var array */
    private $killer = [];

    /** @var array */
    private $assistance = [];

    /** @var string */
    private $username;

    /** @var Arena */
    private $arena;

    /** @var bool */
    public $spectating = false;

    /** @var int */
    public $slot = -1;

    /** @var TargetOffline */
    private $targetOffline;

    /** @var string|null */
    public $chestType = null;

    /** @var bool */
    public $game_debug = false;

    /** @var int */
    public $kills = 0;

    /** @var int */
    public $chestsOpened = [];

    /**
     * Player constructor.
     * @param string $username
     * @param Arena $arena
     */
    public function __construct(string $username, Arena $arena) {
        $this->username = $username;

        $this->arena = $arena;

        $this->targetOffline = HungerGames::getInstance()->getProvider()->getTargetOffline($this->getName());
    }

    public function clearAll() {
        $instance = $this->getInstance();

        $instance->setGamemode(0);

        $instance->getArmorInventory()->clearAll();

        $instance->getInventory()->clearAll();

        $instance->getInventory()->sendContents($instance);

        $instance->getArmorInventory()->sendContents($instance);

        //$this->setFlying(false);

        $instance->setFood($instance->getMaxFood());

        $instance->removeAllEffects();

        $instance->setHealth($instance->getMaxHealth());

        $instance->getInventory()->setHeldItemIndex(4);
    }

    /**
     * @return pocketPlayer|null
     */
    public function getInstance(): ?pocketPlayer {
        $target = Server::getInstance()->getPlayerExact($this->getName());

        if($target instanceof pocketPlayer) {
            return $target;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getUsernameToLower(): string {
        return strtolower($this->username);
    }

    /**
     * @return Arena
     */
    public function getArena(): Arena {
        return $this->arena;
    }

    /**
     * @return PlayerInventory
     */
    public function getInventory(): PlayerInventory {
        return $this->getInstance()->getInventory();
    }

    /**
     * @return ArmorInventory
     */
    public function getArmorInventory(): ArmorInventory {
        return $this->getInstance()->getArmorInventory();
    }

    /**
     * @return PlayerCursorInventory
     */
    public function getCursorInventory(): PlayerCursorInventory {
        return $this->getInstance()->getCursorInventory();
    }

    /**
     * @return TargetOffline|null
     */
    public function getTargetOffline(): ?TargetOffline {
        return $this->targetOffline;
    }

    /**
     * @return HungerGamesPosition
     */
    public function getPosition(): HungerGamesPosition {
        return new HungerGamesPosition($this->getInstance()->x, $this->getInstance()->y, $this->getInstance()->z, $this->getInstance()->level);
    }

    /**
     * @return HungerGamesVector3
     */
    public function getVector3(): HungerGamesVector3 {
        return new HungerGamesVector3($this->getInstance()->x, $this->getInstance()->y, $this->getInstance()->z);
    }

    /**
     * @return Player|null
     */
    public function getAssistance(): ?Player {
        $data = $this->assistance;

        return isset($data['time']) ? (time() - $data['time']) > 10 ? null : ($this->getArena()->getPlayerByName($data['name']) ?? null) : null;
    }

    /**
     * @return Player|null
     */
    public function getKiller(): ?Player {
        $data = $this->killer;

        return isset($data['time']) ? (time() - $data['time']) > 15 ? null : $this->getArena()->getPlayerByName($data['name']) : null;
    }

    /**
     * @return bool
     */
    public function isOnline(): bool {
        return $this->getInstance() instanceof pocketPlayer;
    }

    /**
     * @return bool
     */
    public function isSpectating(): bool {
        return $this->spectating;
    }

    /**
     * @param Vector3 $pos
     */
    public function teleport(Vector3 $pos) {
        $this->getInstance()->teleport($pos);
    }

    /**
     * @param String $message
     * @return void
     */
    public function sendMessage(String $message): void {
        $this->getInstance()->sendMessage($message);
    }

    /**
     * @param string $message
     * @param array $parameters
     */
    public function sendTranslatedMessage(string $message, array $parameters = []) {
        $this->sendMessage(HungerGames::getInstance()->getTranslationManager()->translateString($message, $parameters));
    }

    /**
     * @param bool $value
     */
    public function setFlying(bool $value) {
        if(HungerGames::isPocketMine()) {
            $this->getInstance()->setFlying($value);
        }

        $this->getInstance()->setAllowFlight($value);
    }

    /**
     * @param string $name
     */
    public function attack(string $name){
        if(isset($this->killer['name'])){
            if(strtolower($this->killer['name']) != strtolower($name) and $this->getKiller() != null) {
                $this->assistance = $this->killer;
            }
        }

        $this->killer = ['name' => $name, 'time' => time()];
    }

    /**
     * @param Position $pos
     */
    public function addChest(Position $pos) {
        $this->chestsOpened[$pos->__toString()] = $pos;
    }

    /**
     * @param Position $pos
     * @return bool
     */
    public function isChestRegistered(Position $pos): bool {
        return isset($this->chestsOpened[$pos->__toString()]);
    }
}