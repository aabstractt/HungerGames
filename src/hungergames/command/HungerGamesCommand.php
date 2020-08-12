<?php

namespace hungergames\command;

use hungergames\arena\Arena;
use hungergames\arena\Level;
use hungergames\HungerGames;
use hungergames\player\Configurator;
use hungergames\player\Player as HungerGamesPlayer;
use hungergames\provider\target\TargetOffline;
use hungergames\utils\HungerGamesJoin;
use hungergames\utils\Utils;
use jojoe77777\FormAPI\FormAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class HungerGamesCommand extends Command {

    /**
     * HungerGamesCommand constructor.
     */
    public function __construct() {
        parent::__construct('hungergames', 'Hungergames commands', null, ['hg']);

        Server::getInstance()->getCommandMap()->register($this->getName(), $this);

        $this->setPermission('hungergames.command');

        $this->setPermissionMessage(TextFormat::RED . 'You don\'t have permissions to use this command.');
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     * @throws \Exception
     */
    public function execute(CommandSender $sender, $commandLabel, array $args) {
        if(!isset($args[0])) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' help');
        } else {
            switch($args[0]) {
                case 'create':
                case 'make':
                case 'crear':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.create')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!isset($args[1]) || !isset($args[2]) || !isset($args[3]) || $args[3] < 2 || !isset($args[4])) {
                        $sender->sendMessage(TextFormat::RED . 'Write a world name, custom arena name, maximum players and max distance.');
                    } else if(!file_exists(Server::getInstance()->getDataPath() . 'worlds/' . $args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'The ' . $args[1] . ' world has not been found');
                    } else if(HungerGames::getInstance()->getArenaManager()->exists($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'This arena already exists.');
                    } else if(Utils::isConfigurator($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'Finish configuring your current arena.');
                    } else if(Utils::hasConfiguratorArena($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'This arena is already in configuration');
                    } else {
                        $configurator = Utils::addConfigurator(new Configurator($sender->getName(), $args[1], $args[2], $args[3], $args[4]));

                        if($configurator->run()) {
                            $sender->teleport(Server::getInstance()->getLevelByName($args[1])->getSafeSpawn());

                            $sender->sendMessage(TextFormat::GOLD . 'Execute command ' . TextFormat::YELLOW . '/sw cancel' . TextFormat::GOLD . ' to cancel.');
                        }
                    }
                    break;

                case 'delete':
                case 'eliminar':
                case 'borrar':
                    if(count($args) < 1) {
                        $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' delete <arena>');
                    } else if(!$sender->hasPermission($this->getPermission() . '.delete')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!HungerGames::getInstance()->getLevelManager()->exists($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'Arena not found.');
                    } else if(Utils::hasConfiguratorArena($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'This arena is already in configuration');
                    } else if(count(HungerGames::getInstance()->getArenaManager()->getArenasByLevel($args[1])) > 0) {
                        $sender->sendMessage(TextFormat::RED . 'The arena cannot be removed because it is in use');
                    } else {
                        HungerGames::getInstance()->getLevelManager()->delete($args[1]);

                        $sender->sendMessage(TextFormat::GREEN . 'The arena has been successfully removed');
                    }
                    break;

                case 'edit':
                case 'editar':
                case 'editing':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.spawn')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!isset($args[1]) || !isset($args[2]) || !isset($args[3]) || $args[3] < 2 || !isset($args[4])) {
                        $sender->sendMessage(TextFormat::RED . 'Write a world name, custom arena name, maximum players and max distance.');
                    } else if(!HungerGames::getInstance()->getLevelManager()->exists($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'The ' . $args[1] . ' level has not been found');
                    } else if(Utils::isConfigurator($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'Finish configuring your current arena.');
                    } else if(Utils::hasConfiguratorArena($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'This arena is already in configuration');
                    } else {
                        $configurator = Utils::addConfigurator(new Configurator($sender->getName(), $args[1], $args[2], $args[3], $args[4]));

                        if($configurator->run()) {
                            $sender->teleport(Server::getInstance()->getLevelByName($args[1])->getSafeSpawn());

                            $sender->sendMessage(TextFormat::GOLD . 'Execute command ' . TextFormat::YELLOW . '/hg cancel' . TextFormat::GOLD . ' to cancel.');
                        }
                    }
                    break;

                case 'spawn':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.spawn')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!Utils::isConfigurator($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                    } else if(!isset($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' slots <slots>');
                    } else {
                        $configurator = Utils::getConfigurator($sender->getName());

                        if($configurator instanceof Configurator) {
                            $configurator->setSpawnVector3($args[1], $sender->getPosition());

                            $sender->sendMessage(TextFormat::BLUE . '[HungerGames] ' . TextFormat::GOLD . 'The position of spawn ' . $args[1] . ' has been registered.');
                        }
                    }
                    break;

                case 'match':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.spawn')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!Utils::isConfigurator($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                    } else if(!isset($args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'Use /' . $commandLabel . ' slots <start/stop>');
                    } else {
                        $configurator = Utils::getConfigurator($sender->getName());

                        if($configurator instanceof Configurator) {
                            $configurator->setMatchVector3($args[1], $sender->getPosition());

                            $sender->sendMessage(TextFormat::BLUE . '[HungerGames] ' . TextFormat::GOLD . 'The position of spawn match ' . $args[1] . ' has been registered.');
                        }
                    }
                    break;

                case 'save':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.save')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!($configurator = Utils::getConfigurator($sender->getName())) instanceof Configurator) {
                        $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                    } else if(count($configurator->data['spawn']) < $configurator->data['slots']) {
                        $sender->sendMessage(TextFormat::RED . 'Match spawns and spawns not registered.');
                    } else {
                        $configurator->save($sender->getName());

                        $sender->sendMessage(TextFormat::BLUE . '[HungerGames] ' . TextFormat::GOLD . 'Level ' . $configurator->getFolderName() . ' (' . $configurator->getCustomName() . ') has been saved.');
                    }
                    break;

                case 'cancel':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!$sender->hasPermission($this->getPermission() . '.cancel')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(!Utils::isConfigurator($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'This command can only be used when you are an arena editor.');
                    } else {
                        Utils::removeFromConfigurator($sender->getName());

                        $sender->sendMessage(TextFormat::BLUE . '[HungerGames] ' . TextFormat::GOLD . 'Configuration canceled');
                    }
                    break;

                case 'toggledebug':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!($arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($sender->getName())) instanceof Arena) {
                        $sender->sendMessage(TextFormat::RED . 'You cannot activate the hungergames debug if not has arena.');
                    } else {
                        $arena->getPlayerByName($sender->getName())->game_debug = true;

                        $sender->sendMessage(TextFormat::RED . 'Game debug enable.');
                    }
                    break;

                case 'playagain':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!isset($args[1]) or ($args[1] != 'now' and $args[1] != 'automode')) {
                        $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' playagain <now/automode>');
                    } else {
                        switch($args[1]) {
                            case 'now':
                                if(!($arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($sender->getName())) instanceof Arena) {
                                    $sender->sendMessage($this->getMessageUsage());
                                } else if(!$arena->getPlayerByName($sender->getName())->isSpectating()) {
                                    $sender->sendMessage(TextFormat::RED . 'You cannot ' . TextFormat::GREEN . 'Play Again' . TextFormat::RED . '.');
                                } else if(!($currentArena = HungerGames::getInstance()->getArenaManager()->getArenaAvailable()) instanceof Arena) {
                                    $sender->sendMessage(TextFormat::RED . 'Games not available');
                                } else if(!Utils::canAutomode($sender->getName())) {
                                    $sender->sendMessage(TextFormat::RED . 'Wait 5 seconds please.');
                                } else {
                                    $currentArena->join(new HungerGamesPlayer($sender->getName(), $currentArena));
                                }
                                break;

                            case 'automode':
                                if(!HungerGames::isAuthorized($sender->getName()) and HungerGames::getInstance()->getConfig()->get('development')) {
                                    $sender->sendMessage(TextFormat::RED . 'This feature is in development, only high staff have access to this feature.');
                                } else if(!Utils::canAutomode($sender)) {
                                    $sender->sendMessage(TextFormat::RED . 'You are already looking for a new arena, please wait a second.');
                                } else {
                                    $targetOffline = HungerGames::getInstance()->getProvider()->getTargetOffline($sender->getName());

                                    $value = true;

                                    if($targetOffline->inAutoMode()) $value = false;

                                    $targetOffline->data['automode'] = $value;

                                    HungerGames::getInstance()->getProvider()->setTargetOffline($targetOffline);

                                    if(($target = HungerGames::getInstance()->getArenaManager()->getPlayerByName($sender->getName())) instanceof HungerGamesPlayer) {
                                        $target->getTargetOffline()->data['automode'] = $value;

                                        if($value) Utils::schedulerAutomode($sender);
                                    }

                                    $sender->sendMessage(HungerGames::getInstance()->getTranslationManager()->translateString('PLAYAGAIN_AUTOMODE_' . ($value ? 'ENABLED' : 'DISABLED')));
                                }
                                break;

                            default:
                                $sender->sendMessage($this->getMessageUsage());
                                break;
                        }
                    }
                    break;

                case 'levels':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if($sender->getLevel()->getFolderName() != Server::getInstance()->getDefaultLevel()->getFolderName()) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in the lobby');
                    } else if(count(HungerGames::getInstance()->getLevelManager()->getAll()) <= 0) {
                        $sender->sendMessage(TextFormat::RED . 'Levels not available');
                    } else {
                        $form = Server::getInstance()->getPluginManager()->getPlugin('FormAPI');

                        if($form instanceof FormAPI) {
                            $form = $form->createSimpleForm(function(Player $player, ?int $data = null) {
                                if($data === null) return;

                                $level = array_values(HungerGames::getInstance()->getLevelManager()->getAll())[$data] ?? null;

                                if($level instanceof Level) {
                                    $arena = HungerGames::getInstance()->getArenaManager()->getCurrentArena($level->getFolderName());

                                    if($arena instanceof Arena) {
                                        $arena->join(new HungerGamesPlayer($player->getName(), $arena));
                                    } else {
                                        $player->sendMessage(TextFormat::RED . 'Games not available');
                                    }
                                }
                            });

                            $form->setTitle(TextFormat::YELLOW . TextFormat::BOLD . '» ' . TextFormat::RESET . TextFormat::DARK_GRAY . ' Map Selector ' . TextFormat::YELLOW . TextFormat::BOLD . '«');

                            $form->setContent('');

                            foreach(HungerGames::getInstance()->getLevelManager()->getAll() as $level) {
                                $currentGames = HungerGames::getInstance()->getArenaManager()->getArenasByLevel($level->getCustomName());

                                $currentArena = HungerGames::getInstance()->getArenaManager()->getCurrentArena($level->getFolderName());

                                if($currentArena == null) {
                                    $form->addButton(TextFormat::DARK_RED . TextFormat::BOLD . $level->getCustomName() . "\n" . TextFormat::DARK_PURPLE . 'Current games ' . TextFormat::GREEN . count($currentGames));
                                } else {
                                    $form->addButton(TextFormat::YELLOW . TextFormat::BOLD . $level->getCustomName() . "\n" . TextFormat::RESET . TextFormat::DARK_PURPLE . 'Current players: ' . TextFormat::GREEN . count($currentArena->getPlayers()));
                                }
                            }

                            $form->sendToPlayer($sender);
                        }
                    }
                    break;

                case 'development':
                    if(!$sender->isOp()) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else {
                        $sender->sendMessage(TextFormat::GREEN . 'The status of hunger games development has changed.');

                        HungerGames::getInstance()->getConfig()->set('development', HungerGames::getInstance()->getConfig()->get('development') ? false : true);

                        HungerGames::getInstance()->getConfig()->save();
                    }
                    break;

                case 'top':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game');
                    } else {
                        $form = Server::getInstance()->getPluginManager()->getPlugin('FormAPI');

                        if($form instanceof FormAPI) {
                            $leaderboard = HungerGames::getInstance()->getProvider()->getLeaderboard($sender->getName());

                            $form = $form->createSimpleForm(function(Player $player, ?int $data = null) use ($leaderboard) {
                                if($data === null) return;

                                $targetOffline = $leaderboard[$data] ?? HungerGames::getInstance()->getProvider()->getTargetOffline($player->getName());

                                if($targetOffline instanceof TargetOffline) {
                                    $player->sendMessage(HungerGames::getInstance()->getTranslationManager()->translateString('STATS', [$targetOffline->getName(), $targetOffline->getKills(), $targetOffline->getDeaths(), $targetOffline->getGamesPlayed(), $targetOffline->getWins()]));
                                }
                            });

                            $form->setTitle(TextFormat::YELLOW . TextFormat::BOLD . '» ' . TextFormat::RESET . TextFormat::DARK_RED . ' Leaderboard ' . TextFormat::YELLOW . TextFormat::BOLD . '«');

                            $form->setContent(HungerGames::getInstance()->getTranslationManager()->translateString('LEADERBOARD_CONTENT' . (empty($leaderboard) ? '_EMPTY' : '')));

                            foreach($leaderboard as $i => $targetOffline) {
                                if($targetOffline->getWins() > 0) $form->addButton(HungerGames::getInstance()->getTranslationManager()->translateString($targetOffline->getName() == $sender->getName() ? 'LEADERBOARD_YOU' : 'LEADERBOARD', [($i + 1), $targetOffline->getName(), $targetOffline->getWins()]));
                            }

                            $form->sendToPlayer($sender);
                        }
                    }
                    break;

                case 'players':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game.');
                    } else if(!($arena = HungerGames::getInstance()->getArenaManager()->getArenaByPlayer($sender->getName())) instanceof Arena) {
                        $sender->sendMessage(TextFormat::RED . 'You can\'t see the player list right now');
                    } else if(($arena->getStatus() != Arena::IN_GAME and $arena->getStatus() != Arena::DEATH_MATCH) or !$arena->getPlayerByName($sender->getName())->isSpectating()) {
                        $sender->sendMessage(TextFormat::RED . 'You can\'t see the player list right now');
                    } else {
                        $form = Server::getInstance()->getPluginManager()->getPlugin('FormAPI');

                        if($form instanceof FormAPI) {
                            $form = $form->createSimpleForm(function(Player $player, ?int $action = null) use ($arena) {
                                if($action === null) return;

                                $target = array_values($arena->getPlayersAlive())[$action] ?? null;

                                if($target instanceof HungerGamesPlayer) {
                                    $player->teleport($target->getInstance()->asPosition());

                                    $player->sendMessage(TextFormat::GREEN . 'You were teleported to ' . $target->getName());
                                }
                            });

                            $form->setTitle(TextFormat::colorize('&8&lPlayer Selector'));

                            $form->setContent('Click on name to spectate');

                            foreach($arena->getPlayersAlive() as $p) {
                                $form->addButton($p->getName() . PHP_EOL . TextFormat::GRAY . 'Click to spectate');
                            }

                            $form->sendToPlayer($sender);
                        }
                    }
                    break;

                case 'force-join':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game');
                    } else if(!isset($args[1])) {
                        $sender->sendMessage($this->getMessageUsage());
                    } else if(!$sender->hasPermission($this->getPermission() . '.forcejoin')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else if(HungerGames::getInstance()->getArenaManager()->hasArenaByPlayer($sender->getName())) {
                        $sender->sendMessage(TextFormat::RED . 'Use this command when you don\'t have an arena');
                    } else if(!HungerGames::getInstance()->getArenaManager()->exists('hungergames_' . $args[1])) {
                        $sender->sendMessage(TextFormat::RED . 'Arena not found.');
                    } else {
                        $arena = HungerGames::getInstance()->getArenaManager()->getArenaByName('hungergames_' . $args[1]);

                        if($arena instanceof Arena) {
                            $sender->teleport($arena->getLevel()->getLevel()->getSafeSpawn());

                            $player = new HungerGamesPlayer($sender->getName(), $arena);

                            $player->slot = 0;

                            $player->spectating = true;

                            $arena->players[($arena->status == Arena::LOBBY) ? 0 : strtolower($sender->getName())] = $player;

                            if($arena->inArenaAsPlayerByName($sender->getName())) {
                                $sender->sendMessage(TextFormat::GREEN . 'Get forced to join the ' . $arena->getCustomName() . ' arena');
                            }
                        }
                    }
                    break;

                case 'entity-spawn':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game');
                    } else if(!$sender->hasPermission($this->getPermission() . '.entity')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else {
                        $nbt = HungerGamesJoin::createBaseNBT($sender, null, $sender->yaw, $sender->pitch);

                        $sender->saveNBT();

                        $skintag = $sender->namedtag->getCompoundTag('Skin');

                        assert($skintag !== null);

                        $nbt->setTag(clone $skintag);

                        $entity = HungerGamesJoin::createEntity('HungerGamesJoin', $sender->getLevel(), $nbt);

                        if($entity instanceof HungerGamesJoin) {
                            $entity->setScale(1.5);

                            $entity->spawnToAll();

                            $sender->sendMessage(TextFormat::GREEN . 'HungerGamesJoin entity spawned.');
                        }
                    }
                    break;

                case 'entity-despawn':
                    if(!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . 'Run this command in-game');
                    } else if(!$sender->hasPermission($this->getPermission() . '.entity')) {
                        $sender->sendMessage($this->getPermissionMessage());
                    } else {
                        foreach(Server::getInstance()->getDefaultLevel()->getEntities() as $entity) {
                            if($entity instanceof HungerGamesJoin) {
                                $entity->close();
                            }
                        }

                        $sender->sendMessage(TextFormat::GREEN . 'Entity killed');
                    }
                    break;

                case 'help':
                    $sender->sendMessage(TextFormat::BLUE . 'HungerGames Commands:');

                    $message = TextFormat::BLUE . '/' . $commandLabel . ' {%0}';

                    if($sender->hasPermission($this->getPermission() . '.create')) $sender->sendMessage(str_replace('{%0}', 'create', $message) . ' <world> <customName> <slots>:' . TextFormat::YELLOW . ' Create a arena.');

                    if($sender->hasPermission($this->getPermission() . '.delete')) $sender->sendMessage(str_replace('{%0}', 'delete', $message) . ' <world>: ' . TextFormat::YELLOW . 'Delete level.');

                    if($sender->hasPermission($this->getPermission() . '.spawn')) $sender->sendMessage(str_replace('{%0}', 'spawn', $message) . ' <slot>:' . TextFormat::YELLOW . ' Set spawn position.');

                    if($sender->hasPermission($this->getPermission() . '.save')) $sender->sendMessage(str_replace('{%0}', 'save', $message) . ':' . TextFormat::YELLOW . ' Save the level data.');

                    if($sender->hasPermission($this->getPermission() . '.vote')) $sender->sendMessage(str_replace('{%0}', 'vote', $message) . ':' . TextFormat::YELLOW . ' Vote chest items.');

                    if($sender->hasPermission($this->getPermission() . '.levels')) $sender->sendMessage(str_replace('{%0}', 'levels', $message) . ':' . TextFormat::YELLOW . ' See levels list.');

                    if($sender->hasPermission($this->getPermission())) $sender->sendMessage(str_replace('{%0}', 'help', $message) . ':' . TextFormat::YELLOW . ' See commands list.');
                    break;

                default:
                    $sender->sendMessage($this->getMessageUsage());
                    break;
            }
        }
    }

    /**
     * @return string
     */
    private function getMessageUsage(): string {
        return TextFormat::GREEN . 'HungerGames Reloaded plugin made by iTheTrollIdk, version 1.2.0';
    }
}