<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\forms\{SimpleForm, CustomForm};

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class BcastCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["bcast"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if($sender->hasPermission($this->plugin->cmds["bcast"]["perm"])){
			$message = str_replace("&", "", implode(" ", $args));
			if(empty($message)){
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bcast"]["prefix"], $this->plugin->cmds["bcast"]["text"]["usage"]));
			} else {
				$this->plugin->getServer()->broadcastMessage(str_replace(["{prefix}", "{message}"], [$this->plugin->cmds["bcast"]["prefix"], $message], $this->plugin->cmds["bcast"]["text"]["format"]));
		    }
		} else {
			$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bcast"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
		}
		return true;
	}
}
