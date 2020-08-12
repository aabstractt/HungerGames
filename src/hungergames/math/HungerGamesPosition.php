<?php

namespace hungergames\math;

use pocketmine\level\Level;
use pocketmine\level\Position;

class HungerGamesPosition {

    /** @var Level */
    private $level;

    /** @var int */
    private $x;

    /** @var int */
    private $y;

    /** @var int */
    private $z;

    /**
     * HungerGamesPosition constructor.
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @param Level $level
     */
    public function __construct($x, $y, $z, Level $level) {
        $this->x = $x;

        $this->y = $y;

        $this->z = $z;

        $this->level = $level;
    }

    /**
     * @param array $data
     * @param Level $level
     * @return HungerGamesPosition
     */
    public static function fromArray(array $data, Level $level): HungerGamesPosition {
        return new HungerGamesPosition($data['x'], $data['y'], $data['z'], $level);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return HungerGamesPosition
     */
    public function add($x, $y = 0, $z = 0): HungerGamesPosition {
        return new HungerGamesPosition($this->x + $x, $this->y + $y, $this->z + $z, $this->level);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return HungerGamesPosition
     */
    public function subtract($x, $y = 0, $z = 0): HungerGamesPosition {
        return new HungerGamesPosition($this->x - $x, $this->y - $y, $this->z - $z, $this->level);
    }

    /**
     * @return Position
     */
    public function asPosition(): Position {
        return new Position($this->x, $this->y, $this->z, $this->level);
    }
}