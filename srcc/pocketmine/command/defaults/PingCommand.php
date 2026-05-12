<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\command\defaults;

use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\item\Item;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\EnchantmentTableParticle;
use pocketmine\level\particle\EnchantParticle;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\InstantEnchantParticle;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\Particle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\RainSplashParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\SporeParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\level\particle\WaterParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;

class PingCommand extends VanillaCommand{

    public function __construct($name){
        parent::__construct(
            $name,
            "Get Server Ping ",
            "/ping"
        );
    }

    public function execute(CommandSender $sender, $currentAlias, array $args){

		if(count($args) == 0){
			
			if($sender instanceof Player){
			    if($sender->getPing() <= 100) {
                    $sPing = TextFormat::GREEN . $sender->getPing();
                } elseif($sender->getPing() <= 200) {
                    $sPing = TextFormat::YELLOW . $sender->getPing();
                } elseif($sender->getPing() > 280) {
                    $sPing = TextFormat::RED . $sender->getPing();
                }
				$sender->sendMessage("Ping: " . $sPing . "ms");
			} else {
				$sender->sendMessage("§cThis command can only be used within the game!");
			}
		}
		
		if(count($args) == 1){
			if($player = $sender->getServer()->getPlayer($args[0])){
			    if($player->getPing() <= 100) {
                    $pPing = TextFormat::GREEN . $player->getPing();
                } elseif($player->getPing() <= 200) {
                    $pPing = TextFormat::YELLOW . $player->getPing();
                } elseif($player->getPing() > 280) {
                    $pPing = TextFormat::RED . $player->getPing();
                }
				$sender->sendMessage($player->getName() . 's ping is: ' . $pPing . "ms");
			} else {
				$sender->sendMessage("§cPlayer not found!");
			}
		}
	
        return true;
    }
}