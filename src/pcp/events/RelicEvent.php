<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\Server;

class RelicEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
    
	public function relicBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$blockName = strtolower($block->getName());
		if(array_key_exists($blockName, $this->plugin->relicBlocks))
		{
			$chance = $this->plugin->relicBlocks[$blockName];
			if($this->plugin->relic->isLucky($chance))
			{
				$relic = $this->plugin->relic->craftRelic($player);
				if($relic instanceof Item)
				{
					$arr = $event->getDrops();
					array_push($arr, $relic);
					$event->setDrops($arr);
					$player->sendMessage(str_replace(["{prefix}", "{relic}"], [$this->plugin->events["relic"]["prefix"], $relic->getName()], $this->plugin->events["relic"]["text"]["found"]));
				}
				$this->plugin->relic->sendMagicEffect($player);
			}
		}
	}
	
	public function relicTouch(PlayerInteractEvent $event){
		$player = $event->getPlayer();
        $item = $event->getItem();
		if($item->getId() == 450 && !$player->isCreative()){
			if(!$event->getBlock()->getId() == 389){
				$cs = Server::getInstance()->getPluginManager()->getPlugin("CoreX2");
				if($cs === null) return;
				
				$plevel = $cs->data->getVal($player, 'level');
				$tag = $item->getNamedTag();
				
				if($tag->getTag('requirement') !== null){
					if($plevel < $tag->getInt('requirement')){
						$player->sendTitle(str_replace("{reliclvl}", (string)$tag->getInt('requirement'), $this->plugin->events["relic"]["title"]["need"][0]), str_replace("{reliclvl}", (string)$tag->getInt('requirement'), $this->plugin->events["relic"]["title"]["need"][1]));
						return;
					}
				}
				$player->getInventory()->setItemInHand(Item::get(0));
				$this->plugin->relic->openRelic($player, $item);
			}
		}
	}

}