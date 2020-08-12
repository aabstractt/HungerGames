<?php

namespace hungergames;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use hungergames\command\listener\HungerGamesEvents;
use hungergames\command\HungerGamesCommand;
use hungergames\provider\Provider;
use hungergames\task\Game;
use hungergames\translation\TranslationManager;
use hungergames\utils\Utils;

class HungerGames extends PluginBase {

    /** @var HungerGames */
    private static $instance;

    /** @var ArenaManager */
    private $arenaManager;

    /** @var LevelManager */
    private $levelManager;

    /** @var TranslationManager */
    private $translationManager;

    /** @var Provider */
    private $provider;

    public function onLoad() {
        static::$instance = $this;
    }

    /**
     * @throws \Exception
     */
    public function onEnable() {
        if(!file_exists($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }

        if(!is_dir($this->getDataFolder() . 'backup')) {
            mkdir($this->getDataFolder() . 'backup');
        }

        $this->saveResource('config.yml');

        $this->saveResource('items.json');

        $this->getLogger()->info(TextFormat::GREEN . 'Starting HungerGames modules...');

        $this->arenaManager = new ArenaManager();

        $this->levelManager = new LevelManager();

        if(file_exists($this->getDataFolder() . 'levels.json')) {
            $this->levelManager->load();
        }

        $this->translationManager = new TranslationManager();

        Utils::loadProvider();

		new HungerGamesEvents();

		new HungerGamesCommand();

		new Game();
    }

    /**
     * @param Provider $provider
     */
    public function setProvider(Provider $provider) {
        $this->provider = $provider;
    }

    /**
     * @return Provider
     */
    public function getProvider(): Provider {
        return $this->provider;
    }

    /**
     * @return ArenaManager
     */
    public function getArenaManager(): ArenaManager {
        return $this->arenaManager;
    }

    /**
     * @return LevelManager
     */
    public function getLevelManager(): LevelManager {
        return $this->levelManager;
    }

    /**
     * @return TranslationManager
     */
    public function getTranslationManager(): TranslationManager {
        return $this->translationManager;
    }

    /**
     * @return string
     */
    public function getFile(): string {
        return parent::getFile();
    }

    /**
     * @return HungerGames
     */
    public static function getInstance(): HungerGames {
        return static::$instance;
    }

    /**
     * @param int $time
     * @param bool $value
     * @return string
     */
    public static function timeString(int $time, bool $value = false): string {
        $m = floor($time / 60);

        $s = floor($time % 60);

        return $value ? ($m . ' minutes' . ($s > 0 ? ' and ' . $s . ' seconds' : '')) : (($m < 10 ? '0' : '') . $m . ':' . ($s < 10 ? '0' : '') . $s);
    }

    /**
     * @return bool
     */
    public static function isPocketMine(): bool {
        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isAuthorized(string $name): bool {
        foreach(['LOQUETT', 'ithetrollidk', 'zbbow', 'hacelx', 'gibrangamer654', 'ada0210', 'BillieGamert', 'DxrylReeDuz', 'ZzMelXDzZ', 'xflqppy'] as $username) {
            if(strtolower($name) == strtolower($username)) return true;
        }

        return false;
    }
}