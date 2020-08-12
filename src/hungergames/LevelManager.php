<?php

namespace hungergames;

use hungergames\arena\Level;
use hungergames\utils\Utils;

class LevelManager {

    /** @var Level[] */
    private $levels = [];

    public function load() {
        foreach(Utils::getJsonContents(HungerGames::getInstance()->getDataFolder() . 'levels.json') as $data) {
            $this->add($data);
        }
    }

    public function save() {
        $data = [];

        foreach($this->levels as $level) {
            $data[$level->getFolderName()] = $level->data;
        }

        Utils::putJsonContents($data, HungerGames::getInstance()->getDataFolder() . 'levels.json');
    }

    /**
     * @param array $data
     */
    public function add(array $data) {
        $this->levels[strtolower($data['folderName'])] = new Level($data);
    }

    /**
     * @param string $folderName
     * @return Level|null
     */
    public function get(string $folderName): ?Level {
        $level = $this->levels[strtolower($folderName)] ?? null;

        if($level == null) {
            $level = $this->getByCustomName($folderName);
        }

        return $level;
    }

    /**
     * @param string $customName
     * @return Level|null
     */
    public function getByCustomName(string $customName): ?Level {
        foreach($this->levels as $level) {
            if($level->getCustomName() == $customName) {
                return $level;
            }
        }

        return null;
    }

    /**
     * @param string $folderName
     */
    public function delete(string $folderName) {
        if($this->exists($folderName)) {
            unset($this->levels[strtolower($folderName)]);

            $this->save();
        }
    }

    /**
     * @param string $folderName
     * @return bool
     */
    public function exists(string $folderName) {
        return isset($this->levels[strtolower($folderName)]);
    }

    /**
     * @return Level[]
     */
    public function getAll(): array {
        return $this->levels;
    }

    /**
     * @return Level|null
     */
    public function getLevelForArena(): ?Level {
        $levels = [];

        foreach($this->getAll() as $level) {
            if(count(HungerGames::getInstance()->getArenaManager()->getArenasByLevel($level->getCustomName())) <= HungerGames::getInstance()->getConfig()->get('arenas-allowed-map')) {
                $levels[strtolower($level->getFolderName())] = $level;
            }
        }

        if(empty($levels)) {
            return null;
        }

        return new Level($this->levels[array_rand($levels)]->data);
    }
}