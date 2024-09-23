<?php
namespace pcp\tasks;

use pcp\Core;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pcp\player\Chaser;
use pocketmine\Server;
use pcp\player\Member;

class CombatTask extends Task{

    public function __construct(Core $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $tick){
		foreach (Server::getInstance()->getOnlinePlayers() as $player) {
			if (Chaser::isInCombat($player)) {
				if (Chaser::getCombatType($player, "time") == 0) {
					$player->sendMessage(str_replace("{prefix}", $this->plugin->events["combat"]["prefix"], $this->plugin->events["combat"]["text"]["end"]));
					Chaser::setCombat($player, false);
				} else {
					Chaser::reduceCombat($player);
				}
			}
		}
    }

}
