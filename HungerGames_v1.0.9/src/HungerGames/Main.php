<?php

    /**
     * HungerGames
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Lesser General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or
     * (at your option) any later version.
     *
     * @author Denzel Code
     * @link https://github.com/DenzelCode
     *
     */

    namespace HungerGames;
    
    // Plugin
    use pocketmine\plugin\PluginBase;
    use pocketmine\utils\TextFormat as Color;
    use pocketmine\utils\Config;
    // Basic
    use pocketmine\Player;
    use pocketmine\item\Item;
    use pocketmine\item\enchantment\Enchantment;
    // Commands
    use pocketmine\command\{Command, CommandSender};
    // Tiles
    use pocketmine\tile\Sign;
    // Level
    use pocketmine\level\Level;
    use pocketmine\level\Position;
    // Chest
    use pocketmine\tile\Chest;
    use pocketmine\inventory\ChestInventory;
    
    use HungerGames\{Arena\Arena, Database\Connection, Player\Configurator, Player\Player as mainPlayer, Player\Spectator};

    /**
     * Main Class
     *
     * @author Denzel Code
     */
    class Main extends PluginBase {
        
        private $data = [
            'configurators' => [],
            'arenas' => [],
            'players' => [],
            'spectators' => []
        ];
        
        public function onLoad() {
            $this->data['prefix'] = Color::GRAY . '[' . Color::BLUE . 'Hunger' . Color::RED . 'Games' . Color::GRAY . ']' . Color::RESET . ' ';
            
            $logger = $this->getServer()->getInstance()->getLogger();
            
            $logger->info($this->getPrefix() . Color::BLUE . 'Loading plugin');
        }
        
        public function onEnable() {
            if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755);
            
            $this->getServer()->getPluginManager()->registerEvents(new Events($this), $this);
            
            $logger = $this->getServer()->getInstance()->getLogger();
            
            $mainConfig = $this->setConfiguration('config');
            
            $arenasConfig = $this->setConfiguration('arenas');
            
            $itemsConfig = $this->setConfiguration('items');
            
            $itemsData = $itemsConfig->getAll();
            
            if (!isset($itemsData['normal'])) {
                $itemsData['normal'] = [
                    ['id' => 306, 'meta' => 0, 'count' => 1],
                    ['id' => 307, 'meta' => 0, 'count' => 1],
                    ['id' => 308, 'meta' => 0, 'count' => 1],
                    ['id' => 309, 'meta' => 0, 'count' => 1],
                    ['id' => 310, 'meta' => 0, 'count' => 1],
                    ['id' => 311, 'meta' => 0, 'count' => 1],
                    ['id' => 312, 'meta' => 0, 'count' => 1],
                    ['id' => 313, 'meta' => 0, 'count' => 1],
                    ['id' => 276, 'meta' => 0, 'count' => 1],
                    ['id' => 276, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 9, 'level' => 1]]],
                    ['id' => 276, 'meta' => 0, 'count' => 1],
                    ['id' => 267, 'meta' => 0, 'count' => 1],
                    ['id' => 272, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 9, 'level' => 1]]],
                    ['id' => 272, 'meta' => 0, 'count' => 1],
                    ['id' => 261, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 19, 'level' => 1]]],
                    ['id' => 261, 'meta' => 0, 'count' => 1],
                    ['id' => 262, 'meta' => 0, 'count' => 10],
                    ['id' => 346, 'meta' => 0, 'count' => 1],
                    ['id' => 344, 'meta' => 0, 'count' => 8],
                    ['id' => 332, 'meta' => 0, 'count' => 8],
                    ['id' => 278, 'meta' => 0, 'count' => 1],
                    ['id' => 279, 'meta' => 0, 'count' => 1],
                    ['id' => 257, 'meta' => 0, 'count' => 1],
                    ['id' => 258, 'meta' => 0, 'count' => 1],
                    ['id' => 373, 'meta' => 21, 'count' => 1],
                    ['id' => 373, 'meta' => 13, 'count' => 1],
                    ['id' => 373, 'meta' => 15, 'count' => 1],
                    ['id' => 322, 'meta' => 0, 'count' => 1],
                    ['id' => 364, 'meta' => 0, 'count' => 5]
                ];
            }
            
            if (!isset($itemsData['op'])) {
                $itemsData['op'] = [
                    ['id' => 306, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 0, 'level' => 1]]],
                    ['id' => 307, 'meta' => 0, 'count' => 1],
                    ['id' => 308, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 0, 'level' => 1]]],
                    ['id' => 309, 'meta' => 0, 'count' => 1],
                    ['id' => 310, 'meta' => 0, 'count' => 1],
                    ['id' => 311, 'meta' => 0, 'count' => 1],
                    ['id' => 312, 'meta' => 0, 'count' => 1],
                    ['id' => 305, 'meta' => 0, 'count' => 1],
                    ['id' => 312, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 0, 'level' => 1]]],
                    ['id' => 313, 'meta' => 0, 'count' => 1],
                    ['id' => 313, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 0, 'level' => 1]]],
                    ['id' => 276, 'meta' => 0, 'count' => 1],
                    ['id' => 276, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 9, 'level' => 1]]],
                    ['id' => 272, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 9, 'level' => 2]]],
                    ['id' => 267, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 9, 'level' => 1]]],
                    ['id' => 261, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 19, 'level' => 1]]],
                    ['id' => 261, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 19, 'level' => 3]]],
                    ['id' => 262, 'meta' => 0, 'count' => 15],
                    ['id' => 262, 'meta' => 0, 'count' => 20],
                    ['id' => 344, 'meta' => 0, 'count' => 16],
                    ['id' => 332, 'meta' => 0, 'count' => 16],
                    ['id' => 278, 'meta' => 0, 'count' => 1],
                    ['id' => 346, 'meta' => 0, 'count' => 1],
                    ['id' => 279, 'meta' => 0, 'count' => 1],
                    ['id' => 278, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 15, 'level' => 3]]],
                    ['id' => 279, 'meta' => 0, 'count' => 1, 'enchantments' => [['id' => 15, 'level' => 3]]],
                    ['id' => 373, 'meta' => 21, 'count' => 1],
                    ['id' => 373, 'meta' => 13, 'count' => 1],
                    ['id' => 373, 'meta' => 15, 'count' => 1],
                    ['id' => 322, 'meta' => 0, 'count' => 5],
                    ['id' => 364, 'meta' => 0, 'count' => 10]
                ];
            }
            
            $itemsConfig->setAll($itemsData);
            
            $itemsConfig->save();
                
            $configData = $mainConfig->getAll();
            
            if (empty($configData['serverName'])) $configData['serverName'] = 'UbblyClub';
            
            if (empty($configData['serverIp'])) $configData['serverIp'] = 'play.ubbly.club';
            
            if (empty($configData['mysqlData'])) {
                $configData['mysqlData'] = [ 'host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => '', 'db' => 'hungergames' ];
            }
            
            if (empty($configData['defaultTime'])) $configData['defaultTime'] = 30;
            
            if (empty($configData['defaultEndTime'])) $configData['defaultEndTime'] = 600;
            
            if (empty($configData['defaultResetTime'])) $configData['defaultResetTime'] = 6;
            
            if (empty($configData['defaultInvincibleTime'])) $configData['defaultInvincibleTime'] = 31;
            
            if (empty($configData['startingSound'])) $configData['startingSound'] = 'PopSound';
            
            if (empty($configData['startedSound'])) $configData['startedSound'] = 'PopSound';
            
            if (empty($configData['invincibleSound'])) $configData['invincibleSound'] = 'PopSound';
            
            if (empty($configData['vencibleSound'])) $configData['vencibleSound'] = 'PopSound';
            
            if (empty($configData['endingSound'])) $configData['endingSound'] = 'PopSound';
            
            if (empty($configData['endedSound'])) $configData['endedSound'] = 'PopSound';
            
            if (empty($configData['resetingSound'])) $configData['resetingSound'] = 'PopSound';
            
            if (empty($configData['resetedSound'])) $configData['resetedSound'] = 'EndermanTeleportSound';
            
            if (empty($configData['joinSound'])) $configData['joinSound'] = 'EndermanTeleportSound';
            
            if (empty($configData['leaveSound'])) $configData['leaveSound'] = 'FizzSound';
            
            if (empty($configData['deathMoney'])) $configData['deathMoney'] = 1;
            
            if (empty($configData['killMoney'])) $configData['killMoney'] = 1;
            
            if (empty($configData['winMoney'])) $configData['winMoney'] = 5;
            
            if (empty($configData['minItemsPerChest'])) $configData['minItemsPerChest'] = 0;
            
            if (empty($configData['maxItemsPerChest'])) $configData['maxItemsPerChest'] = 26;
            
            if (empty($configData['killSound'])) $configData['killSound'] = 'AnvilFallSound';
            
            if (empty($configData['deathSound'])) $configData['deathSound'] = 'FizzSound';
            
            $mainConfig->setAll($configData);
            
            $mainConfig->save();
            
            try {
                $this->data['database'] = new Connection($mainConfig->get('mysqlData'));
                
                if (!$this->getDatabase()->run()) {
                    throw new \Exception("Can't connect to the database.");
                }
            } catch (\Exception $ex) {
                $logger->info($this->getPrefix() . Color::RED . $ex->getMessage());
            }
            
            if (!empty(($arenas = $arenasConfig->getAll()))) {
                foreach ($arenas as $name => $data) {
                    if (!file_exists($this->getServer()->getDataPath() . 'worlds/' . $data['name'])) {
                        $this->removeArena($name);

                        continue;
                    }
                    
                    $this->addArena($name, $data);
                }
                
                foreach ($this->getArenas() as $arena) {
                    $arena->initArena();
                }
            }

            $this->data['perm'] = 'hg.';

            $this->data['command'] = 'hg';
            
            $this->getServer()->getCommandMap()->register('hungergames', new Commands\HungerGames($this));
            
            $this->createTask(new Tasks\RemovePlayers($this), 20);

            $this->createTask(new Tasks\Game($this), 20);
            
            $this->createTask(new Tasks\Updater($this), 20);
            
            $logger->info($this->getPrefix() . Color::GREEN . 'Plugin enabled');
        }
        
        public function getPermission() : string {
            return $this->data['perm'];
        }

        public function getCommander() : string {
            return $this->data['command'];
        }
        
        public function getGameName() : string {
            return "HungerGames";
        }
        
        public function getPrefix() : string {
            return $this->data['prefix'];
        }
        
        public function getDatabase() : Connection {
            return $this->data['database'];
        }
        
        public function getPlugin(string $plugin) {
            return $this->getServer()->getPluginManager()->getPlugin($plugin);
        }
        
        public function setConfiguration(string $config) : Config {
            $this->data['configs'][$config] = new Config($this->getDataFolder() . "{$config}.yml", Config::YAML);
            
            return $this->data['configs'][$config];
        }
        
        public function getConfiguration(string $config) : Config {
            return $this->data['configs'][$config];
        }
        
        public function getConfigurations() : array {
            return $this->data['configs'];
        }
        
        public function addConfigurator(string $name, array $data) : Configurator {
            $name = strtolower($name);
            
            $this->data['configurators'][$name] = new Configurator($this, $data);
            
            return $this->data['configurators'][$name];
        }
        
        public function removeConfigurator(string $name) {
            $name = strtolower($name);
            
            unset($this->data['configurators'][$name]);
        }
        
        public function getConfigurator(string $name) {
            $name = strtolower($name);
            
            $return = false;
                    
            if (!empty($this->data['configurators'][$name])) $return = $this->data['configurators'][$name];
            
            return $return;
        }
        
        public function configuratorExist(string $name) : bool {
            if ($this->getConfigurator($name) instanceof Configurator) return true;
            
            return false;
        }
        
        public function getConfigurators() : array {
            return $this->data['configurators'];
        }
        
        public function addPlayer(string $name, array $data) : mainPlayer {
            $name = strtolower($name);
            
            $this->data['players'][$name] = new mainPlayer($this, $data);
            
            return $this->data['players'][$name];
        }
        
        public function removePlayer(string $name) {
            $name = strtolower($name);
            
            $player = $this->getPlayer($name);
            
            $mainConfig = $this->getConfiguration('config');
            
            $sound = $mainConfig->get('leaveSound');

            $sound = "pocketmine\\level\\sound\\{$sound}";
            
            if (!$player instanceof mainPlayer) return;

            if (($instance = $player->getInstance())) {
                $instance->setXPLevel(0);
            }
            
            $players = (count($player->getArena()->getPocketPlayers()) - 1);
            
            $arena = $player->getArena();
            
            if ($players > 0 && $arena->getStatus() == Arena::IN_GAME) {
                $economy = $this->getPlugin('EconomyAPI');

                // $tops = $this->getPlugin('Tops');
                
                if ($arena->getEndTime()) {
                    $economy->reduceMoney($player->getName(), $mainConfig->get('deathMoney'));

                    // $tops->increasePlayerDeaths($player->getName());
                }
            }
            
            foreach ($player->getArena()->getPocketEveryone() as $playerArena) {
                if ($players > 0 && $arena->getStatus() == Arena::WAITING) {
                    $playerArena->getLevel()->addSound(new $sound($playerArena), [$playerArena]);
                    
                    $playerArena->sendMessage(Color::RED . "{$player->getName()} left the game. {$players}/{$player->getArena()->getMaxPlayers()}" . Color::RESET);
                } else if ($players > 0 && $arena->getStatus() == Arena::EDITING) {
                    $playerArena->getLevel()->addSound(new $sound($playerArena), [$playerArena]);
                    
                    $playerArena->sendMessage(Color::RED . "{$player->getName()} left the edition. {$players}/{$players}" . Color::RESET);
                }
            }
            
            if ($player instanceof mainPlayer) {
                $players = $arena->getPlayers();
                
                foreach ($players as $playerArena) {
                    if ($playerArena->getSlot() > $player->getSlot()) {
                        $arena = $playerArena->getArena();
                        
                        if ($arena->getAllowJoin()) {
                            $playerArena->decreaseSlot();

                            if ($arena->getStatus() == Arena::STARTING) $playerArena->teleportToSlot();
                        }
                    }
                }
            }
            
            unset($this->data['players'][$name]);
        }
        
        public function playerOrSpectatorExist(string $name) : bool {
            if ($this->getPlayer($name) instanceof mainPlayer) return true; else if ($this->getSpectator($name) instanceof Spectator) return true;
            
            return false;
        }
        
        public function getPlayerOrSpectator(string $name) {
            if ($this->getPlayer($name) instanceof mainPlayer) return $this->getPlayer($name); else if ($this->getSpectator($name) instanceof Spectator) return $this->getSpectator($name);
            
            return false;
        }
        
        public function getPlayer(string $name) {
            $name = strtolower($name);
            
            $return = false;
            
            if (!empty($this->data['players'][$name])) $return = $this->data['players'][$name];
            
            return $return;
        }
        
        public function playerExist(string $name) : bool {
            if ($this->getPlayer($name) instanceof mainPlayer) return true;
            
            return false;
        }
        
        public function getPlayers() : array {
            return $this->data['players'];
        }

        public function getEveryone() : array {
            $players = [];
            
            foreach ($this->getPlayers() as $player) {
                $players[$player->getName()] = $player;
            }
            
            foreach ($this->getSpectators() as $player) {
                $players[$player->getName()] = $player;
            }
            
            return $players;
        }
        
        public function addSpectator(string $name, array $data) : Spectator {
            $name = strtolower($name);
            
            $this->data['spectators'][$name] = new Spectator($this, $data);
            
            return $this->data['spectators'][$name];
        }
        
        public function removeSpectator(string $name) {
            $name = strtolower($name);
            
            unset($this->data['spectators'][$name]);
        }
        
        public function getSpectator(string $name) {
            $name = strtolower($name);
            
            $return = false;
            
            if (!empty($this->data['spectators'][$name])) $return = $this->data['spectators'][$name];
            
            return $return;
        }
        
        public function spectatorExist(string $name) : bool {
            if ($this->getSpectator($name) instanceof Spectator) return true;
            
            return false;
        }
        
        public function getSpectators() : array {
            return $this->data['spectators'];
        }
        
        public function addArena(string $name, array $data) : Arena {
            $name = strtolower($name);
            
            $this->data['arenas'][$name] = new Arena($this, $data);
            
            $arenasConfig = $this->getConfiguration('arenas');
            
            $arenasConfig->set($name, $data);
            
            $arenasConfig->save();
            
            return $this->data['arenas'][$name];
        }
        
        public function removeArena(string $name) {
            $name = strtolower($name);

            if ($this->arenaExist($name)) $this->getArena($name)->removeBackup();
            
            $arenasConfig = $this->getConfiguration('arenas');
            
            $arenasConfig->remove($name);
            
            $arenasConfig->save();
            
            unset($this->data['arenas'][$name]);
        }
        
        public function getArena(string $name) {
            $name = strtolower($name);
            
            $return = false;
                    
            if (!empty($this->data['arenas'][$name])) $return = $this->data['arenas'][$name];
            
            return $return;
        }
        
        public function arenaExist(string $name) : bool {
            if ($this->getArena($name) instanceof Arena) return true;
            
            return false;
        }
        
        public function getArenasWithPlayers() {
            $arenas = [];
            
            foreach ($this->getArenas() as $arena) {
                $players = count($arena->getPlayers());
                
                if ($players) $arenas[$players][$arena->getName()] = $arena;
            }
            
            return $arenas;
        }
        
        public function getRandomArena() {
            $arenas = $this->getArenasWithPlayers();
            
            krsort($arenas);
            
            foreach ($arenas as $arenaPlayer) {
                foreach ($arenaPlayer as $arenaReturn) {
                    if ($arenaReturn->getAllowJoin()) return $arenaReturn;
                }
            }

            $arenas = $this->getArenas();

            shuffle($arenas);

            foreach ($arenas as $arenaReturn) {
                if ($arenaReturn->getAllowJoin()) return $arenaReturn;
            }
            
            return false;
        }
        
        public function getRandomSpectateArena() {
            $arenas = $this->getArenasWithPlayers();
            
            $arena = false;
            
            krsort($arenas);
            
            foreach ($arenas as $arenaPlayer) {
                foreach ($arenaPlayer as $arenaReturn) {
                    if ($arenaReturn->getAllowSpectate()) {
                        $arena = $arenaReturn;
                        
                        break;
                    }
                }
                
                break;
            }
            
            return $arena;
        }
        
        public function isConfigurating(string $player) : bool {
            if ($this->getConfigurator($player) instanceof Configurator) return true;
            
            return false;
        }
        
        public function isConfiguratingArena(string $arena) : bool {
            foreach ($this->getConfigurators() as $configurator) {
                if ($configurator->getArenaName() == $arena) return true;
            }
            
            return false;
        }
        
        /*
         * @return Arena[]
         */
        public function getArenas() : array {
            return $this->data['arenas'];
        }
        
        public function createTask($task, int $tick) {
            $handler = $this->getServer()->getScheduler()->scheduleRepeatingTask($task, $tick);
            
            $task->setHandler($handler);
            
            $this->data['tasks'][$task->getTaskId()] = $task->getTaskId();
        }
        
        public function removeTask(int $id) {
            unset($this->data['tasks'][$id]);
            
            $this->getServer()->getScheduler()->cancelTask($id);
        }
        
        public function getTasks() : array {
            return $this->data['tasks'];
        }
        
        public function onDisable() {
            $logger = $this->getServer()->getInstance()->getLogger();
            
            foreach ($this->getArenas() as $arena) {
                $arena->stopArena();
            }
            
            $logger->info($this->getPrefix() . Color::RED . 'Plugin disabled');
        }
    }

?>