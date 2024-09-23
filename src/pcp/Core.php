<?php

declare(strict_types=1);

namespace pcp;

use pcp\utils\{
	Utils,
	Placeholder,
	DamageEffect,
	Quests,
	Relics,
	Flags,
	Kit,
	DamageEntity
	};

use pcp\cmds\{
	HubCmd,
	BcastCmd,
	BannerCmd,
	SpawnCmd,
	TellCmd,
	AccessCmd,
	CallCmd,
	QuestCmd,
	StatsCmd,
	LoveCmd,
	BoardCmd,
	PortCmd,
	IntCmd,
	BankCmd,
	PathCmd,
	DistrictCmd,
	ShopCmd,
	TalkCmd,
	KitCmd,
	RouteCmd,
	SparringCmd,
	AfkCmd,
	FlagCmd
	};

use pcp\tasks\{
	BroadcastTask, 
	RestartTask,
	CommandcastTask,
	CombatTask,
	MotdTask
	};

use pcp\events\{
	BorderEvent,
	ChatEvent,
	DeathEvent,
	LoginEvent,
	MineEvent,
	VerifyEvent,
	ForgeEvent,
	CombatEvent,
	RelicEvent,
	ItemEvent,
	FlagEvent,
	OtherEvent,
	KitEvent,
	SparringEvent,
	AfkEvent
	};

use pocketmine\{
	Server,
	Player,
	
	plugin\PluginBase,
	
	item\Item,
	
	utils\Config,
	utils\TextFormat,
	
	level\Level,
	level\Position,
	
	scheduler\ClosureTask,
	scheduler\Task
	};
	
use pcp\libs\score\ScoreFactory;

use pocketmine\entity\Skin;
use pocketmine\utils\SingletonTrait;

use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;

use pocketmine\level\particle\DustParticle;
use pocketmine\math\Vector3;

use pcp\player\Chaser;

class Core extends PluginBase {

	use SingletonTrait;

    public $config, $settings, $relicBlocks, $randRelic;
	public $economy;
	public $data;
	
	public function onLoad() : void {
		self::setInstance($this);
	}

    public function onEnable() {
    	date_default_timezone_set("Asia/Manila");
			
		$this->placeholder = new Placeholder();
		$this->flags = new Flags($this);
		$this->relic = new Relics($this);
		$this->kitmanager = new Kit();
			
		$this->loadDepends();
		$this->configReload();
		$this->initEnchantments();
		$this->reloadCommands();
		$this->reloadEvents();
		
		if (($this->events["indicator"]["enable"] === true) and ($this->events["indicator"]["version"]["entity"] === true)) {
			Entity::registerEntity(DamageEntity::class, true);
		}
    }
	
	private function loadDepends() : void {
		$this->cx = $this->getServer()->getPluginManager()->getPlugin("CoreX2");
	}
	
	public function getPointsManager() {
		return $this->point;
	}
	
	public function getScriptManager() {
		return $this->alpha;
	}
	
	public function configReload() {
			@mkdir($this->getDataFolder());
			@mkdir($this->getDataFolder() . "relics");
			@mkdir($this->getDataFolder() . "db");
			
			$this->saveResource("shop.yml");
			$this->shop = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
			$this->shops = $this->shop->getAll();
			
			$this->saveResource("cmds.yml");
			$this->cmd = new Config($this->getDataFolder() . "cmds.yml", Config::YAML);
			$this->cmds = $this->cmd->getAll();
			
			$this->saveResource("events.yml");
			$this->event = new Config($this->getDataFolder() . "events.yml", Config::YAML);
			$this->events = $this->event->getAll();
			
			$this->saveResource("board.yml");
			$this->board = new Config($this->getDataFolder() . "board.yml", Config::YAML);
			$this->boards = $this->board->getAll();
			
			$this->saveResource("district.yml");
			$this->dist = new Config($this->getDataFolder() . "district.yml", Config::YAML);
			$this->distr = $this->dist->getAll();
			
			$this->saveResource("talk.yml");
			$this->talk = new Config($this->getDataFolder() . "talk.yml", Config::YAML);
			$this->talks = $this->talk->getAll();
			
			$this->saveResource("kits.yml");
			$this->kit = new Config($this->getDataFolder() . "kits.yml", Config::YAML);
			$this->kits = $this->kit->getAll();
			
			$this->saveResource("route.yml");
			$this->route = new Config($this->getDataFolder() . "route.yml", Config::YAML);
			$this->routes = $this->route->getAll();
			
			$this->saveResource("sparring.yml");
			$this->sparr = new Config($this->getDataFolder() . "sparring.yml", Config::YAML);
			$this->sparring = $this->sparr->getAll();
			
			$this->saveResource("db/points.yml");
			$this->point = new Config($this->getDataFolder() . "db/points.yml", Config::YAML);
			$this->points = $this->point->getAll();
			
			$this->saveResource("db/alphas.yml");
			$this->alpha = new Config($this->getDataFolder() . "db/alphas.yml", Config::YAML);
			$this->alphas = $this->alpha->getAll();
			
			if($this->cmds["quest"]["enable"] === true){
				$this->saveResource("quests.yml");
				$this->questData = new Config($this->getDataFolder() . "quests.yml", CONFIG::YAML);
				$this->dbQuest = new \SQLite3($this->getDataFolder() . "db/quests.db"); //creating main database
				$this->dbQuest->exec("CREATE TABLE IF NOT EXISTS pquests (name TEXT PRIMARY KEY COLLATE NOCASE, quest TEXT);");
				$this->dbQuest->exec("CREATE TABLE IF NOT EXISTS pcompleted (name TEXT PRIMARY KEY COLLATE NOCASE, quests TEXT);");
			}
			
			$this->dbFlag = new \SQLite3($this->getDataFolder() . "db/flags.db");
			$this->dbFlag->exec("CREATE TABLE IF NOT EXISTS world (name TEXT PRIMARY KEY COLLATE NOCASE, lock INT, edit INT, sdrop INT, cdrop INT, pvp INT, projectile INT, suffocate INT, fall INT, burn INT, otherdamage INT, gmchange INT, hunger INT, door INT, trapdoor INT, storage INT, fly INT, gm INT, scale INT, explode INT, itemframe INT, liquid INT);");
			
			if($this->events["relic"]["enable"] === true){
				if( count( glob($this->getDataFolder() . "relics/*.yml") ) < 1)
				{
					$r = new Config($this->getDataFolder() . "relics/sample.yml", Config::YAML, [
						'title' => 'This will be the name of the totem',
						'rate' => 90,
						'level_requirement' => 1,
						'loots' => ['random commands' => ['type' => 'cmd', 'commands' => ['give %player% diamond 4', 'give %player% diamond 64']],
									'random items' => ['type' => 'item', 'items' => [
									'random identifier' => [
										'name' => 'stack of stone blocks',
										'id' => 1,
										'meta' => 0,
										'amount' => 64,
										'enchantments' => []
										],
									'put anything' => [
										'name' => 'stack of grass blocks',
										'id' => 2,
										'meta' => 0,
										'amount' => 64,
										'enchantments' => []
										],
									'literally anything' => [
										'name' => 'stack of cobblestones',
										'id' => 4,
										'meta' => 0,
										'amount' => 64,
										'enchantments' => []
										]
										]
									]
								]
						]);
				}
			}
	}
	
	public function initEnchantments() {

        Enchantment::registerEnchantment(new Enchantment(Enchantment::FORTUNE, "Fortune", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_DIG, Enchantment::SLOT_NONE, 3));
        Enchantment::registerEnchantment(new Enchantment(Enchantment::LOOTING, "Looting", Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 3));

    }
	
	public function reloadCommands(){
		$this->mosaicList = [];
		foreach($this->events["chat"]["words"]["messages"] as $m){
			$this->mosaicList[] = str_repeat($this->events["chat"]["words"]["mosaic"], strlen($m));
		}
			
		Server::getInstance()->getCommandMap()->unregister(Server::getInstance()->getCommandMap()->getCommand("tell"));
		// Commands
		$this->getServer()->getCommandMap()->registerAll("CorePCP", [
			new TellCmd("tell", $this),
			new AccessCmd("access", $this),
			new HubCmd("hub", $this),
			new SpawnCmd("spawn", $this),
			new BcastCmd("bcast", $this),
			new BannerCmd("banner", $this),
			new CallCmd("call", $this),
			new QuestCmd("quest", $this),
			new StatsCmd("stats", $this),
			new LoveCmd("love", $this),
			new BoardCmd("board", $this),
			new PortCmd("port",  $this),
			new IntCmd("int", $this),
			new BankCmd("bank", $this),
			new PathCmd("path", $this),
			new DistrictCmd("dist", $this),
			new ShopCmd("shop", $this),
			new TalkCmd("talk", $this),
			new KitCmd("kit", $this),
			new RouteCmd("route", $this),
			new SparringCmd("spar", $this),
			new AfkCmd("afk", $this),
			new FlagCmd("flag", $this)
		]);
	}
	
	public function reloadEvents(){
		/** RELICS V2 **/
		if($this->events["relic"]["enable"] === true) {
			$this->getServer()->getPluginManager()->registerEvents(new RelicEvent($this), $this);
				
			foreach (glob($this->getDataFolder() . "relics/*.yml") as $relic) {
				$tier = str_replace([$this->getDataFolder(), "relics/", ".yml"], "", $relic);
					
				if($tier !== "sample") {
					$data = (new Config($relic, Config::YAML))->getAll();
					$this->randRelic[$tier] = $data['rate'];
				}
			}
			
			foreach($this->events["relic"]["chances"] as $name => $chance) {
				$this->relicBlocks[ $name ] = $chance; 
			}
				
			if($this->randRelic === null or count($this->randRelic) < 1) {
				$this->getLogger()->error("not enough relics, please create atleast one.");
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return false;
			}
				
		}
			
		/** End of Relics **/
		if($this->events["motd"]["enable"] === true){
			$this->getScheduler()->scheduleRepeatingTask(new MotdTask($this), $this->events["motd"]["delay"] ?? 100);
		} else {
			$this->updateMOTD();
		}
			
		// Broadcast
		if($this->events["cast"]["enable"]["message"] === true){
			$this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), $this->events["cast"]["int"]["message"] * 20);
		}
			
		// Command cast
		if($this->events["cast"]["enable"]["command"] === true){
			$this->getScheduler()->scheduleRepeatingTask(new CommandcastTask($this), $this->events["cast"]["int"]["command"] * 20, 20);
		}
			
		// Restarter
		if($this->events["restart"]["enable"] === true){
			$this->getScheduler()->scheduleRepeatingTask(new RestartTask($this), 20);
		}
			
		// Combat Log
		if($this->events["combat"]["enable"] === true){
			$this->getServer()->getPluginManager()->registerEvents(new CombatEvent($this), $this);
			$this->getScheduler()->scheduleRepeatingTask(new CombatTask($this), 20);
		}
			
		if($this->events["indicator"]["enable"] === true){
			$this->getServer()->getPluginManager()->registerEvents(new DamageEffect($this), $this);
		}
			
		if($this->events["verify"]["enable"] === true){
			$this->getServer()->getPluginManager()->registerEvents(new VerifyEvent($this), $this);
		}
	
		if($this->events["chat"]["tag"]["score"]["enable"] === true){
			$this->scoreName();
		}
			
		if($this->events["cleaner"]["enable"] === true){
			$this->magicCleaner();
		}
		
		if($this->events["scoreboard"]["enable"]["self"] === true){
			$this->quickboard();
		}
		
		if($this->events["login"]["join"]["particlespawn"] === true){
			$this->particleSpawn();
		}
		
		if ($this->sparring["enable"] === true){
			$this->getServer()->getPluginManager()->registerEvents(new SparringEvent($this), $this);
		}
		
		// Events
		$events = [
			new BorderEvent($this),
			new DeathEvent($this),
			new LoginEvent($this),
			new MineEvent($this),
			new ItemEvent($this),
			new ChatEvent($this),
			new ForgeEvent($this),
			new OtherEvent($this),
			new KitEvent($this),
			new AfkEvent($this),
			new FlagEvent($this)
		];
		
		foreach ($events as $event) {
			$this->getServer()->getPluginManager()->registerEvents($event, $this);
		}
	}
	
	
	public function scoreName(){
		$this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void {
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$player->setNameTag($this->placeholder->getNormalTags($player, $player->getNameTag()));
				$player->setScoreTag($this->placeholder->getNormalTags($player, $this->events["chat"]["tag"]["score"]["tag"]));
			}
		}), 10, 10);
	}
	
	public function magicCleaner(){
		
        $this->interval = $this->seconds = $this->events["cleaner"]["seconds"];
        $clear = $this->events["cleaner"]["clear"] ?? [];
        $this->clearItems = (bool) ($clear["items"] ?? false);
        $this->clearMobs = (bool) ($clear["mobs"] ?? false);
        $this->clearXpOrbs = (bool) ($clear["xp-orbs"] ?? false);

        $this->exemptEntities = array_map(function($entity) : string{
            return strtolower((string) $entity);
        }, $clear["exempt"] ?? []);

        $this->broadcastTimes = $this->events["cleaner"]["times"] ?? [60, 30, 15, 10, 5, 4, 3, 2, 1];
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function($_) : void{
			if(count($this->getServer()->getOnlinePlayers()) > 0){
				if(--$this->seconds === 0){
					$entitiesCleared = 0;
					foreach($this->getServer()->getLevels() as $level){
						foreach($level->getEntities() as $entity){
							if($this->clearItems && $entity instanceof ItemEntity){
								$entity->flagForDespawn();
								++$entitiesCleared;
							} else if($this->clearMobs && $entity instanceof Creature && !$entity instanceof Human){
								if(!in_array(strtolower($entity->getName()), $this->exemptEntities)){
									$entity->flagForDespawn();
									++$entitiesCleared;
								}
							} else if($this->clearXpOrbs && $entity instanceof ExperienceOrb){
									$entity->flagForDespawn();
									++$entitiesCleared;
							}
						}	
					}
					$this->getServer()->broadcastMessage(str_replace(["{prefix}", "{count}"], [$this->events["cleaner"]["prefix"], $entitiesCleared], $this->events["cleaner"]["text"]["clear"]));
					$this->seconds = $this->interval;
				} else if(in_array($this->seconds, $this->broadcastTimes)){
					$this->getServer()->broadcastMessage(str_replace(["{prefix}", "{seconds}"], [$this->events["cleaner"]["prefix"], $this->seconds], $this->events["cleaner"]["text"]["time"]));
				}
			}
        }), 20);
	}
	
	public function onDisable() {
		if (isset($this->dbQuest)) $this->dbQuest->close();
    }
	
    public function updateMOTD($i = 0) {
        $this->getServer()->getNetwork()->setName(str_replace(["{players}","{maxplayers}"], [count($this->getServer()->getOnlinePlayers()) + $i, $this->getServer()->getMaxPlayers()], $this->events["motd"]["format"]));
    }
	
	public function getLeaderboard(string $leaderboard) : string {
        $names = array_column($this->points, $leaderboard);
        array_multisort($names, SORT_DESC, $this->points);
        reset($this->points);
  
		$tops = str_replace("{type}", $leaderboard, $this->boards["board"]["content"]["lead"][0]);
		$x = 1;
		foreach($this->points as $name => $data){
			$int = $data[$leaderboard];
			$tops .= str_replace(["{number}", "{player}", "{points}"], [$x, $name, $int], $this->boards["board"]["content"]["lead"][1]);
			$x++;
		}
		return $tops;
    }
	
	public function showScreenAnimate($player, $id){
		$packet = new OnScreenTextureAnimationPacket();
		$packet->effectId = $id;
		$player->sendDataPacket($packet);
	}
	
	public function getArena() {
		return $this->arena;
	}
	
	public function getKit() {
		return $this->kitmanager;
	}
	
	public function quickboard(){
		$this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void {
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$this->giveQuickboard($player);
			}
		}), 10, 10);
	}
	
	public function giveQuickboard($player){
		$title = $this->event->getNested("scoreboard.title");
		if(!Chaser::isNoScoreHudPlayer($player)){
			if($this->events["scoreboard"]["enable"]["perworld"] == true){
				$world = strtolower($player->getLevel()->getName());
				if(array_key_exists($world, $this->events["scoreboard"]["lines"])){
					$lines = $this->placeholder->getNormalTags($player, implode("\n", $this->event->getNested("scoreboard.lines.$world")));
					$lines = explode("\n", $lines);
					$this->quicklines($player, $title, $lines);
				} else {
					if($this->events["scoreboard"]["enable"]["default"] == true){
						$lines = $this->placeholder->getNormalTags($player, implode("\n", $this->event->getNested("scoreboard.lines.default")));
						$lines = explode("\n", $lines);
						$this->quicklines($player, $title, $lines);
					} else {
						ScoreFactory::removeScore($player);
					}
				}
			} else if ($this->events["scoreboard"]["enable"]["default"] == true) {
				$lines = $this->placeholder->getNormalTags($player, implode("\n", $this->event->getNested("scoreboard.lines.default")));
				$lines = explode("\n", $lines);
				$this->quicklines($player, $title, $lines);
			} else {
				ScoreFactory::removeScore($player);
			}
		} else {
			ScoreFactory::removeScore($player);
		}
	}
	
	public function quicklines($player, $title, $lines){
		ScoreFactory::setScore($player, $title);
		$i = 0;
		foreach($lines as $line){
			if($i < 15){
				$i++;
				ScoreFactory::setScoreLine($player, $i, $line);
			}
		}
	}
	
	public function particleSpawn() {
		$this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void {
			$level = $this->getServer()->getDefaultLevel();
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$r = rand(1, 300);
			$g = rand(1, 300);
			$b = rand(1, 300);
			$x = $spawn->getX();
			$y = $spawn->getY();
			$z = $spawn->getZ();
			$center = new Vector3($x, $y, $z);
			$radius = 0.5;
			$count = 100;
			$particle = new DustParticle($center, $r, $g, $b, 1);
			for($yaw = 0, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 20, $y += 1 / 20){
				$x = -sin($yaw) + $center->x;
				$z = cos($yaw) + $center->z;
				$particle->setComponents($x, $y, $z);
				$level->addParticle($particle);
			}
		}), 10, 10);
	}
}