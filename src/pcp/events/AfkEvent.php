<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class AfkEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin) {
		$this->plugin = $plugin;
	}
	
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		if (Chaser::isAfkPlayer($player)) {
			Chaser::initAfkPlayer($player, true);
			$player->sendMessage(str_replace("{prefix}", $this->plugin->cmds["afk"]["prefix"], $this->plugin->events["afk"]["text"]["join"]));
		}
	}
	
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		if (Chaser::isAfkPlayer($player)) {
			Chaser::initAfkPlayer($player, true);
		}
	}
	
	public function onDamage(EntityDamageByEntityEvent $event) {
		$player = $event->getEntity();
		$damager = $event->getDamager();
        if($player instanceof Player && $damager instanceof Player) {
			if(Chaser::isAfkPlayer($player)) {
				$damager->sendMessage(str_replace(["{prefix}", "{player}"], [$this->plugin->cmds["afk"]["prefix"], $player->getName()], $this->plugin->events["afk"]["text"]["kill"]));
				$event->setCancelled();
			}
        }
    }

}