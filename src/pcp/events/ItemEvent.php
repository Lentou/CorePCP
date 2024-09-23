<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;

class ItemEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
    
	public function onItemDrop(ItemSpawnEvent $event){
		$entity = $event->getEntity();
		$level = $entity->getLevel();
		$item = $entity->getItem();
		$format = $this->plugin->events["item"]["display"]["format"];
		if($this->plugin->events["item"]["display"]["enable"] === true){
			if(in_array($level->getName(), $this->plugin->events["item"]["display"]["worlds"], true)) {
				if(!in_array($item->getId(), $this->plugin->events["item"]["display"]["items"], true)) {
					if(strpos($format, "{name}") !== false) {
						$format = str_replace("{name}", $item->getName(), $format);
					}
					if(strpos($format, "{count}") !== false) {
						$format = str_replace("{count}", (string)$item->getCount(), $format);
					}
					$entity->setNameTag($format);
					$entity->setNameTagVisible(true);
					$entity->setNameTagAlwaysVisible(true);
				}
			}
		}
	}
	
	public function onConsume(PlayerItemConsumeEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($this->plugin->events["item"]["cooldown"]["enable"] === true){
			if($player->isSurvival() or $player->isAdventure()){
				if(in_array($item->getId(), $this->plugin->events["item"]["cooldown"]["list"]["food"])){
					$cooldown = $this->plugin->events["item"]["cooldown"]["delay"]["food"];
					if(Chaser::isCooldownPlayer($player)) {
						if (Chaser::inCooldown($player, "food", $cooldown)) {
							$event->setCancelled(true);
							$cd = Chaser::getExactCooldown($player, "food", $cooldown);
							$message = $this->plugin->events["item"]["cooldown"]["text"];
							$message = str_replace("{cd}", strval($cd), $message);
							$player->sendTip($message);
						} else {
							Chaser::delCooldown($player, "food");
							Chaser::setCooldown($player, "food");
						}
					} else {
						Chaser::setCooldown($player, "food");
					}
				}
			}
		}
	}
	
	public function onTouch(PlayerInteractEvent $event){
		$player = $event->getPlayer();
        $item = $event->getItem();
		if($this->plugin->events["item"]["cooldown"]["enable"] === true){
			if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
				if($player->isSurvival() or $player->isAdventure()){
					if(in_array($item->getId(), $this->plugin->events["item"]["cooldown"]["list"]["item"])){
						$cooldown = $this->plugin->events["item"]["cooldown"]["delay"]["item"];
						if(Chaser::isCooldownPlayer($player)) {
							if (Chaser::inCooldown($player, "item", $cooldown)) {
								$event->setCancelled(true);
								$message = $this->plugin->events["item"]["cooldown"]["text"];
								$cd = Chaser::getExactCooldown($player, "item", $cooldown);
								$message = str_replace("{cd}", strval($cd), $message);
								$player->sendTip($message);
							} else {
								Chaser::delCooldown($player, "item");
								Chaser::setCooldown($player, "item");
							} 
						} else {
							Chaser::setCooldown($player, "item");
						}
					}
				}
			}
		}
	}

}