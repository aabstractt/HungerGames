<?php

namespace hungergames\player;

use pocketmine\math\Vector3;
use pocketmine\Server;
use hungergames\HungerGames;
use hungergames\utils\Utils;

class Configurator {

    /** @var string */
    private $username;

    /** @var array */
    public $data;

    /**
     * Configurator constructor.
     * @param string $username
     * @param string $folderName
     * @param string $customName
     * @param int $slots
     * @param int $nearDistance
     */
    public function __construct(string $username, string $folderName, string $customName, int $slots, int $nearDistance) {
        $this->username = $username;

        $this->data = [
            'folderName' => $folderName,
            'customName' => $customName,
            'slots' => $slots,
            'match' => (HungerGames::getInstance()->getLevelManager()->exists($folderName) ? HungerGames::getInstance()->getLevelManager()->get($folderName)->data['match'] : []),
            'spawn' => (HungerGames::getInstance()->getLevelManager()->exists($folderName) ? HungerGames::getInstance()->getLevelManager()->get($folderName)->data['spawn'] : []),
            'nearDistance' => $nearDistance
        ];
    }

    /**
     * @return bool
     */
    public function run(): bool {
        if(!Server::getInstance()->isLevelGenerated($this->getFolderName())) {
            Server::getInstance()->getLogger()->error('Error loading level.');
            return false;
        } else if(!Server::getInstance()->isLevelLoaded($this->getFolderName())) {
            Server::getInstance()->loadLevel($this->getFolderName());
        }

        return true;
    }

    /**
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getUsernameToLower(): string {
        return strtolower($this->getUsername());
    }

    /**
     * @return string
     */
    public function getFolderName(): string {
        return $this->data['folderName'];
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return $this->data['customName'];
    }

    /**
     * @param int $slot
     * @param Vector3 $pos
     */
    public function setSpawnVector3(int $slot, Vector3 $pos) {
        $this->data['spawn'][$slot] = ['x' => $pos->x, 'y' => $pos->y, 'z' => $pos->z];
    }

    /**
     * @param string $slot
     * @param Vector3 $pos
     */
    public function setMatchVector3(string $slot, Vector3 $pos) {
        $this->data['match'][$slot] = ['x' => $pos->x, 'y' => $pos->y, 'z' => $pos->z];
    }

    /**
     * @param string $name
     */
    public function save(string $name) {
        Utils::backup(Server::getInstance()->getDataPath() . 'worlds/' . $this->getFolderName(), HungerGames::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName());

        HungerGames::getInstance()->getLevelManager()->add($this->data);

        HungerGames::getInstance()->getLevelManager()->save();

        Utils::removeFromConfigurator($name);
    }
}