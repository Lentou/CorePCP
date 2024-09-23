<?php

namespace pcp\cmds;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\Server;
use pcp\Core;
use pcp\utils\Quests;
use pcp\libs\forms\SimpleForm;

class QuestCmd extends PluginCommand{

    /** @var Core */
    private $plugin;

    public function __construct(string $name, Core $plugin){
    	parent::__construct($name, $plugin);
        $this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["quest"]["desc"]);
		$this->quests = new Quests($this);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if ($sender instanceof Player) {
			if($this->plugin->cmds["quest"]["enable"] === true){
				if(in_array($sender->getLevel()->getName(), $this->plugin->cmds["quest"]["worlds"])){
					if($sender->isSurvival()){
						$this->questMainForm($sender);
					} else {
						$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["quest"]["prefix"], $this->plugin->cmds["utils"]["text"]["survival"]));
					}
				} else {
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["quest"]["prefix"], $this->plugin->cmds["utils"]["text"]["notallowed"]));
				}
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["quest"]["prefix"], $this->plugin->cmds["utils"]["text"]["disable"]));
			}
		}
        return true;
    }
	
	public function questMainForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)){return true;};
			switch($data){
				case 0:
					$this->quests->sendQuestApplyForm($sender);
				break;
				case 1:
					$this->quests->isCompleted($sender);
				break;
			}
		});
		$form->setTitle($this->plugin->cmds["quest"]["title"]);
		$form->addButton($this->plugin->cmds["quest"]["buttons"]["list"][0], $this->plugin->cmds["quest"]["buttons"]["list"][1], $this->plugin->cmds["quest"]["buttons"]["list"][2]);
		$form->addButton($this->plugin->cmds["quest"]["buttons"]["comp"][0], $this->plugin->cmds["quest"]["buttons"]["comp"][1], $this->plugin->cmds["quest"]["buttons"]["comp"][2]);
		$form->sendToPlayer($sender);
	}
	
	
}
