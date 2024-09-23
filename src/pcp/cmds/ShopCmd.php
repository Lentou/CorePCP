<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\libs\forms\{SimpleForm, CustomForm, ModalForm};
use pocketmine\level\Position;

use pocketmine\command\{Command, CommandSender, PluginCommand, ConsoleCommandSender};
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use _64FF00\PurePerms\PurePerms;

class ShopCmd extends PluginCommand {
  
	/** @var Core */
	private $plugin;
	
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->shops["text"]["desc"]);
	}
	
	public function getShopConfig(string $string){
		return $this->plugin->shop->getNested($string);
	}
	
	public function getShopMsg(string $string){
		return str_replace("{prefix}", $this->plugin->shops["text"]["prefix"], (string)$this->getShopConfig($string));
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if($this->getShopConfig("text.enable") === true){
			if($sender instanceof Player){
				$this->menuForm($sender);
			} else {
				if(empty($args[0]) or empty($args[1])){
					$sender->sendMessage($this->getShopMsg("text.shop.usage"));
				} else {
					$othershop = strtolower($args[0]);
					$target = Server::getInstance()->getPlayer($args[1]);
					if (($target instanceof Player) and ($target->isOnline())){
						if($this->getShopConfig("shop." . $othershop) !== null){
							$this->checkPerms($target, $othershop);
						} else {
							$this->checkPerms($target, "default");
						}
					}
				}
			}
		} else {
			$sender->sendMessage($this->getShopMsg("text.shop.disable"));
		}
		return true;
	}
	
	public function worldTp(Player $sender, string $shop){
		$level = $this->getShopConfig("teleport.$shop")[3];
		Server::getInstance()->loadLevel($level);
		if(($level = Server::getInstance()->getLevelByName($level)) == null){
			$sender->sendMessage($this->getShopMsg("text.shop.store"));
			return false;
		}
		$x = $this->getShopConfig("teleport.$shop")[0];
		$y = $this->getShopConfig("teleport.$shop")[1];
		$z = $this->getShopConfig("teleport.$shop")[2];
									
		$sender->teleport(new Position($x, $y, $z, $level));
		$sender->sendMessage(str_replace("{shop}", $shop, $this->getShopMsg("text.shop.teleport")));
	}
	
	public function checkPerms(Player $sender, string $shop){
		if($this->getShopConfig("shop.".$shop.".perm") !== null){
			if($sender->hasPermission($this->getShopConfig("shop.".$shop.".perm"))){
				$this->mainForm($sender, $shop);
			} else {
				$sender->sendMessage($this->getShopMsg("text.shop.perm"));
			}
		} else {
			$this->mainForm($sender, $shop);
		}
	}
	
	public function menuForm(Player $sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if (is_null($data)) return;
			$this->worldTp($sender, $data);
		});
		$form->setTitle($this->getShopConfig("form.title"));
		$form->setContent(implode("\n", str_replace("{player}", $sender->getName(), $this->getShopConfig("form.menu"))));
		foreach($this->getShopConfig("shop") as $shopee => $shop){
			if(isset($shop['image'])){
				if (filter_var($shop['image'], FILTER_VALIDATE_URL)){
					$form->addButton($shop['title'], SimpleForm::IMAGE_TYPE_URL, $shop['image'], $shopee);
				} else {
					$form->addButton($shop['title'], SimpleForm::IMAGE_TYPE_PATH, $shop['image'], $shopee);
				}
			} else {
				$form->addButton($shop['title'], -1, "", $shopee);
			}
		}
		$sender->sendForm($form);
	}
	
	public function mainForm(Player $sender, string $shop) {
		$form = new SimpleForm(function (Player $sender, $data) use ($shop) {
			if (is_null($data)) return;
			$this->categoryForm($sender, $data, $shop);
		});
		$form->setTitle($this->getShopConfig("shop." . $shop . ".title"));
		foreach($this->getShopConfig("shop." . $shop . ".category") as $category => $name){
			if(isset($name['image'])){
				if (filter_var($name['image'], FILTER_VALIDATE_URL)){
					$form->addButton($name['name'], SimpleForm::IMAGE_TYPE_URL, $name['image'], $category);
				} else {
					$form->addButton($name['name'], SimpleForm::IMAGE_TYPE_PATH, $name['image'], $category);
				}
			} else {
				$form->addButton($name['name'], -1, "", $category);
			}
		}
		$sender->sendForm($form);
	}
	
	public function categoryForm(Player $sender, string $category, string $shop){
		$form = new SimpleForm(function (Player $player, $data) use ($category, $shop) {
            if (is_null($data)) return;

            $itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[(string)$data];

            if (isset($itemConfig['sell']) && !isset($itemConfig['buy']) && !isset($itemConfig['perm'])) {
                $this->sellForm($player, $category, $data, $shop);
            } elseif (!isset($itemConfig['sell']) && isset($itemConfig['buy']) && !isset($itemConfig['perm'])) {
                $this->buyForm($player, $category, $data, $shop);
			} elseif (!isset($itemConfig['sell']) && isset($itemConfig['buy']) && isset($itemConfig['perm']) && isset($itemConfig['type'])) {
				$this->cmdForm($player, $category, $data, $shop);
            } else {
                $this->buyAndSellForm($player, $category, $data, $shop);
            }
			
        });
        $form->setTitle($this->getShopConfig("shop." . $shop . ".category." . $category . ".name"));
		foreach($this->getShopConfig("shop." . $shop . ".category." . $category . ".items") as $index => $item){
			if (isset($item['image'])){
				if(filter_var($item['image'], FILTER_VALIDATE_URL)){
					$form->addButton($item['name'], SimpleForm::IMAGE_TYPE_URL, $item['image'], (string)$index);
				} else {
					$form->addButton($item['name'], SimpleForm::IMAGE_TYPE_PATH, $item['image'], (string)$index);
				}
			} else {
				$form->addButton($item['name'], -1, "", (string)$index);
			}
		}
		$sender->sendForm($form);
	}
	
	public function cmdForm(Player $sender, string $category, $index, string $shop){
		$form = new ModalForm(function (Player $sender, $data) use ($category, $index, $shop){
			if(is_null($data)){
				$this->categoryForm($sender, $category, $shop);
                return;
			}
			if($data == true){
				$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
				switch($itemConfig['type']){
					case "gems":
						$money = Core::getInstance()->cx->data->getVal($sender, "gems");
					break;
					case "balance":
						$money = Core::getInstance()->getStats()->getPoints($sender, "balance");
					break;
				}
				$cost = $itemConfig['buy'];
				if($money < $cost){
					$sender->sendMessage(str_replace("{value}", "balance", $this->getShopMsg("text.shop.invalid")));
					return;
				}
				// receipt, to check if inventory has an space, sometimes im using /give
				$item = Item::get(339, 0, 1);
				if (!$sender->getInventory()->canAddItem($item)){
					$sender->sendMessage($this->getShopMsg("text.shop.invfull"));
					return;
				}
				
				if ($sender->hasPermission($itemConfig['perm'])){
					$sender->sendMessage(str_replace("{item}", $itemConfig['name'], $this->getShopMsg("text.shop.already")));
					return;
				}
				
				$this->calcCmdValue($sender, $cost, $itemConfig['type']);
				
				foreach ($itemConfig['cmds'] as $cmds) {
					$cmds = str_replace('{player}', '"' . $sender->getName() . '"', $cmds);
					Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmds);
				}
				
				PurePerms::getInstance()->getPlayerManager()->setPermission($sender, $itemConfig['perm']);
				PurePerms::getInstance()->updatePermissions($sender);
				$sender->sendMessage(str_replace(["{status}", "{item}", "{quantity}", "{cost}"], ["bought", $itemConfig['name'], 1, $cost], $this->getShopMsg("text.shop.status")));
				
			} else {
				$this->categoryForm($sender, $category, $shop);
			}
		});
		$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
		
		$form->setTitle($itemConfig['name']);
		$form->setContent(implode("\n", str_replace(["{cost}", "{name}"], [$itemConfig['buy'], $itemConfig['name']], $this->getShopConfig("form.cmd"))));
		$form->setButton1($this->plugin->cmds["utils"]["buttons"]["confirm"][0]);
		$form->setButton2($this->plugin->cmds["utils"]["buttons"]["cancel"][0]);
		$sender->sendForm($form);
	}
	
	public function calcCmdValue(Player $sender, $cost, string $type){
		switch($type){
			case "gems":
				Core::getInstance()->cx->data->takeGem($sender, $cost);
			break;
			case "balance":
				Core::getInstance()->getStats()->setPoints($sender, "balance", "-", $cost);
			break;
		}
	}
	
	public function buyForm(Player $sender, string $category, $index, string $shop){
		$form = new CustomForm(function (Player $sender, $data) use ($category, $index, $shop) {

            if (is_null($data)) {
                $this->categoryForm($sender, $category, $shop);
                return;
            }

            $itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];

            $money = Core::getInstance()->getStats()->getPoints($sender, "balance");
			$cost = ($itemConfig['buy'] * $data[1]);

            if ($money < $cost) {
                $sender->sendMessage(str_replace("{value}", "balance", $this->getShopMsg("text.shop.invalid")));
                return;
            }

            $itemIdMeta = explode(":", $itemConfig['id']);
            $item = Item::get((int)$itemIdMeta[0], (int)$itemIdMeta[1], (int)$data[1]);
            if (!$sender->getInventory()->canAddItem($item)) {
                $sender->sendMessage($this->getShopMsg("text.shop.invfull"));
                return;
            }

            $sender->getInventory()->addItem($item);
			Core::getInstance()->getStats()->setPoints($sender, "balance", "-", $cost);
			$sender->sendMessage(str_replace(["{status}", "{item}", "{quantity}", "{cost}"], ["bought", $itemConfig['name'], (int)$data[1], $cost], $this->getShopMsg("text.shop.status")));
			
        });

		$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
        $money = Core::getInstance()->getStats()->myMoney($sender);

        $form->setTitle($itemConfig['name']);
		$form->addLabel(implode("\n", str_replace(["{cost}", "{name}"], [$itemConfig['buy'], $itemConfig['name']], $this->getShopConfig("form.buy"))));
        $form->addSlider($this->getShopConfig("form.slider"), 0, $itemConfig['quantity'], 1, 1);
        $sender->sendForm($form);
	}
	
	public function sellForm(Player $sender, string $category, $index, string $shop){
		$form = new CustomForm(function (Player $sender, $data) use ($category, $index, $shop) {
			
            if (is_null($data)) {
                $this->categoryForm($sender, $category, $shop);
                return;
            }

			$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
            $itemIdMeta = explode(":", $itemConfig['id']);
            $item = Item::get((int)$itemIdMeta[0], (int)$itemIdMeta[1], (int)$data[1]);

            if (!$sender->getInventory()->contains($item)) {
				$sender->sendMessage(str_replace("{value}", "item", $this->getShopMsg("text.shop.invalid")));
                return;
            }
			
			$cost = ($data[1] * $itemConfig['sell']);
			Core::getInstance()->getStats()->setPoints($sender, "balance", "+", $cost);
            $sender->getInventory()->removeItem($item);
			$sender->sendMessage(str_replace(["{status}", "{item}", "{quantity}", "{cost}"], ["sell", $itemConfig['name'], (int)$data[1], $cost], $this->getShopMsg("text.shop.status")));
        });

		$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
		
        $itemIdMeta = explode(":", $itemConfig['id']);
        $item = Item::get((int)$itemIdMeta[0], (int)$itemIdMeta[1]);

        $form->setTitle($itemConfig['name']);
		$form->addLabel(implode("\n", str_replace(["{cost}", "{name}"], [$itemConfig['sell'], $itemConfig['name']], $this->getShopConfig("form.sell"))));
        $form->addSlider($this->getShopConfig("form.slider"), 0, $this->getItemInInventory($sender, $item), 1, 1);
        $sender->sendForm($form);
	}
	
	public function buyAndSellForm(Player $sender, string $category, $index, string $shop){
		$form = new CustomForm(function (Player $sender, $data) use ($category, $index, $shop) {
            
            if (is_null($data)) {
                $this->categoryForm($sender, $category, $shop);
                return;
            }

			$itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];
            $itemIdMeta = explode(":", $itemConfig['id']);
            $item = Item::get((int)$itemIdMeta[0], (int)$itemIdMeta[1], (int)$data[2]);

            if (!$data[1]) {

                $money = Core::getInstance()->getStats()->getPoints($sender, "balance");

                if ($money < $itemConfig['buy'] * $data[2]) {
					$sender->sendMessage(str_replace("{value}", "balance", $this->getShopMsg("text.shop.invalid")));
                    return;
                }
				
                if (!$sender->getInventory()->canAddItem($item)) {
					$sender->sendMessage($this->getShopMsg("text.shop.invfull"));
                    return;
                }
				$cost = ($data[2] * $itemConfig['buy']);
                $sender->getInventory()->addItem($item);
				Core::getInstance()->getStats()->setPoints($sender, "balance", "-", $cost);
				$sender->sendMessage(str_replace(["{status}", "{item}", "{quantity}", "{cost}"], ["bought", $itemConfig['name'], (int)$data[1], $cost], $this->getShopMsg("text.shop.status")));
				
            } else {

                if (!$sender->getInventory()->contains($item)) {
					$sender->sendMessage(str_replace("{value}", "item", $this->getShopMsg("text.shop.invalid")));
                    return;
                }
				
				$cost = ($data[2] * $itemConfig['sell']);
                Core::getInstance()->getStats()->setPoints($sender, "balance", "+", $cost);
                $sender->getInventory()->removeItem($item);
				$sender->sendMessage(str_replace(["{status}", "{item}", "{quantity}", "{cost}"], ["sell", $itemConfig['name'], (int)$data[1], $cost], $this->getShopMsg("text.shop.status")));

            }
        });
        $itemConfig = $this->getShopConfig("shop." . $shop . ".category." . $category . ".items")[$index];

        $form->setTitle($itemConfig['name']);
		$form->addLabel(implode("\n", str_replace(["{buy}", "{sell}", "{name}"], [$itemConfig['buy'], $itemConfig['sell'], $itemConfig['name']], $this->getShopConfig("form.buysell"))));
        $form->addToggle($this->getShopConfig("form.toggle"), false); //1
        $form->addSlider($this->getShopConfig("form.slider"), 0, $itemConfig['quantity'], 1, 1); //2
        $sender->sendForm($form);
	}
	
	 private function getItemInInventory(Player $player, Item $item): int {
        $result = array_map(function (Item $invItem) use ($item) {
            if ($invItem->getId() === $item->getId() && $invItem->getDamage() === $item->getDamage()) {
                return $invItem->getCount();
            }
            return 0;
        }, $player->getInventory()->getContents());

        return array_sum($result);
    }
}