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
     * RemovePlayers Class
     *
     * @author Denzel Code
     */
    class RemovePlayers extends PluginTask {
        
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

            foreach (self::getMain()->getConfigurators() as $configurator) {
                if (!$configurator->getInstance() instanceof Player) {
                    self::getMain()->removeConfigurator($configurator->getName());
                    
                    continue;
                }
                
                if ($configurator->getSlot() <= $configurator->getMaxSlots() && $configurator->getRegister()) {
                    $levelName = $configurator->getInstance()->getLevel()->getFolderName();

                    if ($levelName != $configurator->getArenaName() && $levelName != $configurator->getRoomName()) {
                        self::getMain()->removeConfigurator($configurator->getName());
                    }
                }
            }

            foreach (self::getMain()->getEveryone() as $player) {
                $arena = $player->getArena();

                $pocketEveryone = $arena->getPocketEveryone();

                $pocketPlayers = $arena->getPocketPlayers();
                
                if (self::getMain()->playerExist($player->getName())) {
                    if (!$player->getInstance() instanceof Player) {
                        self::getMain()->removePlayer($player->getName());
                    } else {
                        $player = $player->getInstance();

                        if ($player->getLevel()->getFolderName() != $arena->getName() && $player->getLevel()->getFolderName() != $arena->getRoomName()) {
                            $sound = $mainConfig->get('leaveSound');

                            $sound = "pocketmine\\level\\sound\\{$sound}";

                            // $player->getLevel()->addSound(new $sound($player), [$player]);
                        
                            $player = self::getMain()->getPlayer($player->getName());
                            
                            $player->remove(false);
                            
                            foreach ($pocketEveryone as $playerArena) {
                                if ($arena->getStatus() == Arena::IN_GAME) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " left the game" . Color::RESET . ".");

                                    $playersCount = count($pocketPlayers) - 1;

                                    if ($playersCount > 1) {
                                        $playerArena->sendMessage(Color::RED . $playersCount . " players remain alive.");
                                    }
                                    
                                    $playerArena->getLevel()->addSound(new $sound($playerArena), [$playerArena]);
                                }
                            }
                        }
                    }
                } else if (self::getMain()->spectatorExist($player->getName())) {
                    if (!$player->getInstance() instanceof Player) {
                        self::getMain()->removeSpectator($player->getName());
                    } else {
                        $player = $player->getInstance();

                        if ($player->getLevel()->getFolderName() != $arena->getName() && $player->getLevel()->getFolderName() != $arena->getRoomName()) {
                            $spectator = self::getMain()->getSpectator($player->getName());
                            
                            $spectator->remove(false);
                            
                            $sound = $mainConfig->get('leaveSound');

                            $sound = "pocketmine\\level\\sound\\{$sound}";

                            // $player->getLevel()->addSound(new $sound($player), [$player]);
                        }
                    }
                }
            }   
        }
    }

?>
