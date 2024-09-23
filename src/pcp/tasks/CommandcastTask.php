<?php

declare(strict_types=1);

namespace pcp\tasks;

use pcp\Core;
use pocketmine\scheduler\Task;
use pocketmine\command\ConsoleCommandSender;

class CommandcastTask extends Task{
    
    /** @var Core */
    private $plugin;

    public function __construct(Core $plugin)
    {
        $this->plugin = $plugin;
    }
    
    public function onRun(int $currentTick)
    {
    	if(count($this->plugin->getServer()->getOnlinePlayers()) > 0)
        {
			$cmd = $this->plugin->events["cast"]["commandcast"];
			shuffle($cmd);
			$string = explode(":", array_shift($cmd));
			foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
				$player->sendMessage(str_replace("{prefix}", $this->plugin->cmds["bcast"]["prefix"], $string[0]));
				$this->plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), str_replace("{player}", '"'.$player->getName().'"', $string[1]));
			}
        }
    }
    
    
}
