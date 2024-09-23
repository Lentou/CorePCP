<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use _64FF00\PurePerms\PurePerms;
use pcp\libs\forms\{CustomForm, SimpleForm, ModalForm};

class PathCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["path"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
		$this->mainPathForm($sender);
		return true;
	}
	
	public function getPathConfig($string){
		return $this->plugin->cmd->getNested($string);
	}
	
	public function getPathMsg($string){
		return str_replace("{prefix}", $this->plugin->cmds["path"]["prefix"], $this->getPathConfig($string));
	}
	
	public function mainPathForm($sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if($data === null) return false;
			switch($data){
				case 0:
					#if(Core::getInstance()->cx->data->getVal($sender, "level") >= $this->getPathConfig("path.level.class.base")){
						#if(Core::getInstance()->getStats()->getAlpha($sender, "class.name") != "None"){
							#if(Core::getInstance()->cx->data->getVal($sender, "level") >= $this->getPathConfig("path.level.class.evolve")){
								#if(Core::getInstance()->getStats()->getAlpha($sender, "class.type") == "Base") {
									#$this->chasersEvolveForm($sender);
								#} else {
									#$sender->sendMessage($this->getPathMsg("path.text.alreadyclass"));
								#}
							#} else {
								#$sender->sendMessage(str_replace(["{option}", "{lvlneed}"], ["Chaser`s Evolve Class", $this->getPathConfig("path.level.class.evolve")], $this->getPathMsg("path.text.level")));
							#}
						#} else {
							#$this->chasersBaseForm($sender);
						#}
					#} else {
						#$sender->sendMessage(str_replace(["{option}", "{lvlneed}"], ["Chaser`s Base Class", $this->getPathConfig("path.level.class.base")], $this->getPathMsg("path.text.level")));
					#}
					$sender->sendMessage("[ W.I.P ] Coming Soon");
				break;
				case 1: // Rankup
					if(Core::getInstance()->cx->data->getVal($sender, "level") >= $this->getPathConfig("path.level.rank")){
						$ranks = $this->plugin->cmds["path"]["roles"]["rank"];
						$myrank = Core::getInstance()->getStats()->getAlpha($sender, "rank");
						$next = Core::getInstance()->economy->getNextRank($myrank, $ranks);
						$currentstr = Core::getInstance()->getStats()->getPoints($sender, "strength");
						if($myrank != array_key_last($ranks)){
							if($ranks[$next] > $currentstr){
								$sender->sendMessage(str_replace(["{next}", "{str}"], [$next, $ranks[$next]], $this->getPathMsg("path.text.rankup")));
								return false;
							} else {
								Core::getInstance()->getStats()->setAlpha($sender, "rank", $next);
								PurePerms::getInstance()->getUserDataMgr()->setPermission($sender, strtolower(str_replace("{rank}", $next, $this->getPathConfig("path.text.perm"))));
								Server::getInstance()->broadcastMessage(str_replace(["{player}", "{rank}"], [$sender->getName(), $next], $this->getPathMsg("path.text.ascent")));
								return true;
							}
						} else {
							$sender->sendMessage($this->getPathMsg("path.text.lastrank"));
							return false;
						}
					} else {
						$sender->sendMessage(str_replace(["{option}", "{lvlneed}"], ["Rank", $this->getPathConfig("path.level.rank")], $this->getPathMsg("path.text.level")));
					}
				break;
				case 2: // Jobs lvl 1+
					$this->jobForm($sender);
				break;
			}
		});
		$form->setTitle($this->getPathConfig("path.form.title"));
		$form->setContent(implode("\n", $this->getPathConfig("path.form.content.main")));
		foreach($this->plugin->cmds["path"]["form"]["buttons"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$sender->sendForm($form);
	}
	
	public function chasersBaseForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return false;
			
			$stats = Core::getInstance()->getStats();
			$str = $this->getPathConfig("path.required.base.str");
			$bal = $this->getPathConfig("path.required.base.bal");
			$class = $this->plugin->cmds["path"]["roles"]["class"][$data[1]];
			
			if(($stats->getPoints($sender, "strength") >= $str) and ($stats->getPoints($sender, "balance") >= $bal)){
				if(in_array($class, $this->getPathMsg("path.roles.profession.physical"))){
					$stats->setAlpha($sender, "class.passive", "Physical");
				} else if (in_array($class, $this->getPathMsg("path.roles.profession.magical"))){
					$stats->setAlpha($sender, "class.passive", "Magical");
				}
				$stats->setAlpha($sender, "class.name", $class);
				$stats->setAlpha($sender, "class.type", "Base");
				$sender->sendMessage(str_replace(["{class}", "{type}"], [$this->plugin->events["ranks"]["class"]["$class"][0], "§fBase"], $this->getPathMsg("path.text.class")));
			} else {
				$sender->sendMessage(str_replace(["{str}", "{bal}", "{type}"], [$str, $bal, "§fBase"], $this->getPathMsg("path.text.required")));
			}
			
		});
		$form->setTitle($this->getPathConfig("path.form.title"));
		$form->addLabel(implode("\n", $this->getPathConfig("path.form.content.class")));
		$form->addDropdown($this->getPathConfig("path.form.dropdown.class"), $this->getPathConfig("path.roles.class"));
		$sender->sendForm($form);
	}
	
	public function chasersEvolveForm($sender){
		
		$stats = Core::getInstance()->getStats();
		$str = $this->getPathConfig("path.required.evolve.str");
		$bal = $this->getPathConfig("path.required.evolve.bal");
		$currentclass = $stats->getAlpha($sender, "class.name");
		$class = $this->plugin->events["ranks"]["class"]["$currentclass"][1];
				
		$form = new ModalForm(function (Player $sender, $data) use ($str, $bal, $currentclass, $stats, $class){
			if($data == true){
				if(($stats->getPoints($sender, "strength") >= $str) and ($stats->getPoints($sender, "balance") >= $bal)){
					$stats->setAlpha($sender, "class.name", $class);
					$stats->setAlpha($sender, "class.type", "Evolve");
					$sender->sendMessage(str_replace(["{class}", "{type}"], [$this->plugin->events["ranks"]["class"]["$currentclass"][0], "§bEvolve"], $this->getPathMsg("path.text.class")));
				} else {
					$sender->sendMessage(str_replace(["{str}", "{bal}", "{type}"], [$str, $bal, "§bEvolve"], $this->getPathMsg("path.text.required")));
				}
			}
		});
		
		$form->setTitle($this->getPathConfig("path.form.title"));
		$form->setContent(implode("\n", str_replace(["{currentjob}", "{newjob}", "{str}", "{bal}"], [$this->plugin->events["ranks"]["class"]["$currentclass"][0], $class, $str, $bal], $this->getPathMsg("path.form.content.evolve"))));
		$form->setButton1($this->getPathConfig("utils.buttons.confirm")[0]);
		$form->setButton2($this->getPathConfig("utils.buttons.cancel")[0]);
		$sender->sendForm($form);
	}
	
	public function jobForm($sender){
		$stats = Core::getInstance()->getStats();
		$form = new CustomForm(function (Player $sender, $data) use ($stats){
			if($data === null) return false;
			$job = $this->plugin->cmds["path"]["roles"]["job"][$data[1]];
			if($stats->getAlpha($sender, "job.name") == "None"){
				$stats->setAlpha($sender, "job.name", $job);
				$stats->setAlpha($sender, "job.type", "Aspiring");
				$sender->sendMessage(str_replace("{job}", $job, $this->getPathMsg("path.text.job")));
			} else {
				if($stats->getAlpha($sender, "job.name") != $job){
					$this->changeJobForm($sender, $job);
				} else {
					$sender->sendMessage(str_replace("{job}", $job, $this->getPathMsg("path.text.cantchange")));
				}
			}
		});
		$form->setTitle($this->getPathConfig("path.form.title"));
		$form->addLabel(implode("\n", $this->getPathConfig("path.form.content.job")));
		$form->addDropdown($this->getPathConfig("path.form.dropdown.job"), $this->getPathConfig("path.roles.job"));
		$sender->sendForm($form);
	}
	
	public function changeJobForm($sender, $job){
		$stats = Core::getInstance()->getStats();
		$form = new ModalForm(function (Player $sender, $data) use ($job, $stats){
			if($data == true){
				if($this->getPathConfig("path.cost.jobchange") > $stats->getPoints($sender, "balance")){
					$sender->sendMessage($this->getPathMsg("path.text.jobchange"));
				} else {
					$stats->setPoints($sender, "balance", "-", $this->getPathConfig("path.cost.jobchange"));
					$stats->setPoints($sender, "job.prg", "=", 0);
					$stats->setAlpha($sender, "job.name", $job);
					$stats->setAlpha($sender, "job.type", "Aspiring");
					$stats->setPoints($sender, "job.lvl", "=", 1);
					$stats->setPoints($sender, "job.exp", "=", 0);
					$sender->sendMessage(str_replace("{job}", $job, $this->getPathMsg("path.text.job")));
				}
			}
		});
		$form->setTitle($this->getPathConfig("path.form.title"));
		$form->setContent(implode("\n", str_replace(["{job}", "{cost}"], [$job, $this->getPathConfig("path.cost.jobchange")], $this->getPathConfig("path.form.content.changejob"))));
		$form->setButton1($this->plugin->cmds["utils"]["buttons"]["confirm"][0]);
		$form->setButton2($this->plugin->cmds["utils"]["buttons"]["cancel"][0]);
		$sender->sendForm($form);
	}
     
}
