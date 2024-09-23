<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use pcp\libs\forms\{SimpleForm, CustomForm};

class StatsCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["stats"]["desc"]);
	}
	
	public function getStatsConfig(string $string){
		return $this->plugin->cmd->getNested($string);
	}
	
	public function getStatsMsg($string){
		return str_replace("{prefix}", $this->getStatsConfig("stats.prefix"), $this->getStatsConfig($string));
	}
	
	public function getScoreboardMsg($string){
		return str_replace("{prefix}", $this->plugin->cmds["scoreboard"]["prefix"], $this->plugin->cmd->getNested($string));
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
        if(empty($args[0])){
			$this->selfForm($sender);
		} else {
			$name = strtolower($args[0]);
			$player = $this->plugin->getServer()->getPlayer($name);
			if(is_null($player)){
				$sender->sendMessage($this->getStatsMsg("utils.text.player"));
			} else {
				$this->viewForm($sender, $player);
				$player->sendMessage(str_replace("{player}", $sender->getName(), $this->getStatsMsg("stats.text.view")));
			}
		}
		return true;
	}

	public function selfForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->bioForm($sender);
				break;
				case 1:
					$this->rolesForm($sender);
				break;
				case 2:
					if($this->plugin->events["scoreboard"]["enable"]["self"] == true){
						if(!isset($this->plugin->disableQuickboard[$sender->getName()])){
							$this->plugin->disableQuickboard[$sender->getName()] = 1;
							$sender->sendMessage(str_replace("{status}", "§cDisabled", $this->getStatsMsg("stats.text.sb")));
						} else {
							unset($this->plugin->disableQuickboard[$sender->getName()]);
							$sender->sendMessage(str_replace("{status}", "§aEnabled", $this->getStatsMsg("stats.text.sb")));
						}
					} else {
						$sender->sendMessage($this->getScoreboardMsg("utils.text.disable"));
					}
				break;
				case 3:
					$this->iconForm($sender);
				break;
			}
		});
		$form->setTitle(str_replace("{player}", $sender->getName(), $this->getStatsConfig("stats.title")));
		$form->setContent($this->plugin->placeholder->getNormalTags($sender, implode("\n", $this->getStatsConfig("stats.format"))));
		foreach($this->getStatsConfig("stats.menu.buttons") as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$sender->sendForm($form);
	}
	
	public function viewForm($sender, $player){
		$form = new SimpleForm(function (Player $sender, $data){ 
			if(is_null($data)) return true;
		});
		$form->setTitle(str_replace("{player}", $player->getName(), $this->getStatsConfig("stats.title")));
		$form->setContent($this->plugin->placeholder->getNormalTags($player, implode("\n", $this->getStatsConfig("stats.format"))));
		$form->addButton(str_replace("{player}", $player->getName(), $this->plugin->cmds["stats"]["playericon"][0]), $this->plugin->cmds["stats"]["playericon"][1], str_replace("{icon}", Core::getInstance()->getStats()->getAlpha($player, "icon"), $this->plugin->cmds["stats"]["playericon"][2]));
		$sender->sendForm($form);
	}
	
	public function bioForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if(isset($data[0])){
				$pref = $data[0];
				if(strlen($pref) > 0 ){
                    if(strlen($pref) > $this->getStatsConfig("stats.menu.bio.limit")){
                        $sender->sendMessage(str_replace("{limit}", $this->getStatsConfig("stats.menu.bio.limit"), $this->getStatsMsg("stats.text.limit")));
                    } else {
						if(preg_match('~^[\p{L}\p{N}\s]+$~uD', $pref)){
							Core::getInstance()->getStats()->setAlpha($sender, "bio", $pref);
							$sender->sendMessage(str_replace("{bio}", $pref, $this->getStatsMsg("stats.text.bio")));
						} else {
							$sender->sendMessage($this->getStatsMsg("stats.text.letter"));
						}
                    }
				} 			}
		});
		$form->setTitle($this->getStatsConfig("stats.edittitle"));
		$form->addInput(str_replace("{limit}", $this->getStatsConfig("stats.menu.bio.input"), $this->getStatsConfig("stats.menu.bio.place")));
		$sender->sendForm($form);
	}
	
	public function rolesForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if (isset($data[1])){
                $button = $data[1];
				$role = $this->plugin->cmds["stats"]["roles"][ $button ];
				switch($data[2]) {
					case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7: case 8: case 9:
						$role = "§" . $data[2] . $role;
					break;
					case 10:
						$role = "§a" . $role;
					break;
					case 11:
						$role = "§b" . $role;
					break;
					case 12:
						$role = "§c" . $role;
					break;
					case 13:
						$role = "§d" . $role;
					break;
					case 14:
						$role = "§e" . $role;
					break;
					case 15:
						$role = "§f" . $role;
					break;
				}
				Core::getInstance()->getStats()->setAlpha($sender, "role.display", $role);
				$sender->sendMessage(str_replace("{role}", $role, $this->getStatsMsg("stats.text.role")));
                return true;
			}
		});
		$form->setTitle($this->getStatsConfig("stats.edittitle"));
		$form->addLabel($this->getStatsConfig("stats.menu.roles.label"));//data 0
		$form->addDropdown($this->getStatsConfig("stats.menu.roles.dropdown"), $this->getStatsConfig("stats.roles"), 0); //data 1
		$form->addSlider($this->getStatsConfig("stats.menu.roles.slider"), 0, 15, -1, 0); //data 2
        $sender->sendForm($form);
	}
	
	public function iconForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return false;
			$icon = $this->plugin->cmds["stats"]["icons"][$data[1]];
			Core::getInstance()->getStats()->setAlpha($sender, "icon", "textures/ui/".$icon);
			$sender->sendMessage(str_replace("{icon}", $icon, $this->getStatsMsg("stats.text.icon")));
		});
		$form->setTitle($this->getStatsConfig("stats.edittitle"));
		$form->addLabel($this->getStatsConfig("stats.menu.icon.label"));
		$form->addDropdown($this->getStatsConfig("stats.menu.icon.dropdown"), $this->getStatsConfig("stats.icons"));
		$sender->sendForm($form);
	}
}
