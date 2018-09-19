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

    namespace HungerGames\Tasks;
    
    // Plugin
    use pocketmine\scheduler\PluginTask;
    use pocketmine\utils\TextFormat as Color;
    use HungerGames\{Main, Arena\Arena, Player\Player as mainPlayer};
    // Player
    use pocketmine\Player;
    use pocketmine\entity\Entity;
    // Chest
    use pocketmine\tile\Chest;
    use pocketmine\inventory\ChestInventory;
    // Level
    use pocketmine\level\Level;
    use pocketmine\level\Position;
    // Blocks
    use pocketmine\block\Block;
    use pocketmine\block\Air;
    use pocketmine\block\Glass;
    // Math
    use pocketmine\math\Vector3;
    
    /**
     * Game Class
     *
     * @author Denzel Code
     */
    class Game extends PluginTask {
        
        private static $main;
        
        public function __construct(Main $plugin) {
            self::$main = $plugin;
            
            parent::__construct($plugin);
        }
        
        public static function getMain() : Main {
            return self::$main;
        }

        public function onRun($currentTick) {
            $mainConfig = self::getMain()->getConfiguration('config');
                
            $economy = self::getMain()->getPlugin('EconomyAPI');

            $colors = [
                Color::AQUA, Color::WHITE, Color::GREEN, Color::YELLOW, Color::LIGHT_PURPLE, Color::RED, Color::GRAY, Color::YELLOW, Color::GOLD
            ];
            
            $name = Color::AQUA . "✘ " . $colors[array_rand($colors)] . self::getMain()->getGameName() . Color::AQUA . " ✘";

            $float = str_repeat("\n", 8);
                    
            $floatRight = str_repeat(" ", 71);

            foreach (self::getMain()->getArenas() as $arena) {
                $players = $arena->getPlayers();
                
                $pocketPlayers = $arena->getPocketPlayers();
                
                $spectators = $arena->getSpectators();
                
                $pocketSpectators = $arena->getPocketSpectators();
                
                $everyone = $arena->getEveryone();
                
                $pocketEveryone = $arena->getPocketEveryone();
                
                $time = $arena->timeToString($arena->secondsToTime(($arena->getTime() - 1)));
                
                $endTime = $arena->timeToString($arena->secondsToTime($arena->getEndTime()));
                
                $defaultTime = $arena->timeToString($arena->secondsToTime($arena->getDefaultTime()));
                
                if (!self::getMain()->getServer()->isLevelLoaded($arena->getName())) {
                    $arena->setStatus(Arena::UNDEFINED);

                    continue;
                }
                
                if ($arena->getStatus() == Arena::EDITING) {
                    if (count($pocketPlayers)) {
                        $arena->setStatus(Arena::EDITING);
                        
                        foreach ($pocketPlayers as $player) {
                            $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Editor(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getPocketPlayers()) . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Editor" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                        }
                    } else {
                        foreach (self::getMain()->getServer()->getLevels() as $level) {
                            foreach ($level->getPlayers() as $player) {
                                if ($player->hasPermission(self::getMain()->getPermission() . 'edit') || $player->hasPermission(self::getMain()->getPermission() . 'admin')) {
                                    $player->sendMessage(Color::GREEN . "Arena {$arena->getName()} was edited.");
                                }
                            }
                        } 
                        
                        $arena->getLevel()->save(true);
                        
                        $arena->backup();
                        
                        $arena->resetArena();
                    }
                } else {
                    if (count($pocketPlayers) > 1) {
                        if ($arena->getTime()) {
                            $arena->decreaseTime();

                            if ($arena->getMaxPlayers() <= count($arena->getPocketPlayers())) {
                                $arena->setStatus(Arena::FULL);
                                
                                if ($arena->getTime() > 11) $arena->setTime(11);
                            }

                            foreach ($pocketPlayers as $player) {
                                $player->setXpLevel($arena->getTime());

                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "OP Votes:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getChestVotes('op')) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Normal Votes:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getChestVotes('normal')) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "GameTime:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $time . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Player" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                                
                                if ($arena->getTime() <= 10) {
                                    if ($arena->getStatus() != Arena::FULL) $arena->setStatus(Arena::STARTING);

                                    $playerArena = self::getMain()->getPlayer($player->getName());
                                    
                                    if ($arena->getTime() == 10) {
                                        if ($playerArena instanceof mainPlayer) {
                                            $playerArena->setArenaValues();
                                        }
                                    }

                                    if (!$arena->getTime()) {
                                        if ($playerArena instanceof mainPlayer) $playerArena->setBattleValues();
                                        
                                        $arena->fillChests();
                                        
                                        if ($arena->getLevel()->getTime()) $arena->getLevel()->setTime(0);

                                        $player->addTitle(Color::GREEN . "Started", Color::GREEN . "prepare you!");

                                        $sound = $mainConfig->get('startedSound');
                                    } else {
                                        $sound = $mainConfig->get('startingSound');

                                        if ($arena->getTime() == 1) {
                                            $player->addTitle(Color::GREEN . "Starting", Color::GRAY . $arena->getTime() . Color::GREEN . " second!");
                                        } else if ($arena->getTime()) {
                                            $player->addTitle(Color::GREEN . "Starting", Color::GRAY . $arena->getTime() . Color::GREEN . " seconds!");
                                        }
                                    }

                                    $sound = "pocketmine\\level\\sound\\{$sound}";

                                    $arena->getLevel()->addSound(new $sound($player), [$player]);
                                }
                            }
                        }

                        if (!$arena->getTime() && $arena->getEndTime() > 0) {
                            $arena->decreaseEndTime();

                            $arena->setStatus(Arena::IN_GAME);

                            if ($arena->getInvincibleTime() || $arena->getInvincibleTime() == 0) {
                                if ($arena->getInvincibleTime() <= ($arena->getDefaultInvincibleTime() - 1)) {
                                    foreach ($pocketEveryone as $player) {
                                        $sound = $mainConfig->get('invincibleSound');

                                        if ($arena->getInvincibleTime() == 0 && !$arena->getVencible()) {
                                            $player->setXpLevel(0);
                                            
                                            $sound = $mainConfig->get('vencibleSound');
                                            
                                            if ($player->getName() == end($pocketEveryone)->getName()) $arena->setVencible(true);

                                            $player->addTitle(Color::RED . "Battle", Color::RED . "battle start!");
                                        } else if ($arena->getInvincibleTime() == 1) {
                                            $player->addTitle(Color::GOLD . "Invincible", Color::GRAY . $arena->getInvincibleTime() . Color::GOLD . " second");
                                        } else if ($arena->getInvincibleTime() > 1 && $arena->getInvincibleTime() <= 10) {
                                            $player->addTitle(Color::GOLD . "Invincible", Color::GRAY . $arena->getInvincibleTime() . Color::GOLD . " seconds");
                                        }
                                        
                                        if ($arena->getInvincibleTime() > 10) {
                                            $player->sendPopup(Color::GRAY . $arena->getInvincibleTime() . Color::GOLD . " seconds of invincibility.");
                                        }

                                        if ($arena->getInvincibleTime() >= 0 && !$arena->getVencible()) {
                                            $sound = "pocketmine\\level\\sound\\{$sound}";

                                            $arena->getLevel()->addSound(new $sound($player), [$player]);
                                        }
                                    }
                                }

                                if ($arena->getInvincibleTime() != 0) $arena->decreaseInvincibleTime();
                            } else {
                                $player->setXpLevel($arena->getEndTime());
                            }

                            foreach ($pocketPlayers as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "EndTime:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $endTime . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Player" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }

                            foreach ($pocketSpectators as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "EndTime:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $endTime . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Spectator" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }
                            
                            if ($arena->getEndTime() == intval($arena->getDefaultEndTime() / 2)) {
                                $arena->fillChests();
                                
                                foreach ($pocketEveryone as $player) {
                                    $player->sendMessage(Color::GREEN . "Chest items filled");

                                    $player->sendMessage(Color::AQUA . "HungerGames by Denzel Code");
                                }
                            }

                            foreach ($pocketEveryone as $player) {
                                if ($arena->getEndTime() <= 10) {
                                    if (!$arena->getTime()) {
                                        $sound = $mainConfig->get('endedSound');
                                    } else {
                                        $sound = $mainConfig->get('endingSound');
                                    }

                                    $sound = "pocketmine\\level\\sound\\{$sound}";

                                    $player->getLevel()->addSound(new $sound($player), [$player]);

                                    if ($arena->getEndTime() == 1) {
                                        $player->addTitle(Color::RED . "Ending", Color::GRAY . $arena->getEndTime() . Color::RED . " second");
                                    } else if ($arena->getEndTime() == 0) {
                                        $player->addTitle(Color::RED . "Ended", Color::RED . "battle end!");
                                    } else {
                                        $player->addTitle(Color::RED . "Ending", Color::GRAY . $arena->getEndTime() . Color::RED . " seconds");
                                    }
                                }
                            }
                        } 

                        if (!$arena->getEndTime()) {
                            foreach ($pocketPlayers as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Player(s)" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }

                            foreach ($pocketSpectators as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Spectator" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }
                            
                            if ($arena->getResetTime() == $arena->getDefaultResetTime()) {
                                foreach ($players as $player) $player->setResetValues();
                                
                                foreach ($pocketEveryone as $player) {
                                    $player->sendMessage(Color::RED . "Battle end! no winners.");
                                }
                                
                                if (!$arena->getWinned()) self::getMain()->getServer()->broadcastMessage(Color::RED . "There are no winners in the " . self::getMain()->getGameName() . " {$arena->getName()} arena.");
                                
                                $arena->decreaseResetTime();
                            } else if ($arena->getResetTime() || $arena->getResetTime() == 0) {
                                $arena->setStatus(Arena::RESETING);

                                if (!$arena->getResetTime()) {
                                    foreach ($pocketEveryone as $player) {
                                        $player->setXpLevel($arena->getResetTime());

                                        $sound = $mainConfig->get('resetedSound');

                                        $sound = "pocketmine\\level\\sound\\{$sound}";

                                        $player->getLevel()->addSound(new $sound($player), [$player]);

                                        $player->addTitle(Color::AQUA . "Reseted", Color::AQUA . "arena reseted!");

                                        if (self::getMain()->playerExist($player->getName())) {
                                            $playerArena = self::getMain()->getPlayer($player->getName());

                                            $playerArena->remove();
                                        } else if (self::getMain()->spectatorExist($player->getName())) {
                                            $spectator = self::getMain()->getSpectator($player->getName());

                                            $spectator->remove();
                                        }
                                    }

                                    $arena->resetArena();
                                } else if ($arena->getResetTime() <= ($arena->getDefaultResetTime() - 1)) {
                                    foreach ($pocketEveryone as $player) {
                                        $player->setXpLevel($arena->getResetTime());

                                        $sound = $mainConfig->get('resetingSound');

                                        $sound = "pocketmine\\level\\sound\\{$sound}";

                                        $player->getLevel()->addSound(new $sound($player), [$player]);

                                        if ($arena->getResetTime() == 1) {
                                            $player->addTitle(Color::AQUA . "Reseting", Color::GRAY . $arena->getResetTime() . Color::AQUA . " second!");
                                        } else {
                                            $player->addTitle(Color::AQUA . "Reseting", Color::GRAY . $arena->getResetTime() . Color::AQUA . " seconds!");
                                        }
                                    }
                                    
                                    $arena->decreaseResetTime();
                                }
                            }
                        }
                    } else if (count($pocketPlayers)) {
                        if ($arena->getTime()) {
                            $arena->resetTime();

                            foreach ($pocketPlayers as $player) {
                                $player->setXPLevel($arena->getDefaultTime());

                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "GameTime:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $defaultTime . "\n" . $floatRight . Color::BOLD . Color::AQUA . "OP Votes:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getChestVotes('op')) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Normal Votes:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getChestVotes('normal')) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Player" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                                
                                if ($player->getLevel()->getFolderName() != $arena->getRoomLevel()->getFolderName()) {
                                    if (self::getMain()->playerExist($player->getName())) {
                                        self::getMain()->getPlayer($player->getName())->teleportToRoom();
                                    }
                                }
                            }
                        } else {
                            foreach ($pocketPlayers as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Player(s)" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }

                            foreach ($pocketSpectators as $player) {
                                $player->sendTip($floatRight . Color::BOLD . $name . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::RED . "Arena:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . $arena->getName() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GREEN . "Player(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($pocketPlayers) . "/" . $arena->getMaxPlayers() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Status:" . Color::RESET . "\n" . $floatRight . " " .  $arena->getStatusColor() . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::AQUA . "Spectator(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . count($arena->getSpectators()) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::GOLD . "Role:" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . "Spectator" . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::YELLOW . "Coin(s):" . Color::RESET . "\n" . $floatRight . Color::BLUE . " " . number_format($economy->myMoney($player->getName())) . Color::RESET . "\n" . $floatRight . Color::BOLD . Color::WHITE . strtolower($mainConfig->get('serverIp')) . Color::RESET . "\n" . $float);
                            }
                            
                            if ($arena->getResetTime() == $arena->getDefaultResetTime()) {
                                $tops = self::getMain()->getPlugin('Tops');

                                foreach ($players as $player) $player->setResetValues();

                                foreach ($pocketPlayers as $player) {
                                    $arena->setWinned(true);

                                    $tops->increasePlayerWins($player->getName());
                                    
                                    $economy->addMoney($player->getName(), $mainConfig->get('winMoney'));
                                    
                                    $player->addTitle(Color::GREEN . "YOU WIN", Color::GREEN . "+{$mainConfig->get('winMoney')} coins!");
                                    
                                    $player->sendMessage(Color::GREEN . "YOU WIN! +{$mainConfig->get('winMoney')} coins.");

                                    self::getMain()->getServer()->broadcastMessage(Color::YELLOW . $player->getName() . Color::GOLD . " won in the " . Color::YELLOW . $arena->getName() . " " . Color::GOLD . self::getMain()->getGameName() . " arena.");
                                    
                                    $winner = $player;
                                }
                                
                                foreach ($pocketSpectators as $player) {
                                    $playerName = strtoupper($winner->getName());
                                    
                                    $player->addTitle(Color::GREEN . "{$playerName} WIN", Color::GREEN . "try again!");
                                }
                                
                                $arena->decreaseResetTime();
                            } else if ($arena->getResetTime() || $arena->getResetTime() == 0) {
                                $arena->setStatus(Arena::RESETING);

                                if (!$arena->getResetTime()) {
                                    foreach ($pocketEveryone as $player) {
                                        $player->setXpLevel($arena->getResetTime());

                                        $sound = $mainConfig->get('resetedSound');

                                        $sound = "pocketmine\\level\\sound\\{$sound}";

                                        $player->getLevel()->addSound(new $sound($player), [$player]);

                                        $player->addTitle(Color::AQUA . "Reseted", Color::AQUA . "arena reseted!");

                                        if (self::getMain()->playerExist($player->getName())) {
                                            $playerArena = self::getMain()->getPlayer($player->getName());

                                            $playerArena->remove();
                                        } else if (self::getMain()->spectatorExist($player->getName())) {
                                            $spectator = self::getMain()->getSpectator($player->getName());

                                            $spectator->remove();
                                        }
                                    }

                                    $arena->resetArena();
                                } else if ($arena->getResetTime() <= ($arena->getDefaultResetTime() - 1)) {
                                    foreach ($pocketEveryone as $player) {
                                        $player->setXpLevel($arena->getResetTime());

                                        $sound = $mainConfig->get('resetingSound');

                                        $sound = "pocketmine\\level\\sound\\{$sound}";

                                        $player->getLevel()->addSound(new $sound($player), [$player]);

                                        if ($arena->getResetTime() == 1) {
                                            $player->addTitle(Color::AQUA . "Reseting", Color::GRAY . $arena->getResetTime() . Color::AQUA . " second!");
                                        } else {
                                            $player->addTitle(Color::AQUA . "Reseting", Color::GRAY . $arena->getResetTime() . Color::AQUA . " seconds!");
                                        }
                                    }
                                    
                                    $arena->decreaseResetTime();
                                }
                            }
                        }
                    } else {
                        if (!$arena->getTime() || $arena->getResetTime() != $arena->getDefaultResetTime() || $arena->getEndTime() != $arena->getDefaultEndTime()) {
                            if (!$arena->getWinned()) self::getMain()->getServer()->broadcastMessage(Color::RED . "There are no winners in the " . self::getMain()->getGameName() . " {$arena->getName()} arena.");

                            $arena->resetArena();
                        } else {
                            $arena->resetData();
                        }
                    }
                }
            }
        }
    }

?>
