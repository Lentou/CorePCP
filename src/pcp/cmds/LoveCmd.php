<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

use pcp\libs\forms\{
	SimpleForm, CustomForm, ModalForm
};

class LoveCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["love"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		if($this->plugin->cmds["love"]["enable"] === true){
			$this->loveForm($sender);
		} else {
			$sender->sendMessage($this->getLoveMsg("utils.text.disable"));
		}
		return true;
	}
	
	public function getLoveConfig(string $string){
		return $this->plugin->cmd->getNested($string);
	}
	
	public function getLoveMsg(string $string){
		return str_replace("{prefix}", $this->plugin->cmds["love"]["prefix"], $this->getLoveConfig($string));
	}
	
	public function loveForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					if(Core::getInstance()->getStats()->getAlpha($sender, "love.spouse") != "None"){
						$sender->sendMessage($this->getLoveMsg("love.text.cantconfess"));
					} else {
						if(Core::getInstance()->getStats()->getAlpha($sender, "love.status")  == "Single"){
							$this->listSpouseForm($sender);
						} else {
							$sender->sendMessage(str_replace("{status}", Core::getInstance()->getStats()->getAlpha($sender, "love.status"), $this->getLoveMsg("love.text.change")));
						}
					}
				break;
				case 1:
					if(Core::getInstance()->getStats()->getAlpha($sender, "love.spouse") != "None"){
						$this->sendBreakupForm($sender);
					} else {
						$sender->sendMessage($this->getLoveMsg("love.text.cantbreakup"));
					}
				break;
				case 2:
					$get = Core::getInstance()->getStats()->getAlpha($sender, "love.status");
					switch($get){
						case "Broken":
							Core::getInstance()->getStats()->setAlpha($sender, "love.status", "Single");
							$sender->sendMessage(str_replace("{status}", $get, $this->getLoveMsg("love.text.status")));
						break;
						case "Single":
							Core::getInstance()->getStats()->setAlpha($sender, "love.status", "NotInterested");
							$sender->sendMessage(str_replace("{status}", $get, $this->getLoveMsg("love.text.status")));
						break;
						case "NotInterested":
							Core::getInstance()->getStats()->setAlpha($sender, "love.status", "Single");
							$sender->sendMessage(str_replace("{status}", $get, $this->getLoveMsg("love.text.status")));
						break;
						default:
							$sender->sendMessage(str_replace("{status}", $get, $this->getLoveMsg("love.text.cantswitch")));
					}
				break;
			}
		});
		$form->setTitle($this->getLoveConfig("love.form.title"));
		foreach($this->getLoveConfig("love.form.button") as $btn => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$sender->sendForm($form);
	}
	
	public function listSpouseForm($sender){
		$playerList = [];
		$list = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $senders){
            $list[] = $senders->getName();
        }
		$listed = ($this->playerList[$sender->getName()] = $list);
		
		$form = new CustomForm(function (Player $sender, $data) use ($listed) {
			if($data === null) return false;

			$spouse = Server::getInstance()->getPlayer($listed[$data[1]]);
			
			if(Core::getInstance()->getStats()->getAlpha($spouse, "love.spouse") != "None"){
				$sender->sendMessage($this->getLoveMsg("love.text.chosen"));
				return false;
			}
			if(Core::getInstance()->getStats()->getAlpha($spouse, "love.status") == "NotInterested"){
				$sender->sendMessage($this->getLoveMsg("love.text.notinterested"));
				return false;
			}
			if(Core::getInstance()->getStats()->getAlpha($spouse, "love.status") == "Broken"){
				$sender->sendMessage($this->getLoveMsg("love.text.broken"));
				return false;
			}
			if($sender->getName() == $spouse->getName()) {
				$sender->sendMessage($this->getLoveMsg("love.text.self"));
				return false;
			}
			
			$this->sendConfessForm($sender, $spouse);
			$sender->sendMessage(str_replace("{target}", $spouse->getName(), $this->getLoveMsg("love.text.confess")));
			Server::getInstance()->broadcastMessage(str_replace(["{sender}", "{target}"], [$sender->getName(), $spouse->getName()], $this->getLoveMsg("love.cast.confess")));
			
		});
		$form->setTitle($this->getLoveConfig("love.form.title"));
		$form->addLabel(implode("\n", $this->getLoveConfig("love.form.label.select")));
		$form->addDropdown($this->getLoveConfig("love.form.label.dropdown"), $listed);
		$sender->sendForm($form);
	}

	public function sendConfessForm($sender, $spouse){
		$form = new ModalForm(function (Player $spouse, $data) use ($sender){
			if($data == true){
				Core::getInstance()->getStats()->setAlpha($sender, "love.spouse", $spouse->getName());
				Core::getInstance()->getStats()->setAlpha($spouse, "love.spouse", $sender->getName());
				
				Core::getInstance()->getStats()->setAlpha($sender, "love.status", "InRelationship");
				Core::getInstance()->getStats()->setAlpha($spouse, "love.status", "InRelationship");
				Server::getInstance()->broadcastMessage(str_replace(["{sender}", "{target}"], [$sender->getName(), $spouse->getName()], $this->getLoveMsg("love.cast.accept")));
				foreach(Server::getInstance()->getOnlinePlayers() as $player){
					Core::getInstance()->showScreenAnimate($player, 10);
				}
			} else {
				$sender->sendMessage($this->getLoveMsg("love.text.reject"));
				Server::getInstance()->broadcastMessage(str_replace(["{sender}", "{target}"], [$sender->getName(), $spouse->getName()], $this->getLoveMsg("love.cast.reject")));
			}
		});
		$form->setTitle($this->getLoveConfig("love.form.title"));
		$form->setContent(implode("\n", str_replace("{sender}", $sender->getName(), $this->getLoveConfig("love.form.label.confess"))));
		$form->setButton1($this->plugin->cmds["utils"]["buttons"]["confirm"][0]);
		$form->setButton2($this->plugin->cmds["utils"]["buttons"]["cancel"][0]);
		$spouse->sendForm($form);
	}
	
	public function sendBreakupForm($sender){
		$form = new ModalForm(function (Player $sender, $data){
			if($data == true){
				$nameOfSpouse = Core::getInstance()->economy->mySpouse($sender);
				$spouse = Server::getInstance()->getPlayer($nameOfSpouse);
				
				Core::getInstance()->getStats()->setAlpha($sender, "love.spouse", "None");
				Core::getInstance()->getStats()->setAlpha($spouse, "love.spouse", "None");
				
				Core::getInstance()->getStats()->setAlpha($sender, "love.status", "Broken");
				Core::getInstance()->getStats()->setAlpha($spouse, "love.status", "Broken");
				
				Server::getInstance()->broadcastMessage(str_replace(["{sender}", "{target}"], [$sender->getName(), $spouse->getName()], $this->getLoveMsg("love.cast.breakup")));
				foreach(Server::getInstance()->getOnlinePlayers() as $player){
					Core::getInstance()->showScreenAnimate($player, 20);
				}
			} else {
				$sender->sendMessage($this->getLoveMsg("love.text.keep"));
			}
		});
		$nameOfSpouse = Core::getInstance()->economy->mySpouse($sender);
		$form->setTitle($this->getLoveConfig("love.form.title"));
		$form->setContent(implode("\n", str_replace("{spouse}", $nameOfSpouse, $this->getLoveConfig("love.form.label.breakup"))));
		$form->setButton1($this->plugin->cmds["utils"]["buttons"]["confirm"][0]);
		$form->setButton2($this->plugin->cmds["utils"]["buttons"]["cancel"][0]);
		$sender->sendForm($form);
	}
	
}
