<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class AfkCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["afk"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if($sender instanceof Player) {
			if (Chaser::isAfkPlayer($sender)) {
				Chaser::initAfkPlayer($sender, false);
				$sender->setImmobile(false);
				$sender->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["afk"]["prefix"], "no longer"], $this->plugin->cmds["afk"]["text"]["set"]));
				Server::getInstance()->broadcastMessage(str_replace(["{prefix}", "{status}", "{player}"], [$this->plugin->cmds["afk"]["prefix"], "no longer", $sender->getName()], $this->plugin->cmds["afk"]["text"]["cast"]));
			} else {
				Chaser::initAfkPlayer($sender, true);
				$sender->setImmobile(true);
				$sender->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["afk"]["prefix"], "now"], $this->plugin->cmds["afk"]["text"]["set"]));
				Server::getInstance()->broadcastMessage(str_replace(["{prefix}", "{status}", "{player}"], [$this->plugin->cmds["afk"]["prefix"], "now", $sender->getName()], $this->plugin->cmds["afk"]["text"]["cast"]));
			}
		}
		return true;
	}
     
}
