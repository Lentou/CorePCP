<?php

namespace pcp\events;

use pcp\Core;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\Listener;
use pcp\player\Chaser;
use pcp\player\Member;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class CombatEvent implements Listener {

	public function __construct(Core $plugin){
        $this->plugin = $plugin;
    }
	
	public function pvpFly(EntityDamageEvent $event) : void {	
	   $entity = $event->getEntity();
		if($event instanceof EntityDamageByEntityEvent){
			if($entity instanceof Player){
				$damager = $event->getDamager();
				if(!$damager instanceof Player) return;
				if($damager->isCreative()) return;
				if($damager->isFlying()){
					$damager->sendMessage(str_replace("{prefix}", $this->plugin->events["combat"]["prefix"], $this->plugin->events["combat"]["text"]["fly"]));
					$damager->setAllowFlight(false);
					$damager->setFlying(false);
				} 

				if($damager->getScale(0.4) or $damager->getScale(0.6) or $damager->getScale(0.8) or $damager->getScale(1.5)){
				   $damager->setScale(1.0);
				}
			}
		}
	}
	
    /**
     * @param EnityDamageEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function pvpDamage(EntityDamageByEntityEvent $event){
		$damager = $event->getDamager();
		$player = $event->getEntity();
		if($damager instanceof Player and $player instanceof Player){
			if($event->isCancelled()) return;
			if($damager->isCreative() and $player->isCreative()) return;
			
			if (Chaser::isInCombat($player) || Chaser::isInCombat($damager)) {
				if ((Chaser::getCombatType($damager, "name") === $player->getName()) && (Chaser::getCombatType($player, "name") === $damager->getName())) {
					Chaser::setCombatRival($player, $damager);
					Chaser::setCombatRival($damager, $player);
					return;
				}
			
				if ((Chaser::getCombatType($damager, "name") !== $player->getName()) || (Chaser::getCombatType($player, "name") !== $damager->getName())) {
					$event->setCancelled();
					$damager->sendMessage(str_replace(["{prefix}", "{enemy}", "{player}"], [$this->plugin->events["combat"]["prefix"], $enemy->getRival(), $player->getName()], $this->plugin->events["combat"]["text"]["kill"]));
					return;
				}
				
				if ((Chaser::getCombatType($damager, "name") === "none") && (Chaser::getCombatType($player, "name") === "none")) {
					Chaser::setCombatRival($player, $damager);
					Chaser::setCombatRival($damager, $player);
					$this->setCombat($player);
					$this->setCombat($damager);
					return;
				}
			
			} else { 
				Chaser::setCombat($player, true);
				Chaser::setCombat($damager, true);
				Chaser::setCombatRival($player, $damager);
				Chaser::setCombatRival($damager, $player);
					
				$this->setCombat($player);
				$this->setCombat($damager);
			}
			
		}
    }

    private function setCombat(Player $player){
        $tagged = str_replace("{time}", $this->plugin->events["combat"]["interval"], $this->plugin->events["combat"]["text"]["tag"]);
		$player->sendMessage(str_replace("{prefix}", $this->plugin->events["combat"]["prefix"], $tagged));
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function pvpDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		if (Chaser::isInCombat($player)) {
			Chaser::setCombat($player, false);
		}
    }
	
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		Chaser::initCooldownPlayer($player, true);
	}

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function pvpQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(Chaser::isInCombat($player)){
			//if ((time() - Chaser::getCombatPlayer($player)) < $this->plugin->events["combat"]["interval"]) {
                //$player->kill();
            //}
			Chaser::setCombat($player, false);
        }
		Chaser::initCooldownPlayer($player, false);
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function pvpChat(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		if (Chaser::isInCombat($player)) {
            $cmd = strtolower(explode(' ', $event->getMessage())[0]);
            if(in_array($cmd, $this->plugin->events["combat"]["cmds"])){
                $player->sendMessage(str_replace("{prefix}", $this->plugin->events["combat"]["prefix"], $this->plugin->events["combat"]["text"]["cmd"]));
                $event->setCancelled();
            }
        }
    }
	
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$p = $event->getPacket();
		if ($p instanceof LevelSoundEventPacket and $p->sound == LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE or $p instanceof InventoryTransactionPacket and $p->trData instanceof UseItemOnEntityTransactionData) {
			Chaser::addClick($player);
		}
	}
}
