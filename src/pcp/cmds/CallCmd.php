<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\utils\Chaser;
use pcp\libs\forms\{SimpleForm, CustomForm};

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat as TF;

class CallCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["call"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
        
		if($sender->hasPermission($this->plugin->cmds["call"]["perm"]["main"])){
			$this->callForm($sender);
		} else {
			$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
		}
		
		return true;
	}

	public function callForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if($data === null){ return true; }
			switch($data){
				case 0: # CURE
					//$sender->setHealth($sender->getMaxHealth());
					$tag = $sender->namedtag->getTag('Attributes');
					$tag->setFloat('CurrentHP', $tag->getFloat('MaximumHP'));
					$sender->setFood(20);
					$sender->setSaturation(20);
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["call"]["text"]["cure"]));
				break;
				case 1: # CLEANSE
					$sender->removeAllEffects();
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["call"]["text"]["cleanse"]));
				break;
				case 2: # FLY
					if($sender->isSurvival()){
						$sender->setAllowFlight($sender->getAllowFlight() ? false : true);
						$sender->setFlying($sender->getAllowFlight() ? false : true);
						$sender->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["call"]["prefix"], ($sender->getAllowFlight() ? "Enable" : "Disable")], $this->plugin->cmds["call"]["text"]["fly"]));
					} else {
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["utils"]["text"]["survival"]));
					}
				break;
				case 3: # SIZE
					$this->sizeForm($sender);
				break;
				case 4:
					$this->tagForm($sender);
				break;
				case 5: # TIME
					if($sender->hasPermission($this->plugin->cmds["call"]["perm"]["time"])){
						$this->timeForm($sender);
					} else {
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
					}
				break;
				case 6:
					if($sender->hasPermission($this->plugin->cmds["call"]["perm"]["unli"])){
						if(Chaser::isUnliModePlayer($sender)){
							Chaser::initUnliModePlayer($sender, false);
							$string = str_replace("{status}", "§cDeactivated", $this->plugin->cmds["call"]["text"]["unli"]);
						} else {
							Chaser::initUnliModePlayer($sender, true);
							$string = str_replace("{status}", "§aActivated", $this->plugin->cmds["call"]["text"]["unli"]);
						}
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $string));
					} else {
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
					}
				break;
			}
		});
		$form->setTitle($this->plugin->cmds["call"]["title"]);
		$form->setContent(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->cmds["call"]["content"])));
		foreach($this->plugin->cmds["call"]["buttons"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$form->sendToPlayer($sender);
	}
	
	public function sizeForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if(is_null($data)){
				return false;
			}
			if(!is_numeric($data[0])) {
				$sender->sendMessage("It must be numeric dumb!");
				return false;
			}
			if($data[0] > 5 or $data[0] <= 0) {
				$sender->sendMessage("This size must not bigger than 5 or lower than 1");
				return false;
			}
			$sender->setScale((float)$data[0]);
			$sender->sendMessage(str_replace(["{prefix}", "{size}"], [$this->plugin->cmds["call"]["prefix"], $sender->getScale()], $this->plugin->cmds["call"]["text"]["size"]));
		});
		$form->setTitle($this->plugin->cmds["call"]["title"]);
		$form->addInput($this->plugin->cmds["call"]["size"], "Input Here: reset or number below 14");
		$form->sendToPlayer($sender);
	}

	public function tagForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if(is_null($data)) return true;
				
			if($data[0] === true){
				Core::getInstance()->getStats()->setAlpha($sender, "tag", $sender->getName());
				$sender->setDisplayName(Core::getInstance()->getStats()->getAlpha($sender, "tag"));
				$sender->sendMessage(str_replace("{prefix}", (string)$this->plugin->cmds["call"]["prefix"], (string)$this->plugin->cmds["call"]["text"]["tag"]["reset"]));
				return true;
			}
				
			if(!empty($data[1])){
				if(strlen($data[1]) > 20){
					$sender->sendMessage(str_replace(["{prefix}", "{limit}"], [(string)$this->plugin->cmds["call"]["prefix"], (string)$this->plugin->cmds["call"]["limit"]], $this->plugin->cmds["call"]["text"]["tag"]["limit"]));
					return false;
				} else {
					Core::getInstance()->getStats()->setAlpha($sender, "tag", "§f#".$data[1]);
					$sender->setDisplayName(Core::getInstance()->getStats()->getAlpha($sender, "tag"));
					$sender->sendMessage(str_replace(["{prefix}", "{tag}"], [(string)$this->plugin->cmds["call"]["prefix"], (string)$data[1]], $this->plugin->cmds["call"]["text"]["tag"]["success"]));
					return true;
				}
			}
		});
		$form->setTitle($this->plugin->cmds["call"]["title"]);
		$form->addToggle($this->plugin->cmds["call"]["toggle"], false); // data 0
		$form->addInput(str_replace("{limit}", (string)$this->plugin->cmds["call"]["limit"], (string)$this->plugin->cmds["call"]["input"][0]), $this->plugin->cmds["call"]["input"][1]); // data 1
		$form->sendToPlayer($sender);
	}
	
	public function timeForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)){
				return true;
			}
			switch($data){
				case 0: //time_1sunrise
					$sender->getLevel()->setTime(Level::TIME_SUNRISE);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Sunrise"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
				case 1: //time_2day
					$sender->getLevel()->setTime(Level::TIME_DAY);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Day"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
				case 2: //time_3noon
					$sender->getLevel()->setTime(Level::TIME_NOON);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Noon"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
				case 3: //time_4sunset
					$sender->getLevel()->setTime(Level::TIME_SUNSET);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Sunset"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
				case 4: //time_5night
					$sender->getLevel()->setTime(Level::TIME_NIGHT);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Night"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
				case 5: //time_6midnight
					$sender->getLevel()->setTime(Level::TIME_MIDNIGHT);
					$string = str_replace(["{player}", "{status}"], [$sender->getName(), "Midnight"], $this->plugin->cmds["call"]["text"]["time"]);
				break;
			}
			Server::getInstance()->broadcastMessage(str_replace("{prefix}", $this->plugin->cmds["call"]["prefix"], $string));
		});
		$form->setTitle($this->plugin->cmds["call"]["title"]);
		foreach($this->plugin->cmds["call"]["time"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$form->sendToPlayer($sender);
	}
}