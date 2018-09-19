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

    namespace HungerGames\Player\Base;
    
    use HungerGames\Main;

    /**
     * Player Base Class
     *
     * @author Denzel Code
     */
    abstract class Player {
        
        public abstract function __construct(Main $main, array $data);
        
        public abstract static function getMain() : Main;
        
        public abstract function getData() : array;
        
        public abstract function getName() : string;
    }

?>
