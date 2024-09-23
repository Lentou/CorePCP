<?php

declare(strict_types=1);

namespace pcp\utils;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;

use pocketmine\math\Vector3;

use pocketmine\level\sound\{
	FizzSound,
	AnvilBreakSound,
	AnvilFallSound,
	AnvilUseSound,
	BlazeShootSound,
	ClickSound,
	DoorBumpSound,
	DoorCrashSound,
	DoorSound,
	EndermanTeleportSound,
	GhastShootSound,
	GhastSound,
	PlaySound,
	PopSound
};

class Utils {

	public function getNextRank(string $current_rank, array $ranks) : ?string{
		$keys = array_keys( $ranks );
		foreach($keys as $i => $value) {
			if($current_rank ===  $keys[ $i ]) {
				return isset($keys [++$i]) ? $keys[$i] : null;
			}
		}
	}
	
	public function playSound(string $sound, Player $player, bool $cast = false){
		if ($cast) {
			foreach(Server::getInstance()->getPlayers() as $players) {
				return $this->broadcastSound($sound, $players);
			}
		} else {
			return $this->broadcastSound($sound, $player);
		}
	}
	
	private function broadcastSound(string $sound, Player $player){
		$vector = new Vector3($player->getX(), $player->getY(), $player->getZ());
		switch ($sound) {
			case "fizz":
				$sounded = new FizzSound($vector);
			break;
			case "anvil_break":
				$sounded = new AnvilBreakSound($vector);
			break;
			case "anvil_fall":
				$sounded = new AnvilFallSound($vector);
			break;
			case "anvil_use":
				$sounded = new AnvilUseSound($vector);
			break;
			case "blaze_shoot":
				$sounded = new BlazeShootSound($vector);
			break;
			case "click":
				$sounded = new ClickSound($vector);
			break;
			case "door_bump":
				$sounded = new DoorBumpSound($vector);
			break;
			case "door_crash":
				$sounded = new DoorCrashSound($vector);
			break;
			case "door_sound":
				$sounded = new DoorSound($vector);
			break;
			case "enderman_teleport":
				$sounded = new EndermanTeleportSound($vector);
			break;
			case "ghast_shoot":
				$sounded = new GhastShootSound($vector);
			break;
			case "ghast":
				$sounded = new GhastSound($vector);
			break;
			case "pop":
				$sounded = new PopSound($vector);
			break;
			default:
				$sounded = new FizzSound($vector);
		}
		return $player->getLevel()->addSound($sounded);
	}
}