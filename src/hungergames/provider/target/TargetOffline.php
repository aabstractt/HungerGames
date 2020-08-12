<?php


namespace hungergames\provider\target;


use hungergames\HungerGames;

class TargetOffline {

    /** @var array */
    public $data;

    /**
     * TargetOffline constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;

        if(!isset($this->data['kit'])) $this->data['kit'] = HungerGames::getInstance()->getConfig()->get('defaultKit');
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['username'];
    }

    /**
     * @return string
     */
    public function getKit(): string {
        return $this->data['kit'];
    }

    /**
     * @return int
     */
    public function getWins(): int {
        return $this->data['wins'];
    }

    /**
     * @return int
     */
    public function getGamesPlayed(): int {
        return $this->data['gamesplayed'];
    }

    /**
     * @return int
     */
    public function getDeaths(): int {
        return $this->data['deaths'] ?? 0;
    }

    /**
     * @return int
     */
    public function getKills(): int {
        return $this->data['kills'];
    }

    /**
     * @return bool
     */
    public function inAutoMode(): bool {
        return $this->data['automode'] ?? false;
    }
}