<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class SpawnCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
	
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["spawn"]["desc"]);
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		$sender->teleport($sender->getLevel()->getSpawnLocation());
		$sender->sendTitle($this->plugin->cmds["spawn"]["format"][0], $this->plugin->cmds["spawn"]["format"][1]);
		return true;
	} 
	
}