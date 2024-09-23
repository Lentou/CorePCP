<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Position;

class SparringEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function onCmd(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		if (Chaser::isInSparring($player)) {
            $cmd = strtolower(explode(' ', $event->getMessage())[0]);
            if(!in_array($cmd, ["/hub"])){
				$player->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->sparring["text"]["text"]["cantuse"]));
                $event->setCancelled();
            } else {
				Chaser::unsetSparring($player);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				$player->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->sparring["text"]["text"]["exit"]));
			}
        }
    }
	
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		if (Chaser::isInSparring($player)) {
			$type = Chaser::getSparring($player, "type");
			$arena = Chaser::getSparring($player, "arena");
			if ($player->getLevel()->getName() == $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][0]) {
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
			}
			Chaser::unsetSparring($player);
		}
	}
	
	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		if (Chaser::isInSparring($player)) {
			$event->setKeepInventory(true);
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->removeAllEffects();
		}
		$cause = $player->getLastDamageCause();
		if ($cause instanceof EntityDamageByEntityEvent) {
			$damager = $cause->getDamager();
			if ($damager instanceof Player) {
				if (Chaser::isInSparring($damager)) {
					$type = Chaser::getSparring($damager, "type");
					$arena = Chaser::getSparring($damager, "arena");
					
					$damager->getInventory()->clearAll();
					$damager->getArmorInventory()->clearAll();
					$damager->removeAllEffects();
					
					$this->plugin->cx->elo->increasePoints($damager, mt_rand($this->plugin->sparring["int"]["elo"][0], $this->plugin->sparring["int"]["elo"][1]));
					$this->plugin->cx->elo->decreasePoints($player, mt_rand($this->plugin->sparring["int"]["elo"][0], $this->plugin->sparring["int"]["elo"][1]));
					
					if (in_array($arena, $this->plugin->sparring["kits"]["$type"]["arenas"]) and ($damager->getLevel()->getName() === $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][0])) {
						Core::getInstance()->getKit()->giveKit($damager, $this->plugin->sparring["kits"]["$type"]["kits"]);
					}
				}
			}
		}
	}
	
	public function onRespawn(PlayerRespawnEvent $event) {
		$player = $event->getPlayer();
		if (Chaser::isInSparring($player)){
			$type = Chaser::getSparring($player, "type");
			$arena = Chaser::getSparring($player, "arena");
			
			if($this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][1] == true){
				$tp = $player->getLevel()->getSpawnLocation();
			} else {
				$x = $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][2];
				$y = $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][3];
				$z = $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][4];
									
				$tp = new Position($x, $y, $z, $level);
			}
			$event->setRespawnPosition($tp);
			
			if (in_array($arena, $this->plugin->sparring["kits"]["$type"]["arenas"]) and ($player->getLevel()->getName() === $this->plugin->sparring["list"]["$type"]["sparring"]["$arena"]["world"][0])) { 
				Core::getInstance()->getKit()->giveKit($player, $this->plugin->sparring["kits"]["$type"]["kits"]);
			}
		}
	}

}