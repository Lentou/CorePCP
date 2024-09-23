<?php

namespace pcp\cmds;

use pocketmine\command\{PluginCommand, CommandSender, ConsoleCommandSender};
use pocketmine\Player;
use pocketmine\Server;
use pcp\Core;
use pcp\libs\forms\SimpleForm;
use pocketmine\level\Position;

class DistrictCmd extends PluginCommand{

    /** @var Core */
    private $plugin;

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->distr["district"]["desc"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if ($sender instanceof Player) {
			if(!empty($args[0])){
				if(array_key_exists(strtolower($args[0]), $this->plugin->distr["list"])){
					$this->districtForm($sender, strtolower($args[0]));
				} else {
					$sender->sendMessage(str_replace(["{prefix}", "{type}"], [$this->plugin->distr["district"]["prefix"], strtolower($args[0])], $this->plugin->distr["district"]["text"]["exists"]));
				}
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->distr["district"]["prefix"], $this->plugin->distr["district"]["text"]["usage"]));
			}
		}
        return true;
    }
	
	public function districtForm($sender, $button){
		$form = new SimpleForm(function (Player $sender, $data) use ($button) {
            if (is_null($data)) return true;
            switch ($data) {
                case 0:
					$level = $this->plugin->distr["list"]["$button"]["teleport"][0];
					Server::getInstance()->loadLevel(strval($level));
					if(($level = Server::getInstance()->getLevelByName($level)) == null){
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->distr["district"]["prefix"], $this->plugin->distr["district"]["text"]["notexists"]));
						return false;
					}
						
					if($this->plugin->distr["list"]["$button"]["teleport"][1] == true){
						$tp = $level->getSpawnLocation();
					} else {
						$x = $this->plugin->distr["list"]["$button"]["teleport"][0];
						$y = $this->plugin->distr["list"]["$button"]["teleport"][1];
						$z = $this->plugin->distr["list"]["$button"]["teleport"][2];
									
						$tp = new Position($x, $y, $z, $level);
					}
					
					$sender->teleport($tp);
					
					Core::getInstance()->showScreenAnimate($sender, mt_rand(1, 30));
					$sender->sendTitle($this->plugin->distr["list"]["$button"]["header"][0], $this->plugin->distr["list"]["$button"]["header"][1]);
				break;
            }
        });
        $form->setTitle($this->plugin->distr["list"]["$button"]["title"]);
        $form->setContent(implode("\n", $this->plugin->distr["list"]["$button"]["content"]));
        $form->addButton(
			$this->plugin->distr["list"]["$button"]["button"][0],
			$this->plugin->distr["list"]["$button"]["button"][1],
			$this->plugin->distr["list"]["$button"]["button"][2]
		);
        $form->sendToPlayer($sender);
	}
	
}
