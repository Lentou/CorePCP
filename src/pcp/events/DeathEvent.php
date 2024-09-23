<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Member;
use _64FF00\PurePerms\PurePerms;

use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\{
    EntityDamageByEntityEvent,
    EntityDamageEvent,
    EntityDamageByBlockEvent,
    EntityLevelChangeEvent,
	EntityDeathEvent
};
use pocketmine\entity\Creature;
use pocketmine\entity\Human;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

use codeeeh\MobBossEntity;
use codeeeh\MobEntity;
use codeeeh\WorldBossEntity;

class DeathEvent implements Listener {
  
	/** @var Core */
	private $plugin;
	
	public function __construct(Core $plugin){
		$this->plugin = $plugin;
	}
	
	public function onDeath(PlayerDeathEvent $event) : void {
		$deadPlayer = $event->getPlayer();
		
		if(!$deadPlayer->isOnline() or !$deadPlayer instanceof Player or $deadPlayer === null) {
			return;
		}

        $cause = $deadPlayer->getLastDamageCause();
		$causeInstance = $cause->getCause();

		if($causeInstance == null) return;

      	$entity = $event->getEntity();
      	$msgderive = $event->deriveMessage($entity->getDisplayName(), $entity->getLastDamageCause());
		$this->deathCast($msgderive, $entity, $deadPlayer, $event);

		if($causeInstance === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			$ally = new Member($deadMoney);
			$enemy = new Member($damager);
			$core = Core::getInstance()->getStats();
			$entity = $cause->getEntity();
			$damager = $entity->getLastDamageCause()->getDamager();
			if($damager instanceof Player) {
				$killMoney = (mt_rand($this->plugin->events["death"]["rewards"]["money"]["killer"][0], $this->plugin->events["death"]["rewards"]["money"]["killer"][1]));
				$deadMoney = (mt_rand($this->plugin->events["death"]["rewards"]["money"]["victim"][0], $this->plugin->events["death"]["rewards"]["money"]["victim"][1]));
				
				$enemy->addPoints("balance", $killMoney);
				$ally->takePoints("balance", $deadMoney);
				
				$enemy->addPoints("kills", 1);
				$ally->addPoints("deaths", 1);
			
				$this->plugin->cx->data->addVal($damager, "exp", mt_rand($this->plugin->events["death"]["rewards"]["exp"][0], $this->plugin->events["death"]["rewards"]["exp"][1]));
				$this->plugin->cx->data->addVal($damager, "elo", mt_rand($this->plugin->events["death"]["rewards"]["elo"][0], $this->plugin->events["death"]["rewards"]["exp"][1]));
				
				$enemy->addPoints("streak", 1);
				
				$bounty = (mt_rand($this->plugin->events["death"]["rewards"]["bounty"][0], $this->plugin->events["death"]["rewards"]["bounty"][1]));
				$enemy->addPoints("bounty", $bounty);
				
				$msgs = $this->plugin->events["death"]["rand"]["opening"];
				$this->plugin->getServer()->broadcastMessage(str_replace(
					["{prefix}", "{player}", "{streak}", "{randmsg}", "{bounty}"], 
					[$this->plugin->events["death"]["prefix"]["streak"], $damager->getName(), $core->getPoints($damager, "streak"), $msgs[array_rand($msgs)], $core->getPoints($damager, "bounty")], 
					$this->plugin->events["death"]["format"]["streak"])
				);
				
				$enemy->addPoints("balance", $ally->getPoints("bounty"));
				
				if ($ally->getPoints("streak") != 0) {
				#if($core->getPoints($deadPlayer, "streak") != 0){
					$msgs = $this->plugin->events["death"]["rand"]["ending"];
					$this->plugin->getServer()->broadcastMessage(str_replace(
						["{prefix}", "{player}", "{streak}", "{randmsg}", "{victim}", "{bounty}"], 
						[$this->plugin->events["death"]["prefix"]["streak"], $damager->getName(), $enemy->getPoints("bounty"), $msgs[array_rand($msgs)], $deadPlayer->getName(), $ally->getPoints("bounty")], 
						$this->plugin->events["death"]["format"]["ended"])
					);
					$ally->setPoints("streak", 0);
				}
				$ally->setPoints("bounty", 0);
			}
		}
	}
	
	public function onKillMobs(EntityDeathEvent $event) : void {
		$entity = $event->getEntity();
		if(($entity instanceof MobEntity) or ($entity instanceof MobBossEntity) or ($entity instanceof WorldBossEntity) or ($entity instanceof Creature)){
			$cause = $entity->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
				$killer = $cause->getDamager();
				if($killer instanceof Player && $killer->isOnline()){
					if($killer->isSurvival()){
						$member = new Member($killer);
						$member->addPoints("mobs", 1);
					}
				}
			}
		}
	}
	
	public function onRespawn(PlayerRespawnEvent $event) : void {
		$player = $event->getPlayer();
		$player->sendTitle($this->plugin->events["death"]["respawn"]["title"][0], $this->plugin->events["death"]["respawn"]["title"][1]);
		if(in_array($player->getLevel()->getName(), $this->plugin->events["death"]["respawn"]["worlds"])){
			$event->setRespawnPosition($player->getLevel()->getSpawnLocation());
		}

	}

	public function deathCast($msgderive, $entity, $deadPlayer, $event) {
		switch($msgderive){
			case "death.attack.generic":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$vcrank = "N/A";
				$msgs = $this->plugin->events["death"]["rand"]["generic"];

				$playerKilled = str_replace(
					["{prefix}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
			case "death.attack.player":
				$killer = $entity->getLastDamageCause()->getDamager();
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$krank = $this->plugin->perms->getPlayerManager()->getGroup($killer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$krank = PurePerms::getInstance()->getUserDataMgr()->getGroup($killer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				//$kcrank = Core::getInstance()->events["ranks"]["group"]["$krank"][0];
				$kcrank = "N/A";
				$vcrank = "N/A";
				$msgs = $this->plugin->events["death"]["rand"]["player"];

				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{krank}", "{victim}", "{killer}", "{randmsg}", "{item}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $kcrank, $deadPlayer->getName(), $killer->getName(), $msgs[array_rand($msgs)], $killer->getInventory()->getItemInHand()->getName()],
					$this->plugin->events["death"]["format"]["slained"]
				);

				$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.mob":
				$mob = $entity->getLastDamageCause()->getDamager();
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["player"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}", "{mob}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)], $mob->getName()],
					$this->plugin->events["death"]["format"]["killed"]
				);
				$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.outOfWorld":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["outOfWorld"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.inWall":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["inWall"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.onFire":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["flame"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.inFire":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["flame"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.drown":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["drown"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.magic":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["magic"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.cactus":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["cactus"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.fell.accident.generic":
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["highplace"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.arrow":
				$mob = $entity->getLastDamageCause()->getDamager();
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["shot"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}", "{mob}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)], $mob->getName()],
					$this->plugin->events["death"]["format"]["suicide"]
				);
				$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.lava":
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["lava"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

		   		$event->setDeathMessage($playerKilled);
			break;
		  	case "death.attack.explosion.player":
				$mob = $entity->getLastDamageCause()->getDamager();
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["explosion"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}", "{mob}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)], $mob->getName()],
					$this->plugin->events["death"]["format"]["killed"]
				);
				$event->setDeathMessage($playerKilled);
			break;
			default:
				//$vrank = $this->plugin->perms->getPlayerManager()->getGroup($deadPlayer);
				//$vrank = PurePerms::getInstance()->getUserDataMgr()->getGroup($deadPlayer);
				//$vcrank = Core::getInstance()->events["ranks"]["group"]["$vrank"][0];
				$msgs = $this->plugin->events["death"]["rand"]["generic"];
				$vcrank = "N/A";
				$playerKilled = str_replace(
					["{prefix}", "{vrank}", "{victim}", "{randmsg}"],
					[$this->plugin->events["death"]["prefix"]["death"], $vcrank, $deadPlayer->getName(), $msgs[array_rand($msgs)]],
					$this->plugin->events["death"]["format"]["suicide"]
				);

			   	$event->setDeathMessage($playerKilled);
				
		}
	}
}