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

    namespace HungerGames\Commands;
    
    // Plugin
    use pocketmine\utils\TextFormat as Color;
    use HungerGames\{Main, Arena\Arena};
    // Commands
    use pocketmine\command\{Command, CommandSender};
    // Player
    use pocketmine\Player;
    use pocketmine\entity\Entity;
    // Vector
    use pocketmine\math\Vector3;
    // Level
    use pocketmine\level\Level;
    // Particles
    use pocketmine\level\particle\FloatingTextParticle;
    // Tiles
    use pocketmine\tile\Sign;
    
    /**
     * Main Class
     *
     * @author Denzel Code
     */
    class HungerGames extends Command {
        
        private static $main;
        
        public function __construct(Main $plugin) {
            self::$main = $plugin;
            
            parent::__construct('hungergames', 'HungerGames Command', '/hungergames <argument>', [
                'hungergames',
                'hg',
                'hgames'
            ]);
        }
        
        public static function getMain() : Main {
            return self::$main;
        }

        public function getCommander() : string {
            return self::getMain()->getCommander();
        }

        public function execute(CommandSender $sender, $commandLabel, array $args) {
            switch ($args[0]) {
                case 'create':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!$this->hasPermissions($sender, 'create')) {
                        $sender->sendMessage(Color::RED . "You don't have permissions to run this command.");
                    } else if (!isset($args[1]) || !isset($args[2]) || $args[2] == 1) {
                        $sender->sendMessage(Color::RED . "Write a valid arena world name and slot limit.");
                    } else if (!file_exists(self::getMain()->getServer()->getDataPath() . 'worlds/' . $args[1])) {
                        $sender->sendMessage(Color::RED . "Arena world doesn't exists.");
                    } else if (isset($args[3]) && !file_exists(self::getMain()->getServer()->getDataPath() . 'worlds/' . $args[3])) {
                        $sender->sendMessage(Color::RED . "Waiting room world doesn't exists.");
                    } else if (self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena already registered.");
                    } else if (self::getMain()->isConfigurating($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, finish configuring your current arena.");
                    } else if (self::getMain()->playerExist($sender->getName()) || self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, leave this arena.");
                    } else if (self::getMain()->isConfiguratingArena($args[1])) {
                        $sender->sendMessage(Color::RED . "Someone else is configuring this arena.");
                    } else {
                        $mainConfig = self::getMain()->getConfiguration('config');
                        
                        $configData = $mainConfig->getAll();
                        
                        $room = $args[1];
                        
                        $defaultTime = $configData['defaultTime'];
                        
                        $defaultEndTime = $configData['defaultEndTime'];
                        
                        $defaultResetTime = $configData['defaultResetTime'];
                        
                        $defaultInvincibleTime = $configData['defaultInvincibleTime'];
                        
                        if (isset($args[3])) $room = $args[3];
                        
                        if (isset($args[4])) $defaultTime = $args[4];
                        
                        if (isset($args[5])) $defaultEndTime = $args[5];
                        
                        if (isset($args[6])) $defaultResetTime = $args[6];
                        
                        if (isset($args[7])) $defaultInvincibleTime = $args[7];
                        
                        $configurator = self::getMain()->addConfigurator($sender->getName(), [
                            'name' => strtolower($sender->getName()),
                            'room' => $room,
                            'arena' => $args[1],
                            'maxSlots' => $args[2],
                            'defaultTime' => $defaultTime,
                            'defaultEndTime' => $defaultEndTime,
                            'defaultResetTime' => $defaultResetTime,
                            'defaultInvincibleTime' => $defaultInvincibleTime
                        ]);
                        
                        if ($configurator->run()) {
                            $sender->sendMessage(Color::GOLD . "Execute command " . Color::YELLOW . "/{$this->getCommander()} cancel" . Color::GOLD . " to cancel.");

                            $sender->sendMessage(Color::GOLD . "Touch the waiting room spawn point.");
                        }
                    }
                    
                    break;
                    
                case 'delete':
                    
                    if ($sender instanceof Player && !$this->hasPermissions($sender, 'delete')) {
                        $sender->sendMessage(Color::RED . "You don't have permissions to run this command.");
                    } else if (empty($args[1])) {
                        $sender->sendMessage(Color::RED . "Write a valid arena name.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        foreach ($arena->getPocketEveryone() as $playerArena) {
                            $playerArena->sendMessage(Color::GREEN . "Reseting arena, the arena will be deleted.");
                        }
                        
                        $arena->remove();
                        
                        $sender->sendMessage(Color::GREEN . "Arena {$args[1]} removed.");
                    }
                    
                    break;
                    
                case 'edit':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!$this->hasPermissions($sender, 'edit')) {
                        $sender->sendMessage(Color::RED . "You don't have permissions to run this command.");
                    } else if (self::getMain()->playerExist($sender->getName()) || self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, leave this arena.");
                    } else if (!isset($args[1])) {
                        $sender->sendMessage(Color::RED . "Write a valid arena name.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        if ($arena->getStatus() != Arena::EDITING) {
                            foreach ($arena->getPocketEveryone() as $playerArena) {
                                $playerArena->sendMessage(Color::GREEN . "Reseting arena, the arena will be edited.");
                            }

                            $arena->resetArena();

                            $arena->setStatus(Arena::EDITING);
                        } 
                        
                        $player = self::getMain()->addPlayer($sender->getName(), [
                            'name' => $sender->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot()
                        ]);

                        $player->setLastPosition();
                        
                        $player->setEditingValues();
                        
                        $player->teleportToSlot();

                        $sender->sendMessage(Color::GOLD . "Execute command " . Color::YELLOW . "/{$this->getCommander()} cancel" . Color::GOLD . " to cancel.");
                        
                        $sender->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/{$this->getCommander()} leave" . Color::GOLD . " to exit.");
                        
                        $players = count($arena->getPocketPlayers());
                        
                        foreach ($arena->getPocketPlayers() as $playerArena) {
                            $playerArena->sendMessage(Color::DARK_PURPLE . "{$player->getName()} joined the edition. {$players}/{$players}" . Color::RESET);
                        }
                    }
                    
                    break;
                    
                case 'chest':
                    
                    $types = ['op', 'normal'];
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!$this->hasPermissions($sender, 'chest')) {
                        $sender->sendMessage(Color::RED . "Buy ranks to get this adventage: " . Color::GREEN . "http://store.ubbly.club" . Color::RED . ".");
                    } else if (!self::getMain()->playerExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, join an arena.");
                    } else if (!in_array($args[1], $types)) {
                        $sender->sendMessage("Chest types list:");
                        
                        foreach ($types as $type) {
                            $sender->sendMessage(Color::GREEN . "/swr chest {$type}:" . Color::RESET . " Vote {$type} chest.");
                        }
                    } else {
                        $arena = self::getMain()->getPlayer($sender->getName())->getArena();
                        
                        if ($arena->getStatus() != Arena::WAITING && $arena->getStatus() != Arena::STARTING && $arena->getStatus() != Arena::FULL) {
                            $sender->sendMessage(Color::RED . "You can't vote right now.");
                        } else {
                            if (in_array($sender->getName(), $arena->getChestVotes($args[1]))) {
                                $sender->sendMessage(Color::RED . "You already voted for {$args[1]} chest.");
                                
                                return;
                            }
                            
                            if ($args[1] == "op") {
                                $arena->removeChestVote('normal', $sender->getName());

                                $arena->addChestVote('op', $sender->getName());
                                
                                $color = Color::AQUA;
                            } else {
                                $arena->removeChestVote('op', $sender->getName());

                                $arena->addChestVote('normal', $sender->getName());
                                
                                $color = Color::GOLD;
                            }

                            foreach ($arena->getPocketPlayers() as $player) {
                                if ($player->getName() == $sender->getName()) {
                                    $player->sendMessage($color . "You voted for {$args[1]} chest.");
                                } else {
                                    $player->sendMessage($color . "{$sender->getName()} voted for {$args[1]} chest.");
                                }
                            }
                        }
                    }
                    
                    break;
                    
                case 'list':
                    
                    $sender->sendMessage(Color::GREEN . "HungerGames arena list.");
                    
                    foreach (self::getMain()->getArenas() as $arena) {
                        $arena instanceof \HungerGames\Arena\Arena;
                        
                        if (!empty($arena->hasSings())) {
                            $sender->sendMessage(Color::GREEN . $arena->getName() . Color::RESET . " - " . $arena->getStatusColor() . Color::RESET);
                        } else {
                            $sender->sendMessage(Color::RED . $arena->getName() . Color::RESET . " - " . $arena->getStatusColor() . Color::RESET);
                        }
                    }
                    
                    if (empty(self::getMain()->getArenas())) $sender->sendMessage(Color::RED . "There aren't registered arenas.");
                    
                    break;
                    
                case 'join':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (self::getMain()->playerExist($sender->getName()) || self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, leave this arena.");
                    } else if (isset($args[1])) {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        if (!self::getMain()->arenaExist($args[1])) {
                            $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                        } else if (!$arena->getAllowJoin() && !$this->hasPermissions($sender, 'join') || $arena->getStatus() == Arena::RESETING || $arena->getStatus() == Arena::EDITING) {
                            $sender->sendMessage(Color::RED . "Can't join this arena.");
                        } else {
                            $arena = self::getMain()->getArena($args[1]);
                            
                            $player = self::getMain()->addPlayer($sender->getName(), [
                                'name' => $sender->getName(),
                                'arena' => $arena->getName(),
                                'room' => $arena->getRoomName(),
                                'slot' => $arena->getFreeSlot(),
                                'lastPosition' => []
                            ]);

                            $player->setLastPosition();

                            if ($arena->getStatus() != Arena::IN_GAME) $player->joinArena(); else {
                                $player->setBattleValues();
                            
                                $player->teleportToSlot();
                            }
                        }
                    } else {
                        $arena = self::getMain()->getRandomArena();
                        
                        if (!$arena) {
                            $sender->sendMessage(Color::RED . "There aren't avaliable arenas.");
                        } else {
                            $player = self::getMain()->addPlayer($sender->getName(), [
                                'name' => $sender->getName(),
                                'arena' => $arena->getName(),
                                'room' => $arena->getRoomName(),
                                'slot' => $arena->getFreeSlot(),
                                'lastPosition' => []
                            ]);

                            $player->setLastPosition();

                            $player->joinArena();
                        }
                    }
                    
                    break;
                    
                case 'spectate':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else if (self::getMain()->playerExist($sender->getName()) || self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, leave this arena.");
                    } else if (isset($args[1])) {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        if (!$arena->getAllowSpectate()) {
                            $sender->sendMessage(Color::RED . "Can't spectate this arena.");
                        } else {
                            $spectator = self::getMain()->addSpectator($sender->getName(), [
                                'name' => $sender->getName(),
                                'arena' => $arena->getName(),
                                'room' => $arena->getRoomName(),
                                'slot' => $arena->getFreeSlot(),
                                'lastPosition' => []
                            ]);

                            $spectator->setLastPosition();
                            
                            $spectator->setValues();
            
                            $spectator->teleportToSlot();
                        }
                    } else {
                        $arena = self::getMain()->getRandomSpectateArena();
                        
                        if (!$arena) {
                            $sender->sendMessage(Color::RED . "There aren't avaliable arenas.");
                        } else {
                            $spectator = self::getMain()->addSpectator($sender->getName(), [
                                'name' => $sender->getName(),
                                'arena' => $arena->getName(),
                                'room' => $arena->getRoomName(),
                                'slot' => $arena->getFreeSlot()
                            ]);

                            $spectator->setLastPosition();
                            
                            $spectator->setValues();
            
                            $spectator->teleportToSlot();
                        }
                    }
                    
                    break;
                    
                case 'leave':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!self::getMain()->playerExist($sender->getName()) && !self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, join an arena.");
                    } else if (self::getMain()->playerExist($sender->getName())) {
                        self::getMain()->getPlayer($sender->getName())->remove();
                    } else {
                        self::getMain()->getSpectator($sender->getName())->remove();
                    }
                    
                    break;
                    
                case 'start': 
                    
                    $arena = false;
                    
                    if ($args[1]) $arena = $args[1];
                    
                    if ($sender instanceof Player) {
                        if (self::getMain()->playerExist($sender->getName()) && !$arena) {
                            if ($this->hasPermissions($sender, 'start')) {
                                $player = self::getMain()->getPlayer($sender->getName());

                                $arena = $player->getArena();

                                if (count($arena->getPocketPlayers()) <= 1) {
                                    $sender->sendMessage(Color::RED . "Arena need more players.");
                                } else if ($arena->getTime() <= 11) {
                                    $sender->sendMessage(Color::RED . "Arena already starting/started.");
                                } else {
                                    $arena->setTime(11);

                                    foreach ($arena->getPocketPlayers() as $player) {
                                        $player->sendMessage(Color::GREEN . "Arena started by {$sender->getName()}.");
                                    }
                                }
                            } else {
                                $sender->sendMessage(Color::RED . "Buy ranks to get this adventage: " . Color::GREEN . "http://store.ubbly.club" . Color::RED . ".");
                            }
                        } else {
                            if (self::getMain()->arenaExist($arena)) {
                                $arena = self::getMain()->getArena($arena);
                                
                                if (count($arena->getPocketPlayers()) <= 1) {
                                    $sender->sendMessage(Color::RED . "Arena need more players.");
                                } else if ($arena->getTime() <= 11) {
                                    $sender->sendMessage(Color::RED . "Arena already starting/started.");
                                } else {
                                    $arena->setTime(11);
                                    
                                    foreach ($arena->getPocketPlayers() as $player) {
                                        $player->sendMessage(Color::GREEN . "Arena started by {$sender->getName()}.");
                                    }
                                }
                            } else {
                                $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                            }
                        }
                    } else {
                        if (!$arena) {
                            $sender->sendMessage(Color::RED . "Write a valid arena name.");
                        } else if (!self::getMain()->arenaExist($arena)) {
                            $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                        } else {
                            $arena = self::getMain()->getArena($arena);

                            if (count($arena->getPocketPlayers()) <= 1) {
                                $sender->sendMessage(Color::RED . "Arena need more players.");
                            } else if ($arena->getStarted()) {
                                $sender->sendMessage(Color::RED . "Arena already starting/started.");
                            } else {
                                $arena->setTime(11);

                                foreach ($arena->getPocketPlayers() as $player) {
                                    $player->sendMessage(Color::GREEN . "Arena started by {$sender->getName()}.");
                                }
                            }
                        }
                    }
                    
                    break;
                    
                case 'players':
                    
                    if ($sender instanceof Player && empty($args[1])) {
                        if (!self::getMain()->playerExist($sender->getName()) && !self::getMain()->spectatorExist($sender->getName())) {
                            $sender->sendMessage(Color::RED . "First, join an arena.");
                        } else {
                            $player = self::getMain()->getPlayer($sender->getName());
                            
                            if (self::getMain()->spectatorExist($sender->getName())) {
                                $player = self::getMain()->getSpectator($sender->getName());
                            }
                            
                            $arena = $player->getArena();
                            
                            $main = self::getMain();

                                                    $sender->sendMessage(Color::GREEN . "{$main->getGameName()} {$arena->getName()} players:" . Color::RESET);
                            
                            if (empty($arena->getPocketPlayers())) {
                                $sender->sendMessage(Color::RED . 'No players were found.');
                            } else {
                                $players = [];

                                foreach ($arena->getPocketPlayers() as $player) {
                                    $players[] = $player->getName();
                                }

                                $sender->sendMessage(Color::GOLD . join(Color::WHITE  . ', ' . Color::GOLD, $players) . Color::WHITE . '.');
                            }
                        }
                            
                        return;
                    }
                    
                    if (empty($args[1])) {
                        $sender->sendMessage(Color::RED . "Type an arena name.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        $main = self::getMain();

                        $sender->sendMessage(Color::GREEN . "{$main->getGameName()} {$arena->getName()} players:" . Color::RESET);
                            
                        if (empty($arena->getPocketPlayers())) {
                            $sender->sendMessage(Color::RED . 'No players were found.');
                        } else {
                            $players = [];
                            
                            foreach ($arena->getPocketPlayers() as $player) {
                                $players[] = $player->getName();
                            }
                            
                            $sender->sendMessage(Color::GOLD . join(Color::WHITE  . ', ' . Color::GOLD, $players) . Color::WHITE . '.');
                        }
                    }
                    
                    break;
                    
                case 'spectators':
                    
                    if ($sender instanceof Player && empty($args[1])) {
                        if (!self::getMain()->playerExist($sender->getName()) && !self::getMain()->spectatorExist($sender->getName())) {
                            $sender->sendMessage(Color::RED . "First, join an arena.");
                        } else {
                            $player = self::getMain()->getPlayer($sender->getName());
                            
                            if (self::getMain()->spectatorExist($sender->getName())) {
                                $player = self::getMain()->getSpectator($sender->getName());
                            }
                            
                            $arena = $player->getArena();
                            
                            $main = self::getMain();

                            $sender->sendMessage(Color::AQUA . "{$main->getGameName()} {$arena->getName()} spectators:" . Color::RESET);
                            
                            if (empty($arena->getPocketSpectators())) {
                                $sender->sendMessage(Color::RED . 'No spectators were found.');
                            } else {
                                $players = [];

                                foreach ($arena->getPocketSpectators() as $player) {
                                    $players[] = $player->getName();
                                }

                                $sender->sendMessage(Color::GOLD . join(Color::WHITE  . ', ' . Color::GOLD, $players) . Color::WHITE . '.');
                            }
                        }
                            
                        return;
                    }
                    
                    if (empty($args[1])) {
                        $sender->sendMessage(Color::RED . "Type an arena name.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else {
                        $arena = self::getMain()->getArena($args[1]);
                        
                        $main = self::getMain();

                            $sender->sendMessage(Color::AQUA . "{$main->getGameName()} {$arena->getName()} spectators:" . Color::RESET);
                            
                        if (empty($arena->getPocketSpectators())) {
                            $sender->sendMessage(Color::RED . 'No spectators were found.');
                        } else {
                            $players = [];

                            foreach ($arena->getPocketSpectators() as $player) {
                                $players[] = $player->getName();
                            }

                            $sender->sendMessage(Color::GOLD . join(Color::WHITE  . ', ' . Color::GOLD, $players) . Color::WHITE . '.');
                        }
                    }
                    
                    break;

                case 'steps':

                    $types = [
                        'player' => "Show steps to reach the nearest player."
                    ];

                    $player = self::getMain()->getPlayer($sender->getName());

                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!self::getMain()->playerExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, join an arena.");
                    } else if (!in_array($args[1], array_keys($types))) {
                        $sender->sendMessage(Color::RED . "Steps types:");

                        foreach ($types as $key => $value) {
                            $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} steps {$key}:" . Color::RESET . " {$value}.");
                        }
                    } else if (!$player->getArena()->getStarted()) {
                        $sender->sendMessage(Color::RED . "Wait that the arena start.");
                    } else {
                        if ($args[1] == 'player') {
                            if (count($player->getArena()->getPlayers()) == 1) {
                                $sender->sendMessage(Color::RED . "There aren't players.");

                                return;
                            }

                            $findPlayer = $player->getNearlestPlayer();

                            $steps = $player->getSteps($findPlayer->getInstance()->getPosition())['steps'];

                            if ($steps >= 30) {
                                $steps = Color::RED . $steps;
                            } else if ($steps >= 10) {
                                $steps = Color::BLUE . $steps;
                            } else if (!$steps) {
                                $steps = Color::GREEN . "You arrived";
                            } else {
                                $steps = Color::GREEN . $steps;
                            }

                            $up = $player->getSteps($findPlayer->getInstance()->getPosition())['up'];

                            if ($up >= 30) {
                                $up = Color::RED . $up;
                            } else if ($up >= 10) {
                                $up = Color::BLUE . $up;
                            } else if (!$up) {
                                $up = Color::GREEN . "You arrived";
                            } else {
                                $up = Color::GREEN . $up;
                            }

                            $sender->setCompassDestination($findPlayer->getInstance()->getX(), $findPlayer->getInstance()->getY(), $findPlayer->getInstance()->getZ());

                            $sender->sendPopup(Color::GREEN . "Player: " . Color::BLUE . $findPlayer->getName() . Color::AQUA . " Steps: " . $steps . Color::WHITE . " Up: " . Color::BLUE . $up);
                        }
                    }

                    break;
                    
                case 'sign':
                    
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!$this->hasPermissions($sender, 'sign')) {
                        $sender->sendMessage(Color::RED . "You don't have permissions to run this command.");
                    } else if (!isset($args[1])) {
                        $sender->sendMessage(Color::RED . "Write a valid arena name.");
                    } else if (!self::getMain()->arenaExist($args[1])) {
                        $sender->sendMessage(Color::RED . "Arena doesn't exist.");
                    } else if (self::getMain()->configuratorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "You are configurating other thing.");
                    } else if (self::getMain()->playerExist($sender->getName()) || self::getMain()->spectatorExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "First, leave this arena.");
                    } else {
                        $configurator = self::getMain()->addConfigurator($sender->getName(), [
                            'name' => strtolower($sender->getName()),
                            'arena' => $args[1],
                            'sign' => true,
                            'maxSlots' => 0,
                            'defaultTime' => 0,
                            'defaultEndTime' => 0,
                            'defaultResetTime' => 0,
                            'defaultInvincibleTime' => 0
                        ]);
                        
                        $configurator->run();

                        $sender->sendMessage(Color::GOLD . "Execute command " . Color::YELLOW . "/{$this->getCommander()} cancel" . Color::GOLD . " to cancel.");

                        $sender->sendMessage(Color::GOLD . "Touch a sign to register.");
                    }
                    
                    break;

                case 'cancel':

                    if (!$sender instanceof Player) {
                        $sender->sendMessage(Color::RED . "Run this command in-game.");
                    } else if (!$this->hasPermissions($sender, 'cancel')) {
                        $sender->sendMessage(Color::RED . "You don't have permissions to run this command.");
                    } else if (!self::getMain()->configuratorExist($sender->getName()) && !self::getMain()->playerExist($sender->getName())) {
                        $sender->sendMessage(Color::RED . "You aren't configuring/editing an arena.");
                    } else {
                        if (self::getMain()->configuratorExist($sender->getName())) {
                            $configurator = self::getMain()->getConfigurator($sender->getName());

                            $configurator->teleportToLastPosition();

                            $sender->setGamemode(Player::SURVIVAL);

                            self::getMain()->removeConfigurator($configurator->getName());

                            $sender->sendMessage(Color::GREEN . "Configuration canceled.");
                        } else {
                            $player = self::getMain()->getPlayer($sender->getName());

                            $arena = $player->getArena();

                            if ($arena->getStatus() != Arena::EDITING) {
                                $sender->sendMessage(Color::RED . "You must be editing the arena.");

                                return;
                            }

                            foreach ($arena->getPocketEveryone() as $playerArena) {
                                $playerArena->sendMessage(Color::GREEN . "{$sender->getName()} cancelled the edition.");
                            }

                            $arena->resetArena();
                        }
                    }

                    break;
                        
                case 'help':
                default:
                    
                    $sender->sendMessage(Color::RED . "HungerGames commands list:");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} help:" . Color::RESET . " Commands list.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} create <arena> <slots> <room> <time> <endTime> <invincibleTime>:" . Color::RESET . " Create arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} delete <arena>:" . Color::RESET . " Delete arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} cancel:" . Color::RESET . " Cancel configuration.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} edit <arena> <player>:" . Color::RESET . " Edit arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} list:" . Color::RESET . " List arenas.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} join <arena>:" . Color::RESET . " Join arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} spectate <arena>:" . Color::RESET . " Spectate arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} start <arena>:" . Color::RESET . " Start arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} players <arena>:" . Color::RESET . " Show arena players.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} spectators <arena>:" . Color::RESET . " Show arena players.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} steps <type>:" . Color::RESET . " Show steps to cord.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} chest op:" . Color::RESET . " Vote OP chest.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} chest normal:" . Color::RESET . " Vote Normal chest.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} leave:" . Color::RESET . " Leave arena.");
                    $sender->sendMessage(Color::GREEN . "/{$this->getCommander()} sign <arena>:" . Color::RESET . " Register arena sign.");
                    $sender->sendMessage(Color::AQUA . "HungerGames by Denzel Code.");
                    
                    break;
            }
        }
        
        public function hasPermissions(Player $player, string $permission) {
            return $player->hasPermission(self::getMain()->getPermission() . 'admin') || $player->hasPermission(self::getMain()->getPermission() . $permission);
        }
    }

?>
