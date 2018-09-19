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
    use pocketmine\tile\Sign;
    
    /**
     * Signs Class
     *
     * @author Denzel Code
     */
    class Updater extends PluginTask {
        
        private static $main;
        
        public function __construct(Main $plugin) {
            self::$main = $plugin;
            
            parent::__construct($plugin);
        }
        
        public static function getMain() : Main {
            return self::$main;
        }

        public function onRun($currentTick) {
            foreach (self::getMain()->getArenas() as $arena) {
                foreach ($arena->getSigns() as $sign) {
                    $newText = [];
                                    
                    $newText[0] = Color::BOLD . "HungerGames";

                    $newText[1] = $arena->getStatusColor();

                    $newText[2] = Color::UNDERLINE . $arena->getName();

                    $newText[3] = Color::GRAY . count($arena->getPocketPlayers()) . "/" . $arena->getMaxPlayers();

                    $sign->setText($newText[0], $newText[1], $newText[2], $newText[3]);

                    $block = $arena->getStatusBlock();
                    
                    $level = $sign->getLevel();
                    
                    $positions = [
                        $sign->add(-1, 0, 0),
                        $sign->add(+1, 0, 0),
                        $sign->add(0, 0, -1),
                        $sign->add(0, 0, +1)
                    ];
                    
                    $blocks = [20, 241, 236];
                    
                    $allow = false;
                    
                    foreach ($positions as $position) {
                        foreach ($blocks as $blockId) {
                            if ($level->getBlock($position)->getId() == $blockId) {
                                $allow = true;
                                
                                break;
                            }
                        }
                        
                        if (!$allow) if ($level->getBlock($position)->getId() == $block->getId()) $allow = true;
                        
                        if ($allow) {
                            $level->setBlock($position, $block);
                            
                            break;
                        }
                    }
                }
            }
        }
    }

?>
