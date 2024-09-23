<?php

declare(strict_types=1);

namespace pcp\tasks;

use pcp\Core;
use pocketmine\scheduler\Task;

class BroadcastTask extends Task{
    
    /** @var Core */
    private $plugin, $messages;

    public function __construct(Core $plugin)
    {
        $this->plugin = $plugin;
        $this->messages = $this->plugin->events["cast"]["broadcast"];
    }
    
    public function onRun(int $currentTick)
    {
    	if(count($this->plugin->getServer()->getOnlinePlayers()) > 0)
        {
           $this->plugin->getServer()->broadcastMessage($this->randomMessage());
        }
    }
    
    private function randomMessage() : string
    {
        $msg = "";
        
        try {
            $msg = (string) $this->messages[ array_rand($this->messages) ];
            $msg = str_replace("{max_players}", (string)$this->plugin->getServer()->getMaxPlayers(), $msg);
            $msg = str_replace("{online}", (string)count($this->plugin->getServer()->getOnlinePlayers()), $msg);
			$msg = str_replace("{prefix}", (string)$this->plugin->cmds["bcast"]["prefix"], $msg);
        } catch (\Exceptions $err) 
        {
            
        }
        return $msg;
    }
}
