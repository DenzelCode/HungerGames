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
    use pocketmine\utils\TextFormat as Color;
    use pocketmine\block\{Glass, Air};

    /**
     * Player Base Class
     *
     * @author Denzel Code
     */
    class Player extends PlayerBase {
        
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
        
        public function joinArena() {
            if (!$this->playerOnline()) return;
            
            if ($this->getArena()->getTime() <= 10) {
                $this->setArenaValues();
            } else {
                $this->setRoomValues();
            }
            
            $players = count($this->getArena()->getPocketPlayers());
            
            $mainConfig = self::getMain()->getConfiguration('config');
            
            $sound = $mainConfig->get('joinSound');

            $sound = "pocketmine\\level\\sound\\{$sound}";
            
            foreach ($this->getArena()->getPocketEveryone() as $player) {
                $player->getLevel()->addSound(new $sound($player), [$player]);
                
                $player->sendMessage(Color::DARK_PURPLE . "{$this->getName()} joined the game. {$players}/{$this->getArena()->getMaxPlayers()}" . Color::RESET);
            }
            
            $command = self::getMain()->getCommander();
            $player->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/{$command} leave" . Color::GOLD . " to exit.");
            $player->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/{$command} players" . Color::GOLD . " to see arena players.");
            $player->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/kits select <kit>" . Color::GOLD . " to buy/select your kit.");
            $player->sendMessage(Color::GOLD . "Execute " . Color::YELLOW . "/invite <player>" . Color::GOLD . " to invite an online friend to the game.");
        }
        
        public function setDefaultValues(bool $teleport = true) {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            if ($teleport) {
                $this->teleportToLastPosition();
            }
            
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
        
        public function setRoomValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            $this->teleportToRoom();
            $player->setGamemode(pocketminePlayer::SURVIVAL);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            self::getMain()->getPlugin('JoinItems')->removeItems($player, ['group' => 'join']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Leave']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Players']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => strtolower(self::getMain()->getGameName())]);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function setArenaValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            $this->teleportToSlot();
            $player->setGamemode(pocketminePlayer::SURVIVAL);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            self::getMain()->getPlugin('JoinItems')->removeItems($player, ['group' => 'join']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Leave']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Players']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => strtolower(self::getMain()->getGameName())]);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function setEditingValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            $this->teleportToSlot();
            $player->setGamemode(pocketminePlayer::CREATIVE);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            $player->setXpLevel(0);
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            self::getMain()->getPlugin('JoinItems')->removeItems($player, ['group' => 'join']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Leave']);
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Players']);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function setResetValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            $player->setGamemode(pocketminePlayer::SURVIVAL);
            $player->setMaxHealth(20);
            $player->setHealth(20);
            $player->setFood(20);
            $player->setXpLevel(0);
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Leave']);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
        }
        
        public function setBattleValues() {
            if (!$this->playerOnline()) return;
            
            $player = $this->getInstance();
            
            // Player
            $player->setGamemode(pocketminePlayer::SURVIVAL);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setFood(20);
            if ($player->getCurrentWindow() instanceof Inventory) $player->getCurrentWindow()->onClose();
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            
            foreach ($player->getInventory()->getContents() as $item) {
                if ($item->isPlaceable()) $player->getInventory()->removeItem($item);
            }
            
            self::getMain()->getPlugin('JoinItems')->setItems($player, ['group' => self::getMain()->getCommander() . 'Near']);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->sendContents($player);
            $player->getInventory()->sendHeldItem($player);
            
            $level = $this->getArena()->getLevel();
            
            $downPosition = $player->add(0, -1, 0);

            if ($level->getBlock($downPosition)->getId() == 20 || $level->getBlock($downPosition)->getId() == 241) {
                $level->setBlock($downPosition, \pocketmine\block\Block::get(\pocketmine\block\Block::AIR));
            }
        }

        public function getSteps(Position $position) : array {
            if (!$this->playerOnline()) return [];

            $player = $this->getInstance();

            $playerPosition = $player->getPosition();

            $playerZ = round($playerPosition->getZ());

            $playerY = round($playerPosition->getY());

            $positionX = round($position->getX());

            $playerX = round($playerPosition->getX());

            $positionZ = round($position->getZ());

            $positionY = round($position->getY());

            $steps = $playerX - $positionX;

            $steps = abs($steps + $playerZ - $positionZ);

            $up = round($positionY - $playerY);

            return [
                'steps' => $steps,
                'up' => $up
            ];
        }

        public function getNearlestPlayer() : Player {
            $players = [];

            foreach ($this->getArena()->getPlayers() as $player) {
                if ($player->getName() == $this->getName()) continue;

                $players[$this->getSteps($player->getInstance()->getPosition())['steps']] = $player;
            }

            krsort($players);

            return end($players);
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
        
        public function decreaseSlot() {
            $this->data['slot']--;
        }
        
        public function increaseSlot() {
            $this->data['slot']--;
        }
        
        public function hasArena() : bool {
            if (isset($this->data['arena'])) return true;
            
            return false;
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
            if ($this->getArena()->getStatus() == Arena::IN_GAME && $tp || $this->getArena()->getStatus() == Arena::RESETING && $tp && count($this->getArena()->getPocketPlayers()) > 1) {
                $this->convertToSpectator();
            } else {
                $this->setDefaultValues($tp);
            }
            
            self::getMain()->removePlayer($this->getName());
        }
        
        public function convertToSpectator() {
            if (!$this->playerOnline()) return;
            
            $spectator = self::getMain()->addSpectator($this->getName(), [
                'name' => $this->getName(),
                'arena' => $this->getArena()->getName(),
                'slot' => $this->getSlot(),
                'room' => $this->getArena()->getRoomName(),
                'lastPosition' => []
            ]);
            
            $spectator->setLastPosition($this->data['lastPosition']);

            $spectator->setValues();

            $spectator->teleportToSlot();
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
