<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pcp\player\Member;
use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;

class IntCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["int"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if(!$sender instanceof Player){
			// /int <playername> <type> <amount>
			if (empty($args[0]) or empty($args[1]) or empty($args[2])) {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["int"]["prefix"], $this->plugin->cmds["int"]["text"]["usage"]));
				return false;
			}
			if (($name = strtolower($args[0])) === Server::getInstance()->getPlayer($name)) {
				// /int <playername> <type> <amount> <random>
				if(empty($args[3])){
					$this->grantInt($sender, $name, (string)$args[1], (int)$args[2]);
				} else {
					$this->grantInt($sender, $name, (string)$args[1], (int)$args[2], (int)$args[3]);
				}
				
			} else {
				if ($this->plugin->point->exists(strtolower($args[0]))) {
					if(empty($args[3])){
						$this->grantInt($sender, $args[0], (string)$args[1], (int)$args[2]);
					} else {
						$this->grantInt($sender, $args[0], (string)$args[1], (int)$args[2], (int)$args[3]);
					}
				} else {
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["int"]["prefix"], $this->plugin->cmds["utils"]["text"]["player"]));
				}
			}
		}
		return true;
	}
	
	public function grantInt($sender, $player, $type, $int = 0, $rand = 0){
		switch($type){
			case "+bal":
				$calc = "+";
				$typ3 = "receive";
				$mode = "balance";
			break;
			case "-bal":
				$calc = "-";
				$typ3 = "taken";
				$mode = "balance";
			break;
			default:
				$string = $this->plugin->cmds["int"]["text"]["exist"];
		}
		
		
		if (($user = Server::getInstance()->getPlayer($player)) instanceof Player) {
			$member = new Member($user);
		}
				
		if(is_numeric($int)) {
			
			if($rand != 0){
				$integer = mt_rand($int, $rand);
			} else {
				$integer = $int;
			}
			
			switch($calc) {
				case "+":
					$member->addPoints($mode, $integer);
				break;
				case "-":
					$member->takePoints($mode, $integer);
				break;
			}

			$string = str_replace(["{player}", "{amount}", "{type}"], [$player, (string)$integer, $type], $this->plugin->cmds["int"]["text"]["grant"]);
			if(!is_null($user = Server::getInstance()->getPlayer($player))){
				$user->sendMessage(str_replace(["{prefix}", "{amount}", "{type}", "{mode}"], [$this->plugin->cmds["int"]["prefix"], (string)$integer, $typ3, $mode], $this->plugin->cmds["int"]["text"]["gift"]));
			}
		} else {
			$string = $this->plugin->cmds["int"]["text"]["num"];
		}
		
		$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["int"]["prefix"], $string));
	}
}
