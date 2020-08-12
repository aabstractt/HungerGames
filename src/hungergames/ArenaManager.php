<?php

namespace hungergames;

use DirectoryIterator;
use hungergames\player\Player;
use pocketmine\Server;
use hungergames\arena\Arena;
use hungergames\arena\Level;
use hungergames\utils\Utils;

class ArenaManager {

    /** @var Arena[] */
    public $arenas = [];

    public function __construct() {
        foreach(new DirectoryIterator(Server::getInstance()->getDataPath() . 'worlds/') as $file) {
            if($file->isDir()) {
                if(strpos($file->getPathname(), 'hungergames_game') !== false) {
                    Utils::deleteDir($file->getPathname());
                }
            }
        }
    }

    /**
     * @param Level|null $level
     * @return Arena|null
     */
    public function createArena(?Level $level = null): ?Arena {
        if($level == null) {
            $level = HungerGames::getInstance()->getLevelManager()->getLevelForArena();
        }

        if($level == null) {
            return null;
        }

        $name = $this->identifierGame();

        $this->arenas[$name] = ($arena = new Arena($name, $level));

        unset($name, $level);

        return $arena;
    }

    /**
     * @param string $folderName
     * @param bool $value
     * @return Arena|null
     */
    public function getCurrentArena(string $folderName, bool $value = false): ?Arena {
        $arenas = $this->getArenasByLevel($folderName);

        foreach($arenas as $arena) {
            if(($arena->getStatus() == Arena::LOBBY and $arena->getLobbytime() > 0) and (count($arena->getPlayersAlive()) > 0 and !$arena->isFull())) {
                return $arena;
            }
        }

        if($value) return null;

        foreach($arenas as $arena) {
            if($arena->getStatus() == Arena::LOBBY and $arena->getLobbytime() > 0 and !$arena->isFull()) {
                return $arena;
            }
        }

        return count($arenas) <= HungerGames::getInstance()->getConfig()->get('arenas-allowed-map') ? $this->createArena(new Level(HungerGames::getInstance()->getLevelManager()->get($folderName)->data)) : null;
    }

    /**
     * @return Arena|null
     */
    public function getCurrentArenaWithMorePlayers(): ?Arena {
        $currentArena = null;

        foreach($this->arenas as $arena) {
            if(($arena->getStatus() == Arena::LOBBY and $arena->getLobbytime() > 0) and !$arena->isFull()) {
                if(($currentArena instanceof Arena and count($arena->getPlayers()) > count($currentArena->getPlayers())) or ($currentArena == null)) {
                    $currentArena = $arena;
                }
            }
        }

        return $currentArena;
    }

    /**
     * @param string $customName
     * @return Arena[]
     */
    public function getArenasByLevel(string $customName): array {
        $arenas = [];

        foreach($this->arenas as $arena) {
            if($arena->getLevel()->getCustomName() == $customName) {
                $arenas[$arena->getName()] = $arena;
            } else if($arena->getLevel()->getFolderName() == $customName) {
                $arenas[$arena->getName()] = $arena;
            }
        }

        return $arenas;
    }

    /**
     * @param string $name
     * @return Arena|null
     */
    public function getArenaByPlayer(string $name): ?Arena {
        foreach($this->arenas as $arena) {
            if($arena->inArenaAsPlayerByName($name)) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getPlayerByName(string $name): ?Player {
        if(($arena = $this->getArenaByPlayer($name)) instanceof Arena) {
            return $arena->getPlayerByName($name);
        }

        return null;
    }

    /**
     * @return Arena|null
     */
    public function getArenaAvailable(): ?Arena {
        return $this->getCurrentArenaWithMorePlayers() ?? $this->createArena();
    }

    /**
     * @param string $name
     * @return Arena|null
     */
    public function getArenaByName(string $name): ?Arena {
        return $this->arenas[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasArenaByPlayer(string $name): bool {
        return $this->getArenaByPlayer($name) != null;
    }

    /**
     * @param string $name
     * @throws \Exception
     */
    public function removeFromArena(string $name): void {
        $arena = $this->getArenaByPlayer($name);

        if($arena instanceof Arena) {
            $arena->disconnect($name);
        }
    }

    /**
     * @return string
     */
    public function identifierGame(): string {
        $ks = 'abcdefghijklmnopqrstuvwxyz';

        $id = 'hungergames_game' . rand(1, 10) . $ks[rand(0, (strlen($ks) - 1))] . '0r';

        foreach($this->arenas as $arena) {
            if($arena->getName() == $id) {
                return $this->identifierGame();
            }
        }

        return $id;
    }

    /**
     * @param Arena $arena
     */
    public function deleteFromArenas(Arena $arena) {
        if($this->exists($arena->getName())) {
            unset($this->arenas[$arena->getName()]);
        }
    }

    /**
     * @return int
     */
    public function getOnlinePlayers(): int {
        $online = 0;

        foreach($this->arenas as $arena) {
            $online += count($arena->getPlayers());
        }

        return $online;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool {
        return isset($this->arenas[$name]);
    }
}