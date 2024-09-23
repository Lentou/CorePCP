<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\Player;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\item\ItemFactory;
use pocketmine\block\BlockFactory;
use pocketmine\level\Level;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class MineEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function unliBlock(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($player->isCreative()) return;
		if(Chaser::isUnliModePlayer($player)){
			$hand = $player->getInventory()->getItemInHand();
			if(!in_array($hand->getId() . "+" . $hand->getDamage(), $this->plugin->cmds["call"]["blocks"])){
				if(!$event->isCancelled()){
					if($hand->getCount() != 64){
						$hand->setCount($hand->getCount() + 1);
					}
					$event->getPlayer()->getInventory()->setItemInHand($hand);
				}
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event) {
		$block = $event->getBlock();
		if($event->isCancelled()) return;
        if(!$this->plugin->events["mine"]["drops"]["creative"] && $event->getPlayer()->isCreative()) return;
        foreach ($this->plugin->events["mine"]["drops"]["items"] as $id => $value) {
            if ($block->getId() . ":" . $block->getDamage() === $id) {
            	foreach ($value['drops'] as $drops){
            		$item = explode(":", $drops);
            		if (mt_rand(1, (int)$item[3]) === 1){
            			$event->setDrops([Item::get((int)$item[0], (int)$item[1], (int)$item[2])]);
					}
				}
            }
        }
    }
	
	public function onMine(BlockBreakEvent $event) {
		if ($event->isCancelled()) {
            return;
        }

        $block = $event->getBlock();
        $player = $event->getPlayer();
        $world = $block->getLevelNonNull();

        if (!$block->isCompatibleWithTool($event->getItem())) {
            return;
        }

        if (!isset($this->plugin->events["mine"]['blocks'])) {
            return;
        }

        $blockReplace = ItemFactory::fromString($this->plugin->events["mine"]["replaced"]);
        $replaceBlock = null;
        $customReplace = null;

        foreach ($this->plugin->events["mine"]['blocks'] as $value) {
            $explode = explode('=', $value);

            if (count($explode) === 1) {
                $replaceBlock = ItemFactory::fromString($value);
            } elseif (count($explode) === 2) {
                $replaceBlock = ItemFactory::fromString($explode[0]);
                $customReplace = ItemFactory::fromString($explode[1]);
            }

            if ($block->getId() === $replaceBlock->getId() && $block->getDamage() === $replaceBlock->getDamage()) {
                if (!in_array($world->getFolderName(), $this->plugin->events["mine"]["worlds"])) {
                    return;
                }

                if (!$player->hasPermission('mine.bypass')) {
                    return;
                }

                foreach ($event->getDrops() as $drops) {
                    if ((bool) $this->plugin->events["mine"]["auto-pickup"]) {
                        (!$player->getInventory()->canAddItem($drops)) ? ($world->dropItem($block, $drops)) : ($player->getInventory()->addItem($drops));
                        (!$player->canPickupXp()) ? ($world->dropExperience($block, $event->getXpDropAmount())) : ($player->addXp($event->getXpDropAmount()));

                        continue;
                    }

                    $world->dropItem($block, $drops);
                    $world->dropExperience($block, $event->getXpDropAmount());
                }

                $event->setCancelled();

                $world->setBlock($block, BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));

                if ($customReplace === null) {
                    $world->setBlock($block, BlockFactory::get($blockReplace->getId(), $blockReplace->getDamage()));
                } else {
                    $world->setBlock($block, BlockFactory::get($customReplace->getId(), $customReplace->getDamage()));
                }

                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function (int $currentTick) use ($block, $world): void {
                        $world->setBlock($block, BlockFactory::get($block->getId(), $block->getDamage()));
                    }
                ), 20 * $this->plugin->events["mine"]["delay"]);
            }
        }
	}
	
}