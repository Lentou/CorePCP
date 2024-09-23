<?php

namespace pcp\cmds;

use pocketmine\command\{CommandSender, ConsoleCommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pcp\libs\forms\{CustomForm, SimpleForm};
use pcp\Core;
use pcp\utils\Chaser;

class AccessCmd extends PluginCommand{

    /** @var Core */
    private $plugin;
	
	public $playerList = [];

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["access"]["desc"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if($sender instanceof Player){
			if($sender->hasPermission($this->plugin->cmds["access"]["perm"])){
				$this->accessForm($sender);
				return true;
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["access"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
				return false;
			}
		}
        return true;
    }
	
	public function accessForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					if($sender->isOp()){
						$this->consoleForm($sender);
					} else {
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["access"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
						return false;
					}
					break;
				case 1:
					$this->plugin->configReload();
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["access"]["prefix"], $this->plugin->cmds["access"]["text"]["reload"]));
					break;
				case 2:
					$this->invSeeForm($sender);
					break;
				case 3:
					if(!Chaser::isVanishPlayer($sender)){
						foreach(Server::getInstance()->getOnlinePlayers() as $staff){
							$staff->hidePlayer($sender);
						}
						$quit = str_replace(["{player}", "{group}", "{tag}"], [$sender->getName(), Core::getInstance()->placeholder->checkTags("group", $sender, 0), $sender->getDisplayName()], $this->plugin->events["login"]["quit"]["format"]["default"]);
						Server::getInstance()->broadcastMessage($quit);
						$sender->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["access"]["prefix"], "Vanish"], $this->plugin->cmds["access"]["text"]["vanish"]));
                        Chaser::initVanishPlayer($sender, true);
                    } else {
						foreach(Server::getInstance()->getOnlinePlayers() as $staff){
							$staff->showPlayer($sender);
						}
						$join = str_replace(["{player}", "{group}", "{tag}"], [$sender->getName(), Core::getInstance()->placeholder->checkTags("group", $sender, 0), $sender->getDisplayName()], $this->plugin->events["login"]["join"]["format"]["default"]);
						Server::getInstance()->broadcastMessage($join);
						$sender->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["access"]["prefix"], "Visible"], $this->plugin->cmds["access"]["text"]["vanish"]));
						Chaser::initVanishPlayer($sender, false);
                    }
					break;
			
			}
		});
		$form->setTitle($this->plugin->cmds["access"]["title"]);
		$form->setContent(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->cmds["access"]["content"]["access"])));
		foreach($this->plugin->cmds["access"]["buttons"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
        $form->sendToPlayer($sender);
	}
	
	public function consoleForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if(is_null($data)) {
				return true;
			}
            Server::getInstance()->getCommandMap()->dispatch(new ConsoleCommandSender(), $data[0]);
            $sender->sendMessage(str_replace(["{prefix}", "{cmd}"], [$this->plugin->cmds["access"]["prefix"], $data[0]], $this->plugin->cmds["access"]["text"]["console"]));
        });
        $form->setTitle($this->plugin->cmds["access"]["title"]);
		$form->addInput(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->cmds["access"]["content"]["console"])), "");
        $form->sendToPlayer($sender);
	}
	
	public function invSeeForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->chestForm("invsee", $sender);
				break;
				case 1:
					$this->chestForm("enderinvsee", $sender);
				break;
			}
		});
		$form->setTitle($this->plugin->cmds["access"]["title"]);
		$form->addButton($this->plugin->cmds["access"]["inv"]["invchest"][0], $this->plugin->cmds["access"]["inv"]["invchest"][1], $this->plugin->cmds["access"]["inv"]["invchest"][2]);
		$form->addButton($this->plugin->cmds["access"]["inv"]["invechest"][0], $this->plugin->cmds["access"]["inv"]["invechest"][1], $this->plugin->cmds["access"]["inv"]["invechest"][2]);
		$form->sendToPlayer($sender);
	}
	
	public function chestForm(string $string, Player $sender){
		$list = [];
		foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
			$list[] = $p->getName();
		}
		$this->playerList[$sender->getName()] = $list;
		$form = new CustomForm(function (Player $sender, $data) use ($string) {
			if(is_null($data)) return true;
			$index = $data[0];
			$playerName = $this->playerList[$sender->getName()][$index];
			$this->plugin->getServer()->getCommandMap()->dispatch($sender, "{$string} {$playerName}");
			
		});
		$form->setTitle($this->plugin->cmds["access"]["title"]);
		$form->addDropdown(implode("\n", str_replace(["{player}", "{invtype}"], [$sender->getName(), $string], $this->plugin->cmds["access"]["content"]["invsee"])), $this->playerList[$sender->getName()]);
		$form->sendToPlayer($sender);
	}
}
