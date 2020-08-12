<?php

namespace hungergames\provider;

use Exception;
use hungergames\HungerGames;
use mysqli;
use hungergames\provider\target\TargetOffline;

class MysqlProvider implements Provider {

    /** @var array */
    private $data;

    /** @var mysqli */
    private $connection;

    /**
     * MysqlProvider constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data) {
        $this->data = $data;

        $connection = $this->connect();

        mysqli_select_db($connection, $this->data['dbname']);

        if(!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS hg_players(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(200), wins INT, kills INT, deaths INT, gamesplayed INT, kit VARCHAR(200))')) {
            throw new Exception(mysqli_error($connection));
        }
    }

    /**
     * @return false|mysqli
     * @throws Exception
     */
    private function connect() {
        if($this->connection == null) {
            $this->connection = mysqli_connect($this->data['host'], $this->data['username'], $this->data['password']);
        }

        if(mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        } else if(!mysqli_query($this->connection, 'CREATE DATABASE IF NOT EXISTS ' . $this->data['dbname'])) {
            throw new Exception(mysqli_error($this->connection));
        }

        return $this->connection;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllPlayers(): array {
        $connection = $this->connect();

        mysqli_select_db($connection, $this->data['dbname']);

        $players = [];

        if(mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        } else {
            $query = mysqli_query($connection, "SELECT * FROM hg_players");

            if(!$query) {
                throw new Exception(mysqli_error($connection));
            }

            foreach($query as $data) {
                $players[] = new TargetOffline($data);
            }
        }

        return $players;
    }

    /**
     * Get player data by returning data in a class
     *
     * @param string $name
     * @return TargetOffline|null
     * @throws Exception
     */
    public function getTargetOffline(string $name): ?TargetOffline {
        $connection = $this->connect();

        mysqli_select_db($connection, $this->data['dbname']);

        if(mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        } else {
            $query = mysqli_query($connection, "SELECT * FROM hg_players WHERE username = '{$name}'");

            if(!$query) {
                throw new Exception(mysqli_error($connection));
            } else if(mysqli_num_rows($query) > 0) {
                return new TargetOffline(mysqli_fetch_assoc($query));
            }
        }

        return new TargetOffline(['username' => $name, 'wins' => 0, 'gamesplayed' => 0, 'kills' => 0, 'deaths' => 0, 'kit' => HungerGames::getInstance()->getConfig()->get('defaultKit'), 'automode' => false]);
    }

    /**
     * Save or update player data in the provider using the class
     *
     * @param TargetOffline $target
     * @return void
     * @throws Exception
     */
    public function setTargetOffline(TargetOffline $target) {
        $connection = $this->connect();

        mysqli_select_db($connection, $this->data['dbname']);

        if(mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        } else {
            $query = mysqli_query($connection, "SELECT * FROM hg_players WHERE username = '{$target->getName()}'");

            if(!$query) {
                throw new Exception(mysqli_error($connection));
            }

            if(mysqli_num_rows($query) > 0) {
                $boolean = mysqli_query($connection, "UPDATE hg_players set wins = '{$target->getWins()}', kills = '{$target->getKills()}', gamesplayed = '{$target->getGamesPlayed()}', deaths = '{$target->getDeaths()}', kit = '{$target->getKit()}', automode = '{$target->inAutoMode()}' WHERE username = '{$target->getName()}'");
            } else {
                $boolean = mysqli_query($connection, "INSERT hg_players (username, wins, kills, deaths, gamesplayed, kit, automode) VALUES ('{$target->getName()}', '{$target->getWins()}', '{$target->getKills()}', '{$target->getDeaths()}', '{$target->getGamesPlayed()}', '{$target->getKit()}', '{$target->inAutoMode()}')");
            }

            if(!$boolean) {
                throw new Exception(mysqli_error($connection));
            }
        }
    }

    /**
     * @return TargetOffline[]
     * @throws Exception
     */
    public function getLeaderboard(string $name = null): array {
        $connection = $this->connect();

        mysqli_select_db($connection, $this->data['dbname']);

        $players = [];

        if(mysqli_connect_errno()) {
            throw new Exception(mysqli_connect_error());
        } else {
            $query = mysqli_query($connection, "SELECT * FROM sw_players ORDER BY wins DESC LIMIT 10");

            foreach($query as $data) {
                $players[] = new TargetOffline($data);
            }
        }

        return $players;
    }
}