<?php

namespace pcp\utils;

use pcp\Core;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pcp\libs\forms\{SimpleForm, ModalForm, CustomForm};
use pocketmine\command\ConsoleCommandSender;
use pcp\cmds\QuestCmd;

class Quests
{

  	public $main;
	private $questCache = [];
	
	public function __construct(QuestCmd $core)
	{
        $this->main = $core;
	}
	
	public function hasQuest(Player $player) : bool
	{
		$name = $player->getName();
		$result = Core::getInstance()->dbQuest->query("SELECT * FROM pquests WHERE name= '$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}

	public function getPlayerQuest(Player $player) : string
	{
		$name = $player->getName();
		$result = Core::getInstance()->dbQuest->query("SELECT * FROM pquests WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["quest"];
	}
	
	public function validatePlayerQuest(Player $player, $quest) : bool
	{
		if($this->hasQuest($player) == false) //Checks if the player is NOT on a quest
		{
			if($this->questExist($quest)) //Checks if the quest is still existing
			{
				if(Core::getInstance()->cx->data->getVal($player, "level") >= $this->getQuestLevel($quest)) //Checks if the player is equal or above the level
				{
					if($this->hasSpace($player)) //Now the book is important, just for the info.
					{
						$this->givePlayerQuest($player, $quest); //finally giving the quest
						$message = str_replace(["{prefix}", "{quest}"], [Core::getInstance()->cmds["quest"]["prefix"], $this->getQuestTitle($quest)], Core::getInstance()->cmds["quest"]["text"]["added"]);
						return true;
					}
					$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["failed"]);
					return false;
				}
				$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["level"]);
				return false;
			}
			$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["error"]);
			return false;
		}
		$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["already"]);
		$player->sendMessage($message);
		return false;
	}

 	public function givePlayerQuest(Player $player, string $quest) : void
	{
		$stmt = Core::getInstance()->dbQuest->prepare("INSERT OR REPLACE INTO pquests (name, quest) VALUES (:name, :quest);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":quest", $quest);
		$result = $stmt->execute();
		
		$book = Item::get(Item::WRITTEN_BOOK, 0, 1);
		$book->setTitle(str_replace("{quest}", $this->getQuestTitle($quest), Core::getInstance()->cmds["quest"]["book"]["title"]));
		$book->setPageText(0, implode("\n", str_replace(["{quest}", "{level}"], [$this->getQuestTitle($quest), $this->getQuestLevel($quest)], Core::getInstance()->cmds["quest"]["book"]["page1"])));
		$book->setPageText(1, implode("\n", str_replace(["{player}", "{info}"], [$player->getName(), $this->getQuestInfo($quest)], Core::getInstance()->cmds["quest"]["book"]["page2"])));
		$book->setAuthor(Core::getInstance()->cmds["quest"]["book"]["author"]);
		
		$player->getInventory()->addItem($book);
    }
	
	public function removePlayerQuest(Player $player) : void
	{
		$name = $player->getName();
		Core::getInstance()->dbQuest->query("DELETE FROM pquests WHERE name = '$name';");
		$inventory = $player->getInventory();
		if($inventory->contains(Item::get(Item::WRITTEN_BOOK, 0, 1))) {
			$inventory->removeItem(Item::get(Item::WRITTEN_BOOK, 0, 1));
		}
	}

	/* Quest Data handling */
	public function questExist(string $quest): bool
	{
		return (array_key_exists($quest, Core::getInstance()->questData->getAll() )) ? true : false;
	}

	public function getQuestTitle(string $quest) : string
	{
		return Core::getInstance()->questData->getNested($quest.".title");
	}

	public function getQuestLevel(string $quest) : string
	{
		return Core::getInstance()->questData->getNested($quest.".level");
	}
	
	public function getQuestInfo(string $quest) : string
	{
		return Core::getInstance()->questData->getNested($quest.".desc");
	}

	public function getQuestItem(string $quest) : Item
	{
		$item = (string) Core::getInstance()->questData->getNested($quest.".item");
		$i = explode(":", $item);
		return Item::get($i[0], $i[1], $i[2]);
	}

	public function getQuestCmds(string $quest) : array
	{
		return Core::getInstance()->questData->getNested($quest.".cmd");
	}
	
	public function isCompleted(Player $player) : bool
	{
		if($player->isSurvival())
		{
			if( $this->hasQuest($player) )
			{
				$quest = $this->getPlayerQuest($player);
				$item = $this->getQuestItem($quest);
				$hand = $player->getInventory()->getItemInHand();
				if($hand->getId() == $item->getId())
				{
					if($hand->getCount() >= $item->getCount())
					{
						$hand->setCount($hand->getCount() - $item->getCount());
						$player->getInventory()->setItemInHand($hand);

						foreach($this->getQuestCmds($quest) as $cmd)
						{
							$this->rac($player, $cmd);
						}

						$player->sendTitle(Core::getInstance()->cmds["quest"]["complete"][0], str_replace("{quest}", $this->getQuestTitle($quest), Core::getInstance()->cmds["quest"]["complete"][1]));
						$this->removePlayerQuest($player);
						Core::getInstance()->economy->addTakeQuest($player);
						return true;
					}
					$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["insufficient"]);
					return false;
				}
				$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["item"]);
				return false;
			}
			$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["quest"]["text"]["notquest"]);
			return false;
		}
		$message = str_replace("{prefix}", Core::getInstance()->cmds["quest"]["prefix"], Core::getInstance()->cmds["utils"]["text"]["survival"]);
		$player->sendMessage($message);
		return false;
	}
	
	public function sendQuestApplyForm(Player $player)
    {
		$form = new SimpleForm(function (Player $player, $data)
		{
			if (isset($data))
			{
				$button = $data;
				$list = array_keys( Core::getInstance()->questData->getAll() );
				$quest = $list[ $button ];
				//$player->sendMessage($quest); //for debug
				$this->questCache[ $player->getName() ] = $quest;
				$this->sendQuestInfo($player, $quest);
				return true;
			}
		});
        $form->setTitle(Core::getInstance()->cmds["quest"]["title"]);
		foreach( array_keys(Core::getInstance()->questData->getAll()) as $questid)
		{
			$form->addButton( Core::getInstance()->questData->getNested($questid.".title") );
		}
        $form->sendToPlayer($player);
    }
	
	public function sendQuestInfo(Player $player, string $quest)
	{
		$form = new ModalForm(function (Player $player, $data)
		{
			if($data)
			{
				$this->validatePlayerQuest( $player, $this->questCache[ $player->getName() ]);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    unset( $this->questCache[$player->getName()] );
				}
				return;
			} else {
				$this->sendQuestApplyForm($player);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    unset( $this->questCache[$player->getName()] );
				}
				return;
			}
		});
		
        	$form->setTitle(strtoupper( $this->getQuestTitle($quest) ));
			$form->setContent(implode("\n", str_replace(["{quest}", "{level}", "{info}"], [$this->getQuestTitle($quest), $this->getQuestLevel($quest), $this->getQuestInfo($quest)], Core::getInstance()->cmds["quest"]["content"])));
			$form->setButton1(Core::getInstance()->cmds["utils"]["buttons"]["confirm"][0]);
			$form->setButton2(Core::getInstance()->cmds["utils"]["buttons"]["cancel"][0]);
        	$form->sendToPlayer($player);
	}
	
	public function hasSpace(Player $player) : bool
	{
		return $player->getInventory()->canAddItem(Item::get(Item::STICK, 0, 1)) ? true : false; //Test item xD
	}

	public function rac(Player $player, string $string) : void
	{
		$command = str_replace("{player}", $player->getName(), $string);
		Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command);
	}
	
}
