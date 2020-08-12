<?php

namespace hungergames\utils;

use pocketmine\level\Position;

class GameChest {

    /** @var Position */
    public $position;

    /** @var bool */
    public $refillType = 0;

    /**
     * GameChest constructor.
     * @param Position $position
     */
    public function __construct(Position $position) {
        $this->position = $position;
    }
}