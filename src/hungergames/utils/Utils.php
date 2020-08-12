<?php

namespace hungergames\utils;

use Exception;
use hungergames\arena\Arena;
use hungergames\player\Player as HungerGamesPlayer;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use hungergames\player\Configurator;
use hungergames\provider\MysqlProvider;
use hungergames\provider\YamlProvider;
use hungergames\HungerGames;
use hungergames\task\HungerGamesSlapper;

class Utils {

    /** @var Configurator[] */
    private static $configurators = [];

    /** @var Player[] */
    public static $automode = [];

    /**
     * @param string $name
     * @return bool
     */
    public static function isConfigurator(string $name): bool {
        return isset(static::$configurators[strtolower($name)]);
    }

    /**
     * @param string $folderName
     * @return bool
     */
    public static function hasConfiguratorArena(string $folderName): bool {
        foreach(static::$configurators as $configurator) {
            if(strtolower($configurator->getFolderName()) == strtolower($folderName)) {
                unset($configurator, $folderName);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Configurator $configurator
     * @return Configurator
     */
    public static function addConfigurator(Configurator $configurator): Configurator {
        static::$configurators[$configurator->getUsernameToLower()] = $configurator;

        return $configurator;
    }

    /**
     * @param string $name
     * @return Configurator|null
     */
    public static function getConfigurator(string $name): ?Configurator {
        return static::$configurators[strtolower($name)] ?? null;
    }

    /**
     * @param string $name
     */
    public static function removeFromConfigurator(string $name) {
        if(Utils::isConfigurator($name)) {
            unset(static::$configurators[strtolower($name)], $name);
        }
    }

    /**
     * Give the data to a .json file
     *
     * @param array $data
     * @param string $path
     */
    public static function putJsonContents(array $data, string $path) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);

        unset($data, $path);
    }

    /**
     * Get the data from a .json file using the path
     *
     * @param string $path
     * @return array
     */
    public static function getJsonContents(string $path): array {
        return json_decode(file_get_contents($path), true);
    }

    /**
     * @param array $data
     * @param string $path
     */
    public static function putYamlContents(array $data, string $path) {
        file_put_contents($path, yaml_emit($data));
    }

    /**
     * @param string $path
     * @return array
     */
    public static function getYamlContents(string $path): array {
        return yaml_parse(file_get_contents(HungerGames::getInstance()->getDataFolder() . $path));
    }

    /**
     * @return Config
     */
    private static function getConfig(): Config {
        return HungerGames::getInstance()->getConfig();
    }

    /**
     * @return array
     */
    public static function getItemsSpectator(): array {
        return self::getConfig()->get('items');
    }

    /**
     * @return bool
     */
    public static function canStartWhenIsFull(): bool {
        return self::getConfig()->get('start-when-full');
    }

    /**
     * @param string $dirPath
     */
    public static function deleteDir(string $dirPath) {
        if(substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach($files as $file) {
            if(is_dir($file)) {
                self::deleteDir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir($dirPath);
    }

    /**
     * @param string $src
     * @param string $dst
     */
    public static function backup(string $src, string $dst) {
        $dir = opendir($src);

        @mkdir($dst);

        while(false !== ($file = readdir($dir))) {
            if(($file != '.') && ($file != '..')) {
                if(is_dir($src . '/' . $file)) {
                    self::backup($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Give the data provider
     *
     * @throws Exception
     */
    public static function loadProvider() {
        Server::getInstance()->getLogger()->info(TextFormat::GREEN . 'Starting HungerGamesReloaded provider...');

        $config = Utils::getYamlContents('config.yml');

        if(!isset($config['provider']) || !isset($config['mysql'])) {
            throw new Exception('Could not read config.yml file');
        }

        if($config['provider'] == 'mysql') {
            HungerGames::getInstance()->setProvider(new MysqlProvider($config['mysql']));
        } else if($config['provider'] == 'yaml') {
            HungerGames::getInstance()->setProvider(new YamlProvider());
        } else {
            throw new Exception('Provider ' . $config['provider'] . ' not found.');
        }

        HungerGamesJoin::registerEntity(HungerGamesJoin::class, true);

        new HungerGamesSlapper();

        unset($config);
    }

    public static function canUseThisKit(string $kitName): bool {
        return in_array($kitName, (array) HungerGames::getInstance()->getConfig()->get('kitsAvailable'));
    }

    /**
     * @param Arena $arena
     * @param bool $value
     * @param Chest[]|GameChest[] $nearby
     * @throws Exception
     */
    public static function refillChests(Arena $arena, bool $value = false, array $nearby = []) {
        $items = Utils::getJsonContents(HungerGames::getInstance()->getDataFolder() . 'items.json')[$arena->getChestType()] ?? [];

        if(empty($items)) {
            throw new Exception('Items now received null');
        }

        if(empty($nearby)) {
            if($value) {
                foreach($arena->chests as $chest) {
                    if(($tile = $arena->getLevel()->getLevel()->getTile($chest->position) instanceof Chest) and Utils::inRegion($chest->position, $arena->getLevel()->getLevel()->getSafeSpawn(), $arena->getLevel()->getNearDistance())) {
                        $nearby[] = $chest;
                    }
                }
            } else {
                foreach($arena->chests as $chest) {
                    if(($tile = $arena->getLevel()->getLevel()->getTile($chest->position) instanceof Chest)) $nearby[] = $chest;
                }
            }
        }

        foreach($nearby as $chests) {
            if($chests instanceof GameChest) {
                $chests = $arena->getLevel()->getLevel()->getTile($chests->position);
            }

            if($chests instanceof Chest) {
                $chests->getInventory()->clearAll();

                if($chests->getInventory() instanceof ChestInventory) {
                    $maxContents = HungerGames::getInstance()->getConfig()->get('max-chestcontent');
                    
                    while($maxContents != 0) {
                        $data = $items[rand(0, count($items) - 1)];

                        $chests->getInventory()->setItem(rand(0, 26), Item::get($data['id'], $data['meta'], $data['count']));

                        $maxContents--;
                    }

                    $arena->chests[$chests->asPosition()->__toString()]->refillType = 2;
                }
            }
        }
    }

    /**
     * @param Arena $arena
     * @param GameChest[] $chests
     * @throws Exception
     */
    public static function loadChests(Arena $arena, array &$chests) {
        foreach($arena->getLevel()->getLevel()->getTiles() as $tile) {
            if($tile instanceof Chest) {
                if(!isset($chests[$tile->asPosition()->__toString()])) {
                    $chests[$tile->asPosition()->__toString()] = ($chest = new GameChest($tile->asPosition()));

                    Utils::refillChests($arena, false, [$tile]);

                    $chest->refillType = 2;

                    echo 'holaaa' . PHP_EOL;
                } else if(($chest = $chests[$tile->asPosition()->__toString()])->refillType == 2 and ($arena->refilltime <= 0 and Utils::inRegion($chest->position, $arena->getLevel()->getLevel()->getSafeSpawn(), $arena->getLevel()->getNearDistance()))) {
                    Utils::refillChests($arena, false, [$chest]);

                    echo 'loading xd' . PHP_EOL;

                    $chest->refillType = 3;
                }

                echo 'hola, en teoria soy un tile pero no lo se xd' . PHP_EOL;
            }
        }
    }

    /**
     * @param Position $pos
     * @param Position $spawn
     * @param int $distance
     * @return bool
     */
    private static function inRegion(Position $pos, Position $spawn, int $distance): bool {
        return $pos->distance($spawn) <= $distance;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function canAutomode(string $name): bool {
        return !isset(Utils::$automode[strtolower($name)]);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function schedulerAutomode(Player $player): bool {
        if(Utils::canAutomode($player->getName())) {
            new class($player) extends Task {

                /** @var Player */
                private $player;

                /** @var string */
                private $name;

                public function __construct(Player $player) {
                    $this->player = $player;

                    $this->name = $player->getName();

                    Utils::$automode[strtolower($this->name)] = $player;

                    $this->setHandler(HungerGames::getInstance()->getScheduler()->scheduleDelayedTask($this, 100));
                }

                /**
                 * Actions to execute when run
                 *
                 * @param int $currentTick
                 * @return void
                 */
                public function onRun(int $currentTick) {
                    if($this->player instanceof Player and isset(Utils::$automode[strtolower($this->name)])) {
                        if(!($currentArena = HungerGames::getInstance()->getArenaManager()->getArenaAvailable()) instanceof Arena) {
                            $this->player->sendMessage(TextFormat::RED . 'Games not available');
                        } else {
                            $currentArena->join(new HungerGamesPlayer($this->player->getName(), $currentArena));
                        }
                    }

                    if(isset(Utils::$automode[strtolower($this->name)])) unset(Utils::$automode[strtolower($this->name)]);

                    $this->getHandler()->cancel();
                }
            };

            $player->sendMessage(TextFormat::YELLOW . 'We\'ll find you another game in 5s!');

            return true;
        }

        return false;
    }
}