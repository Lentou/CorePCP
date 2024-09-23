<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pcp\libs\forms\CustomForm;

class BankCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["bank"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		$this->bankForm($sender);
		return true;
	}
	
	public function bankForm($player){
		
		$playerList = [];
		$list = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $players){
            $list[] = $players->getName();
        }
		$listed = ($this->playerList[$player->getName()] = $list);
		
		$form = new CustomForm(function (Player $player, $data) use ($listed) {
			if($data === null) return false;
			
            $input = $data["amount"];
			$playerName = $listed[$data["player"]];
			$otherPlayer = Server::getInstance()->getPlayer($playerName);
			
            if($input === null) return false;
			
			if($input <= 0){
				$player->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bank"]["prefix"], $this->plugin->cmds["bank"]["text"]["notvalid"]));
				return false;
			}
			if(Core::getInstance()->getStats()->getPoints($player, "balance") < $input){
				$player->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["bank"]["prefix"], "Balance"], $this->plugin->cmds["bank"]["text"]["notenough"]));
				return false;
			}
			if(Core::getInstance()->getStats()->getPoints($player, "tickets") < $this->plugin->cmds["bank"]["cost"]){
				$player->sendMessage(str_replace(["{prefix}", "{status}"], [$this->plugin->cmds["bank"]["prefix"], "Tickets"], $this->plugin->cmds["bank"]["text"]["notenough"]));
				return false;
			}
			if($player->getName() === $playerName){
				$player->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bank"]["prefix"], $this->plugin->cmds["bank"]["text"]["payself"]));
				return false;
			}
			if($input > $this->plugin->cmds["bank"]["limit"][0] and $input <= $this->plugin->cmds["bank"]["limit"][1]){
				Core::getInstance()->getStats()->setPoints($otherPlayer, "balance", "+", $input);
				Core::getInstance()->getStats()->setPoints($player, "balance", "-", $input);
				Core::getInstance()->getStats()->setPoints($player, "tickets", "-", $this->plugin->cmds["bank"]["cost"]);
				$player->sendMessage(str_replace(["{prefix}", "{amount}", "{target}"], [$this->plugin->cmds["bank"]["prefix"], $input, $otherPlayer->getName()], $this->plugin->cmds["bank"]["text"]["format"]));
				$otherPlayer->sendMessage(str_replace(["{prefix}", "{amount}", "{target}"], [$this->plugin->cmds["bank"]["prefix"], $input, $player->getName()], $this->plugin->cmds["bank"]["text"]["accept"]));
				return true;
			} else {
				$player->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bank"]["prefix"], $this->plugin->cmds["bank"]["text"]["limit"]));
				return false;
			}
			
		});
		$form->setTitle($this->plugin->cmds["bank"]["title"]);
		$form->addLabel(implode("\n", str_replace(["{tickets}", "{balance}", "{player}"], [Core::getInstance()->getStats()->getPoints($player, "tickets"), Core::getInstance()->getStats()->getPoints($player, "balance"), $player->getName()], $this->plugin->cmds["bank"]["content"])));
		$form->addDropdown("§7Select a Player:", $listed, null, "player");
		$form->addInput($this->plugin->cmds["bank"]["input"][0], $this->plugin->cmds["bank"]["input"][0], null, "amount");
		$form->sendToPlayer($player);
	}
}
