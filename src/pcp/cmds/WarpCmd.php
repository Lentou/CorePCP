<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class WarpCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
	
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->warps["text"]["desc"]);
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if($sender instanceof Player){
			if (empty($args[0])) {
				$this->warpForm($sender);
			} else {
				if ($args[0] == "admin") {
					if ($sender->isOp()) {
						$this->warpAdminForm($sender);
					}
				}
			}
			
		}
		$sender->teleport($sender->getLevel()->getSpawnLocation());
		$sender->sendTitle($this->plugin->cmds["spawn"]["format"][0], $this->plugin->cmds["spawn"]["format"][1]);
		return true;
	} 
	
	public function warpForm(Player $player) {
		$form = new SimpleForm(function (Player $player, $data){
			if ($data == null) return true;
			switch($data) {
				case 0:
					$this->lobbyForm($player);
				break;
				case 1:
					$this->worldForm($player);
				break;
			}
		});
		$form->setTitle("Warp");
		$form->setContent("Welcome to Global Warps");
		$form->addButton("Lobby\nTap to View"); # hub / districts
		$form->addButton("World\nTap to View"); # spawn / warppoints
		$player->sendForm($form);
	}
	
	public function lobbyForm(Player $player) {
		$form = new SimpleForm(function (Player $player, $data){
			if ($data == null) $this->warpForm($player);
		});
		$form->setTitle("Lobby Warp");
		$form->setContent("The Lounge Teleport");
		$form->addButton("Hub\nTap to Teleport to Lobby");
		$player->sendForm($form);
	}
}