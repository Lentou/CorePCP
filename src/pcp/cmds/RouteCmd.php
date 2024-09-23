<?php

namespace pcp\cmds;

use pocketmine\command\{PluginCommand, CommandSender, ConsoleCommandSender};
use pocketmine\Player;
use pocketmine\Server;
use pcp\Core;
use pcp\libs\forms\SimpleForm;
use pocketmine\level\Position;

class RouteCmd extends PluginCommand{

    /** @var Core */
    private $plugin;

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->routes["text"]["desc"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if ($sender instanceof Player) {
			if(in_array($sender->getLevel()->getName(), $this->plugin->routes["text"]["worlds"])){
				$this->routeCategoryForm($sender);
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->routes["text"]["prefix"], $this->plugin->cmds["utils"]["text"]["notallowed"]));
			}
		}
        return true;
    }
	
	public function routeCategoryForm($sender) {
		$form = new SimpleForm(function ($sender, $data){
			if(is_null($data)) return;
			if($this->plugin->routes["list"]["$data"]["enable"]) {
				$this->routeSelectForm($sender, $data);
			} else {
				$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->routes["text"]["prefix"], "Category"], $this->plugin->routes["text"]["text"]["close"]));
			}
		});
		$form->setTitle($this->plugin->routes["form"]["title"]);
		foreach ($this->plugin->routes["list"] as $category => $name) {
			if ($name["enable"]) {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/online", $category);
			} else {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/offline", $category);
			}
        }
		$sender->sendForm($form);
	}
	
	public function routeSelectForm($sender, $category) {
		$form = new SimpleForm(function ($sender, $data) use ($category) {
			if(is_null($data)) return;
			$this->routeDestinationForm($sender, $category, $data);
		});
		$form->setTitle($this->plugin->routes["list"]["$category"]["title"]);
		foreach ($this->plugin->routes["list"]["$category"]["dungeons"] as $dungeons => $name) {
			if ($name["enable"]) {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/online", $dungeons);
			} else {
				$form->addButton(str_replace("{name}", $name["name"], $name["button"]), 0, "textures/ui/offline", $dungeons);
			}
        }
		$sender->sendForm($form);
	}
	
	public function routeDestinationForm($sender, $category, $route) {
		$form = new SimpleForm(function ($sender, $data) use ($category, $route) {
            if (is_null($data)) return true;
            switch ($data) {
                case 0:
					$level = $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["world"][0];
					Server::getInstance()->loadLevel(strval($level));
					if(($level = Server::getInstance()->getLevelByName($level)) == null){
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->routes["text"]["prefix"], $this->plugin->routes["text"]["text"]["notexists"]));
						return false;
					}
					
					if ($this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["enable"] === false) {
						$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->routes["text"]["prefix"], "Instance"], $this->plugin->routes["text"]["text"]["close"]));
						return false;
					}
					
					if($this->plugin->cx->data->getVal($sender, 'level') < $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["level"]){
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->routes["text"]["prefix"], $this->plugin->routes["text"]["text"]["level"]));
						return false;
					}
						
					if($this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["world"][1] == true){
						$tp = $level->getSpawnLocation();
					} else {
						$x = $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["world"][2];
						$y = $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["world"][3];
						$z = $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["world"][4];
									
						$tp = new Position($x, $y, $z, $level);
					}
					
					$sender->teleport($tp);
					
					Core::getInstance()->showScreenAnimate($sender, mt_rand(1, 30));
					$sender->sendTitle(
						str_replace("{dungeon}", $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"], $this->plugin->routes["text"]["text"]["title"][0]), 
						str_replace("{dungeon}", $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"], $this->plugin->routes["text"]["text"]["title"][1])
					);
				break;
            }
        });
        $form->setTitle($this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"]);
        $form->setContent(str_replace(
			["{name}", "{player}", "{bool}", "{level}"], 
			[$this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"], $sender->getName(), ($this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["enable"] ? "§aYES" : "§cNO"), (string)$this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["level"]], 
			implode("\n", $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["label"]))
		);
		if ($this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["enable"]) {
			$form->addButton(str_replace("{name}", $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"], $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["button"]), 0, "textures/ui/online");
		} else {
			$form->addButton(str_replace("{name}", $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["name"], $this->plugin->routes["list"]["$category"]["dungeons"]["$route"]["button"]), 0, "textures/ui/offline");
		}
        $sender->sendForm($form);
	}
	
}
