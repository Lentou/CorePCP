<?php

declare(strict_types=1);

namespace pcp\tasks;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\Player;
use pcp\Core;

class RestartTask extends Task{
	/** @var Core */
    private $plugin, $time;

    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
		$this->time = intval( $plugin->events["restart"]["time"] ) * 60;
    }

	public function onRun(int $tick) : void {
		$interval = ($this->time -= 1);
		if (in_array($interval, $this->plugin->events["restart"]["int"])) {
			Server::getInstance()->broadcastMessage(str_replace(["{prefix}", "{seconds}"], [$this->plugin->events["restart"]["prefix"], (string)$this->time], $this->plugin->events["restart"]["text"]["countdown"]));
			Server::getInstance()->broadcastTitle(str_replace("{seconds}", (string)$interval, $this->plugin->events["restart"]["title"][0]), str_replace("{seconds}", (string)$interval, $this->plugin->events["restart"]["title"][1]));
			
			foreach (Server::getInstance()->getOnlinePlayers() as $player) {
				if (Chaser::isInCombat($player)) {
					Chaser::setCombat($player, false);
				}
			}
		}
		
		if ($interval == 0) {
			foreach(Server::getInstance()->getOnlinePlayers() as $player) {
				$player->close("", strval($this->plugin->events["restart"]["text"]["reset"]));
			}
				
			// Extra precautionary measure
			foreach(Server::getInstance()->getLevels() as $level) {
				$level->save();
			}
				
			Server::getInstance()->shutdown();
		}
	}
}
