<?php

declare(strict_types = 1);

namespace pcp\events;

use pocketmine\block\Block;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\item\{
    Armor,
    Bow,
    Item,
    Sword,
    Tool
};

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

use pocketmine\Player;
use pcp\libs\forms\{SimpleForm, ModalForm, CustomForm};
use pcp\Core;

class ForgeEvent implements Listener {

    private $plugin;

    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
    }
	
	public function onTouch(PlayerInteractEvent $event) {
		
		$player = $event->getPlayer();
        $block = $event->getBlock();
		
		if(($player->isSneaking() === false) and ($player->isSurvival())){
			if($block->getId() === Block::STONECUTTER){
				if($this->plugin->events["forge"]["toggle"] === true){
					$event->setCancelled();
					$this->mainForgeForm($player);
				}
			}
		}
    }
	
	private function mainForgeForm(Player $player) {
		$form = new SimpleForm(function (Player $player, $data) {
			if (is_null($data)) return true;
			switch($data) {
				case 0:
					$main = $player->getInventory()->getItemInHand();
                    if ($main instanceof Item or $main instanceof Tool or $main instanceof Armor){
                        $this->renameForm($player);
                    } else {
						$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["rename"]["exist"]));
                    }
				break;
				case 1:
					$main = $player->getInventory()->getItemInHand();
                    if ($main instanceof Tool or $main instanceof Armor){
                        $this->repairForm($player);
                    } else {
						$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["repair"]["notrepair"]));
                    }
				break;
				case 2:
					$main = $player->getInventory()->getItemInHand();
					if ($main instanceof Tool or $main instanceof Armor) {
						$this->enchantForm($player, $main);
					} else {
						$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["enchant"]["notench"]));
					}
				break;
			}
		});
		$form->setTitle($this->plugin->events["forge"]["form"]["title"]);
		$form->setContent(implode("\n", $this->plugin->events["forge"]["form"]["content"]["main"]));
		$form->addButton($this->plugin->events["forge"]["form"]["buttons"]["rename"][0], $this->plugin->events["forge"]["form"]["buttons"]["rename"][1], $this->plugin->events["forge"]["form"]["buttons"]["rename"][2]);
		$form->addButton($this->plugin->events["forge"]["form"]["buttons"]["repair"][0], $this->plugin->events["forge"]["form"]["buttons"]["repair"][1], $this->plugin->events["forge"]["form"]["buttons"]["repair"][2]);
		$form->addButton($this->plugin->events["forge"]["form"]["buttons"]["enchant"][0], $this->plugin->events["forge"]["form"]["buttons"]["enchant"][1], $this->plugin->events["forge"]["form"]["buttons"]["enchant"][2]);
		$player->sendForm($form);
	}

    private function renameForm(Player $player){
        $form = new CustomForm(function (Player $player, $data) {
            if (is_null($data)) {
				$this->mainForgeForm($player);
                return true;
            }
            if (!is_null($data[2])){
                if ($player->getXpLevel() >= $this->plugin->events["forge"]["int"]["rename"]){
                    $item = $player->getInventory()->getItemInHand();
					
					$banitems = $this->plugin->events["forge"]["rename"]["bans"];
					foreach ($banitems as $bannames) {
						if ($item->getNamedTag()->hasTag($bannames)) {
							$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["rename"]["cant"]));
							return false;
						}
					}
					
					$set = str_replace(["#", "&"], ["\n", ""], $data[2]);
					if($data[1] === true){
						$item->setLore([$set]);
						$player->sendMessage(str_replace(["{root}", "{item}", "{type}"], [$this->plugin->events["forge"]["root"], $set, "lore"], $this->plugin->events["forge"]["text"]["rename"]["item"]));
					} else {
						if(strlen($data[2]) > $this->plugin->events["forge"]["int"]["limit"]){
							$player->sendMessage(str_replace(["{root}", "{limit}"], [$this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["int"]["limit"]], $this->plugin->events["forge"]["text"]["rename"]["limit"]));
							return false;
						}
						$item->setCustomName($set);
						$player->sendMessage(str_replace(["{root}", "{item}", "{type}"], [$this->plugin->events["forge"]["root"], $set, "name"], $this->plugin->events["forge"]["text"]["rename"]["item"]));
					}
					
                    $player->subtractXpLevels($this->plugin->events["forge"]["int"]["rename"]);
                    $player->getInventory()->setItemInHand($item);
						
                } else {
					$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["xp"]));
                }
            } else {
				$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["rename"]["input"]));
            }
        });

        $form->setTitle($this->plugin->events["forge"]["form"]["title"]);
		$form->addLabel(implode("\n", str_replace(["{player}", "{xp}", "{limit}"], [$player->getName(), $this->plugin->events["forge"]["int"]["rename"], $this->plugin->events["forge"]["int"]["limit"]["rename"]], $this->plugin->events["forge"]["form"]["content"]["rename"])));
		$form->addToggle($this->plugin->events["forge"]["form"]["toggle"], false);
        $form->addInput($this->plugin->events["forge"]["form"]["input"]);
		$player->sendForm($form);
    }
	
	private function repairForm($player){
        $form = new ModalForm(function (Player $player, $data) {
			if($data === true){
                $main = $player->getInventory()->getItemInHand();
                if ($player->getXpLevel() >= $this->plugin->events["forge"]["int"]["repair"]){
                    if ($main->getDamage() == 0){
						$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["repair"]["already"]));
                    } else {
                        $main->setDamage(0);
                        $player->getInventory()->setItemInHand($main);
                        $player->subtractXpLevels($this->plugin->events["forge"]["int"]["repair"]);
						$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["repair"]["item"]));
                    }
                } else {
					$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["xp"]));
                }
                 
            } else {
				$this->mainForgeForm($player);
			}
        });
		$item = $player->getInventory()->getItemInHand();
        $form->setTitle($this->plugin->events["forge"]["form"]["title"]);
		$form->setContent(implode("\n", str_replace(["{player}", "{item}", "{damage}", "{xp}"], [$player->getName(), $item->getName(), $item->getDamage(), $this->plugin->events["forge"]["int"]["repair"]], $this->plugin->events["forge"]["form"]["content"]["repair"])));
		$form->setButton1($this->plugin->cmds["utils"]["buttons"]["confirm"][0]);
		$form->setButton2($this->plugin->cmds["utils"]["buttons"]["cancel"][0]);
		$player->sendForm($form);
    }
	
	private function enchantForm(Player $player, Item $toEnchant) {

        $enchants = $this->generateEnchants($toEnchant);
        if(empty($enchants)) {
            $player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["item"]));
            return;
        }

        $form = new SimpleForm(function (Player $player, int $data = null) use ($toEnchant, $enchants) {
			if (is_null($data)) {
				$this->mainForgeForm($player);
				return false;
			}
            switch ($data) {
				case 0:
					$this->mainForgeForm($player);
				break;
				case 1:
                    $arr = explode(":", $enchants[0]);
					$this->setEnchant($arr, $player, $toEnchant);
                break;
				case 2:
                    $arr = explode(":", $enchants[1]);
                    $this->setEnchant($arr, $player, $toEnchant);
                break;
				case 3:
                    $arr = explode(":", $enchants[2]);
					$this->setEnchant($arr, $player, $toEnchant);
                break;
				case 4:
					$this->enchantForm($player, $toEnchant);
				break;
			}
        });
		$form->setTitle($this->plugin->events["forge"]["form"]["title"]);
        $form->addButton($this->plugin->cmds["utils"]["buttons"]["back"][0], $this->plugin->cmds["utils"]["buttons"]["back"][1], $this->plugin->cmds["utils"]["buttons"]["back"][2]);
        foreach ($enchants as $ec) {
            $arr = explode(":", $ec);
            $lvl = $arr[1];
            if($lvl <= 0) {
                $lvl = 1;
            }
			$form->addButton(str_replace(["{name}", "{lvl}", "{cost}"], [strval($arr[0]), strval($lvl), strval($arr[2])], $this->plugin->events["forge"]["form"]["buttons"]["select"][0]), $this->plugin->events["forge"]["form"]["buttons"]["select"][1], $this->plugin->events["forge"]["form"]["buttons"]["select"][2]);
        }
		$form->addButton($this->plugin->events["forge"]["form"]["buttons"]["refresh"][0], $this->plugin->events["forge"]["form"]["buttons"]["refresh"][1], $this->plugin->events["forge"]["form"]["buttons"]["refresh"][2]);
        $form->setContent(implode("\n", str_replace(["{item}", "{xp}"], [$toEnchant->getName(), $player->getXpLevel()], $this->plugin->events["forge"]["form"]["content"]["enchant"])));
        $player->sendForm($form);
    }

    private function generateEnchants(Item $toEnchant) : array {
		
		$swordEnch = $this->plugin->event->getNested("forge.enchants.sword");
        $armorEnch = $this->plugin->event->getNested("forge.enchants.armor");
        $toolEnch = $this->plugin->event->getNested("forge.enchants.tool");
        $bowEnch = $this->plugin->event->getNested("forge.enchants.bow");
		
		$levelsub = mt_rand(0, 3);
		switch($levelsub) {
			case 0:
				$levelSub = 0.20;
			break;
			case 1:
				$levelSub = 0.40;
			break;
			case 2:
				$levelSub = 0.70;
			break;
			case 3:
				$levelSub = 1;
			break;
			default:
				$levelSub = 0.20;
		}
		
        if($toEnchant instanceof Sword) {
			$enchh = $swordEnch[array_rand($swordEnch)];
            $firstEnch = explode(":", $enchh);
            $secondEnch = explode(":", $enchh);
            $thirdEnch = explode(":", $enchh);           
        } else if($toEnchant instanceof Bow) {
			$enchh = $bowEnch[array_rand($bowEnch)];
            $firstEnch = explode(":", $enchh);
            $secondEnch = explode(":", $enchh);
            $thirdEnch = explode(":", $enchh);
        } else if($toEnchant instanceof Tool) {
			$enchh = $toolEnch[array_rand($toolEnch)];
            $firstEnch = explode(":", $enchh);
            $secondEnch = explode(":", $enchh);
            $thirdEnch = explode(":", $enchh);
        } else if($toEnchant instanceof Armor) {
			$enchh = $armorEnch[array_rand($armorEnch)];
            $firstEnch = explode(":", $enchh);
            $secondEnch = explode(":", $enchh);
            $thirdEnch = explode(":", $enchh);
        } else {
            $enchants = [];
        }
		
		$enchants = [
            0 => $firstEnch[0].":".rand(1, intval($firstEnch[1] * ($levelSub - 0.15))).":".rand(intval(2 * ($levelSub + 1)), intval(6 * ($levelSub + 1))),
            1 => $secondEnch[0].":".rand(1, intval($secondEnch[1] * ($levelSub - 0.10))).":".rand(intval(6 * ($levelSub + 1)), intval(10 * ($levelSub + 1))),
			2 => $thirdEnch[0].":".rand(2, intval($thirdEnch[1] * ($levelSub))).":".rand(intval(10 * ($levelSub + 1)), intval(15 * ($levelSub + 1)))
		];
			
        return $enchants;
    }
	
	private function setEnchant($arr, $player, $toEnchant) {
		if($player->getXpLevel() < $arr[2]) {
			$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["enchant"]["level"]));
            return;
        } else {
			$ench = Enchantment::getEnchantmentByName($arr[0]);
            if($toEnchant->getEnchantment($ench->getId())) {
				$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["enchant"]["same"]));
				return;
			}
			
			if (($limit = $this->plugin->events["forge"]["int"]["limit"]["enchant"]) !== -1 && count($player->getInventory()->getItemInHand()->getEnchantments()) >= $limit) {
				$player->sendMessage(str_replace(["{root}", "{limit}"], [$this->plugin->events["forge"]["root"], (string)$limit], $this->plugin->events["forge"]["text"]["enchant"]["limit"]));
				return;
			}
							
            if($toEnchant->getId() !== $player->getInventory()->getItemInHand()->getId()){
				$player->sendMessage(str_replace("{root}", $this->plugin->events["forge"]["root"], $this->plugin->events["forge"]["text"]["enchant"]["dupe"]));
                return;
			}
			
            $player->setXpLevel($player->getXpLevel() - $arr[2]);
            $level = $arr[1];
            if($level <= 0) {
				$level = 1;
            }
                            
			$toEnchant->addEnchantment(new EnchantmentInstance($ench, (int) $level));
            $player->getInventory()->setItemInHand($toEnchant);
			$player->sendMessage(str_replace(["{root}", "{item}"], [$this->plugin->events["forge"]["root"], $toEnchant->getName()], $this->plugin->events["forge"]["text"]["enchant"]["success"]));
		}
	}

}