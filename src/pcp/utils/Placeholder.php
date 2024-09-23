<?php

declare(strict_types=1);

namespace pcp\utils;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;
use pcp\player\Chaser;
use pcp\player\Member;

class Placeholder {
	// For Whole PlaceHolder
	public function getNormalTags($player, $string) {
		
		$core = Core::getInstance();
		$member = new Member($player);
		#$member = $this->getMember($player);

		$holder = [
			# NORMAL TAGS
			"{ping}" => $this->checkTags("ping", $player),
			"{player}" => $player->getName(),
			"{display_name}" => $player->getDisplayName(),
			"{lvl}" => ($core->cx->isRecorded($player) == true ? $core->cx->data->getVal($player, "level") : "0"),
			"{exp}" => $core->cx->data->getVal($player, "exp"),
			"{texp}" => ($core->cx->data->getVal($player, "level") * $core->cx->settings->get("baseExp")),
			"{group}" => $this->checkTags("group", $player, 0),
			"{elorank}" => $this->checkTags("elo", $player, 0),
			"{div}" => $core->cx->elo->getDivRoman($player),
			"{ielorank}" => $this->checkTags("elo", $player, 1),
			"{kills}" => $member->getPoints("kills"),
			"{deads}" => $member->getPoints("deaths"),
			"{money}" => $member->getPoints("balance"),
			"{charm}" => $member->getPoints("social.charm"),
			"{bio}" => $member->getScript("bio"),
			"{role.display}" => $member->getScript("role.display"),
			"{profession}" => $this->checkTags("profession", $player),
			"{class}" => $this->checkTags("class", $player),
			"{gems}" => $core->cx->data->getVal($player, "gems"),
			"{title}" => ($core->cx->titles->hasTitles($player) == true ? $core->cx->titles->getTitle($player) : ""),
			"{streaks}" => $member->getPoints("streak"),
			"{bounty}" => $member->getPoints("bounty"),
			"{xp}" => $player->getCurrentTotalXp(),
			"{xplvl}" => $player->getXpLevel(),
			"{cs}" => $this->checkTags("cursor", $player),
			"{love.spouse}" => $member->getScript("social.spouse"),
			"{love.status}" => $member->getScript("social.status"),
			"{os}" => Chaser::getOs($player),
			"{mkills}" => $member->getPoints("mobs"),
			"{tquest}" => $member->getPoints("quests"),
			"{votes}" => $member->getPoints("votes"),
			"{job}" => $member->getScript("job.name"),
			"{time}" => date("h:iA"),
			"{date}" => date("m/d/Y"),
			"{online}" => count($player->getServer()->getOnlinePlayers()),
			"{max_online}" => $player->getServer()->getMaxPlayers(),
			"{rank}" => $this->checkTags("rank", $player),
			"{class.passive}" => $this->checkTags("passive", $player),
			#SACRED SKILLS
			"{lvl.sword}" => $member->getPoints("skills.sword.lvl"),
			"{lvl.axe}" => $member->getPoints("skills.axe.lvl"),
			"{lvl.pickaxe}" => $member->getPoints("skills.pickaxe.lvl"),
			"{lvl.hoe}" => $member->getPoints("skills.hoe.lvl"),
			"{lvl.shovel}" => $member->getPoints("skills.shovel.lvl"),
			"{exp.sword}" => $member->getPoints("skills.sword.exp"),
			"{exp.axe}" => $member->getPoints("skills.axe.exp"),
			"{exp.pickaxe}" => $member->getPoints("skills.pickaxe.exp"),
			"{exp.hoe}" => $member->getPoints("skills.hoe.exp"),
			"{exp.shovel}" => $member->getPoints("skills.shovel.exp"),
			"{role.status}" => $member->getScript("role.status"),
			"{cps}" => Chaser::getClicks($player),
			"{hp}" => $this->checkTags("attributes", $player, 0),
			"{physical.damage}" => $this->checkTags("attributes", $player, 1),
			"{critical.damage}" => $this->checkTags("attributes", $player, 2),
			"{critical.chance}" => $this->checkTags("attributes", $player, 3),
			"{true.damage}" => $this->checkTags("attributes", $player, 4),
			"{physical.defence}" => $this->checkTags("attributes", $player, 5),
			"{magic.defence}" => $this->checkTags("attributes", $player, 6),
			"{fire.defence}" => $this->checkTags("attributes", $player, 7),
			"{fall.defence}" => $this->checkTags("attributes", $player, 8),
			"{max.hp}" => $this->checkTags("attributes", $player, 9),
			"{current.hp}" => $this->checkTags("attributes", $player, 10),
			"{saturation}" => $this->checkTags("attributes", $player, 11),
			"{regen}" => $this->checkTags("attributes", $player, 12),
			"{passive}" => $this->checkTags("attributes", $player, 13),
			"{avail.pts}" => $this->checkTags("attributes", $player, 14),
			"{rating}" => $this->checkTags("attributes", $player, 15),
		];
		$string = strtr($string, $holder);
		return $string;
	}
	
	public function checkTags($check, $player, $int = 0){
		$member = new Member($player);
		switch($check){
			case "ping":
				$ping = $player->getPing();
				if($ping <= 100){
					return "§a" . $ping;
				} else if($ping > 101 and $ping <= 199){
					return "§6" . $ping;
				} else if($ping > 200){
					return "§c" . $ping;
				} else {
					return "§f" . $ping;
				}
			break;
			case "elo":
				return Core::getInstance()->cx->elo->getRank($player);
			break;
			case "cursor":
				$mks = $member->getPoints("mobs");
				$pks = $member->getPoints("kills");
				if($mks >= $pks){
					return "§a";
				} else if ($mks <= $pks) {
					return "§6";
				} else {
					return "§b";
				}
			break;
			case "rank":
				return $member->getScript("rank");
			break;
			case "class":
				return $member->getScript("class.name");
			break;
			case "passive":
				$profession = $member->getScript("class.type");
				switch($profession){
					case "Physical":
						return "§c" . $profession;
					break;
					case "Magical":
						return "§9" . $profession;
					break;
					default:
						return "§f" . $profession;
				}
			break;
			case "profession":
				return $member->getScript("job.type");
			break;
			case "attributes":
				if (($tag = $player->namedtag->getTag('Attributes')) !== null) {
					switch($int) {
						case 0:
							return ($tag->getFloat('CurrentHP') / $tag->getFloat('MaximumHP')) * 100;
						break;
						case 1:
							return $tag->getFloat('PhysicalDamage');
						break;
						case 2:
							return $tag->getFloat('CriticalDamage');
						break;
						case 3:
							return $tag->getFloat('CriticalChance');
						break;
						case 4:
							return $tag->getFloat('TrueDamage');
						break;
						case 5:
							return $tag->getFloat('PhysicalDefence');
						break;
						case 6:
							return $tag->getFloat('MagicDefence');
						break;
						case 7:
							return $tag->getFloat('FireDefence');
						break;
						case 8:
							return $tag->getFloat('FallDefence');
						break;
						case 9:
							return $tag->getFloat('MaximumHP');
						break;
						case 10:
							return $tag->getFloat('CurrentHP');
						break;
						case 11:
							return $tag->getFloat('Saturation');
						break;
						case 12:
							return $tag->getFloat('Regeneration');
						break;
						case 13:
							return $tag->getString('Passive');
						break;
						case 14:
							return $tag->getInt('AvailablePTS');
						break;
						case 15:
							$pdmg = $tag->getFloat('PhysicalDamage');
							$cdmg = $tag->getFloat('CriticalDamage');
							$ccha = $tag->getFloat('CriticalChance');
							$tdmg = $tag->getFloat('TrueDamage');
							$pdef = $tag->getFloat('PhysicalDefence');
							$mdef = $tag->getFloat('MagicDefence');
							$firedef = $tag->getFloat('FireDefence');
							$falldef = $tag->getFloat('FallDefence');
							$maxhp = $tag->getFloat('MaximumHP');
							$curhp = $tag->getFloat('CurrentHP');
							$sat = $tag->getFloat('Saturation');
							$regen = $tag->getFloat('Regeneration');
							
							$rating = $pdmg + $cdmg + $ccha + $tdmg + $pdef + $mdef + $firedef + $falldef + $maxhp + $curhp + $sat + $regen;
							return $rating;
						break;
					}
				}
			break;
		}	
	}

}