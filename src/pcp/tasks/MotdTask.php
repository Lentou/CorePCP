<?php

namespace pcp\tasks;

use pcp\Core;
use pocketmine\scheduler\Task;

class MotdTask extends Task {

	private $plugin;

    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
		$allMessages = $this->plugin->events["motd"]["message"];
        $network = $this->plugin->getServer()->getNetwork();
        if (in_array($network->getName(), $allMessages)) {
            $messageNumber = array_search($network->getName(), $allMessages);
            $network->setName($allMessages[$messageNumber + 1] ?? $allMessages[0]);
        } else {
            $network->setName($allMessages[0]);
        }
    }
}