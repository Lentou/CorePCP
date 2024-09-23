<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Member;
use pocketmine\Player;
use pocketmine\Server;

use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\entity\{
		Effect,
		EffectInstance,
		Living,
		Entity
	};;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pcp\libs\score\ScoreFactory;
use _64FF00\PurePerms\PurePerms;
use pcp\player\Chaser;

class LoginEvent implements Listener {

	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function getDevice(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
        if ($packet instanceof LoginPacket) {
            $device = $packet->clientData["DeviceOS"];
			$username = $packet->username;
            $types = ["UN", "PE", "iOS", "MacOS", "FireOS", "GearVR", "HoloLens", "Win10", "W", "D", "Orbis", "NX"];
			Chaser::registerOs($username, $types[$device]);
        }
    }
	
	public function onLogin(PlayerLoginEvent $event) {
		$player = $event->getPlayer();
		if($this->plugin->events["login"]["join"]["alwaysspawn"] === true){
			$player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		}
		$member = new Member($player);
		$member->update();
	}
	
	public function onWhitelist(PlayerPreLoginEvent $event) {
		$player = $event->getPlayer();
		if(!$player->isWhitelisted()) {
			$event->setKickMessage($this->plugin->events["login"]["whitelist"]["reason"]);
			$event->setCancelled();
		}
	}
	
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$this->greetEffect($player);

		if($this->plugin->events["motd"]["enable"] === false){
			$this->plugin->updateMOTD();
		}

		$member = new Member($player);
		$player->setDisplayName($member->getScript("tag"));
		
		$join = str_replace(
			["{player}", "{group}", "{tag}"], 
			[$player->getName(), Core::getInstance()->placeholder->checkTags("group", $player, 0), $player->getDisplayName()], 
			($player->hasPlayedBefore() ? $this->plugin->events["login"]["join"]["format"]["default"] : $this->plugin->events["login"]["join"]["format"]["first"])
		);
		$joinmessage = implode("\n", str_replace("{player}", $player->getName(), $this->plugin->events["login"]["join"]["welcome"]));
		$player->sendMessage($joinmessage);
		$event->setJoinMessage($join);

	}
	
	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();

		$quit = str_replace(
			["{player}", "{group}", "{tag}"], 
			[$player->getName(), Core::getInstance()->placeholder->checkTags("group", $player, 0), $player->getDisplayName()], 
			($player->hasPlayedBefore() ? $this->plugin->events["login"]["quit"]["format"]["default"] : $this->plugin->events["login"]["quit"]["format"]["first"])
		);
		
		$event->setQuitMessage($quit);
		
		if($this->plugin->events["motd"]["enable"] === false){
			$this->plugin->updateMOTD(-1);
		}
		
		$this->checkApply($player);
		ScoreFactory::removeScore($player);
	
	}
	
	public function greetEffect($player){
		if($this->plugin->events["login"]["join"]["effect"]["enable"] === true){
			$effectID = Effect::getEffect((int) $this->plugin->events["login"]["join"]["effect"]["sideeffect"]);
			$duration = (int) $this->plugin->events["login"]["join"]["effect"]["duration"];
			$player->addEffect(new EffectInstance($effectID, $duration * 20, 0, false));
        }
		if($this->plugin->events["login"]["join"]["title"]["enable"] === true){
			$player->sendTitle(str_replace("{player}", $player->getName(), $this->plugin->events["login"]["join"]["title"]["format"][0]), str_replace("{player}", $player->getName(), $this->plugin->events["login"]["join"]["title"]["format"][1]));
		}
        if($this->plugin->events["login"]["join"]["effect"]["guardian"] === true){
        	$pk = new LevelEventPacket();
            $pk->evid = LevelEventPacket::EVENT_GUARDIAN_CURSE;
            $pk->data = 0;
            $pk->position = $player->asVector3();
            $player->dataPacket($pk);
        }
		if($player->isSurvival()){ 
			$player->setAllowFlight(false);
			$player->setFlying(false);
		}
	}
	
	public function checkApply($player){
		if (Chaser::isUnliModePlayer($player)) {
			Chaser::initUnliModePlayer($player, false);
		}
		if (Chaser::isNoScoreHudPlayer($player)){
			Chaser::initScoreHudPlayer($player, false);
		}
	}
	
}