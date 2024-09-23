<?php

declare(strict_types=1);

namespace pcp\player;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;

class Member {
	
	private $player;
	
	public function __construct(Player $player) {
		$this->player = $player;
	}
	
	public function getPoints(string $type) : int {
		return Core::getInstance()->getPointsManager()->getNested(strtolower($this->player->getName()) . "." . $type) ?? 0;
	}
	
	public function addPoints(string $type, int $points) : void {
		Core::getInstance()->getPointsManager()->setNested(
			strtolower($this->player->getName()) . "." . $type, $this->getPoints($type) + $points
		);
		Core::getInstance()->getPointsManager()->save();
	}
	
	public function takePoints(string $type, int $points) : void {
		Core::getInstance()->getPointsManager()->setNested(
			strtolower($this->player->getName()) . "." . $type, $this->getPoints($type) - $points
		);
		Core::getInstance()->getPointsManager()->save();
	}
	
	public function setPoints(string $type, int $points) : void {
		Core::getInstance()->getPointsManager()->setNested(strtolower($this->player->getName()) . "." . $type, $points);
		Core::getInstance()->getPointsManager()->save();
	}
	
	public function getScript(string $type) : ?string {
		return Core::getInstance()->getScriptManager()->getNested(strtolower($this->player->getName()) . "." . $type) ?? "";
	}
	
	public function setScript(string $type, string|array $script) : void {
		Core::getInstance()->getScriptManager()->setNested(strtolower($this->player->getName()) . "." . $type, $script);
		Core::getInstance()->getScriptManager()->save();
	}
	
	public function addValueScript(string $type, string $value) : void {
		$addedValue = $this->getScript($type);
		$addedValue[] = $value;
		$this->setScript($type, $addedValue);
	}
	
	public function takeValueScript(string $type, string $value) : void {
		$listValue = $this->getScript($type);
		$newValue = [];
		foreach ($listValue as $oldValue){
			if ($oldValue !== $value) {
				$newValue[] = $oldValue;
			}
		}
		$this->setScript($type, $newValue);
	}
	
	public function update() : void {
		
		$points = [
			"balance",
			"bounty",
			"streak",
			"kills",
			"deaths",
			"mobs",
			"quests",
			"votes",
			"rating"
		];
		$scripts = [
			"icon",
			"bio"
		];
		$skills = [
			"sword",
			"shovel",
			"pickaxe",
			"axe",
			"hoe"
		];
		
		if (!Core::getInstance()->getPointsManager()->exists(strtolower($this->player->getName()))){
			Core::getInstance()->getPointsManager()->set(strtolower($this->player->getName()), []);
		}
		
		if (!Core::getInstance()->getScriptManager()->exists(strtolower($this->player->getName()))){
			Core::getInstance()->getScriptManager()->set(strtolower($this->player->getName()), []);
		}
		
		$pointsData = $this->getData("points");
		$scriptData = $this->getData("script");
		
		foreach ($points as $type) {
			if (!isset($pointsData[$type])) {
				$this->setPoints($type, 0);
			}
		}
		
		foreach ($scripts as $type) {
			if (!isset($scriptData[$type])) {
				$this->setScript($type, "None");
			}
		}
		
		if (!isset($scriptData["role"])) {
			$this->setScript("role.display", "Citizen");
			$this->setScript("role.status", "Player");
		}
		
		if (!isset($scriptData["class"])) {
			$this->setScript("class.name", "None");
			$this->setScript("class.type", "None");
		}
		
		if (!isset($scriptData["job"])) {
			$this->setScript("job.name", "None");
			$this->setScript("job.type", "None");
		}
		
		if (!isset($pointsData["class"])) {
			$this->setPoints("class.lvl", 1);
			$this->setPoints("class.exp", 0);
		}
		
		if (!isset($pointsData["job"])) {
			$this->setPoints("job.lvl", 1);
			$this->setPoints("job.exp", 0);
			$this->setPoints("job.prg", 0);
		}
		
		if (!isset($pointsData["social"])) {
			$this->setPoints("social.lvl", 1);
			$this->setPoints("social.exp", 0);
			$this->setPoints("social.charm", 0);
		}
		
		if (!isset($pointsData["skills"])) {
			foreach($skills as $skill){
				if (!isset($scriptData[$skill])) {
					$this->setPoints("skills.".$skill.".exp", 0);
					$this->setPoints("skills.".$skill.".lvl", 1);
				}
			}
		}
		
		if (!isset($scriptData["tag"])) {
			$this->setScript("tag", $this->player->getName());
		}
		
		if(!isset($scriptData["rank"])) {
			$rank = Core::getInstance()->cmds["path"]["roles"]["rank"];
			$this->setScript("rank", key($rank));
		}
		
		if(!isset($scriptData["verify"])){
			$this->setScript("verify", "false");
		}
		
		if(!isset($scriptData["social"])){ 
			$this->setScript("social.spouse", "None");
			$this->setScript("social.buddy", "None");
			$this->setScript("social.status", "Single");
			$this->setScript("social.friends", []);
		}
		
	}
	
	private function getData(string $type) {
		switch($type){
			case "points":
				return Core::getInstance()->getPointsManager()->getAll()[strtolower($this->player->getName())];
			break;
			case "script":
				return Core::getInstance()->getScriptManager()->getAll()[strtolower($this->player->getName())];
			break;
		}
	}
	
}