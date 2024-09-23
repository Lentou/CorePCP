<?php

namespace pcp\utils;

use pcp\Core;
use pcp\utils\DamageEntity;

use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Entity;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class DamageEffect implements Listener {
	
	/** @var Core */
    private $plugin;

    public function __construct(Core $plugin) {
		$this->plugin = $plugin;
	}

	public function onDamageEvent(EntityDamageEvent $event) {
		if ($this->plugin->events["indicator"]["version"]["event"] === true) {
			if (!$event->getEntity() instanceof Entity) return;
			
			if ($event->getBaseDamage() < 3) {
				$color = TextFormat::GREEN;
			} else if ($event->getBaseDamage() < 6) {
				$color = TextFormat::YELLOW;
			} else {
				$color = TextFormat::RED;
			}
			
			$pos = $event->getEntity()->add(0.1 * mt_rand(1, 9) * mt_rand(-1, 1), 0.1 * mt_rand(5, 9), 0.1 * mt_rand(1, 9) * mt_rand(-1, 1));
			$damageParticle = new FloatingTextParticle($pos, "", $color . "-" . $event->getBaseDamage());
			
			if ($event->getEntity()->getHealth() < 7) {
				$color = TextFormat::RED;
			} else if ($event->getEntity()->getHealth() < 14) {
				$color = TextFormat::YELLOW;
			} else {
				$color = TextFormat::GREEN;
			}
			
			$pos = $event->getEntity()->add(0, 2.5, 0);
			$healthParticle = new FloatingTextParticle($pos, "", $color . ($event->getEntity()->getHealth() - $event->getBaseDamage()) . " / " . $event->getEntity()->getMaxHealth());
			#$level = $event->getEntity()->getLevel();
			$level = $event->getEntity()->getLevelNonNull();
			
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
				function (int $currentTick) use ($damageParticle, $level, $event): void {
					#if($level instanceof Level){
						$this->eventCheck($damageParticle, $level, $event);
					#}
				}
			), 1);
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
				function (int $currentTick) use ($healthParticle, $level, $event): void {
					#if($level instanceof Level){
						$this->eventCheck($healthParticle, $level, $event);
					#}
				}
			), 1);
		}
	}

	public function eventCheck(FloatingTextParticle $particle, Level $level, $event) {
		if ($event instanceof EntityDamageEvent) if ($event->isCancelled()) return;
		#if($level instanceof Level){
			$level->addParticle($particle);
			#$levels = $event->getEntity()->getLevel();
			$levels = $event->getEntity()->getLevelNonNull();
			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
				function (int $currentTick) use ($particle, $levels): void {
					#if($levels instanceof Level){
						$this->deleteParticles($particle, $levels);
					#}
				}
			), 20);
		#}
	}

	public function deleteParticles(FloatingTextParticle $particle, Level $level) {
		#if($level instanceof Level){
			$particle->setInvisible();
			$level->addParticle($particle);
		#}
	}
	
	public function onDamageEntity(EntityDamageEvent $event) {
		if ($this->plugin->events["indicator"]["version"]["entity"] === true) {
			if ($event instanceof EntityDamageByEntityEvent) {
				$damager = $event->getDamager();
				$victim = $event->getEntity();
				
				if ($event->isCancelled()) {
					return;
				}
				
				if ($victim instanceof DamageEntity) {
					$event->setCancelled();
					return;
				}
				
				if ($damager instanceof Player && $victim instanceof Player) {
					$motion = new Vector3(lcg_value() * 0.2 - 0.1, 0.5, lcg_value() * 0.2 - 0.1);
					
					$nbt = Entity::createBaseNBT($victim->add(0, 1, 0), $motion, 0, 0);
					
					$skinTag = $victim->namedtag->getCompoundTag("Skin");
					assert($skinTag !== null);
					$nbt->setTag(clone $skinTag);
					
					$damageEntity = Entity::createEntity("DamageEntity", $victim->getLevelNonNull(), $nbt, $victim);
					if ($damageEntity !== null) {
						$damageEntity->getDataPropertyManager()->setFloat(38, 0);
						if ($event->getFinalDamage() < 3) {
							$color = TextFormat::GREEN;
						} else if ($event->getFinalDamage() < 6) {
							$color = TextFormat::YELLOW;
						} else {
							$color = TextFormat::RED;
						}
						$damageEntity->setNameTag($color . "-" . $event->getFinalDamage());
						$damageEntity->spawnToAll();
					}
				}
			}
		}
	}
	
}