<?php

declare(strict_types=1);

namespace pcp\player;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;

class Chaser {
	
	private static $clicks = [];
	private static $combat = [];
	private static $cooldown = [];
	private static $os = [];
	private static $afk = [];
	private static $unli = [];
	private static $scorehud = [];
	private static $sparring = [];
	
	// CPS STATIC API
	public static function getClicks(Player $player): int {
		if(!isset(self::$clicks[$player->getLowerCaseName()])){
			return 0;
		}

		$time = self::$clicks[$player->getLowerCaseName()][0];
		$clicks = self::$clicks[$player->getLowerCaseName()][1];

		if($time !== time()){
			unset(self::$clicks[$player->getLowerCaseName()]);
			return 0;
		}

		return $clicks;
	}
	
	public static function addClick(Player $player): void {
		if(!isset(self::$clicks[$player->getLowerCaseName()])){
			self::$clicks[$player->getLowerCaseName()] = [time(), 0];
		}

		$time = self::$clicks[$player->getLowerCaseName()][0];
		$clicks = self::$clicks[$player->getLowerCaseName()][1];

		if($time !== time()){
			$time = time();
			$clicks = 0;
		}

		$clicks++;
		self::$clicks[$player->getLowerCaseName()] = [$time, $clicks];
	}
	
	// COMBAT LOG STATIC API
	public static function setCombat(Player $player, bool $type) {
		if (self::isInCombat($player)){
			if (!$type) {
				unset(self::$combat[$player->getName()]);
			} else {
				self::$combat[$player->getName()] = ["name" => "none"];
			}
		} else {
			self::$combat[$player->getName()] = ["name" => "none"];
		}
	}
	
	public static function setCombatRival(Player $player, Player $enemy) {
		return self::$combat[$player->getName()] = ["name" => $enemy->getName(), "time" => Core::getInstance()->events["combat"]["interval"]];
	}
	
	public static function getCombatType(Player $player, string $type) {
		if (self::isInCombat($player)){
			if (array_key_exists($type, self::$combat[$player->getName()])) {
				return self::$combat[$player->getName()][$type];
			}
		}
	}
	
	public static function reduceCombat(Player $player) {
		if (self::isInCombat($player)){
			if (array_key_exists("time", self::$combat[$player->getName()])) {
				return self::$combat[$player->getName()]["time"]--;
			}
		}
	}
	
	public static function isInCombat(Player $player) : bool {
		if (isset(self::$combat[$player->getName()])) {
			return true;
		} else return false;
	}
	
	public static function getCombatPlayer(Player $player) {
		if (self::isInCombat($player)) {
			return self::$combat[$player->getName()];
		}
	}
	
	// revised Sparring
	public static function setSparring(Player $player, string $type, string $arena) {
		self::$sparring[$player->getName()] = ["arena" => $arena, "type" => $type];
	}
	
	public static function getSparring(Player $player, string $type) {
		if (array_key_exists($type, self::$sparring[$player->getName()])){
			return self::$sparring[$player->getName()][$type];
		}
	}
	
	public static function unsetSparring(Player $player) {
		unset(self::$sparring[$player->getName()]);
	}
	
	public static function isInSparring(Player $player) {
		if (isset(self::$sparring[$player->getName()])) {
			return true;
		} else return false;
	}
	
	// COOLDOWN STATIC API
	public static function initCooldownPlayer(Player $player, bool $type) {
		if (self::isCooldownPlayer($player)) {
			if (!$type) {
				unset(self::$cooldown[$player->getName()]);
			} else {
				self::$cooldown[$player->getName()] = [];
			}
		} else {
			self::$cooldown[$player->getName()] = [];
		}
	}
	
	public static function setCooldown(Player $player, string $cdname) {
		return self::$cooldown[$player->getName()] = [$cdname => time()];
	}
	
	public static function getCooldown(Player $player, string $cdname) {
		if (self::isCooldownPlayer($player)) {
			if (array_key_exists($cdname, self::$cooldown[$player->getName()])) {
				return self::$cooldown[$player->getName()][$cdname];
			}
		}
	}
	
	public static function getExactCooldown(Player $player, string $cdname, int $cd) {
		return ($cd - (time() - self::getCooldown($player, $cdname)));
	}
	
	public static function delCooldown(Player $player, string $cdname) {
		if (self::isCooldownPlayer($player)) {
			if (array_key_exists($cdname, self::$cooldown[$player->getName()])) {
				unset(self::$cooldown[$player->getName()][$cdname]);
			}
		}
	}
	
	public static function inCooldown(Player $player, string $cdname, int $seconds) {
		if (array_key_exists($cdname, self::$cooldown[$player->getName()])) {
			return (self::$cooldown[$player->getName()][$cdname] + $seconds) > time(); 
		}
	}
	
	public static function isCooldownPlayer(Player $player) : bool {
		if (isset(self::$cooldown[$player->getName()])) {
			return true;
		} else return false;
	}
	
	public static function getCooldownPlayer(Player $player) {
		if (self::isCooldownPlayer($player)) {
			return self::$cooldown[$player->getName()];
		}
	}
	
	// OS STATIC API
	public static function registerOs($username, $types) {
		return self::$os[$username] = ["OS" => $types];
	}
	
	public static function getOs(Player $player) {
		return self::$os[$player->getName()]["OS"];
	}
	
	// AFK STATIC API
	public static function initAfkPlayer(Player $player, bool $type) {
		if (self::isAfkPlayer($player)) {
			if (!$type) {
				unset(self::$afk[$player->getName()]);
			} else {
				self::$afk[$player->getName()] = $player->getName();
			}
		} else {
			self::$afk[$player->getName()] = $player->getName();
		}
	}
	
	public static function isAfkPlayer(Player $player) {
		if (isset(self::$afk[$player->getName()])) {
			return true;
		} else return false;
	}
	
	// UNLI MODE STATIC API
	public static function initUnliModePlayer(Player $player, bool $type) {
		if (self::isUnliModePlayer($player)) {
			if (!$type) {
				unset(self::$unli[$player->getName()]);
			} else {
				self::$unli[$player->getName()] = $player->getName();
			}
		} else {
			self::$unli[$player->getName()] = $player->getName();
		}
	}
	
	public static function isUnliModePlayer(Player $player) : bool {
		if (isset(self::$unli[$player->getName()])) {
			return true;
		} else return false;
	}
	
	// SCOREBOARD STATIC API
	public static function initNoScoreHudPlayer(Player $player, bool $type) {
		if (self::isNoScoreHudPlayer($player)) {
			if (!$type) {
				unset(self::$scorehud[$player->getName()]);
			} else {
				self::$scorehud[$player->getName()] = $player->getName();
			}
		} else {
			self::$scorehud[$player->getName()] = $player->getName();
		}
	}
	
	public static function isNoScoreHudPlayer(Player $player) : bool {
		if (isset(self::$scorehud[$player->getName()])) {
			return true;
		} else return false;
	}
}