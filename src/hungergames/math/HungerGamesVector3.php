<?php

namespace hungergames\math;

use pocketmine\math\Vector3;

class HungerGamesVector3 {

    /** @var int */
    public $x;

    /** @var int */
    public $y;

    /** @var int */
    public $z;

    /**
     * HungerGamesPosition constructor.
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     */
    public function __construct($x, $y, $z) {
        $this->x = $x;

        $this->y = $y;

        $this->z = $z;
    }

    /**
     * @param array $data
     * @return HungerGamesVector3
     */
    public static function fromArray(array $data): HungerGamesVector3 {
        return new HungerGamesVector3($data['x'], $data['y'], $data['z']);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return HungerGamesVector3
     */
    public function add($x, $y = 0, $z = 0): HungerGamesVector3 {
        return new HungerGamesVector3($this->x + $x, $this->y + $y, $this->z + $z);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return HungerGamesVector3
     */
    public function subtract($x, $y = 0, $z = 0): HungerGamesVector3 {
        return new HungerGamesVector3($this->x - $x, $this->y - $y, $this->z - $z);
    }

    /**
     * @return Vector3
     */
    public function asVector3(): Vector3 {
        return new Vector3($this->x, $this->y, $this->z);
    }
}