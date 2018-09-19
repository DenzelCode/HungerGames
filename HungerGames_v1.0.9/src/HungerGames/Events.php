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
    use pocketmine\utils\TextFormat as Color;
    use pocketmine\utils\Config;
    // Basic
    use pocketmine\Player;
    use pocketmine\item\Item;
    use pocketmine\item\enchantment\Enchantment;
    // Events
    use pocketmine\event\Listener;
    use pocketmine\event\player\{PlayerInteractEvent, PlayerMoveEvent, PlayerLoginEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent};
    use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent};
    use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityCombustEvent, EntityCombustByEntityEvent};
    // Tiles
    use pocketmine\tile\Sign;
    // Level
    use pocketmine\level\Level;
    use pocketmine\level\Position;
    use pocketmine\math\Vector3;
    // Chest
    use pocketmine\tile\Chest;
    use pocketmine\inventory\ChestInventory;
    
    use HungerGames\{Player\Configurator, Arena\Arena, Player\Player as mainPlayer};

    /**
     * Events Class
     *
     * @author Denzel Code
     */
    class Events implements Listener {
        
        private static $main;
        
        private $data = [];
        
        public function __construct(Main $main) {
            self::$main = $main;
        }
        
        public static function getMain() : Main {
            return self::$main;
        }
        
        private function getData(string $name) {
            return $this->data[$name];
        }
        
        public function onInteract(PlayerInteractEvent $event) {
            $player = $event->getPlayer();
            
            $block = $event->getBlock();
            
            $tile = $player->getLevel()->getTile($block);
            
            $configurator = self::getMain()->getConfigurator($player->getName());
            
            if ($configurator instanceof Configurator && !$configurator->getRegister()) {
                if ($tile instanceof Sign) {
                    $arena = self::getMain()->getArena($configurator->getArenaName());
                    
                    $tile->setText(
                        Color::BOLD . self::getMain()->getGameName(),
                        $arena->getStatusColor(),
                        Color::UNDERLINE . $arena->getName(),
                        Color::GRAY . count($arena->getPocketPlayers()) . "/" . $arena->getMaxPlayers()
                    );
                    
                    self::getMain()->removeConfigurator($configurator->getName());
                    
                    $player->sendMessage(Color::GREEN . "Sign registered.");
                } else {
                    $player->sendMessage(Color::RED . "It's not a sign.");
                }
                
                return;
            } else if ($configurator instanceof Configurator && $configurator->getRegister()) {
                if (!$configurator->getRoomConfigured()) {
                    $configurator->setArenaData('room', [
                        'level' => $block->getLevel()->getFolderName(),
                        'position' => [
                            'X' => $block->getX(),
                            'Y' => $block->getY(),
                            'Z' => $block->getZ()
                        ]
                    ]);
                    
                    $configurator->setRoomConfigured(true);
                    
                    $configurator->teleportToLevel();
                    
                    $player->sendMessage(Color::GREEN . "Room registered, touch the spawn points.");
                } else if ($configurator->getSlot() <= $configurator->getMaxSlots()) {
                    $this->data[$player->getName()][$configurator->getArenaName()]['spawns'][$configurator->getSlot()] = [
                        'X' => $block->getX(),
                        'Y' => $block->getY(),
                        'Z' => $block->getZ()
                    ];
                    
                    $player->sendMessage(Color::GREEN . "Spawn point {$configurator->getSlot()} registered.");
                    
                    if ($configurator->getSlot() == $configurator->getMaxSlots()) {
                        $configurator->setArenaData('spawns', $this->data[$player->getName()][$configurator->getArenaName()]['spawns']);
                        
                        $configurator->teleportToLastPosition();
                    }
                    
                    $configurator->increaseSlot();

                    if ($configurator->getSlot() > $configurator->getMaxSlots()) {
                         $data = [
                            'name' => $configurator->getArenaName(),
                            'level' => $configurator->getArenaName(),
                            'status' => 'Waiting',
                            'defaultTime' => $configurator->getDefaultTime(),
                            'defaultEndTime' => $configurator->getDefaultEndTime(),
                            'defaultResetTime' => $configurator->getDefaultResetTime(),
                            'defaultInvincibleTime' => $configurator->getDefaultInvincibleTime(),
                            'room' => $configurator->getArenaData('room'),
                            'spawns' => $configurator->getArenaData('spawns'),
                            'maxSlots' => $configurator->getMaxSlots()
                        ];
                        
                        self::getMain()->addArena($configurator->getArenaName(), $data)->backup();
                        
                        self::getMain()->removeConfigurator($configurator->getName());
                        
                        $player->sendMessage(Color::GREEN . "Arena registered.");
                    }
                }
                
                $event->setCancelled();
                
                return;
            }
            
            if ($tile instanceof Sign) {
                $text = $tile->getText();
                
                if ($text[0] != Color::BOLD . self::getMain()->getGameName()) return;
                
                $arena = str_replace(Color::UNDERLINE, "", $text[2]);
                
                $arena = self::getMain()->getArena($arena);
                
                if ($arena instanceof Arena) {
                    if ($arena->getAllowJoin() || $player->hasPermission(self::getMain()->getPermission() . 'join') && $arena->getStatus() == Arena::IN_GAME || $player->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::IN_GAME) {
                        $oldPlayer = $player;
                        
                        $player = self::getMain()->addPlayer($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);
                        
                        $player->setLastPosition();
                        
                        if ($oldPlayer->hasPermission(self::getMain()->getPermission() . 'join') && $arena->getStatus() == Arena::IN_GAME || $oldPlayer->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::IN_GAME) {
                            $player->setBattleValues();

                            $player->teleportToSlot();
                        } else {
                            $player->joinArena();
                        }
                    } else if ($arena->getAllowSpectate()) {
                        $spectator = self::getMain()->addSpectator($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);

                        $spectator->setLastPosition();

                        $spectator->setValues();

                        $spectator->teleportToSlot();
                    } else if ($player->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::EDITING || $player->hasPermission(self::getMain()->getPermission() . 'edit') && $arena->getStatus() == Arena::EDITING) {
                        $player = self::getMain()->addPlayer($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);

                        $player->setLastPosition();
                        
                        $player->setEditingValues();
                        
                        $player->teleportToSlot();
                        
                        if ($player->playerOnline()) $player->getInstance()->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/" . self::getMain()->getCommander() . " leave" . Color::GOLD . " to exit.");
                        
                        $players = count($arena->getPocketPlayers());
                        
                        foreach ($arena->getPocketEveryone() as $playerArena) {
                            $playerArena->sendMessage(Color::DARK_PURPLE . "{$player->getName()} joined the edition. {$players}/{$players}" . Color::RESET);
                        }
                    } else {
                        $player->sendMessage(Color::RED . "Can't join this arena.");
                    }
                }
                
                $event->setCancelled();
                
                return;
            }
            
            if ($tile instanceof Sign) {
                $text = $tile->getText();
                
                if ($text[0] != Color::BOLD . self::getMain()->getGameName()) return;
                
                $arena = str_replace(Color::UNDERLINE, "", $text[2]);
                
                $arena = self::getMain()->getArena($arena);
                
                if ($arena instanceof Arena) {
                    if ($arena->getAllowJoin() || $player->hasPermission(self::getMain()->getPermission() . 'join') && $arena->getStatus() == Arena::IN_GAME || $player->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::IN_GAME) {
                        $oldPlayer = $player;
                        
                        $player = self::getMain()->addPlayer($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);
                        
                        $player->setLastPosition();
                        
                        if ($oldPlayer->hasPermission(self::getMain()->getPermission() . 'join') && $arena->getStatus() == Arena::IN_GAME || $oldPlayer->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::IN_GAME) {
                            $player->setBattleValues();

                            $player->teleportToSlot();
                        } else {
                            $player->joinArena();
                        }
                    } else if ($arena->getAllowSpectate()) {
                        $spectator = self::getMain()->addSpectator($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);

                        $spectator->setLastPosition();

                        $spectator->setValues();

                        $spectator->teleportToSlot();
                    } else if ($player->hasPermission(self::getMain()->getPermission() . 'admin') && $arena->getStatus() == Arena::EDITING || $player->hasPermission(self::getMain()->getPermission() . 'edit') && $arena->getStatus() == Arena::EDITING) {
                        $player = self::getMain()->addPlayer($player->getName(), [
                            'name' => $player->getName(),
                            'arena' => $arena->getName(),
                            'room' => $arena->getRoomName(),
                            'slot' => $arena->getFreeSlot(),
                            'lastPosition' => []
                        ]);

                        $player->setLastPosition();
                        
                        $player->setEditingValues();
                        
                        $player->teleportToSlot();
                        
                        if ($player->playerOnline()) $player->getInstance()->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/" . self::getMain()->getCommander() . " leave" . Color::GOLD . " to exit.");
                        
                        $players = count($arena->getPocketPlayers());
                        
                        foreach ($arena->getPocketEveryone() as $playerArena) {
                            $playerArena->sendMessage(Color::DARK_PURPLE . "{$player->getName()} joined the edition. {$players}/{$players}" . Color::RESET);
                        }
                    } else {
                        $player->sendMessage(Color::RED . "Can't join this arena.");
                    }
                }
                
                $event->setCancelled();
                
                return;
            }
            
            if (!self::getMain()->playerExist($player->getName())) return;
            
            $mainPlayer = self::getMain()->getPlayer($player->getName());

            $mainPlayer instanceof mainPlayer;

            $arena = $mainPlayer->getArena();

            if ($arena->getTime() || $arena->getStatus() == Arena::RESETING || !$arena->getEndTime() && $arena->getResetTime()) {
                $event->setCancelled();

                return;
            }
        }
        
        public function onChat(\pocketmine\event\player\PlayerChatEvent $event) {
            $player = $event->getPlayer();
            
            $message = $event->getMessage();
            
            if (self::getMain()->spectatorExist($player->getName())) {
                $spectator = self::getMain()->getSpectator($player->getName());
                
                foreach ($spectator->getArena()->getPocketSpectators() as $spectatorArena) {
                    $spectatorArena->sendMessage(Color::GRAY . "[" . Color::BLUE . "SPECTATOR" . Color::GRAY . "] " . Color::BLUE . $player->getName() . Color::WHITE . " > " . Color::GRAY . $message);
                }
                
                $event->setCancelled(true);
            } else if (self::getMain()->playerExist($player->getName())) {
                $playerArena = self::getMain()->getPlayer($player->getName());
                
                foreach ($playerArena->getArena()->getPocketPlayers() as $playersArena) {
                    $playersArena->sendMessage(Color::GRAY . "[" . Color::YELLOW . "GAME" . Color::GRAY . "] " . Color::YELLOW . $player->getName() . Color::WHITE . " > " . Color::GRAY . $message);
                }
                
                $event->setCancelled(true);
            }
        }
        
        public function onBlockBreak(BlockBreakEvent $event) {
            $player = $event->getPlayer();
            
            if (self::getMain()->configuratorExist($player->getName())) {
                $configurator = self::getMain()->getConfigurator($player->getName());
                
                if ($configurator->getSlot() < $configurator->getMaxSlots()) $event->setCancelled();
            }
            
            if (self::getMain()->playerExist($player->getName())) {
                $mainPlayer = self::getMain()->getPlayer($player->getName());
                
                $arena = $mainPlayer->getArena();

                if ($arena->getStatus() == Arena::EDITING) {
                    $event->setCancelled(false);

                    return;
                }

                if (!$arena->getTime() || $arena->getTime() > 0 || !$arena->getEndTime() && $arena->getResetTime()) $event->setCancelled();
            }
        }
        
        public function onBlockPlace(BlockPlaceEvent $event) {
            $player = $event->getPlayer();
            
            if (self::getMain()->configuratorExist($player->getName())) {
                $configurator = self::getMain()->getConfigurator($player->getName());
                
                if ($configurator->getSlot() < $configurator->getMaxSlots()) $event->setCancelled();
            }
            
            if (self::getMain()->playerExist($player->getName())) {
                $mainPlayer = self::getMain()->getPlayer($player->getName());
                
                $arena = $mainPlayer->getArena();

                if ($arena->getStatus() == Arena::EDITING) {
                    $event->setCancelled(false);

                    return;
                }

                if (!$arena->getTime() || $arena->getTime() > 0 || !$arena->getEndTime() && $arena->getResetTime()) $event->setCancelled();
            }
        }
        
        public function onEntityDamage(EntityDamageEvent $event) {
            $player = $event->getEntity();
            
            if (!$player instanceof Player) return;
            
            if (!self::getMain()->playerExist($player->getName())) return;
            
            $mainPlayer = self::getMain()->getPlayer($player->getName());
            
            $arena = $mainPlayer->getArena();
            
            $pocketEveryone = $arena->getPocketEveryone();
            
            $pocketPlayers = $arena->getPocketPlayers();
            
            $tops = self::getMain()->getPlugin('Tops');
            
            $economy = self::getMain()->getPlugin('EconomyAPI');
            
            $mainConfig = self::getMain()->getConfiguration('config');
            
            if ($arena->getTime() || $arena->getStatus() == Arena::RESETING || !$arena->getEndTime() && $arena->getResetTime() || !$arena->getVencible()) {
                $event->setCancelled();
                
                return;
            }
            
            if (!$arena->getTime()) {
                $killSound = $mainConfig->get('killSound');

                $killSound = "pocketmine\\level\\sound\\{$killSound}";
                
                $damage = $event->getFinalDamage() >= $player->getHealth();
                
                switch ($event->getCause()) {
                    case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                        // Killer
                        $killer = $event->getDamager();
                        
                        if (!self::getMain()->playerExist($player->getName())) {
                            $event->setCancelled();
                            
                            return;
                        } else if (!self::getMain()->playerExist($killer->getName())) {
                            $event->setCancelled();
                            
                            return;
                        }
                        
                        if ($damage) {
                            if ($killer instanceof Player && $arena->playerExist($killer->getName())) {
                                foreach ($pocketEveryone as $playerArena) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was slain by " . Color::YELLOW . "{$killer->getName()}" . Color::RESET . ".");
                                }
                                
                                // Killer
                                $economy->addMoney($killer->getName(), $mainConfig->get('killMoney'));
                                
                                $killer->setHealth(20);
                                
                                $killer->setFood(20);
                                
                                $tops->increasePlayerKills($killer->getName());
                                
                                $killer->getLevel()->addSound(new $killSound($killer), [$killer]);
                                
                                $killer->addTitle(Color::GOLD . "YOU KILLED", Color::GOLD . "+{$mainConfig->get('killMoney')} coins.");
                            
                                $killer->sendMessage(Color::GOLD . "YOU KILLED! +{$mainConfig->get('killMoney')} coins.");
                            }
                        }

                        break;
                        
                    case EntityDamageEvent::CAUSE_VOID:
                        if ($damage) {
                            if ($event instanceof EntityDamageByEntityEvent) {
                                // Killer
                                $killer = $event->getDamager();

                                if ($killer instanceof Player && $arena->playerExist($killer->getName())) {
                                    foreach ($pocketEveryone as $playerArena) {
                                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was thrown into the void by " . Color::YELLOW . "{$killer->getName()}" . Color::RESET . ".");
                                    }

                                    // Killer
                                    $economy->addMoney($killer->getName(), $mainConfig->get('killMoney'));

                                    $killer->setHealth(20);

                                    $killer->setFood(20);

                                    $tops->increasePlayerKills($killer->getName());

                                    $killer->getLevel()->addSound(new $killSound($killer), [$killer]);

                                    $killer->addTitle(Color::GOLD . "YOU KILLED", Color::GOLD . "+{$mainConfig->get('killMoney')} coins.");

                                    $killer->sendMessage(Color::GOLD . "YOU KILLED! +{$mainConfig->get('killMoney')} coins.");
                                } else {
                                    foreach ($pocketEveryone as $playerArena) {
                                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " fell into the void" . Color::RESET . ".");
                                    }
                                }
                            } else {
                                foreach ($pocketEveryone as $playerArena) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " fell into the void" . Color::RESET . ".");
                                }
                            }
                        }

                        break;
                        
                    case EntityDamageEvent::CAUSE_PROJECTILE:
                        if ($damage) {
                            if ($event instanceof EntityDamageByEntityEvent) {
                                // Killer
                                $killer = $event->getDamager();

                                if ($killer instanceof Player && $arena->playerExist($killer->getName())) {
                                    foreach ($pocketEveryone as $playerArena) {
                                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was shot by " . Color::YELLOW . "{$killer->getName()}" . Color::RESET . ".");
                                    }

                                    // Killer
                                    $economy->addMoney($killer->getName(), $mainConfig->get('killMoney'));

                                    $killer->setHealth(20);

                                    $killer->setFood(20);

                                    $tops->increasePlayerKills($killer->getName());

                                    $killer->getLevel()->addSound(new $killSound($killer), [$killer]);

                                    $killer->addTitle(Color::GOLD . "YOU KILLED", Color::GOLD . "+{$mainConfig->get('killMoney')} coins.");

                                    $killer->sendMessage(Color::GOLD . "YOU KILLED! +{$mainConfig->get('killMoney')} coins.");
                                } else {
                                    foreach ($pocketEveryone as $playerArena) {
                                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was shot by arrow" . Color::RESET . ".");
                                    }
                                }
                            } else {
                                foreach ($pocketEveryone as $playerArena) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was shot by arrow" . Color::RESET . ".");
                                }
                            }
                        }

                        break;
                        
                    case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                        if ($damage) {
                            // Killer
                            $killer = $event->getDamager();

                            if ($killer instanceof Player && $arena->playerExist($killer->getName())) {
                                foreach ($pocketEveryone as $playerArena) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " was exploded by " . Color::YELLOW . "{$killer->getName()}" . Color::RESET . ".");
                                }

                                // Killer
                                $economy->addMoney($killer->getName(), $mainConfig->get('killMoney'));

                                $killer->setHealth(20);

                                $killer->setFood(20);

                                $tops->increasePlayerKills($killer->getName());

                                $killer->getLevel()->addSound(new $killSound($killer), [$killer]);

                                $killer->addTitle(Color::GOLD . "YOU KILLED", Color::GOLD . "+{$mainConfig->get('killMoney')} coins.");

                                $killer->sendMessage(Color::GOLD . "YOU KILLED! +{$mainConfig->get('killMoney')} coins.");
                            } else {
                                foreach ($pocketEveryone as $playerArena) {
                                    $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " died exploded." . Color::RESET . ".");
                                }
                            }
                        }

                        break;

                    default:
                        if ($damage) {
                            foreach ($pocketEveryone as $playerArena) {
                                $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " died on the arena" . Color::RESET . ".");
                            }
                        }
                        
                        break;
                }
                
                if ($damage) {
                    $event->setCancelled();
                    
                    $mainPlayer->remove();
                    
                    $sound = $mainConfig->get('deathSound');

                    $sound = "pocketmine\\level\\sound\\{$sound}";
                    
                    $player->sendMessage(Color::RED . "YOU DEATH! -{$mainConfig->get('deathMoney')} coins.");

                    $player->getLevel()->addSound(new $sound($player), [$player]);
                    
                    $player->addTitle(Color::RED . "YOU DEATH", Color::RED . "-{$mainConfig->get('deathMoney')} coins.");
                    
                    foreach ($pocketEveryone as $playerArena) {
                        $playersCount = (count($pocketPlayers) - 1);
                        
                        if ($playersCount > 1) {
                            $playerArena->sendMessage(Color::RED . $playersCount . " players remain alive.");
                        }
                    }
                }
            }
        }
        
        public function onEntityCombust(EntityCombustEvent $event) {
            $player = $event->getEntity();
            
            if (!$player instanceof Player) return;
            
            if (!self::getMain()->playerExist($player->getName())) return;
            
            $mainPlayer = self::getMain()->getPlayer($player->getName());
            
            $mainPlayer instanceof mainPlayer;
            
            $arena = $mainPlayer->getArena();
            
            $pocketEveryone = $arena->getPocketEveryone();
            
            if ($arena->getTime() || $arena->getStatus() == Arena::RESETING || !$arena->getEndTime() && $arena->getResetTime() || !$arena->getVencible()) {
                $event->setCancelled();
                
                return;
            }
        }
        
        public function onMove(PlayerMoveEvent $event) {
            $player = $event->getPlayer();
            
            if (self::getMain()->configuratorExist($player->getName())) {
                $configurator = self::getMain()->getConfigurator($player->getName());
                
                if ($event->getTo()->getFloorY() < 1) {
                    if (!$configurator->getRegister()) return;

                    if ($configurator->getRoomConfigured()) {
                        $configurator->teleportToLevel();
                    } else {
                        $configurator->teleportToRoom();
                    }
                }

                return;
            }
            
            if (self::getMain()->spectatorExist($player->getName()) && $event->getTo()->getFloorY() < 1) {
                $spectator = self::getMain()->getSpectator($player->getName());
                
                $spectator->teleportToSlot();
                
                return;
            }
            
            if (!self::getMain()->playerExist($player->getName())) return;
            
            $mainPlayer = self::getMain()->getPlayer($player->getName());
            
            $mainPlayer instanceof mainPlayer;
            
            $arena = $mainPlayer->getArena();
            
            $pocketEveryone = $arena->getPocketEveryone();
            
            $pocketPlayers = $arena->getPocketPlayers();
            
            $tops = self::getMain()->getPlugin('Tops');
            
            $economy = self::getMain()->getPlugin('EconomyAPI');
            
            $mainConfig = self::getMain()->getConfiguration('config');
            
            if ($arena->getTime() && $arena->getTime() <= 10) {
                $to = clone $event->getFrom();
                
                $to->yaw = $event->getTo()->yaw;
                
                $to->pitch = $event->getTo()->pitch;
                
                $event->setTo($to);
                
                return;
            }
            
            if ($event->getTo()->getFloorY() < 1) {
                if ($arena->getStatus() == Arena::RESETING || $arena->getStatus() == Arena::EDITING || count($arena->getPocketPlayers()) == 1 && $arena->getStatus() == Arena::IN_GAME) {
                    $mainPlayer->teleportToSlot();
                    
                    return;
                }
                
                if ($arena->getTime() && $arena->getTime() >= 10) {
                    $mainPlayer->teleportToRoom();
                    
                    return;
                }
                
                if (!$arena->getVencible()) {
                    $mainPlayer->teleportToSlot();
                } else {
                    /*
                    $tops->increasePlayerDeaths($player->getName());
                    
                    $mainPlayer->remove();
                    
                    $sound = $mainConfig->get('deathSound');

                    $sound = "pocketmine\\level\\sound\\{$sound}";
                    
                    $player->sendMessage(Color::RED . "YOU DEATH! -{$mainConfig->get('deathMoney')} coins.");

                    $player->getLevel()->addSound(new $sound($player), [$player]);
                    
                    foreach ($pocketEveryone as $playerArena) {
                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " fell into the void" . Color::RESET . ".");
                    }

                    foreach ($pocketEveryone as $playerArena) {
                        $playersCount = (count($pocketPlayers) - 1);
                        
                        if ($playersCount > 1) {
                            $playerArena->sendMessage(Color::RED . $playersCount . " players remain alive.");
                        }
                    }
                    
                    $player->addTitle(Color::RED . "YOU DEATH", Color::RED . "-{$mainConfig->get('deathMoney')} coins.");
                    */
                }
            }
        }
        
        public function onLogin(PlayerLoginEvent $event) {
            $player = $event->getPlayer();
            
            // Player
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->setGamemode(Player::SURVIVAL);
            $player->getInventory()->clearAll();
            $player->setXpLevel(0);
            $player->removeAllEffects();
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
            
            // Teleport
            /*$world = $this->getMain()->getServer()->getDefaultLevel();
            
            $spawn = $world->getSafeSpawn();
            
            if (!$world->isChunkLoaded($spawn->getFloorY(), $spawn->getFloorZ())) {
                $world->loadChunk($spawn->getFloorY(), $spawn->getFloorZ());
            }
            
            $player->teleport($spawn);
             * 
             */
        }
        
        public function onQuit(PlayerQuitEvent $event) {
            $player = $event->getPlayer();
            
            if (self::getMain()->playerExist($player->getName())) {
                $player = self::getMain()->getPlayer($player->getName());
                
                $mainConfig = self::getMain()->getConfiguration('config');
            
                $sound = $mainConfig->get('leaveSound');

                $sound = "pocketmine\\level\\sound\\{$sound}";
                
                $players = (count($player->getArena()->getPocketPlayers()) - 1);
                
                foreach ($player->getArena()->getPocketEveryone() as $playerArena) {
                    if ($players > 0 && $player->getArena()->getStatus() == Arena::WAITING) break; 
                    
                    $playerArena->getLevel()->addSound(new $sound($playerArena), [$playerArena]);
                    
                    if ($player->getArena()->getStatus() == Arena::EDITING) break;
                    
                    if ($player->getArena()->getStatus() != Arena::IN_GAME) {
                        $playerArena->sendMessage(Color::RED . "{$player->getName()} left the game. {$players}/{$player->getArena()->getMaxPlayers()}" . Color::RESET);
                    } else {
                        $playerArena->sendMessage(Color::YELLOW . "{$player->getName()}" . Color::GOLD . " left the game" . Color::RESET . ".");
                        
                        if ($players > 1) {
                            $playerArena->sendMessage(Color::RED . $players . " players remain alive.");
                        }
                    }
                }
                
                self::getMain()->removePlayer($player->getName());
            }
            
            if (self::getMain()->spectatorExist($player->getName())) self::getMain()->removeSpectator($player->getName());
            
            if (self::getMain()->configuratorExist($player->getName())) self::getMain()->removeConfigurator($player->getName());
        }
    }

?>
