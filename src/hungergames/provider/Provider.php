<?php

namespace hungergames\provider;

use hungergames\provider\target\TargetOffline;

interface Provider {

    /**
     * Get player data by returning data in a class
     *
     * @param string $name
     * @return TargetOffline|null
     */
    public function getTargetOffline(string $name): ?TargetOffline;

    /**
     * Save or update player data in the provider using the class
     *
     * @param TargetOffline $target
     */
    public function setTargetOffline(TargetOffline $target);

    /**
     * @param string|null $name
     * @return TargetOffline[]
     */
    public function getLeaderboard(string $name = null): array;
}