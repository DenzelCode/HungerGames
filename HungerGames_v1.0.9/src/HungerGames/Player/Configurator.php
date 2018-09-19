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
    
    use HungerGames\{Main, Player\Base\Player as PlayerBase};
    
    use pocketmine\Player as pocketminePlayer;
    use pocketmine\utils\TextFormat as Color;
    use pocketmine\level\{Level, Position};

    /**
     * Player Base Class
     *
     * @author Denzel Code
     */
    class Configurator extends PlayerBase {
        
        private static $main;
        
        private $data;
        
        public function __construct(Main $main, array $data) {
            self::$main = $main;
            
            $this->data = $data;
        }
        
        public static function getMain() : Main {
            return self::$main;
        }
        
        public function run() : bool {
            $this->data['slot'] = 1;
            
            if (empty($this->data['sign'])) {
                $this->data['register'] = true;
                
                $this->data['roomConfigured'] = false;

                if (!self::getMain()->getServer()->isLevelLoaded($this->getRoomName())) {
                    self::getMain()->getServer()->loadLevel($this->getRoomName());
                }

                if (!self::getMain()->getServer()->isLevelLoaded($this->getArenaName())) {
                    self::getMain()->getServer()->loadLevel($this->getArenaName());
                }
                
                if (!$this->getLevel() instanceof Level) {
                    $this->getInstance()->sendMessage(Color::RED . "Error getting the arena level.");
                    
                    self::getMain()->removeConfigurator($this->getName());

                    return false;
                }
                
                if (!$this->getRoomLevel() instanceof Level) {
                    $this->getInstance()->sendMessage(Color::RED . "Error getting the room level.");
                    
                    self::getMain()->removeConfigurator($this->getName());

                    return false;
                }

                $this->setLastPosition();

                $this->setValues();

                $this->teleportToRoom();
            } else {
                $this->data['register'] = false;
                
                $this->data['roomConfigured'] = true;
            }
            
            return true;
        }
        
        public function getData() : array {
            return $this->data;
        }
        
        public function getName() : string {
            return $this->data['name'];
        }
        
        public function getInstance() {
            return self::getMain()->getServer()->getPlayer($this->getName());
        }
        
        public function getArenaName() : string {
            return $this->data['arena'];
        }
        
        public function getRoomName() : string {
            return $this->data['room'];
        }
        
        public function getLevel() {
            return self::getMain()->getServer()->getLevelByName($this->getArenaName());
        }
        
        public function getRoomLevel() {
            return self::getMain()->getServer()->getLevelByName($this->getRoomName());
        }
        
        public function setLastPosition() {
            $this->data['lastPosition'] = $this->getInstance()->getPosition();
        }
        
        public function getLastPosition() : Position {
            return $this->data['lastPosition'];
        }
        
        public function getSlot() : int {
            return $this->data['slot'];
        }
        
        public function getMaxSlots() : int {
            return $this->data['maxSlots'];
        }
        
        public function setCurrentSlot(int $slot) {
            $this->data['slot'] = $slot;
        }
        
        public function increaseSlot() {
            $this->data['slot']++;
        }
        
        public function getSign() : bool {
            return $this->data['sign'];
        }
        
        public function getRoomConfigured() : bool {
            return $this->data['roomConfigured'];
        }
        
        public function setRoomConfigured(bool $data) {
            $this->data['roomConfigured'] = $data;
        }
        
        public function setArenaData(string $name, $value) {
            $this->data['arenaData'][$name] = $value;
        }
        
        public function getArenaData(string $data) {
            return $this->data['arenaData'][$data];
        }
        
        public function getDefaultTime() : int {
            return $this->data['defaultTime'];
        }
        
        public function getDefaultEndTime() : int {
            return $this->data['defaultEndTime'];
        }
        
        public function getDefaultResetTime() : int {
            return $this->data['defaultResetTime'];
        }
        
        public function setDefaultInvincibleTime(int $time) {
            $this->data['defaultInvincibleTime'] = $time;
        }
        
        public function getDefaultInvincibleTime() : int {
            return $this->data['defaultInvincibleTime'];
        }
        
        public function getRegister() : bool {
            return $this->data['register'];
        }

        public function setValues() {
            $player = $this->getInstance();

            $player->setGamemode(pocketminePlayer::CREATIVE);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
        }

        public function teleportToRoom() {
            $level = $this->getRoomLevel();
            
            $safeSpawn = $level->getSafeSpawn();
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
        
        public function teleportToLevel() {
            $level = $this->getLevel();
            
            $safeSpawn = $level->getSafeSpawn();
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->setGamemode(pocketminePlayer::CREATIVE);
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
        
        public function teleportToLastPosition() {
            $level = $this->getLastPosition()->getLevel();
            
            $safeSpawn = $this->getLastPosition();
            
            if (!$level->isChunkLoaded($safeSpawn->getFloorX(), $safeSpawn->getFloorZ())) {
                $level->loadChunk($safeSpawn->getFloorX(), $safeSpawn->getFloorZ());
            }
            
            $this->getInstance()->setGamemode(pocketminePlayer::CREATIVE);
            
            $this->getInstance()->teleport($safeSpawn, 0, 0);
        }
    }

?>
