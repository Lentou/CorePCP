<?php

namespace pcp\utils;

use pcp\Core;
use pocketmine\Player;
use pocketmine\Server;

class Flags {
  
	public $plugin;

    public function __construct(Core $plugin) {
		$this->main = $plugin;
    }
	
    public function hasSecurity(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT name FROM world WHERE name='$world';");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }

    public function getLock(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT lock FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["lock"];
    }

	// SignChangeEvent, BlockBreakEvent, BlockPlaceEvent, BlockFlowEvent
    public function getEdit(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT edit FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["edit"];
    }
	
    public function getSDrop(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT sdrop FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["sdrop"];
    }

    public function getCDrop(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT cdrop FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["cdrop"];
    }
  
    public function getPvpDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT pvp FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["pvp"];
    }
	
    public function getProjectileDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT projectile FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["projectile"];
    }
	
    public function getSuffocateDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT suffocate FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["suffocate"];
    }
	
    public function getFallDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT fall FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["fall"];
    }
	
    public function getBurnDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT burn FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["burn"];
    }
  
    public function getOtherDamage(string $world) : bool {
        $result = $this->main->dbFlag->query("SELECT otherdamage FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["otherdamage"];
    }
  
    public function getGMChange(string $world) : bool {
	$result = $this->main->dbFlag->query("SELECT gmchange FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["gmchange"];
    }
	
    public function getAntiStarve(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT hunger FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["hunger"];
    }
	
    public function getStorageBan(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT storage FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["storage"];
    }

    public function getDoorBan(string $world) : bool {
	$result = $this->main->dbFlag->query("SELECT door FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["door"];
    }
    
    public function getTrapdoorBan(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT trapdoor FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["trapdoor"];
    }

    public function getFlyBan(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT fly FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["fly"];
    }
	
    public function getWorldMode(string $world) : int {
		$result = $this->main->dbFlag->query("SELECT gm FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["gm"];
    }
    
    public function getExplosion(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT explode FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["explode"];
    }
    
    public function getWorldScale(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT scale FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["scale"];
    }
	
	public function getItemFrame(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT itemframe FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["itemframe"];
    }
	
	public function getLiquid(string $world) : bool {
		$result = $this->main->dbFlag->query("SELECT liquid FROM world WHERE name = '$world';");
        return $result->fetchArray(SQLITE3_ASSOC)["liquid"];
    }
    
    public function getAllData(string $world) : array {
		$result = $this->main->dbFlag->query("SELECT * FROM world WHERE name = '$world';");
		return $result->fetchArray(SQLITE3_ASSOC);
    }
	
    public function updateSetting(string $world, array $value) : void {

      $stmt = $this->main->dbFlag->prepare(
		"INSERT OR REPLACE INTO world (name, lock, edit, sdrop, cdrop, pvp, projectile, suffocate, fall, burn, otherdamage, gmchange, hunger, door, trapdoor, storage, fly, gm, scale, explode, itemframe, liquid) 
		VALUES 
		(:name, :lock, :edit, :sdrop, :cdrop, :pvp, :projectile, :suffocate, :fall, :burn, :otherdamage, :gmchange, :hunger, :door, :trapdoor, :storage, :fly, :gm, :scale, :explode, :itemframe, :liquid);"
		);
      $stmt->bindValue(":name", $world);
      $stmt->bindValue(":lock", $value[1]);
      $stmt->bindValue(":edit", $value[2]);
      $stmt->bindValue(":sdrop", $value[3]);
      $stmt->bindValue(":cdrop", $value[4]);
      $stmt->bindValue(":pvp", $value[5]);
      $stmt->bindValue(":projectile", $value[6]);
      $stmt->bindValue(":suffocate", $value[7]);
      $stmt->bindValue(":fall", $value[8]);
      $stmt->bindValue(":burn", $value[9]);
      $stmt->bindValue(":otherdamage", $value[10]);
      $stmt->bindValue(":gmchange", $value[11]);
      $stmt->bindValue(":hunger", $value[12]);
      $stmt->bindValue(":door", $value[13]);
      $stmt->bindValue(":trapdoor", $value[14]);
      $stmt->bindValue(":storage", $value[15]);
      $stmt->bindValue(":fly", $value[16]);
      $stmt->bindValue(":explode", $value[17]);
      $stmt->bindValue(":gm", $value[18]);
      $stmt->bindValue(":scale", $value[19]);
	  $stmt->bindValue(":itemframe", $value[20]);
	  $stmt->bindValue(":liquid", $value[21]);
      
      $result = $stmt->execute();
    }
}
