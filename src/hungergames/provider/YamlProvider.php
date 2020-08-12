<?php

namespace hungergames\provider;

use hungergames\provider\target\TargetOffline;
use hungergames\HungerGames;
use hungergames\utils\Utils;

class YamlProvider implements Provider {

    /**
     * YamlProvider constructor.
     */
    public function __construct() {
        if(!file_exists(HungerGames::getInstance()->getDataFolder() . 'players')) {
            mkdir(HungerGames::getInstance()->getDataFolder() . 'players');
        }
    }

    /**
     * Get player data by returning data in a class
     *
     * @param string $name
     * @return TargetOffline|null
     */
    public function getTargetOffline(string $name): ?TargetOffline {
        if(!is_dir(HungerGames::getInstance()->getDataFolder() . 'players')) {
            return null;
        } else if(!file_exists(HungerGames::getInstance()->getDataFolder() . 'players/' . strtolower($name) . '.yml')) {
            return new TargetOffline(['username' => $name, 'wins' => 0, 'gamesplayed' => 0, 'kills' => 0, 'deaths' => 0, 'kit' => HungerGames::getInstance()->getConfig()->get('defaultKit'), 'automode' => false]);
        }

        return new TargetOffline(Utils::getYamlContents('players/' . strtolower($name) . '.yml'));
    }

    /**
     * Save or update player data in the provider using the class
     *
     * @param TargetOffline $target
     */
    public function setTargetOffline(TargetOffline $target) {
        if(!is_dir(HungerGames::getInstance()->getDataFolder() . 'players')) {
            return;
        }

        Utils::putYamlContents($target->data, HungerGames::getInstance()->getDataFolder() . 'players/' . strtolower($target->getName()) . '.yml');
    }

    /**
     * @param string|null $name
     * @return TargetOffline[]
     */
    public function getLeaderboard(string $name = null): array {
        $leaderboard = [];

        $players = [];

        foreach(scandir(HungerGames::getInstance()->getDataFolder() . 'players') as $fileData) {
            if(strpos($fileData, '.yml') !== false) {
                $target = $this->getTargetOffline(str_replace('.yml', '', $fileData));

                if($target instanceof TargetOffline) {
                    $players[$target->getName()] = $target->getWins();
                }
            }
        }

        arsort($players);

        for($i = 0; $i < 10; $i++) {
            $username = array_keys($players)[$i] ?? null;

            if($username != null) $leaderboard[$i] = $this->getTargetOffline($username);

            if($name != null and (strtolower($name) == strtolower($username))) $name = null;
        }

        if($name != null) {
            $i = 0;

            foreach(array_keys($players) as $targetName) {
                $i++;

                if(strtolower($targetName) == strtolower($name)) {
                    $leaderboard[$i] = $this->getTargetOffline($name);

                    break;
                }
            }
        }

        return $leaderboard;
    }
}