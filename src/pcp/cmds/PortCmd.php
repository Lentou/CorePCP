<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\block\Block;
use pcp\libs\forms\SimpleForm;
// Crafting Table
use pocketmine\inventory\CraftingGrid;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
// Ender Chest
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\EnderChest;
use pocketmine\tile\Tile;

class PortCmd extends PluginCommand {
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["port"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if($sender instanceof Player){
			if($sender->hasPermission($this->plugin->cmds["port"]["perm"])){
				if($sender->isSurvival()){
					$this->portForm($sender);
					return true;
				} else {
					$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["port"]["prefix"], $this->plugin->cmds["utils"]["text"]["survival"]));
					return false;
				}
			} else {
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["port"]["prefix"], $this->plugin->cmds["utils"]["text"]["perm"]));
				return false;
			}
		}
		return false;
    }
	
	public function portForm(Player $sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->sendCraftingTable($sender);
					$sender->setCraftingGrid(new CraftingGrid($sender, CraftingGrid::SIZE_BIG));
					if(!array_key_exists($windowId = Player::HARDCODED_CRAFTING_GRID_WINDOW_ID, $sender->openHardcodedWindows)){
						$pk = new ContainerOpenPacket();
						$pk->windowId = $windowId;
						$pk->type = WindowTypes::WORKBENCH;
						$pk->x = $sender->getFloorX();
						$pk->y = $sender->getFloorY() - 2;
						$pk->z = $sender->getFloorZ();
						$sender->sendDataPacket($pk);
						$sender->openHardcodedWindows[$windowId] = true;
					}
				break;
				case 1:
					$nbt = new CompoundTag("", [new StringTag("id", Tile::CHEST), new StringTag("CustomName", "EnderChest"), new IntTag("x", (int)floor($sender->x)), new IntTag("y", (int)floor($sender->y) - 4), new IntTag("z", (int)floor($sender->z))]);
					/** @var EnderChest $tile */
					$tile = Tile::createTile("EnderChest", $sender->getLevel(), $nbt);
					$block = Block::get(Block::ENDER_CHEST);
					$block->x = (int)$tile->x;
					$block->y = (int)$tile->y;
					$block->z = (int)$tile->z;
					$block->level = $tile->getLevel();
					$block->level->sendBlocks([$sender], [$block]);
					$sender->getEnderChestInventory()->setHolderPosition($tile);
					$sender->addWindow($sender->getEnderChestInventory());
				return true;
				break;
			}
		});
		$form->setTitle($this->plugin->cmds["port"]["title"]);
		$form->setContent(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->cmds["port"]["content"])));
		$form->addButton($this->plugin->cmds["port"]["craft"][0], $this->plugin->cmds["port"]["craft"][1], $this->plugin->cmds["port"]["craft"][2]);
		$form->addButton($this->plugin->cmds["port"]["echest"][0], $this->plugin->cmds["port"]["echest"][1], $this->plugin->cmds["port"]["echest"][2]);
		$form->sendToPlayer($sender);
	}
	
	public function sendCraftingTable(Player $player){
        $block1 = Block::get(Block::CRAFTING_TABLE);
        $block1->x = (int)floor($player->x);
        $block1->y = (int)floor($player->y) - 2;
        $block1->z = (int)floor($player->z);
        $block1->level = $player->getLevel();
        $block1->level->sendBlocks([$player], [$block1]);
    }
}
