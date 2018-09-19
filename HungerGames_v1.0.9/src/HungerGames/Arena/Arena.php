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

    namespace HungerGames\Arena;
    
    use HungerGames\Main;
    use pocketmine\level\Level;
    use pocketmine\Player as pocketminePlayer;
    use HungerGames\Player\Player;
    use pocketmine\level\Position;
    use pocketmine\tile\Sign;
    use pocketmine\item\Item;
    use pocketmine\item\enchantment\Enchantment;
    use pocketmine\utils\TextFormat as Color; 
    use pocketmine\block\Block;
    use pocketmine\tile\Chest;
    use pocketmine\inventory\ChestInventory;

    /**
     * Arena Class
     *
     * @author Denzel Code
     */
    class Arena {
        
        private static $main;
        
        private $data;
        
        const WAITING = 0,
              STARTING = 1,
              IN_GAME = 2,
              RESETING = 3,
              EDITING = 4,
              FULL = 5,
              UNDEFINED = 6;
        
        public function __construct(Main $main, array $data) {
            self::$main = $main;
            
            $this->data = $data;
            
            $this->resetData();
        }
        
        private static function getMain() : Main {
            return self::$main;
        }
        
        public function getData() : array {
            return $this->data;
        }
        
        public function getName() : string {
            return $this->data['name'];
        }
        
        public function setStatus(int $status) {
            $this->data['status'] = $status;
        }
        
        public function getStatus() : int {
            return $this->data['status'];
        }
        
        public function getTime() : int {
            return $this->data['time'];
        }
        
        public function setTime(int $time) {
            $this->data['time'] = $time;
        }
        
        public function getEndTime() : int {
            return $this->data['endTime'];
        }
        
        public function setEndTime(int $time) {
            $this->data['endTime'] = $time;
        }
        
        public function getResetTime() : int {
            return $this->data['resetTime'];
        }
        
        public function setResetTime(int $time) {
            $this->data['resetTime'] = $time;
        }
        
        public function getKitGived() : bool {
            return $this->data['kitGived'];
        }
        
        public function setKitGived(bool $value) {
            $this->data['kitGived'] = $value;
        }
        
        public function getInvincibleTime() : int {
            return $this->data['invincibleTime'];
        }
        
        public function setInvincibleTime(int $time) {
            $this->data['invincibleTime'] = $time;
        }
        
        public function setVencible(bool $value) {
            $this->data['vencible'] = $value;
        }
        
        public function getVencible() : bool {
            return $this->data['vencible'];
        }
        
        public function getDefaultTime() : int {
            return $this->data['defaultTime'];
        }
        
        public function setDefaultTime(int $time) {
            $this->data['defaultTime'] = $time;
        }
        
        public function getDefaultEndTime() : int {
            return $this->data['defaultEndTime'];
        }
        
        public function setDefaultEndTime(int $time) {
            $this->data['defaultEndTime'] = $time;
        }
        
        public function getDefaultResetTime() : int {
            return $this->data['defaultResetTime'];
        }
        
        public function setDefaultResetTime(int $time) {
            $this->data['defaultResetTime'] = $time;
        }
        
        public function setDefaultInvincibleTime(int $time) {
            $this->data['defaultInvincibleTime'] = $time;
        }
        
        public function getDefaultInvincibleTime() : int {
            return $this->data['defaultInvincibleTime'];
        }
        
        public function increaseInvincibleTime() {
            $this->data['invincibleTime']++;
        }
        
        public function decreaseInvincibleTime() {
            $this->data['invincibleTime']--;
        }
        
        public function increaseTime() {
            $this->data['time']++;
        }
        
        public function decreaseTime() {
            $this->data['time']--;
        }
        
        public function increaseEndTime() {
            $this->data['endTime']++;
        }
        
        public function decreaseEndTime() {
            $this->data['endTime']--;
        }
        
        public function increaseResetTime() {
            $this->data['resetTime']++;
        }
        
        public function decreaseResetTime() {
            $this->data['resetTime']--;
        }
        
        public function addChestVote(string $name, string $player) {
            $this->data['chestVotes'][$name][$player] = $player;
        }
        
        public function removeChestVote(string $name, string $player) {
            unset($this->data['chestVotes'][$name][$player]);
        }
        
        public function setChestVotes(string $name, array $votes) {
            $this->data['chestVotes'][$name] = $votes;
        }
        
        public function getChestVotes(string $name) {
            return $this->data['chestVotes'][$name];
        }
        
        public function getLevelName() {
            return $this->data['level'];
        }
        
        public function getLevel() {
            return self::getMain()->getServer()->getLevelByName($this->getLevelName());
        }
        
        public function getRoomName() : string {
            return $this->data['room']['level'];
        }
        
        public function setWinned(bool $value) {
            $this->data['winned'] = $value;
        }
        
        public function getWinned() : bool {
            return $this->data['winned'];
        }
        
        public function getRoomLevel() : Level {
            return self::getMain()->getServer()->getLevelByName($this->getRoomName());
        }
        
        public function getRoomPosition() : Position {
            $position = $this->getData()['room']['position'];
            
            return new Position($position['X'] + 0.5, $position['Y'] + 1, $position['Z'] + 0.5, $this->getRoomLevel());
        }
        
        public function getSlotPosition(int $slot) {
            if ($slot > $this->getMaxPlayers()) return false;
            
            $position = $this->getData()['spawns'][$slot];
            
            return new Position(round($position['X']) + 0.5, round($position['Y']) + 1, round($position['Z']) + 0.5, $this->getLevel());
        }
        
        public function getPlayers() : array {
            $players = [];
            
            foreach (self::getMain()->getPlayers() as $player) {
                if ($player->getArenaName() == $this->getName()) $players[$player->getName()] = $player;
            }
            
            return $players;
        }

        public function playerExist(string $name) : bool {
            $name = strtolower($name);

            if (!self::getMain()->playerExist($name)) return false;

            $player = self::getMain()->getPlayer($name);

            if ($player->getArena()->getName() != $this->getName()) return false;

            return true;
        }
        
        public function getPocketPlayers() : array {
            $players = [];
            
            foreach ($this->getPlayers() as $player) {
                if ($player->getInstance() instanceof pocketminePlayer) $players[$player->getName()] = $player->getInstance();
            }
            
            return $players;
        }
        
        public function getSpectators() : array {
            $players = [];
            
            foreach (self::getMain()->getSpectators() as $player) {
                if ($player->getArenaName() == $this->getName()) $players[$player->getName()] = $player;
            }
            
            return $players;
        }
        
        public function getPocketSpectators() : array {
            $players = [];
            
            foreach ($this->getSpectators() as $player) {
                if ($player->getInstance() instanceof pocketminePlayer) $players[$player->getName()] = $player->getInstance();
            }
            
            return $players;
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
        
        public function getPocketEveryone() : array {
            $players = [];
            
            foreach ($this->getPocketPlayers() as $player) {
                $players[$player->getName()] = $player;
            }
            
            foreach ($this->getPocketSpectators() as $player) {
                $players[$player->getName()] = $player;
            }
            
            return $players;
        }
        
        public function getAllowJoin() : bool {
            if ($this->getStatus() == self::RESETING || $this->getStatus() == self::EDITING || $this->getStatus() == self::UNDEFINED || $this->getTime() == 0) return false;
            
            if ($this->getMaxPlayers() <= count($this->getPlayers())) return false;
            
            return true;
        }
        
        public function getAllowSpectate() : bool {
            if ($this->getStatus() != self::IN_GAME) return false;
            
            return true;
        }
        
        public function getMaxPlayers() : int {
            return $this->data['maxSlots'];
        }
        
        public function getStarted() : bool {
            return !$this->getTime();
        }
        
        public function getFreeSlot() : int {
            if (empty($this->getPlayers())) return 1;
            
            if (count($this->getPlayers()) >= $this->getMaxPlayers()) return $this->getMaxPlayers();
            
            return (count($this->getPlayers()) + 1);
        }
        
        public function secondsToTime(int $seconds) {
            $hours = floor($seconds / 3600);
            $minutes = floor($seconds % 3600 / 60);
            $seconds = $seconds % 60;

            return [
                $hours, $minutes, $seconds
            ];
        }
        
        public function timeToString(array $data) : string {
            $time = "";
                                        
            if ($data[0]) {
                if ($data[0] < 10) {
                    $time .= "0" . $data[0] . ":";
                } else {
                    $time .= $data[0] . ":";
                }
            }

            if ($data[1] < 10) {
                $time .= "0" . $data[1] . ":";
            } else {
                $time .= $data[1] . ":";
            }

            if ($data[2] < 10) {
                $time .= "0" . $data[2];
            } else {
                $time .= $data[2];
            }
            
            return $time;
        }
        
        public function getStatusString() {
            if ($this->getStatus() == Arena::WAITING) {
                $status = "Waiting";
            } else if ($this->getStatus() == Arena::STARTING) {
                $status = "Starting";
            } else if ($this->getStatus() == Arena::IN_GAME) {
                $status = "In-Game";
            } else if ($this->getStatus() == Arena::RESETING) {
                $status = "Reseting";
            } else if ($this->getStatus() == Arena::EDITING) {
                $status = "Editing";
            } else if ($this->getStatus() == Arena::FULL) {
                $status = "Full";
            } else {
                $status = "Undefined";
            }
            
            return $status;
        }
        
        public function getStatusColor() {
            if ($this->getStatus() == Arena::WAITING) {
                $status = Color::GREEN;
            } else if ($this->getStatus() == Arena::STARTING) {
                $status = Color::GOLD;
            } else if ($this->getStatus() == Arena::IN_GAME) {
                $status = Color::RED;
            } else if ($this->getStatus() == Arena::RESETING) {
                $status = Color::DARK_AQUA;
            } else if ($this->getStatus() == Arena::EDITING) {
                $status = Color::DARK_PURPLE;
            } else if ($this->getStatus() == Arena::FULL) {
                $status = Color::LIGHT_PURPLE;
            } else {
                $status = Color::WHITE;
            }
            
            $status .= $this->getStatusString();
            
            return $status;
        }
        
        public function getStatusBlock() {
            if ($this->getStatus() == Arena::WAITING) {
                $block = Block::get('241', 5);
            } else if ($this->getStatus() == Arena::STARTING) {
                $block = Block::get('241', 1);
            } else if ($this->getStatus() == Arena::IN_GAME) {
                $block = Block::get('241', 14);
            } else if ($this->getStatus() == Arena::RESETING) {
                $block = Block::get('241', 3);
            } else if ($this->getStatus() == Arena::EDITING) {
                $block = Block::get('241', 10);
            } else if ($this->getStatus() == Arena::FULL) {
                $block = Block::get('241', 6);
            } else {
                $block = Block::get('241');
            }
            
            return $block;
        }
        
        public function getSigns() {
            $signs = [];
            
            foreach (self::getMain()->getServer()->getLevels() as $level) {
                foreach ($level->getTiles() as $tile) {
                    if ($tile instanceof Sign) {
                        $text = $tile->getText();
                        
                        if ($text[0] != Color::BOLD . "HungerGames") continue;
                        
                        $arena = str_replace(Color::UNDERLINE, "", $text[2]);

                        if ($arena == $this->getName()) $signs[] = $tile;
                    }
                } 
            }
            
            return $signs;
        }
        
        public function hasSings() {
            if (empty($this->getSigns())) return false;
            
            return true;
        }
        
        public function removeSigns() {
            foreach ($this->getSigns() as $sign) {
                $sign->setText();
            }
        }
        
        public function fillChests() {
            $tiles = $this->getLevel()->getTiles();
            
            $itemsConfig = self::getMain()->getConfiguration('items');

            $mainConfig = self::getMain()->getConfiguration('config');
            
            foreach ($tiles as $tile) {
                if ($tile instanceof Chest) {
                    if ($tile->getInventory() instanceof ChestInventory) {
                        $tile->getInventory()->clearAll();
                        
                        if (count($this->getChestVotes('op')) > count($this->getChestVotes('normal'))) {
                            $chestItems = $itemsConfig->getAll()['op'];
                        } else {
                            $chestItems = $itemsConfig->getAll()['normal'];
                        }

                        for ($i = $mainConfig->get('minItemsPerChest'); $i <= $mainConfig->get('maxItemsPerChest'); $i++) {
                            $rand = rand(0, 4);

                            if ($rand == 1) {
                                $key = array_rand($chestItems);

                                $value = $chestItems[$key];

                                $item = Item::get($value['id'], $value['meta'], $value['count']);
                                
                                $enchantments = $value['enchantments'];

                                if (isset($enchantments)) {
                                    foreach ($enchantments as $key => $value) {
                                        if (is_string($value['id'])) {
                                            $enchantment = Enchantment::getEnchantmentByName($value['id']);
                                        } else {
                                            $enchantment = Enchantment::getEnchantment($value['id']);
                                        }

                                        if ($enchantment instanceof Enchantment) {
                                            if (isset($value['level'])) $enchantment->setLevel($value['level']);

                                            $item->addEnchantment($enchantment);
                                        }
                                    }
                                }

                                $tile->getInventory()->setItem($i, $item);
                            }
                        }
                    }
                }
            }
        }
        
        public function initArena() {
            $this->resetData();
            
            $this->setBackup();
            
            if (!self::getMain()->getServer()->isLevelLoaded($this->getLevelName())) {
                self::getMain()->getServer()->loadLevel($this->getLevelName());
            }
            
            if (!self::getMain()->getServer()->isLevelLoaded($this->getRoomName())) {
                self::getMain()->getServer()->loadLevel($this->getRoomName());
            }
        }
        
        public function resetData() {
            $this->setKitGived(false);
            
            $this->setChestVotes('op', []);
            
            $this->setChestVotes('normal', []);
            
            $this->resetTime();
        }
        
        public function resetTime() {
            $this->setTime($this->getDefaultTime());
            
            $this->setEndTime($this->getDefaultEndTime());
            
            $this->setResetTime($this->getDefaultResetTime());
            
            $this->setInvincibleTime($this->getDefaultInvincibleTime());
            
            $this->setVencible(false);
            
            $this->setWinned(false);
            
            $this->setStatus(self::WAITING);
        }
        
        public function resetArena() {
            foreach ($this->getEveryone() as $player) $player->remove();

            $this->resetData();
            
            if (self::getMain()->getServer()->isLevelLoaded($this->getLevelName())) {
                self::getMain()->getServer()->unloadLevel($this->getLevel());
            }
            
            $this->setBackup();
            
            if (!self::getMain()->getServer()->isLevelLoaded($this->getLevelName())) {
                self::getMain()->getServer()->loadLevel($this->getLevelName());
            }
        }
        
        public function remove() {
            $this->removeSigns();
            
            $this->stopArena();
            
            unlink(self::getMain()->getDataFolder() . 'Backups/' . $this->getName() . '.zip');
            
            self::getMain()->removeArena($this->getName());
        }
        
        public function stopArena() {
            foreach ($this->getEveryone() as $player) $player->remove();
            
            $this->resetData();
            
            if (self::getMain()->getServer()->isLevelLoaded($this->getLevelName())) self::getMain()->getServer()->unloadLevel($this->getLevel());
            
            $this->setBackup();
            
            $this->setStatus(Arena::UNDEFINED);
        }
        
        public function backup() {
            $pathWorlds = realpath(self::getMain()->getServer()->getDataPath() . 'worlds/' . $this->getName());
            
            $zip = new \ZipArchive;
            
            $path = self::getMain()->getDataFolder() . 'Backups/';
            
            if (!file_exists($path)) @mkdir($path, 0755);
            
            $zip->open($path . $this->getName() . '.zip', $zip::CREATE | $zip::OVERWRITE);
            
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathWorlds), \RecursiveIteratorIterator::LEAVES_ONLY);
            
            foreach ($files as $data) {
                if (!$data->isDir()) {
                    $relativePath = $this->getName() . '/' . substr($data, strlen($pathWorlds) + 1);
                    
                    $zip->addFile($data, $relativePath);
                }
            }
            
            $zip->close();
        }
        
        public function setBackup() : bool {
            $extract = false;

            $path = self::getMain()->getDataFolder() . 'Backups/';
            
            if (!file_exists($path)) @mkdir($path, 0755);
            
            $backUp = $path . $this->getName() . '.zip';

            if (file_exists($backUp)) {
                $worldsPath = self::getMain()->getServer()->getDataPath() . 'worlds';

                $zip = new \ZipArchive();

                $open = $zip->open($backUp);
                
                if ($open) $extract = $zip->extractTo($worldsPath);
                
                $zip->close();
            }
            
            return $extract;
        }

        public function removeBackup() : bool {
            $file = self::getMain()->getDataFolder() . 'Backups/' . $this->getName() . '.zip';

            if (file_exists($file)) {
                unlink($file); 

                return true;
            } else return false;
        }
    }

?>
