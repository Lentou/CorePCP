<?php

namespace pcp\utils;

use pcp\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\inventory\Inventory;
use pocketmine\utils\Config;
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use DaPigGuy\PiggyCustomEnchants\{PiggyCustomEnchants, CustomEnchantManager};
use pocketmine\command\ConsoleCommandSender;

use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

use pocketmine\nbt\tag\{
	CompoundTag,
	StringTag,
	IntTag
};;

class Relics {
	
	public $main;
  
	public function __construct(Core $main)
	{
		$this->main = $main;
	}
  
  	public function craftRelic(Player $player, $identified_tier = null)
  	{		
		$item = Item::get(450, 0, 1);
		$tier = ($identified_tier == null) ? $this->getRandomRelic() : $identified_tier;
			
		$data = (new Config($this->main->getDataFolder() . "relics/".$tier.".yml", Config::YAML))->getAll();
		//var_dump($data);
		$title = $data['title'];
		$req = $data['level_requirement'];
		$loot = array_rand($data['loots']);
		$key = mt_rand(100, 999);
				
		//Extra Protection
			$security = new CompoundTag("", [
				new StringTag("tier", $tier),
				new StringTag("loot", $loot),
				new IntTag("requirement", $req),
				new IntTag("version", 1),
				new IntTag("serial", $key)
			]);
			$item->setNamedTag($security);
		//End of Extra Protection
		
		$item->setLore([
			str_replace("{reqlvl}", $req, $this->main->events["relic"]["lore"][0]),
			str_replace("{loot}", $loot, $this->main->events["relic"]["lore"][1]),
			$this->main->events["relic"]["lore"][2]
		]);
		
		$item->setCustomName($title);
		$item->setDamage($key);
		
		$this->sendMagicEffect($player);
		return $item;
  	}

	public function openRelic(Player $player, Item $item) : void
	{
		$tag = $item->getNamedTag();
		
		if($tag->getInt('serial') <> $item->getDamage())
		{
			$player->sendTitle($this->main->events["relic"]["title"]["serial"][0], $this->main->events["relic"]["title"]["serial"][1]);
			return;
		}
		
		$data = (new Config($this->main->getDataFolder() . "relics/".$tag->getString('tier').".yml", Config::YAML))->getAll();
		if($data === null)
		{
			$player->sendMessage(str_replace("{prefix}", $this->main->events["relic"]["prefix"], $this->main->events["relic"]["text"]["exists"]));
			$item = $this->craftRelic($player);
			$player->getInventory()->setItemInHand($item);
			return;
		}

		if(!array_key_exists($tag->getString('loot'), $data['loots']))
		{
			$player->sendMessage(str_replace("{prefix}", $this->main->events["relic"]["prefix"], $this->main->events["relic"]["text"]["exists"]));
			$item = $this->craftRelic($player, $tag->getString('tier'));
			$player->getInventory()->setItemInHand($item);
			return;
		}
		
		$loot = $data['loots'][$tag->getString('loot')];
		
		switch($loot['type'])
		{
			case 'item':
				if(empty($loot['items'])) return;
				foreach($loot['items'] as $identifier)
				{	
					$item = Item::get($identifier['id'], $identifier['meta']); //id & meta
					$item->setCount($identifier['amount']); //count
					$item->setCustomName($identifier['name']); //custom name
					foreach($identifier['enchantments'] as $name => $modifier)
					{
						$item = $this->enchantItem($item, $name, $modifier);
					}
					$player->getLevel()->dropItem(new Vector3($player->getX(), $player->getY() + 0.75, $player->getZ()), $item);
				}
				
			break;
			
			case 'cmd':
				if(empty($loot['commands'])) return;
				foreach($loot['commands'] as $command)
				{
					$command = str_replace("%player%", '"'. $player->getName(). '"', $command);
					Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command);
				}
			break;
		}
	}
  
	public function isLucky(int $chance) : bool
	{
		return (mt_rand(5, 100) <= $chance);
	}
  
	private function getRandomRelic() : string
	{
		$relics = $this->main->randRelic;
		$rand = mt_rand(1, (int) array_sum($relics));
		foreach ($relics as $relic => $chance)
		{
     		$rand -= $chance;
      		if ($rand <= 0)
	    	{
		    	return $relic;
      		}
    	}
  	}
	
	private function enchantItem(Item $item, $enchId, int $lvl) : Item
	{
		if(is_string($enchId))
		{
			//if (($enchant = CustomEnchantManager::getEnchantmentByName($enchId)) !== null)
			//{
				//$item->addEnchantment(new EnchantmentInstance($enchant, $lvl));
				//return $item;
			//}
			if (($enchant = Enchantment::getEnchantmentByName($enchId)) !== null)
			{
				$item->addEnchantment(new EnchantmentInstance($enchant, $lvl));
				return $item;
			}
		} else {
			//if($enchId >= 100)
			//{
				//$enchant = CustomEnchantManager::getEnchantment($enchId);
				//if ($enchant != null)
				//{
					//$item->addEnchantment(new EnchantmentInstance($enchant, $lvl));
					//return $item;
				//}
			//}
			//if($enchId <= 32 && $enchId >= 0)
			//{
				$enchantment = Enchantment::getEnchantment((int) $enchId);
				if($enchantment instanceof Enchantment)
				{
					$item->addEnchantment( new EnchantmentInstance($enchantment, $lvl) );
					return $item;
				}
			//}
		}
		return $item;
	}
	
	public function sendMagicEffect(Player $player)
	{
		$player->broadcastEntityEvent(ActorEventPacket::CONSUME_TOTEM);
		$player->getLevel()->broadcastLevelEvent($player->add(0, $player->eyeHeight, 0), LevelEventPacket::EVENT_SOUND_TOTEM);
	}
}
