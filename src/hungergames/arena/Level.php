<?php

namespace hungergames\arena;

use hungergames\player\Player as HungerGamesPlayer;
use pocketmine\level\Level as pocketLevel;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\Server;
use hungergames\math\HungerGamesPosition;
use hungergames\math\HungerGamesVector3;
use hungergames\HungerGames;
use hungergames\utils\Utils;

class Level {

    /** @var array */
    public $data = [];

    /** @var Arena */
    public $arena;

    /**
     * Level constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
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
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->data['slots'];
    }

    /**
     * @return int
     */
    public function getNearDistance(): int {
        return $this->data['nearDistance'];
    }

    /**
     * @return pocketLevel
     */
    public function getLevel(): ?pocketLevel {
        return Server::getInstance()->getLevelByName($this->arena->getName());
    }

    /**
     * @param int $slot
     * @return HungerGamesPosition|null
     */
    public function getSpawnPosition(int $slot): ?HungerGamesPosition {
        if(!$this->isSlot($slot)) {
            return null;
        }

        return HungerGamesPosition::fromArray($this->data['spawn'][$slot], $this->getLevel());
    }

    /**
     * @param int $slot
     * @return HungerGamesVector3|null
     */
    public function getSpawnVector(int $slot): ?HungerGamesVector3 {
        if(!$this->isSlot($slot)) {
            return null;
        }

        return HungerGamesVector3::fromArray($this->data['spawn'][$slot]);
    }

    /**
     * @return HungerGamesVector3|null
     */
    public function getMatchStart(): ?HungerGamesVector3 {
        return HungerGamesVector3::fromArray($this->data['match']['start']);
    }

    /**
     * @return HungerGamesVector3|null
     */
    public function getMatchStop(): ?HungerGamesVector3 {
        return HungerGamesVector3::fromArray($this->data['match']['stop']);
    }

    /**
     * @param int $slot
     * @return bool
     */
    public function isSlot(int $slot): bool {
        return isset($this->data['spawn'][$slot]);
    }

    /**
     * @param HungerGamesVector3 $vector3
     * @param string $soundName
     * @param Player[] | HungerGamesPlayer[]  $players
     */
    public function addSound(HungerGamesVector3 $vector3, string $soundName, array $players = []) {
        $pk = new PlaySoundPacket();

        $pk->soundName = $soundName;

        $pk->volume = 1;

        $pk->pitch = 1;

        $pk->x = $vector3->x;

        $pk->y = $vector3->y;

        $pk->z = $vector3->z;

        if(empty($players)) {
            $this->getLevel()->addChunkPacket($vector3->x >> 4, $vector3->z >> 4, $pk);
        } else {
            $this->broadcastPacket($players, $pk);
        }
    }

    /**
     * @param Player[] | HungerGamesPlayer[] $players
     * @param DataPacket $pk
     */
    public function broadcastPacket(array $players, DataPacket $pk) {
        foreach($players as $player) {
            if($player instanceof HungerGamesPlayer) {
                $player->getInstance()->dataPacket($pk);
            } else if($player instanceof Player) {
                $player->dataPacket($pk);
            }
        }
    }

    public function load() {
        if(!is_dir(HungerGames::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName() . DIRECTORY_SEPARATOR)) {
            Server::getInstance()->getLogger()->error('Level ' . $this->arena->getName() . ' not found.');
        } else {
            Utils::backup(HungerGames::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName(), Server::getInstance()->getDataPath() . 'worlds/' . $this->arena->getName());

            Server::getInstance()->loadLevel($this->arena->getName());

            $this->getLevel()->setTime(0);

            $this->getLevel()->stopTime();
        }
    }
}