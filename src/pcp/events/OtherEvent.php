<?php

declare(strict_types=1);

namespace pcp\events;

use pcp\Core;
use pcp\player\Member;
use pcp\utils\Utils;

use pocketmine\event\Listener;
use pocketmine\event\player\{
    PlayerInteractEvent,
	PlayerDeathEvent
};

use pocketmine\event\block\{
    BlockBreakEvent,
    BlockPlaceEvent
};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\item\Item;
use pocketmine\item\{Pickaxe, Hoe, Shovel, Axe, Sword};
use slapper\entities\{SlapperEntity, SlapperHuman};

use codeeeh\MobBossEntity;
use codeeeh\MobEntity;
use codeeeh\WorldBossEntity;
use pocketmine\entity\Creature;

class OtherEvent implements Listener{

    /** @var Core */
    private $plugin;

    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
    }

	public function onInteract(PlayerInteractEvent $event) : void {	
		$player = $event->getPlayer();
        $item = $event->getItem();
		
		// To Prevent Exp orb not obtain
		if($item->getId() == 384 && $player->isCreative()){
			$event->setCancelled();
		}
	    
    }
	
	/**
	 * @priority HIGHEST
	 * @param BlockPlaceEvent $event
	 */
	public function onPlace(BlockPlaceEvent $event) : void {
		$blockid = $event->getBlock()->getItemId();
		if(($blockid == 205 and $event->isCancelled()) or ($blockid == 218 and $event->isCancelled())){
			return;
		}
	}

	/**
	 * @priority HIGHEST
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) : void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		
		$blockItemId = $block->getItemId();

		$inv = $player->getInventory();
		$inHand = $inv->getItemInHand();
		
		if($inHand instanceof Pickaxe){
			if($inHand->hasEnchantment(16) && $inHand->hasEnchantment(18)){
				$fixedItem = Item::get($blockItemId);
				$fixedItem->setCount(1);
				$event->setDrops([$fixedItem]);
			}
		}
		
		if($player->isCreative()) return;
		//if($event->isCancelled()) return;
		
		$blockid = $blockItemId;
		$blockmeta = $block->getDamage();
		
		if(($blockid == 205 and $event->isCancelled()) or ($blockid == 218 and $event->isCancelled())){
			return;
		}
	}
	
	/**
	 * @priority Monitor
	 * @param BlockBreakEvent $event
	 */
	public function onJobBreak(BlockBreakEvent $event) : void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$blockId = $block->getId();
		$blockMeta = $block->getDamage();
		
		$inv = $player->getInventory();
		$inHand = $inv->getItemInHand();
		
		if($player->isCreative()) return;
		if($event->isCancelled()) return;
		
		if(in_array($player->getLevel()->getName(), $this->plugin->events["job"]["worlds"])){
			$member = new Member($player);
			$myjob = $member->getScript("job.name");
			if(($myjob == null) || ($myjob == "None") || ($myjob == "")) return;
			if(!array_key_exists(strtolower($myjob), $this->plugin->events["job"]["roles"])) return;
			$job = $this->plugin->events["job"]["roles"][strtolower($myjob)];
			if($job["work"] == "break"){
				if(empty($job["task"])) return;
				if(in_array(($blockId . ":" . $blockMeta), $job["task"])){
					$goal = $job["goal"];
					$progress = $member->getPoints("job.prg");
					if($progress >= $goal){
						$member->addPoints("job.prg", $job["progress"]);
						$this->salaryGenerator($player, strtolower($member->getScript("job.name")));
					} else {
						$member->addPoints("job.prg", $job["progress"]);
						$player->sendTip(str_replace(["{prefix}", "{progress}", "{jobgoal}"], [$this->plugin->events["job"]["prefix"], $member->getPoints("job.prg"), $job["goal"]], $this->plugin->events["job"]["tip"]["progress"]));
					}
				}
			}
		}
	}
	
	public function onJobKillPlayer(PlayerDeathEvent $event){
		$deadPlayer = $event->getPlayer();
		
		if(!$deadPlayer->isOnline() or !$deadPlayer instanceof Player or $deadPlayer === null) {
			return;
		}

        $cause = $deadPlayer->getLastDamageCause();
		$causeInstance = $cause->getCause();
		if($causeInstance == null) return;

      	$entity = $event->getEntity();

		if($causeInstance === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			$damager = $entity->getLastDamageCause()->getDamager();
			if($damager instanceof Player) {
				if($damager->isCreative()) return;
				$member = new Member($damager);
				$myjob = $member->getScript("job.name");
				if(in_array($damager->getLevel()->getName(), $this->plugin->events["job"]["worlds"])){
					if(($myjob == null) || ($myjob == "None") || ($myjob == "")) return;
					if(!array_key_exists(strtolower($myjob), $this->plugin->events["job"]["roles"])) return;
					$job = $this->plugin->events["job"]["roles"][strtolower($myjob)];
					if($job["work"] == "kill"){
						$goal = $job["goal"];
						$progress = $member->getPoints("job.prg");
						if($progress >= $goal){
							$member->setPoints("job.prg", 0);
							$this->salaryGenerator($damager, strtolower($member->getScript("job.name")));
						} else {
							$member->addPoints("job.prg", $job["progress"]);
							$damager->sendTip(str_replace(["{prefix}", "{progress}", "{jobgoal}"], [$this->plugin->events["job"]["prefix"], $member->getPoints("job.prg"), $job["goal"]], $this->plugin->events["job"]["tip"]["progress"]));
						}
					}
				}
			}
		}
		
	}
	
	public function onJobKillEntity(EntityDeathEvent $event) : void {
		$entity = $event->getEntity();
		if(($entity instanceof MobEntity) or ($entity instanceof MobBossEntity) or ($entity instanceof WorldBossEntity) or ($entity instanceof Creature)){
			$cause = $entity->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
				$damager = $cause->getDamager();
				if($damager instanceof Player && $damager->isOnline()){
					if($damager->isCreative()) return;
					$member = new Member($damager);
					$myjob = $member->getScript("job.name");
					if(in_array($damager->getLevel()->getName(), $this->plugin->events["job"]["worlds"])){
						if(($myjob == null) || ($myjob == "None") || ($myjob == "")) return;
						if(!array_key_exists(strtolower($myjob), $this->plugin->events["job"]["roles"])) return;
						$job = $this->plugin->events["job"]["roles"][strtolower($myjob)];
						if($job["work"] == "kill"){
							$goal = $job["goal"];
							$progress = $member->getPoints("job.prg");
							if($progress >= $goal){
								$member->setPoints("job.prg", 0);
								$this->salaryGenerator($damager, strtolower($member->getScript("job.name")));
							} else {
								$member->addPoints("job.prg", $job["progress"]);
								$damager->sendTip(str_replace(["{prefix}", "{progress}", "{jobgoal}"], [$this->plugin->events["job"]["prefix"], $member->getPoints("job.prg"), $job["goal"]], $this->plugin->events["job"]["tip"]["progress"]));
							}
						}
					}
				}	
			}
		}
	}
	
	public function salaryGenerator(Player $player, string $job){
		$estimatedBal = mt_rand($this->plugin->events["job"]["roles"]["$job"]["salary"]["bal"][0], $this->plugin->events["job"]["roles"]["$job"]["salary"]["bal"][1]);
		$estimatedExp = mt_rand($this->plugin->events["job"]["roles"]["$job"]["salary"]["exp"][0], $this->plugin->events["job"]["roles"]["$job"]["salary"]["exp"][1]);
		$member = new Member($player);
		$member->addPoints("balance", $estimatedBal);
		$this->plugin->cx->data->addVal($player, "exp", $estimatedExp);
		$message = str_replace(["{prefix}", "{job}", "{bal}", "{exp}"], [$this->plugin->events["job"]["prefix"], Core::getInstance()->getStats()->getAlpha($player, "job.name"), $estimatedBal, $estimatedExp], $this->plugin->events["job"]["tip"]["message"]);
		$player->sendMessage($message);
	}
	
	/**
	 * @priority Monitor
	 * @param BlockBreakEvent $event
	 */
	public function onSkillsBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		
		$inv = $player->getInventory();
		$inHand = $inv->getItemInHand();
		$stats = $this->plugin->economy;
		
		if($player->isCreative()) return;
		if($event->isCancelled()) return;
		
		switch($block->getId()){
			case Item::WHEAT_BLOCK:
            case Item::BEETROOT_BLOCK:
            case Item::PUMPKIN:
            case Item::MELON_BLOCK:
            case Item::CARROT_BLOCK:
            case Item::POTATO_BLOCK:
            case Item::SUGARCANE_BLOCK:
				if($inHand instanceof Hoe){
					$this->calculateSkillLevel($player, "Hoe");
				}
				return;
			case Item::STONE:
            case Item::DIAMOND_ORE:
            case Item::GOLD_ORE:
            case Item::REDSTONE_ORE:
            case Item::IRON_ORE:
            case Item::COAL_ORE:
            case Item::EMERALD_ORE:
            case Item::OBSIDIAN:
				if($inHand instanceof Pickaxe){
					$this->calculateSkillLevel($player, "Pickaxe");
				}
				return;
			case Item::DIRT:
            case Item::GRASS:
            case Item::GRASS_PATH:
            case Item::FARMLAND:
            case Item::SAND:
            case Item::GRAVEL:
                if($inHand instanceof Shovel){
					$this->calculateSkillLevel($player, "Shovel");
				}
                return;
			case Item::LOG:
            case Item::LOG2:
            case Item::LEAVES:
            case Item::LEAVES2:
				if($inHand instanceof Axe){
					$this->calculateSkillLevel($player, "Axe");
				}
				return;
		}
	}
	
	/**
	 * @priority Monitor
	 * @param EntityDamageByEntityEvent $event
	 */
	public function onSkillDamage(EntityDamageByEntityEvent $event){
		$entity = $event->getEntity();
		$stats = $this->plugin->economy;
		
		if($event->isCancelled()) return;
		if(($entity instanceof SlapperEntity) or ($entity instanceof SlapperHuman)) return;
		
		switch($event->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				$damager = $event->getDamager();
				if($damager instanceof Player){
					if($damager->isCreative()) return;
					$inHand = $damager->getInventory()->getItemInHand();
					if($inHand instanceof Sword){
						$this->calculateSkillLevel($damager, "Sword");
					} else if ($inHand instanceof Axe) {
						$this->calculateSkillLevel($damager, "Axe");
					}
				}
			break;
		}
	}
	
	public function calculateSkillLevel(Player $player, string $type){
		$stats = new Member($player);
		$skill = strtolower($type);
		$goal = ($stats->getPoints("skills." . $skill . ".lvl") * 100);
		if ($stats->getPoints("skills." . $skill . ".exp") >= $goal) {
			$stats->addPoints("skills." . $skill . ".lvl", 1);
			$stats->setPoints("skills." . $skill . ".exp", 0);
			$utils = new Utils();
			$utils->playSound("enderman_teleport", $player);
			$player->sendMessage(str_replace(["{prefix}", "{slvl}", "{stype}"], [$this->plugin->events["skills"]["prefix"], $stats->getPoints("skills.".$skill.".lvl"), $type], $this->plugin->events["skills"]["text"]));
		} else {
			$stats->addPoints("skills." . $skill . ".exp", 1);
		}
	}
}