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

    namespace HungerGames\Player;
    
    use HungerGames\{Main, Player\Base\Player as PlayerBase, Arena\Arena};
    
    use pocketmine\Player as pocketminePlayer;
    use pocketmine\level\{Level, Position};

    /**
     * Player Base Class
     *
     * @author Denzel Code
     */
    class Spectator extends PlayerBase {
        
        private static $main;
        
        private $data;
        
        public function __construct(Main $main, array $data) {
            self::$main = $main;
            
            $this->data = $data;
        }
        
        public static function getMain() : Main {
            return self::$main;
        }
        
        public function getData() : array {
            return $this->data;
        }
        
        public function setData(array $data) {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }
        
        public function getName() : string {
            return $this->data['name'];
        }
        
        public function setDefaultValues(bool $teleport = true) {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            if ($teleport) $this->teleportToLastPosition();
            
            $player->setGamemode(pocketminePlayer::SURVIVAL);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->removeAllEffects();
            $player->setXpLevel(0);
            $player->getInventory()->clearAll();
            $position = $player->getLevel()->getSafeSpawn();
            $player->setCompassDestination($position->getX(), $position->getY(), $position->getZ());
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => 'join']);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function setValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            $player->setGamemode(pocketminePlayer::SPECTATOR);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->setXpLevel(0);
            $player->getInventory()->clearAll();
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Leave']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Players']);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function getInstance() {
            return self::getMain()->getServer()->getPlayer($this->getName());
        }
        
        public function playerOnline() : bool {
            return $this->getInstance() instanceof pocketminePlayer;
        }
        
        public function getArenaName() : string {
            return $this->data['arena'];
        }
        
        public function getArena() : Arena {
            return self::getMain()->getArena($this->getArenaName());
        }
        
        public function getLevel() : Level {
            return self::getMain()->getServer()->getLevelByName($this->getArenaName());
        }
        
        public function setLastPosition(array $position = []) {
            if (!$this->playerOnline()) return;
            
            if (empty($position) && $this->getInstance() instanceof pocketminePlayer) {
                $position = $this->getInstance()->getPosition();

                $this->data['lastPosition'] = [
                    'X' => $position->getX(),
                    'Y' => $position->getY(),
                    'Z' => $position->getZ(),
                    'level' => $position->getLevel()
                ];
            } else if (!empty($position)) {
                $this->data['lastPosition'] = $position;
            }
        }
        
        public function getLastPosition() : Position {
            $position = $this->data['lastPosition'];
            
            return new Position($position['X'], $position['Y'], $position['Z'], $position['level']);
        }
        
        public function getSlot() : int {
            return $this->data['slot'];
        }
        
        public function setSlot(int $slot) {
            $this->data['slot'] = $slot;
        }
        
        public function remove(bool $tp = true) {
            self::getMain()->removeSpectator($this->getName());

            $this->setDefaultValues($tp);
        }
        
        public function teleportToRoom() {
            if (!$this->playerOnline()) return;
            
            $level = $this->getArena()->getRoomLevel();
            
            $safeSpawn = $this->getArena()->getRoomPosition();
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
        
        public function teleportToSlot() {
            if (!$this->playerOnline()) return;
            
            $level = $this->getLevel();
            
            if (!$this->getSlot()) {
                $this->setSlot(1);
            }
            
            $safeSpawn = $this->getArena()->getSlotPosition($this->getSlot());
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
        
        public function teleportToLastPosition() {
            if (!$this->playerOnline()) return;
            
            if (isset($this->data['lastPosition'])) {
                $level = $this->getLastPosition()->getLevel();

                $safeSpawn = $this->getLastPosition();
            }
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
    }

?>
