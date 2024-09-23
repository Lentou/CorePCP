<?php

declare(strict_types=1);

namespace pcp\cmds;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\{Command, CommandSender, PluginCommand, ConsoleCommandSender};

use pocketmine\item\Item;
use pocketmine\nbt\JsonNbtParser;

use pcp\libs\forms\{SimpleForm, CustomForm};
use pcp\Core;

class BannerCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->cmds["banner"]["desc"]);
    
		$this->colortags = ['BLACK'=>'0', 'DARK_GREEN'=>'2', 'DARK_AQUA'=>'3', 'DARK_PURPLE'=>'5', 'ORANGE'=>'6', 'GRAY'=>'7', 'DARK_GRAY'=>'8', 'BLUE'=>'9', 'GREEN'=>'a', 'AQUA'=>'b', 'RED'=>'c', 'LIGHT_PURPLE'=>'d', 'YELLOW'=>'e', 'WHITE'=>'f'];
		$this->colors = ['BLACK', 'DARK_GREEN', 'DARK_AQUA', 'DARK_PURPLE', 'ORANGE', 'GRAY', 'DARK_GRAY', 'BLUE', 'GREEN', 'AQUA', 'RED', 'LIGHT_PURPLE', 'YELLOW', 'WHITE'];
		$this->bannerc = ['BLACK'=>'0',  'DARK_GREEN'=>'2', 'DARK_AQUA'=>'6', 'DARK_PURPLE'=>'5', 'ORANGE'=>'14', 'GRAY'=>'7', 'DARK_GRAY'=>'8', 'BLUE'=>'4', 'GREEN'=>'10', 'AQUA'=>'12', 'RED'=>'1', 'LIGHT_PURPLE'=>'9', 'YELLOW'=>'11', 'WHITE'=>'15'];
		$this->items = ['Gradient top to bottom', 'Gradient bottom to top', 'Bricks', 'Top half rectangle', 'Bottom half rectangle', 'Left half rectangle', 'Right half rectangle', 'Top small rectangle', 'Bottom small rectangle', 'Left small rectangle', 'Right small rectangle', 'Top left triangle', 'Top right triangle', 'Bottom left triangle', 'Bottom right triangle', 'Big §lX', 'Diagonal §l/', 'Diagonal §l\\', 'Cross §l+', 'Centered vertical line', 'Centered horizontal line', 'Top left square', 'Top right square', 'Bottom left square', 'Bottom right square', 'Top triangle', 'Bottom triangle', 'Centered rhombus', 'Centered "circle"', 'Bottom spikes', 'Top spikes', '4 horizontal lines', 'Frame', 'Spiky frame', 'Centered flower', 'Creeper head', 'Centered skull', 'Mojang logo'];
		$this->patterns = ['gra', 'gru', 'bri', 'hh','hhb','vh','vhr','ts','bs','ls','rs','ld','rud','lud','rd','cr','dls','drs','sc','cs','ms','tl','bl','tr','br','tt','bt','mr','mc','bts','tts','ss','bo','cbo','flo','cre','sku','moj'];
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		$player = $sender->getName();
        if(isset($args[0])){
			if(!in_array(strtoupper($args[0]), $this->colors)){
				$sender->sendMessage(str_replace("{prefix}", $this->plugin->cmds["banner"]["prefix"], $this->plugin->cmds["banner"]["text"]["invalid"]));
			} else { 
				$this->$player =  new \stdClass(); 
				$this->layer($sender, strtolower($args[0]));
			}
        } else {
			$this->colorChooser($sender);
		}
        return false;
	}
	
	public function colorChooser($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if(!is_null($data)) {
				switch($data[0]) {
					case 0:
						$string = "banner BLACK";
					break;
					case 1:
						$string = "banner DARK_GREEN";
					break;
					case 2:
						$string = "banner DARK_AQUA";
					break;
					case 3:
						$string = "banner DARK_PURPLE";
					break;
					case 4:
						$string = "banner ORANGE";
					break;
					case 5:
						$string = "banner GRAY";
					break;
					case 6:
						$string = "banner DARK_GRAY";
					break;
					case 7:
						$string = "banner BLUE";
					break;
					case 8:
						$string = "banner GREEN";
					break;
					case 9:
						$string = "banner AQUA";
					break;
					case 10:
						$string = "banner RED";
					break;
					case 11:
						$string = "banner LIGHT_PURPLE";
					break;
					case 12:
						$string = "banner YELLOW";
					break;
					case 13:
						$string = "banner WHITE";
					break;
					default:
						return;
                }
				$this->plugin->getServer()->getCommandMap()->dispatch($sender, $string);
            }

        });
        $form->setTitle($this->plugin->cmds["banner"]["title"]);
        $form->addDropdown(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->cmds["banner"]["content"]["main"])), $this->plugin->cmds["banner"]["colors"]);
        $form->sendToPlayer($sender);
	}
	
    public function layer($player, $color, $all = false){
       $form = new SimpleForm(function (Player $player, $data = null) {
            $result = $data;
            if (is_null($result)) return true;
            switch ($result) {
                case 0:
                default:
                    $playern = $player->getName();
                    if ($this->$playern->all === false) {
                        $selected = $result;
                    } elseif ($result == 0){
                        $playern = $player->getName();
						if(Core::getInstance()->getStats()->getPoints($player, "balance") >= $this->plugin->cmds["banner"]["int"]["cost"]){
							$to_text = '§'.$this->colortags[strtoupper($this->$playern->color)]."§rEdited ".$this->$playern->color." banner";
							
							$player->sendMessage(str_replace(["{prefix}", "{banner}", "{cost}"], [$this->plugin->cmds["banner"]["prefix"], $to_text, $this->plugin->cmds["banner"]["int"]["cost"]], $this->plugin->cmds["banner"]["text"]["success"]));
							$item = Item::fromString("minecraft:banner:".$this->bannerc[strtoupper($this->$playern->color)]);
							
							$item->setCount($this->plugin->cmds["banner"]["int"]["count"]);
							$item->setNamedTag(JsonNbtParser::parseJSON("{display:{Name:".$to_text."},BlockEntityTag:{Base:".$this->bannerc[strtoupper($this->$playern->color)].",Patterns:[".substr($this->$playern->all, 0, -1)."]}}"));
							Core::getInstance()->getStats()->setPoints($player, "balance", "-", (int)$this->plugin->cmds["banner"]["int"]["cost"]);
							$player->getInventory()->addItem($item);
							
							$this->$playern->color = null;
							$this->$playern->all = null;
							$this->$playern->pattern = null;
							return;
                        } else {
                        	$player->sendMessage(str_replace(["{prefix}", "{cost}"], [$this->plugin->cmds["banner"]["prefix"], $this->plugin->cmds["banner"]["int"]["cost"]], $this->plugin->cmds["banner"]["text"]["nomoney"]));
                            return;
                        }
                    } else {
                        $selected = $result-1;
                    }
                    $this->color($player, $this->$playern->color, $this->$playern->all, $selected);
                    return;
            }
        });
        $colortag = '§'.$this->colortags[strtoupper($color)];
        $form->setTitle($this->plugin->cmds["banner"]["title"]);
        $form->setContent(implode("\n", str_replace(["{colortag}", "{color}", "{player}"], [$colortag, $color, $player->getName()], $this->plugin->cmds["banner"]["content"]["layer"])));
        if($all !== false) $form->addButton($this->plugin->cmds["banner"]["done"][0], $this->plugin->cmds["banner"]["done"][1], $this->plugin->cmds["banner"]["done"][2]);
        foreach($this->items as $item){
            $form->addButton($item);
        }
        $playern = $player->getName();
        $this->$playern->color = $color;
        $this->$playern->all = $all;
        $form->sendToPlayer($player);
    }

    public function color($player, $color, $all, $pattern){
       $form = new SimpleForm(function (Player $player, $data = null ) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            $playern = $player->getName();
            $this->$playern->all .=  '{Pattern:' . $this->patterns[$this->$playern->pattern] . ',Color:' . $this->bannerc[$this->colors[$result]].'},';
            $this->layer($player, $this->$playern->color, $this->$playern->all);
            return;
        });
        $colortag = '§'.$this->colortags[strtoupper($color)];
		$form->setTitle($this->plugin->cmds["banner"]["title"]);
		$form->setContent(implode("\n", str_replace(["{player}", "{colortag}", "{color}", "{pattern}"], [$player->getName(), $colortag, $color, $this->items[$pattern]], $this->plugin->cmds["banner"]["content"]["color"])));
        foreach($this->colors as $item){
            $form->addButton('§'.$this->colortags[$item] . ucfirst(strtolower(str_replace('_', ' ', $item))));
        }
        $playern = $player->getName();
        $this->$playern->pattern = $pattern;
        $form->sendToPlayer($player);
    }
}
