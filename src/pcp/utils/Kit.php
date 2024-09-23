<?php

declare(strict_types=1);

namespace pcp\utils;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\enchantment\Enchantment; 
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class Kit {
	
	public function giveKit($player, $kit) {
        if (!empty($kit)) {
        	if(isset($kit['armour'])) {
				$this->giveArmor($player, $kit);
				$this->giveItems($player, $kit);
			} else {
				$this->giveItems($player, $kit);
			}
        }
    }
	
	private function giveArmor($player, $kit) {
		foreach ($kit['armour'] as $key => $value) {
			$item = Item::get($value['id'], $value['meta']);
			if (!$player->getInventory()->canAddItem($item)) {
				return;
			}
			if (isset($value['enchantments'])) {
				foreach ($value['enchantments'] as $k => $v) {
					if (is_int($v['id'])) {
						$enchantment = Enchantment::getEnchantment($v['id']);
					} else {
                        $enchantment = Enchantment::getEnchantmentByName($v['id']);
                    }
                    if ($enchantment instanceof Enchantment) {	
						if (isset($v['level'])) $enchantment = new EnchantmentInstance($enchantment, $v['level']);
						$item->addEnchantment($enchantment);
                    }
				}
			}
			if (isset($value["name"])){
				$item->setCustomName($value["name"]);
			}
			if (isset($value["lore"])){
				$item->setLore([implode("\n", $value["lore"])]);
			}  
			if ($key == 'helmet') {
				if($player->getArmorInventory()->getHelmet()->isNull()){
					$player->getArmorInventory()->setHelmet($item);
				} else {
					$player->getInventory()->addItem($item);
				}
			}
            if ($key == 'chestplate') {
				if($player->getArmorInventory()->getChestplate()->isNull()){
					$player->getArmorInventory()->setChestplate($item);
				} else {
					$player->getInventory()->addItem($item);
				}
			}
            if ($key == 'leggings') {
				if($player->getArmorInventory()->getLeggings()->isNull()){
					$player->getArmorInventory()->setLeggings($item);
				} else {
					$player->getInventory()->addItem($item);
				}
			}
            if ($key == 'boots') {
				if($player->getArmorInventory()->getBoots()->isNull()){
					$player->getArmorInventory()->setBoots($item);
				} else {
					$player->getInventory()->addItem($item);
				}
			}
		}
	}
	
	private function giveItems($player, $kit) {
		foreach ($kit['items'] as $key => $value) {
			$item = Item::get($value['id'], $value['meta'], $value['count']);
			if (!$player->getInventory()->canAddItem($item)) {
				return;
			}
			if (isset($value["name"])){
				$item->setCustomName($value["name"]);
			}
			if (isset($value["lore"])){
				$item->setLore([implode("\n", $value["lore"])]);
			}
            if (isset($value['enchantments'])) {
                foreach ($value['enchantments'] as $key => $value) {
					if (is_int($value['id'])) {
						$enchantment = Enchantment::getEnchantment($value['id']);
                    } else {
                        $enchantment = Enchantment::getEnchantmentByName($value['id']);
                    }
                    if ($enchantment instanceof Enchantment) {	
						if (isset($value['level']))$enchantment = new EnchantmentInstance($enchantment, $value['level']);
                        $item->addEnchantment($enchantment);
                    } 
                }
            }
            $player->getInventory()->addItem($item);   
		}
	}

	
}