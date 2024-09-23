<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;

use pocketmine\Player;
use pocketmine\event\Listener;

use pocketmine\event\player\{
    PlayerDropItemEvent,
    PlayerItemHeldEvent,
    PlayerCommandPreprocessEvent,
    PlayerGameModeChangeEvent,
    PlayerExhaustEvent,
	//PlayerItemUseEvent,
	PlayerInteractEvent,
	PlayerToggleFlightEvent,
	PlayerBucketEmptyEvent
};

use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityLevelChangeEvent,
	ExplosionPrimeEvent
};

use pocketmine\event\block\{
    BlockBreakEvent,
    BlockPlaceEvent,
	SignChangeEvent
};

use codeeeh\PetEntity;

class FlagEvent implements Listener {
  
	/** @var Core */
	private $plugin;
	
	private $trapdoors = array(96, 167), $doors = array(64, 71, 193, 194, 195, 196, 197), $storage = array(4, 130, 146, 205, 218, 58, 61, 116, 145), $itemframe = array(389, 199);
  
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerInteractEvent $event
     * @priority HIGHEST
	 */
	public function banItem(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		$world = $player->getLevel()->getFolderName();
		if(array_key_exists($world, $this->plugin->events["flag"]["item"])){
			if(in_array(strtolower($item->getVanillaName()), $this->plugin->events["flag"]["item"]["$world"])){
				$event->setCancelled();
				$player->sendTitle(
					str_replace(["{prefix}", "{type}"], [$this->plugin->events["flag"]["prefix"], $item->getVanillaName()], $this->plugin->events["flag"]["title"][0]),
					str_replace(["{prefix}", "{type}"], [$this->plugin->events["flag"]["prefix"], $item->getVanillaName()], $this->plugin->events["flag"]["title"][1])
				);
			}
		}
	}
	
	/**
	 * @param PlayerCommandPreprocess $event
     * @priority HIGHEST
	 */
	public function banCmds(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$world = $player->getLevel()->getFolderName();
		$command = explode(" ", $event->getMessage());
		
		if(strlen($command[0]) <= 1) return;
		try{
		  if(in_array($command[0], $this->plugin->events["flag"]["cmds"]["$world"]) ) {
				$event->setCancelled();
				$player->sendTitle(
					str_replace(["{prefix}", "{type}"], [$this->plugin->events["flag"]["prefix"], $command[0]], $this->plugin->events["flag"]["title"][0]),
					str_replace(["{prefix}", "{type}"], [$this->plugin->events["flag"]["prefix"], $command[0]], $this->plugin->events["flag"]["title"][1])
				);
		  }
		}catch(\ErrorException $ex){ }
	}
	
	/**
    * @param ExplosionPrimeEvent $boom
    * @priority HIGHEST
    */
    public function onTerroristAttack(ExplosionPrimeEvent $event) : void {
        $world = $event->getEntity()->getLevel()->getFolderName();
        if($this->plugin->flags->hasSecurity($world) and $this->plugin->flags->getExplosion($world)) $event->setBlockBreaking(false);
    }
	
	/**
    * @param PlayerGameModeChangeEvent $event
    * @priority HIGHEST
    */
	public function onGMChange(PlayerGameModeChangeEvent $event) : void {
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if($this->plugin->flags->hasSecurity($world) and $this->plugin->flags->getGMChange($world)){
            $player = $event->getPlayer();
            if($player->isCreative() and $event->getNewGamemode() <> 1){
            	$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->getCursorInventory()->clearAll();
			}
			if($player->isSurvival() or $player->isAdventure()){
				if($event->getNewGamemode() == 1){
					$player->getInventory()->clearAll();
					$player->getArmorInventory()->clearAll();
					$player->getCursorInventory()->clearAll();
				}
			}
		}
	}
	
	/**
    * @param EntityLevelChangeEvent $event
    * @priority HIGHEST
    */
	public function onTP(EntityLevelChangeEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			$world = $event->getTarget()->getFolderName();
			if($this->plugin->flags->hasSecurity($world)){
                $this->refreshGM($player, $this->plugin->flags->getWorldMode($world));
                if($this->plugin->flags->getWorldScale($world)){
					$player->setScale(1.0);
				}
			}
		}
	}
	
	/**
	 * @param PlayerDropItemEvent $event
     * @priority HIGHEST
     */
    public function onDrop(PlayerDropItemEvent $event) : void {
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if($this->plugin->flags->hasSecurity($world))
		{
			switch($event->getPlayer()->getGamemode())
			{
				case 0:
				if($this->plugin->flags->getSDrop($world)) $event->setCancelled();
				break;
				
				case 1:
				if($this->plugin->flags->getCDrop($world)) $event->setCancelled();
				break;
			}
		}
    }
	
	public function onInteract(PlayerInteractEvent $event) {
		if($event->getPlayer()->isOp()) return;
		
		$world = $event->getPlayer()->getLevel()->getFolderName();
		$block = $event->getBlock();
		
		if($event->getPlayer()->isCreative() && in_array($block->getId(), $this->storage)) return $event->setCancelled();
		
		if($this->plugin->flags->hasSecurity($world)){
			if(in_array($block->getId(), $this->doors) && $this->plugin->flags->getDoorBan($world)) return $event->setCancelled();
			if(in_array($block->getId(), $this->trapdoors) && $this->plugin->flags->getTrapdoorBan($world)) return $event->setCancelled();
			if(in_array($block->getId(), $this->storage) && $this->plugin->flags->getStorageBan($world)) return $event->setCancelled();
			if(in_array($block->getId(), $this->itemframe) && $this->plugin->flags->getItemFrame($world)) return $event->setCancelled();
		}
    }
	
	/**
	 * @param PlayerToggleFlightEvent $event
     * @priority HIGHEST
     */
	public function onFly(PlayerToggleFlightEvent $event) : void {
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if($this->plugin->flags->hasSecurity($world) and $this->plugin->flags->getFlyBan($world)) {
			if(!$event->getPlayer()->isCreative()) {
				$event->setCancelled();
			}
		}
    }
	
	/**
     * @param EntityDamageEvent $event
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageEvent $event) : void {
		if(!$event->getEntity() instanceof Player) return;
        if($event->getEntity() instanceof Player) {
			$world = $event->getEntity()->getLevel()->getFolderName();
			if($this->plugin->flags->hasSecurity($world)) {
				switch( (int) $event->getCause() ) {
					case 1: //pvp
						if ($this->plugin->flags->getPvpDamage($world)){
							if(($event->getDamager() instanceof Player) or ($event->getDamager() instanceof PetEntity)) $event->setCancelled();
						}
					break;
					case 2: //projectile
						if ($this->plugin->flags->getProjectileDamage($world)){
							$event->setCancelled();
						}
					break;
					case 3: case 8: //suffocate
						if ($this->plugin->flags->getSuffocateDamage($world)){
							$event->setCancelled();
						}
					break;
					case 4: //fall
						if ($this->plugin->flags->getFallDamage($world)){
							$event->setCancelled();
						}
					break;
					case 5: case 6: case 7: //burn
						if ($this->plugin->flags->getBurnDamage($world)){
							$event->setCancelled();
						}
					break;
					case 11:
						if ($this->plugin->events["flag"]["antivoid"] === true) { //void
							$event->setCancelled();
							$event->getEntity()->teleport( $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn() );
						}
					break;
					default:
						if ($this->plugin->flags->getOtherDamage($world)){
							$event->setCancelled();
						}
				}
			}
		}
    }
	
	/**
     * @param PlayerExhaustEvent $event
     * @priority HIGHEST
	 */	
	public function onExhaust(PlayerExhaustEvent $event) : void {
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if($this->plugin->flags->hasSecurity($world) and $this->plugin->flags->getAntiStarve($world)){
			$event->setCancelled();
		}
	}
	
	/**
    * @param PlayerBucketEmptyEvent $event
    * @priority HIGHEST
    */
	public function onLiquid(PlayerBucketEmptyEvent $event){
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if($this->plugin->flags->hasSecurity($world)){
			if($this->plugin->flags->getLock($world)){ //locked
				$event->setCancelled();
			}
			if($this->plugin->flags->getLiquid($world)){ //liquid
				if(!$event->getPlayer()->hasPermission($this->plugin->events["flag"]["perm"])) { //not permitted
					$event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
     * @priority HIGHEST
     */				   
	public function onBreak(BlockBreakEvent $event){
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if ($event->isCancelled()) return;
		if($this->plugin->flags->hasSecurity($world)){
			if($this->plugin->flags->getLock($world)){ //locked -> cancelled
				$event->setCancelled(); return;
			}
			if($this->plugin->flags->getEdit($world)){ //protected
				if(!$event->getPlayer()->hasPermission($this->plugin->events["flag"]["perm"])) { //not permitted
					$event->setCancelled(); return;	
				}
			}
		}
		//return;
    }

	/**
	 * @param BlockPlaceEvent $event
     * @priority HIGHEST
     */
	public function onPlace(BlockPlaceEvent $event){
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if ($event->isCancelled()) return;
		if($this->plugin->flags->hasSecurity($world)){
			if($this->plugin->flags->getLock($world)){ //locked -> cancelled
				$event->setCancelled();
				return;
			}
			if($this->plugin->flags->getEdit($world)){ //protected
				if(!$event->getPlayer()->hasPermission($this->plugin->events["flag"]["perm"])) { //not permitted
					$event->setCancelled();	
					return;
				}
			}
		}
    }
	
	
	/**
	 * @param SignChangeEvent $event
     * @priority HIGHEST
     */
	public function onSign(SignChangeEvent $event){
		$world = $event->getPlayer()->getLevel()->getFolderName();
		if ($event->isCancelled()) return;
		if($this->plugin->flags->hasSecurity($world)){
			if($this->plugin->flags->getLock($world)){ //locked -> cancelled
				$event->setCancelled();
				return;
			}
			if($this->plugin->flags->getEdit($world)){ //protected
				if(!$event->getPlayer()->hasPermission($this->plugin->events["flag"]["perm"])) { //not permitted
					$event->setCancelled();	
					return;
				}
			}
		}
	}
	
	private function refreshGM(Player $player, int $gm) {
		if($player->isOp() or $player->hasPermission($this->plugin->events["flag"]["perm"])) return;
		$player->setGamemode($gm);
		$player->sendTip(str_replace("{prefix}", $this->plugin->events["flag"]["prefix"], $this->plugin->events["flag"]["change"]));
	}

}