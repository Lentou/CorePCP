<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;
use pcp\libs\forms\CustomForm;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Server;

class FlagCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
	private $worldname;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["flag"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		if($sender->isOp()){
			if(empty($args[0])){
				$worldName = empty($args[0]) ? $sender->getLevel()->getFolderName() : $args[0];
				if(!$this->plugin->flags->hasSecurity($worldName)){
					$sender->sendMessage(str_replace(["{prefix}", "{world}"], [$this->plugin->events["flag"]["prefix"], $worldName], $this->plugin->cmds["flag"]["text"]["noprotect"]));
					return false;
				}
				$this->flagForm($sender, $worldName);
			} else {
				switch($args[0]){
					case "add":
						if(empty($args[1])){
							$args[1] = $sender->getLevel()->getFolderName();
						}
						if(!file_exists(Server::getInstance()->getDataPath() . "/worlds/" . $args[1])){
							$sender->sendMessage(str_replace(["{prefix}", "{world}"], [$this->plugin->events["flag"]["prefix"], $args[1]], $this->plugin->cmds["flag"]["text"]["notexists"]));
							return true;	
						}
						if($this->plugin->flags->hasSecurity($args[1])){
							$sender->sendMessage(str_replace(["{prefix}", "{world}"], [$this->plugin->events["flag"]["prefix"], $args[1]], $this->plugin->cmds["flag"]["text"]["exists"]));
							return true;
						}
						$this->plugin->flags->updateSetting($args[1], array(0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1));
						$sender->sendMessage(str_replace(["{prefix}", "{world}"], [$this->plugin->events["flag"]["prefix"], $args[1]], $this->plugin->cmds["flag"]["text"]["register"]));
					break;
					default:
				}
			}
		} else {
			$sender->sendMessage(str_replace("{prefix}", $this->plugin->events["flag"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
		}
		return true;
	}
	
	public function flagForm(Player $player, string $world) {
        $form = new CustomForm(function (Player $player, $data) {
		
            if (isset($data[1])) {
				$pref = array($data[0], $data[1], $data[2], $data[3],
			      $data[4], $data[5], $data[6], $data[7],
			      $data[8], $data[9], $data[10], $data[11],
			      $data[12], $data[13], $data[14], $data[15],
			      $data[16], $data[17], $data[18], $data[19],
			      $data[20], $data[21]
			     );
                $this->plugin->flags->updateSetting($this->worldname, $pref);
				$player->sendMessage(str_replace(["{prefix}", "{world}"], [$this->plugin->events["flag"]["prefix"], $this->worldname], $this->plugin->cmds["flag"]["text"]["save"]));
				unset($this->worldname);
            }
        });
	
		$this->worldname = $world;
		$wdata = $this->plugin->flags->getAllData($this->worldname); //world data

    	$form->setTitle('§lFlag Event');
	  
    	$form->addLabel("World: §b" . $world . "\n§7 - Toggling on will cancel the event");//data 0
		$form->addToggle("Lockdown (Maximum Security)", (bool) $wdata["lock"] ); //1
		$form->addToggle("Anti Edit", (bool) $wdata["edit"] ); //2
		$form->addToggle("No Survival Drops", (bool) $wdata["sdrop"] ); //3
		$form->addToggle("No Creative Drops", (bool) $wdata["cdrop"] ); //4
		$form->addToggle("No PvP", (bool) $wdata["pvp"]); //5
		$form->addToggle("No Projectile Damage", (bool) $wdata["projectile"]); //6
		$form->addToggle("No Suffocate/Drown Damage", (bool) $wdata["suffocate"]); //7
		$form->addToggle("Disable Fall Damage", (bool) $wdata["fall"]); //8
		$form->addToggle("Disable Burn Damage", (bool) $wdata["burn"]); //9
		$form->addToggle("Disable Other Damage", (bool) $wdata["otherdamage"]); //10
		$form->addToggle("Clearinventory on GM Change", (bool) $wdata["gmchange"]); //11
		$form->addToggle("Freeze hunger bar", (bool) $wdata["hunger"]); //12
		$form->addToggle("Door protection", (bool) $wdata["door"]); //13
		$form->addToggle("Trapdoor protection", (bool) $wdata["trapdoor"]); //14
		$form->addToggle("Storage(s) protection", (bool) $wdata["storage"]); //15
		$form->addToggle("No Fly zone", (bool) $wdata["fly"]); //16
		$form->addToggle("Anti Terrorism", (bool) $wdata["explode"]); //17
		$form->addDropdown("World Gamemode", ["§lSurvival", "§lCreative", "§lAdventure"], (int) $wdata["gm"]); //18
		$form->addToggle("Reset Player Size", (bool) $wdata["scale"]); //19
		$form->addToggle("Disable Item Frame", (bool) $wdata["itemframe"]); //20
		$form->addToggle("Disable Liquid", (bool) $wdata["liquid"]); //21
    	$form->sendToPlayer($player);
  	}
     
}
