<?php

declare(strict_types=1);

namespace pcp\utils;

use pocketmine\entity\Human;

class DamageEntity extends Human {

	protected $age = 0;
	
	public static function init() : void {
		parent::init();
	}
	
	public function entityBaseTick(int $tickDiff = 1) : bool {
		if ($this->closed) {
			return false;
		}
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if (!$this->isFlaggedForDespawn()) {
			$this->age += $tickDiff;
			if ($this->age > 20) {
				$this->flagForDespawn();
			}
		}
		return $hasUpdate;
	}
	
	
}