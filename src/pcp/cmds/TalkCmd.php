<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pcp\libs\forms\SimpleForm;

class TalkCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->talks["talk"]["desc"]);
	}
	
	public function getTalkConfig(string $config){
		return $this->plugin->talk->getNested($config);
	}
	
	public function getTalkMsg(string $config){
		return str_replace("{prefix}", $this->getTalkConfig("talk.prefix"), $this->getTalkConfig($config));
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player){
			if(empty($args[0]) or empty($args[1])){
				$sender->sendMessage($this->getTalkMsg("talk.text.usage"));
			} else {
				$target = Server::getInstance()->getPlayer($args[0]);
				$npc = strtolower($args[1]);
				if($target instanceof Player){
					if ($this->getTalkConfig("npcs.".$npc) !== null){
						$this->talkForm($target, $npc);
					} else {
						$this->talkForm($target, "self");
					}
				}
			}
		}
		return true;
	}
	
	public function talkForm(Player $target, string $npc){
		$form = new SimpleForm(function (Player $target, $data){
			if($data == null) return;
		});
		$msgs = $this->getTalkConfig("npcs.".$npc.".msg");
		$form->setTitle($this->getTalkConfig("npcs.".$npc.".title"));
		$form->setContent(str_replace(["==", "#"], [$this->getTalkConfig("prefix.line"), "\n"], $msgs[array_rand($msgs)]));
		$target->sendForm($form);
	}
     
}
