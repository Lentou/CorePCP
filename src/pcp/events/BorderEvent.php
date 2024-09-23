<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\entity\{
	Effect,
	EffectInstance,
};;

class BorderEvent implements Listener {
  
	/** @var Core */
	private $plugin;
	
	/** @var limit */
	private $hasLimit, $limitType, $limit, $limitedWorlds;

	public function __construct(Core $plugin){
		$this->plugin = $plugin;
		
		$this->hasLimit = $plugin->events["border"]["enable"];
		$this->limitType = $plugin->events["border"]["type"];
		$this->limit = (int) $plugin->events["border"]["value"];
		$this->limitedWorlds = $plugin->events["border"]["worlds"];
	}
	
	public function onBorderBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		
		$block = $event->getBlock();
		$levelName = $player->getLevel()->getFolderName();
		
		if($this->hasLimit && !$player->isOp())
		{
			if(in_array($levelName, $this->limitedWorlds))
			{
				switch($this->limitType)
				{
					case "center": case "sphere":
						$spawn = $player->getLevel()->getSafeSpawn();
				
						$spawnPos = [
							$spawn->getX()  - 1,
							$spawn->getZ()
							];
						$blockPos = [
							$block->getX(),
							$block->getZ()
							];
							
						$dist = round($this->checkEuclideanDistance($spawnPos, $blockPos));
						
						if($dist > $this->limit)
						{
							$player->sendTip(str_replace("{prefix}", $this->plugin->events["border"]["prefix"], $this->plugin->events["border"]["text"]["tip"]));
							$player->sendMessage(str_replace(["{prefix}", "{limit}", "{dist}"], [$this->plugin->events["border"]["prefix"], $this->limit, $dist], $this->plugin->events["border"]["text"]["dist"]));
							
							$player->addEffect(new EffectInstance(Effect::getEffect((int) 15), 100, 5, true));
							$event->setCancelled(true);
							return;
						}
					break;
					
					case "grid": case "cube":
						$bX = abs($block->getX());
						$bZ = abs($block->getZ());
						if($bX > $this->limit or $bZ > $this->limit)
						{
							$player->sendTip(str_replace("{prefix}", $this->plugin->events["border"]["prefix"], $this->plugin->events["border"]["text"]["tip"]));
							$player->sendMessage(str_replace(["{prefix}", "{status}", "{limit}", "{x}", "{z}"], [$this->plugin->events["border"]["prefix"], "break", $this->limit, $bX, $bZ], $this->plugin->events["border"]["text"]["edit"]));
							
							$player->addEffect(new EffectInstance(Effect::getEffect((int) 15), 100, 5, true));
							$event->setCancelled(true);
							return;
						}
					break;
				}
			}
		}
	}
	
	public function onBorderPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		
		if($event->isCancelled()) return;
		
		$block = $event->getBlock();
		$levelName = $player->getLevel()->getFolderName();
		
		if($this->hasLimit && !$player->isOp())
		{
			if(in_array($levelName, $this->limitedWorlds))
			{
				switch($this->limitType)
				{
					case "center": case "sphere":
						$spawn = $player->getLevel()->getSafeSpawn();
				
						$spawnPos = [
							$spawn->getX()  - 1,
							$spawn->getZ()
							];
						$blockPos = [
							$block->getX(),
							$block->getZ()
							];
							
						$dist = round($this->checkEuclideanDistance($spawnPos, $blockPos));
						
						if($dist > $this->limit)
						{
							$player->sendTip(str_replace("{prefix}", $this->plugin->events["border"]["prefix"], $this->plugin->events["border"]["text"]["tip"]));
							$player->sendMessage(str_replace(["{prefix}", "{limit}", "{dist}"], [$this->plugin->events["border"]["prefix"], $this->limit, $dist], $this->plugin->events["border"]["text"]["dist"]));
							
							$player->addEffect(new EffectInstance(Effect::getEffect((int) 15), 100, 5, true));
							$event->setCancelled(true);
							return;
						}
					break;
					
					case "grid": case "cube":
						$bX = abs($block->getX());
						$bZ = abs($block->getZ());
						if($bX > $this->limit or $bZ > $this->limit)
						{
							$player->sendTip(str_replace("{prefix}", $this->plugin->events["border"]["prefix"], $this->plugin->events["border"]["text"]["tip"]));
							$player->sendMessage(str_replace(["{prefix}", "{status}", "{limit}", "{x}", "{z}"], [$this->plugin->events["border"]["prefix"], "placed", $this->limit, $bX, $bZ], $this->plugin->events["border"]["text"]["edit"]));
							
							$player->addEffect(new EffectInstance(Effect::getEffect((int) 15), 100, 5, true));
							$event->setCancelled(true);
							return;
						}
					break;
				}
			}
		}
	}
	
	private function checkEuclideanDistance(array $a, array $b)
	{
		return
			array_sum(
				array_map(
					function($x, $y)
					{
						return abs($x - $y) ** 2;
					}, $a, $b
				)
		) ** (1/2);
	}
  
}