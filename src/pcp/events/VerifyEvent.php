<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

use pcp\libs\forms\CustomForm;
use pcp\libs\forms\CaptchaForm;
use pcp\player\Member;

class VerifyEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function onVerifyJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$member = new Member($player);
		if($member->getScript("verify") == "false"){
			$this->onVerifyForm($player);
		}
	}
	
	public function onVerifyForm($player) {
		$form = new CustomForm(function (Player $player, $data) {
			if(is_null($data)){
				$this->onVerifyForm($player);
				return true;
			}
			$this->onVerification($player);
		});
		
		$form->setTitle($this->plugin->events["verify"]["title"]);
		$form->addLabel(implode("\n", str_replace("{player}", $player->getName(), $this->plugin->events["verify"]["content"]["opening"])));
		$form->sendToPlayer($player);
	}
	
	public function onVerification($player){
		$form = new CaptchaForm(CaptchaForm::CAPTCHA_TYPE_ALPHANUMERIC, CaptchaForm::CAPTCHA_LENGTH_MODERATE);
		
		$form->setSuccessCallable(function (Player $player){
			$this->successForm($player);
		});
		
		$form->setFailureCallable(function (Player $player){
			$this->onVerification($player);
		});
		
		$player->sendForm($form);
	}
	
	public function successForm($player){
		$form = new CustomForm(function (Player $player, $data){
			if(is_null($data)) {
				$this->successForm($player);
				return true;
			}
			$member = new Member($player);
			$member->setScript("verify", "true");
			foreach($this->plugin->events["verify"]["rewards"] as $reward){
				$reward = str_replace('{player}', '"' . $player->getName() . '"', $reward);
				$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $reward);
			}
			$this->plugin->getServer()->broadcastMessage(str_replace("{player}", $player->getName(), $this->plugin->events["verify"]["message"]));	
		});
	 
		$form->setTitle($this->plugin->events["verify"]["title"]);
		$form->addLabel(implode("\n", str_replace("{player}", $player->getName(), $this->plugin->events["verify"]["content"]["ending"])));
		$form->sendToPlayer($player);
	}

}