<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\libs\forms\SimpleForm;

use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KitCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->getKitCfg("text.desc"));
	}
  
	public function getKitCfg(string $string) {
		return $this->plugin->kit->getNested($string);
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		if($sender->isSurvival()){
			if(in_array($sender->getLevel()->getName(), $this->plugin->kits["text"]["worlds"])){
				$this->kitMenuForm($sender);
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->kits["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["notallowed"]));
			}
		} else {
			$sender->sendMessage(str_replace("{prefix}", $this->plugin->kits["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["survival"]));
		}
		return true;
	}
	
	public function kitMenuForm($sender) {
		$form = new SimpleForm(function ($sender, $data){
			if(is_null($data)) return;
			if(is_null($this->plugin->kits["kits"]["$data"]["kits"]) or empty($this->plugin->kits["kits"]["$data"]["kits"])){
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->kits["text"]["prefix"], $this->plugin->cmds["text"]["kit"]["empty"]));
			} else {
				$this->kitCategoryForm($sender, $data);
			}
		});
		$form->setTitle($this->plugin->kits["form"]["title"]);
        foreach ($this->getKitCfg("kits") as $category => $name) {
			if (isset($name['image'])) {
				if (filter_var($name["image"], FILTER_VALIDATE_URL)) {
                    $form->addButton($name["name"], SimpleForm::IMAGE_TYPE_URL, $name["image"], $category);
                } else {
                    $form->addButton($name["name"], SimpleForm::IMAGE_TYPE_PATH, $name["image"], $category);
                }
            } else {
                $form->addButton($name["name"], -1, "", $category);
            }
        }
		$sender->sendForm($form);
	}
	
	public function kitCategoryForm($sender, $category) {
		$form = new SimpleForm(function ($sender, $data) use ($category) {
            if (is_null($data)) return;
			$kit = $data;
			$this->kitSeeForm($sender, $kit, $category);
        });

        $form->setTitle($this->getKitCfg("kits." . $category . ".name"));
        foreach ($this->getKitCfg("kits." . $category . ".kits") as $index => $item) {
            if (isset($item["button"]["image"])) {
                if (filter_var($item["button"]["image"], FILTER_VALIDATE_URL)) {
                    $form->addButton($item["button"]["name"], SimpleForm::IMAGE_TYPE_URL, $item["button"]["image"], $index);
                } else {
                    $form->addButton($item["button"]["name"], SimpleForm::IMAGE_TYPE_PATH, $item["button"]["image"], $index);
                }
            } else {
                $form->addButton($item["button"]["name"], -1, "", $index);
            }
        }
        $sender->sendForm($form);
	}
	
	public function kitSeeForm($sender, $kit, $category){
		$form = new SimpleForm(function ($sender, $data) use ($kit, $category){
			if(is_null($data)) return;
			$this->checkKits($sender, $kit, $category);
		});
		$form->setTitle($this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["kit"]["name"]);
		$form->setContent(implode("\n", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["lore"]));
		$form->addButton($this->plugin->kits["form"]["button"]["get"][0], $this->plugin->kits["form"]["button"]["get"][1], $this->plugin->kits["form"]["button"]["get"][2]);
		$sender->sendForm($form);
	}
	
	public function checkKits($sender, $kit, $category) {
		if(array_key_exists("perm", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"])){
			if(!$sender->hasPermission($this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["perm"])){
				$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->kits["text"]["prefix"], "claim"], $this->plugin->kits["text"]["kit"]["perm"]));
				return;
			}
		}
		
		$chestkit = explode(":", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["kit"]["chest"]);
		$item = Item::get((int)$chestkit[0], (int)$chestkit[1]);
		$item->setCustomName($this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["kit"]["name"]);
		
		if(array_key_exists("lore", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"])){
			$item->setLore([implode("\n", $this->plugin->kits["kits"]["$category"]["kits"]["$kit"]["lore"])]);
		}

		$item->setNamedTagEntry(new StringTag("KitType", $category));
		$item->setNamedTagEntry(new StringTag("Kit", $kit));
		
		if (!$sender->getInventory()->canAddItem($item)) {
			$sender->sendMessage(str_replace("{prefix}", $this->plugin->kits["text"]["prefix"], $this->plugin->kits["text"]["kit"]["space"]));
			return;
		}
		
		$sender->getInventory()->addItem($item);
		$sender->sendMessage(str_replace(["{prefix}", "{kit}"], [$this->plugin->kits["text"]["prefix"], ucfirst($kit)], $this->plugin->kits["text"]["kit"]["claim"]));
	}
     
}
