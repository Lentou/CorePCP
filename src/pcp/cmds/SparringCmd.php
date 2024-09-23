<?php

namespace pcp\cmds;

use pocketmine\command\{PluginCommand, CommandSender, ConsoleCommandSender};
use pocketmine\Player;
use pocketmine\Server;
use pcp\Core;
use pcp\libs\forms\SimpleForm;
use pocketmine\level\Position;
use pcp\player\Chaser;


class SparringCmd extends PluginCommand{

    /** @var Core */
    private $plugin;

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->sparring["text"]["desc"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if ($sender instanceof Player) {
			if($this->plugin->sparring["enable"] === true){
				if(in_array($sender->getLevel()->getName(), $this->plugin->sparring["text"]["worlds"])){
					$this->sparringCategoryForm($sender);
				} else {
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["notallowed"]));
				}
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["disable"]));
			}
		}
        return true;
    }
	
	public function sparringCategoryForm($sender) {
		$form = new SimpleForm(function ($sender, $data){
			if(is_null($data)) return;
			if($this->plugin->sparring["list"]["$data"]["enable"]) {
				$this->sparringSelectForm($sender, $data);
			} else {
				$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->sparring["text"]["prefix"], "Category"], $this->plugin->sparring["text"]["text"]["close"]));
			}
		});
		$form->setTitle($this->plugin->sparring["form"]["title"]);
		foreach ($this->plugin->sparring["list"] as $category => $name) {
			if ($name["enable"]) {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/online", $category);
			} else {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/offline", $category);
			}
        }
		$sender->sendForm($form);
	}
	
	public function sparringSelectForm($sender, $category) {
		$form = new SimpleForm(function ($sender, $data) use ($category) {
			if(is_null($data)) return;
			$this->sparringDestinationForm($sender, $category, $data);
		});
		$form->setTitle($this->plugin->sparring["list"]["$category"]["title"]);
		foreach ($this->plugin->sparring["list"]["$category"]["sparring"] as $arena => $name) {
			if ($name["enable"]) {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/online", $arena);
			} else {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/offline", $arena);
			}
        }
		$sender->sendForm($form);
	}
	
	public function sparringDestinationForm($sender, $category, $arena) {
		$form = new SimpleForm(function ($sender, $data) use ($category, $arena) {
            if (is_null($data)) return true;
            switch ($data) {
                case 0:
					$level = $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][0];
					Server::getInstance()->loadLevel(strval($level));
					if(($level = Server::getInstance()->getLevelByName($level)) == null){
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->sparring["text"]["text"]["notexists"]));
						return false;
					}
					
					if ($this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["enable"] === false) {
						$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->sparring["text"]["prefix"], "Arena"], $this->plugin->sparring["text"]["text"]["close"]));
						return false;
					}
					
					if (!in_array(Chaser::getOs($sender), $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["os"])) {
						$sender->sendMessage(str_replace(["{prefix}", "{os}", "{needos}"], [$this->plugin->sparring["text"]["prefix"], Chaser::getOs($sender), implode(", ", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["os"])], $this->plugin->sparring["text"]["text"]["os"]));
						return false;
					}
					
					if($this->plugin->cx->data->getVal($sender, 'level') < $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["level"]){
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->sparring["text"]["prefix"], $this->plugin->sparring["text"]["text"]["level"]));
						return false;
					}
						
					if($this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][1] == true){
						$tp = $level->getSpawnLocation();
					} else {
						$x = $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][2];
						$y = $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][3];
						$z = $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][4];
									
						$tp = new Position($x, $y, $z, $level);
					}
					
					$sender->teleport($tp);
					
					Core::getInstance()->showScreenAnimate($sender, mt_rand(1, 30));
					$sender->sendTitle(
						str_replace("{arena}", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"], $this->plugin->sparring["text"]["text"]["title"][0]), 
						str_replace("{arena}", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"], $this->plugin->sparring["text"]["text"]["title"][1])
					);
					
					Chaser::setSparring($sender, $category, $arena);
					
					if (in_array($arena, $this->plugin->sparring["kits"]["$category"]["arenas"]) and ($sender->getLevel()->getName() === $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["world"][0])) {
						$sender->getInventory()->clearAll();
						$sender->getArmorInventory()->clearAll();
						$sender->removeAllEffects();
						Core::getInstance()->getKit()->giveKit($sender, $this->plugin->sparring["kits"]["$category"]["kits"]);
					}
					
				break;
            }
        });
        $form->setTitle($this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"]);
        $form->setContent(str_replace(
			["{name}", "{player}", "{bool}", "{level}", "{needos}"], 
			[$this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"], $sender->getName(), ($this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["enable"] ? "§aYES" : "§cNO"), (string)$this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["level"], implode(", ", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["os"])], 
			implode("\n", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["label"]))
		);
		if ($this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["enable"]) {
			$form->addButton(str_replace("{name}", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"], $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["button"]), 0, "textures/ui/online");
		} else {
			$form->addButton(str_replace("{name}", $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["name"], $this->plugin->sparring["list"]["$category"]["sparring"]["$arena"]["button"]), 0, "textures/ui/offline");
		}
        $sender->sendForm($form);
	}
	
}
