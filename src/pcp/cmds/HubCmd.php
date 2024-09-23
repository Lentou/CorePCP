<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pcp\utils\Utils;

class HubCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["hub"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		$sender->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		$sender->sendTitle($this->plugin->cmds["hub"]["format"][0], $this->plugin->cmds["hub"]["format"][1]);
		return true;
	}
     
}
