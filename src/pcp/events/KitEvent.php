<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;

use pocketmine\Player;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;

class KitEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function onGettingKit(PlayerInteractEvent $event){
		$player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getNamedTag()->hasTag("Kit")) {
			$category = $item->getNamedTag()->getString("KitType");
			$kit = $item->getNamedTag()->getString("Kit");
			$items = $this->plugin->kits["kits"]["$category"]["kits"]["$kit"];
			
			if($player->isCreative()){
				$player->sendMessage(str_replace("{prefix}", $this->plugin->kits["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["survival"]));
				return;
			}
			
			if(array_key_exists("perm", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"])){
				if(!$player->hasPermission($this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["perm"])){
					$player->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->kits["text"]["prefix"], "open"], $this->plugin->kits["text"]["kit"]["perm"]));
					return;
				}
			}
			
            Core::getInstance()->getKit()->giveKit($player, $items);
			$item->setCount($item->getCount() - 1);
			$player->getInventory()->setItemInHand($item);
			$player->sendMessage(str_replace(["{prefix}", "{kit}"], [$this->plugin->kits["text"]["prefix"], ucfirst($kit)], $this->plugin->kits["text"]["kit"]["open"]));
			$event->setCancelled();
        }
		
	}

}