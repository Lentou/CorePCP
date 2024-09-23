<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Chaser;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class ChatEvent implements Listener {
  
	/** @var Core */
	private $plugin;
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	/**
     * @param PlayerChatEvent $event
     * @priority HIGHEST
     * @ignoreCancelled true
     */
	public function chatFormat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		
		$level = $player->getLevel()->getFolderName();
		
		if($this->plugin->event->getNested("chat.text.$level")){
			$format = $this->plugin->event->getNested("chat.text.$level");
		} else {
			$format = $this->plugin->event->getNested("chat.text.default");
		}
		
		$event->setFormat($this->plugin->placeholder->getNormalTags($player, $event->getFormat()));
		
	}
	
	public function tagFormat(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		//$format = $this->plugin->events["chat"]["tag"]["name"];
		
		//$player->setNameTag($this->plugin->placeholder->getNormalTags($player, $format));
		
		if($this->plugin->events["chat"]["tag"]["op"]["enable"] === true){
			if($player->isOp()){
				$player->setDisplayName($player->getDisplayName(). $this->plugin->events["chat"]["tag"]["op"]["tag"]);
			}
		}
	}
	
	public function joinTag(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$player->setNameTag($this->plugin->placeholder->getNormalTags($player, $player->getNameTag()));
		if($this->plugin->events["chat"]["tag"]["hide"]["enable"] === true){
		   $level = $player->getLevel()->getFolderName();
		   $player->setNameTagAlwaysVisible(!in_array($level, $this->plugin->events["chat"]["tag"]["hide"]["worlds"]));
	    }
	}
	
	public function tpTag(EntityLevelChangeEvent $event)
	{
		if(($player = $event->getEntity()) instanceof Player){
			if($this->plugin->events["chat"]["tag"]["hide"]["enable"] === true){
				$level = $event->getTarget()->getName();
				$player->setNameTagAlwaysVisible(!in_array($level, $this->plugin->events["chat"]["tag"]["hide"]["worlds"]));
			}
		}
	}
	
	public function onCommandFor(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$msgs = $event->getMessage();
		
        if(!$player->hasPermission("core.chat")){
			if($this->plugin->events["chat"]["slow"]["enable"] === true){
				if (Chaser::isCooldownPlayer($player)) {
					if (Chaser::inCooldown($player, "chat", $this->plugin->events["chat"]["slow"]["sec"])) {
						$event->setCancelled();
						$player->sendMessage(str_replace("{prefix}", $this->plugin->events["chat"]["prefix"], $this->plugin->events["chat"]["slow"]["notice"]));	
					} else {
						Chaser::delCooldown($player, "chat");
						Chaser::setCooldown($player, "chat");
					}
				} else {
					Chaser::setCooldown($player, "chat");
				}
			}
			$event->setMessage($this->plugin->events["chat"]["words"]["capital"] ? str_replace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $msgs) : str_ireplace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $msgs));
		}	
    }
	
	public function onChat(PlayerChatEvent $event) : void {	
        $player = $event->getPlayer();
		$msgs = $event->getMessage();	
		foreach(Server::getInstance()->getOnlinePlayers() as $staffs){
			if(substr($msgs, 0, 1) === "#"){
				if($staffs->hasPermission("core.staff")){
					$event->setCancelled(true);
					$format = $this->plugin->events["chat"]["text"]["staffchat"];
					$info = $this->checkPermEvent($msgs, $player, $this->plugin->placeholder->getNormalTags($player, $format));
					$staffs->sendMessage($info);
					Server::getInstance()->getLogger()->info($info);
				} else {
					$event->setCancelled(true);
					$staffs->sendMessage(str_replace("{prefix}", $this->plugin->events["chat"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
				}
			}
		}
		if(!$player->hasPermission("core.chat")){
			$event->setMessage($this->plugin->events["chat"]["words"]["capital"] ? str_replace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $msgs) : str_ireplace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $msgs));
		}
        
	}
	
	public function onSign(SignChangeEvent $event){
		$player = $event->getPlayer();
		if(!$player->hasPermission("core.chat")){
			$event->setLines($this->plugin->events["chat"]["words"]["capital"] ? str_replace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $event->getLines()) : str_ireplace($this->plugin->events["chat"]["words"]["messages"], $this->plugin->mosaicList, $event->getLines()));
		}
	}
	
	//public function checkPermEvent($message, $player, $string){
		//if($player->hasPermission("core.colorchat")){
            //$string = str_replace("{msg}", TextFormat::colorize($message), $string);
        //}else{
            //$string = str_replace("{msg}", TextFormat::clean($message), $string);
        //}
		//return $string;
	//}
}