<?php

namespace pcp\cmds;

use pocketmine\command\{PluginCommand, Command, CommandSender};
use pocketmine\Player;
use pocketmine\Server;
use pcp\Core;

class TellCmd extends PluginCommand {

    /** @var Core */
    private $plugin;

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["tell"]["desc"]);
		$this->setAliases(["t", "w", "msg"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if (count($args) < 2) {
            $sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["tell"]["prefix"], $this->plugin->cmds["tell"]["text"]["usage"]));
			return true;
        }
        $player = $sender->getServer()->getPlayer(array_shift($args));
        if ($player === $sender) {
            $sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["tell"]["prefix"], $this->plugin->cmds["utils"]["text"]["yourself"]));
            return true;
        }
        if ($player instanceof Player) {
            $name = $sender instanceof Player ? $sender->getDisplayName() : $sender->getName();
            $message = implode(" ", $args);
            $format = str_replace(["{prefix}", "{sender}", "{recipient}", "{message}"], [$this->plugin->cmds["tell"]["prefix"], $name, $player->getDisplayName(), $message], $this->plugin->cmds["tell"]["text"]["format"]);
            $sender->sendMessage($format);
            $player->sendMessage($format);
        } else {
            $sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["tell"]["prefix"], $this->plugin->cmds["utils"]["text"]["player"]));
        }
        return true;
    }
}