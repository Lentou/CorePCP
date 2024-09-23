<?php

declare(strict_types=1);

namespace pcp\cmds;

use pcp\Core;

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pcp\libs\forms\{SimpleForm, CustomForm};

use pcp\libs\discord\Message;
use pcp\libs\discord\Webhook;
use pcp\libs\discord\Embed;

class BoardCmd extends PluginCommand{
  
	/** @var Core */
	private $plugin;
  
	public function __construct($name, Core $plugin){
		parent::__construct($name, $plugin);
		$this->plugin = $plugin;
		$this->setDescription($this->plugin->boards["board"]["desc"]);
	}
  
	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player) return false;
			$this->boardForm($sender);
			return true;
	}
	
	public function getBoardConfig(string $string){
		return $this->plugin->board->getNested($string);
	}
	
	public function boardMsg(string $string){
		return str_replace("{prefix}", $this->plugin->boards["board"]["prefix"], (string)$this->getBoardConfig($string));
	}
	
	public function boardForm(Player $sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->wikiForm($sender);
				break;
				case 1:
					if($this->plugin->boards["broadcast"]["enable"] === true){
						$this->broadcastForm($sender);
					} else {
						$sender->sendMessage($this->boardMsg("broadcast.text.close"));
					}
				break;
				case 2:
					if($this->plugin->boards["apply"]["enable"] === true){
						$this->applyForm($sender);
					} else {
						$sender->sendMessage($this->boardMsg("apply.text.close"));
					}
				break;
				case 3:
					if($this->plugin->boards["feedback"]["enable"] === true){
						$this->feedbackForm($sender);
					} else {
						$sender->sendMessage($this->boardMsg("feedback.text.close"));
					}
				break;
				case 4:
					if($this->plugin->boards["suggest"]["enable"] === true){
						$this->suggestForm($sender);
					} else {
						$sender->sendMessage($this->boardMsg("suggest.text.close"));
					}
				break;
				case 5:
					if($this->plugin->boards["report"]["enable"] === true){
						$this->reportForm($sender);
					} else {
						$sender->sendMessage($this->boardMsg("report.text.close"));
					}
				break;
				case 6:
					$this->leadForm($sender);
				break;
			}
		});
		$form->setTitle($this->plugin->boards["board"]["title"]["main"]);
		foreach($this->plugin->boards["board"]["buttons"]["main"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$form->sendToPlayer($sender);
	}
	
	public function wikiForm(Player $sender) {
        $form = new SimpleForm(function (Player $sender, $data) {
            if (is_null($data)) return true;
            $buttons = array_keys($this->plugin->boards["wiki"]);
            if (count($buttons) == $data) return;
            $button = $buttons[$data];
			$this->wikiPageForm($sender, $button);
        });
        $form->setTitle($this->plugin->boards["board"]["title"]["wiki"]);
        $form->setContent(implode("\n", str_replace("{player}", $sender->getName(), $this->plugin->boards["board"]["content"]["wiki"])));
        foreach(array_keys($this->plugin->boards["wiki"]) as $wiki) {
            $form->addButton(
				$this->plugin->boards["wiki"]["$wiki"]["button"][0],
				$this->plugin->boards["wiki"]["$wiki"]["button"][1], 
				$this->plugin->boards["wiki"]["$wiki"]["button"][2]
			);
        }
        $form->sendToPlayer($sender);
    }
	
	public function wikiPageForm(Player $sender, $button){
		$form = new SimpleForm(function (Player $sender, $data) {
            if (is_null($data)) return true;
            switch ($data) {
                case 0:
					$this->wikiForm($sender);
				break;
            }
        });
        $form->setTitle($this->plugin->boards["wiki"]["$button"]["title"]);
        $form->setContent(implode("\n", $this->plugin->boards["wiki"]["$button"]["content"]));
        $form->addButton(
			$this->plugin->cmds["utils"]["buttons"]["back"][0], 
			$this->plugin->cmds["utils"]["buttons"]["back"][1], 
			$this->plugin->cmds["utils"]["buttons"]["back"][2]
		);
        $form->sendToPlayer($sender);
	}
	
	public function broadcastForm(Player $sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data == null) return false;
			if($data[1] == true){
				if($data[2] == null){
					$sender->sendMessage($this->boardMsg("broadcast.text.notempty"));
					return false;
				}
				if(Core::getInstance()->getStats()->getPoints($sender, "balance") < $this->boardMsg("broadcast.cost.input")){
					$sender->sendMessage($this->boardMsg("broadcast.text.notenough"));
					return false;
				}
				Core::getInstance()->getStats()->setPoints($sender, "balance", "-", $this->getBoardConfig("broadcast.cost.input"));
				Server::getInstance()->broadcastMessage(str_replace(["{player}", "{msg}"], [$sender->getName(), $data[2]], $this->boardMsg("broadcast.text.cast")));
				$sender->sendMessage($this->boardMsg("broadcast.text.success"));
				$placeholder = [
					"{broadcast}" => $data[2]
				];
				$this->sendEmbedMessage("broadcast", $sender->getName(), $sender->getName(), $placeholder);
			} else {
				if($data[2] != null){
					$sender->sendMessage($this->boardMsg("broadcast.text.empty"));
					return false;
				}
				if(Core::getInstance()->getStats()->getPoints($sender, "balance") < $this->boardMsg("broadcast.cost.dropdown")){
					$sender->sendMessage($this->boardMsg("broadcast.text.notenough"));
					return false;
				}
				$bcast = $this->plugin->boards["broadcast"]["list"][$data[3]];
				Core::getInstance()->getStats()->setPoints($sender, "balance", "-", $this->getBoardConfig("broadcast.cost.dropdown"));
				Server::getInstance()->broadcastMessage(str_replace(["{player}", "{msg}"], [$sender->getName(), $bcast], $this->boardMsg("broadcast.text.cast")));
				$sender->sendMessage($this->boardMsg("broadcast.text.success"));
				$placeholder = [
					"{broadcast}" => $bcast
				];
				$this->sendEmbedMessage("broadcast", $sender->getName(), $sender->getName(), $placeholder);
			}
		});
		$form->setTitle($this->getBoardConfig("broadcast.form.title"));
		$form->addLabel(implode("\n", $this->getBoardConfig("broadcast.form.content"))); //0
		$form->addToggle($this->getBoardConfig("broadcast.form.toggle"), false); //1
		$form->addInput($this->getBoardConfig("broadcast.form.input")); //2
		$form->addDropdown($this->getBoardConfig("broadcast.form.dropdown"), $this->getBoardConfig("broadcast.list")); //3
		$sender->sendForm($form);
	}
	
	public function leadForm(Player $sender){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->topForm($sender, "balance");
				break;
				case 1:
					$this->topForm($sender, "strength");
				break;
				case 2:
					$this->topForm($sender, "bounty");
				break;
				case 3:
					$this->topForm($sender, "streak");
				break;
				case 4:
					$this->topForm($sender, "kills");
				break;
				case 5:
					$this->topForm($sender, "deaths");
				break;
				case 6:
					$this->topForm($sender, "charm");
				break;
				case 7:
					$this->topForm($sender, "level");
				break;
				case 8:
					$this->topForm($sender, "elo");
				break;
				case 9:
					$this->topForm($sender, "quest");
				break;
				case 10:
					$this->topForm($sender, "votes");
				break;
			}
		});
		$form->setTitle($this->plugin->boards["board"]["title"]["lead"]);
		foreach($this->plugin->boards["board"]["buttons"]["lead"] as $buttons => $value){
			$form->addButton($value[0], $value[1], $value[2]);
		}
		$form->sendToPlayer($sender);
	}
	
	public function topForm(Player $sender, string $leaderboards){
		$form = new SimpleForm(function (Player $sender, $data){
			if(is_null($data)) return true;
			switch($data){
				case 0:
					$this->leadForm($sender);
				break;
			}
		});
		$form->setTitle($this->plugin->boards["board"]["title"]["lead"]);
		switch($leaderboards){
			case "balance": case "strength": case "bounty": case "streak": case "kills": case "deaths": case "charm": case "quest":
				$string = $this->plugin->getLeaderboard($leaderboards);
			break;
			case "level": case "elo":
				$string = $this->plugin->cx->getTopBy($leaderboards, 10);
			break;
		}
		$form->setContent($string);
		$form->addButton($this->plugin->cmds["utils"]["buttons"]["back"][0], $this->plugin->cmds["utils"]["buttons"]["back"][1], $this->plugin->cmds["utils"]["buttons"]["back"][2]);
		$form->sendToPlayer($sender);
	}
	
	public function applyForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return true;
			unset($data[0]);
			$position = $this->plugin->boards["apply"]["position"][$data[1]];
			unset($data[1]);
			$this->sendReportMessage($sender, $position, $data, "apply");
			$form = new CustomForm(function (Player $sender, $data){
				if($data === null){
					$sender->sendMessage($this->boardMsg("apply.text.sent"));
				} else {
					$sender->sendMessage($this->boardMsg("apply.text.sent"));
				}
			});
			$form->setTitle($this->getBoardConfig("apply.form.title"));
			$form->addLabel(implode("\n", $this->getBoardConfig("apply.form.pending")));
			$sender->sendForm($form);
		});
		$form->setTitle($this->getBoardConfig("apply.form.title"));
		$form->addLabel(implode("\n", $this->getBoardConfig("apply.form.content")));
		$form->addDropdown($this->getBoardConfig("apply.form.dropdown"), $this->getBoardConfig("apply.position"));
		foreach($this->getBoardConfig("apply.fields") as $question){
			$form->addInput($question);
		}
		$sender->sendForm($form);
	}
	
	public function feedbackForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return false;
			if($data[2] == null){
				$sender->sendMessage($this->boardMsg("feedback.text.empty"));
				return false;
			}
			$rate = $this->plugin->boards["feedback"]["rate"][ $data[1] ];
			$review = $data[2];
			$sender->sendMessage($this->boardMsg("feedback.text.success"));
			$placeholder = [
				"{rate}" => $rate,
				"{review}" => $review
			];
			$this->sendEmbedMessage("feedback", $sender->getName(), $sender->getName(), $placeholder);
		});
		$form->setTitle($this->getBoardConfig("feedback.form.title"));
		$form->addLabel(implode("\n", $this->getBoardConfig("feedback.form.content")));
		$form->addStepSlider($this->getBoardConfig("feedback.form.slider"), $this->getBoardConfig("feedback.rate"));
		$form->addInput($this->getBoardConfig("feedback.form.input"));
		$sender->sendForm($form);
	}
	
	public function suggestForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return true;
			if($data[1] === null || $data[2] === null){
				$sender->sendMessage($this->boardMsg("suggest.text.empty"));
				return true;
			}
			$placeholder = [
				"{title}" => $data[1],
				"{desc}" => $data[2]
			];
			$this->sendEmbedMessage("suggestion", $sender->getName(), $sender->getName(), $placeholder);
			$sender->sendMessage($this->boardMsg("suggest.text.success"));
		});
		$form->setTitle($this->getBoardConfig("suggest.form.title"));
		$form->addLabel(implode("\n", $this->getBoardConfig("suggest.form.content")));
		$form->addInput($this->getBoardConfig("suggest.form.input.title"));
		$form->addInput($this->getBoardConfig("suggest.form.input.desc"));
		$sender->sendForm($form);
	}
	
	public function reportForm($sender){
		$form = new CustomForm(function (Player $sender, $data){
			if($data === null) return true;
			unset($data[0]);
			$reason = $this->plugin->boards["report"]["reason"][$data[1]];
			unset($data[1]);
			$this->sendReportMessage($sender, $reason, $data, "report");
			$form = new CustomForm(function (Player $sender, $data){
				if($data === null){
					$sender->sendMessage($this->boardMsg("report.text.sent"));
				} else {
					$sender->sendMessage($this->boardMsg("report.text.sent"));
				}
			});
			$form->setTitle($this->getBoardConfig("report.form.title"));
			$form->addLabel(implode("\n", $this->getBoardConfig("report.form.pending")));
			$sender->sendForm($form);
		});
		$form->setTitle($this->getBoardConfig("report.form.title"));
		$form->addLabel(implode("\n", $this->getBoardConfig("report.form.content")));
		$form->addDropdown($this->getBoardConfig("report.form.dropdown"), $this->getBoardConfig("report.reason"));
		foreach($this->getBoardConfig("report.fields") as $input){
			$form->addInput($input);
		}
		$sender->sendForm($form);
	}
	
	public function sendEmbedMessage(string $type, string $username, string $staffname, array $placeholder){
		$webhook = new Webhook($this->getBoardConfig("discord." . $type . ".webhook"));
		
		$embed = new Embed();
		$embed->setTitle(str_replace("{username}", $username, $this->getBoardConfig("discord." . $type . ".title")));
		$embed->setColor($this->getBoardConfig("discord." . $type . ".color"));
		
		foreach($this->getBoardConfig("discord." . $type . ".description") as $title => $field){
			$embed->addField($title, strtr($field, $placeholder));
		}
		
		$embed->setFooter(str_replace("{staffname}", $staffname, $this->getBoardConfig("discord." . $type . ".footer")));
		$embed->setTimestamp(date_create());
		
		$msg = new Message();
		$msg->addEmbed($embed);
		
		$webhook->send($msg);
	}
	
	public function sendReportMessage($sender = null, string $position = "", array $data = [], string $type = "") : void {
		$webHook = new Webhook($this->getBoardConfig("discord." . $type . ".webhook"));

		$embed = new Embed();
		$embed->setTitle(str_replace("{username}", $sender->getName(), $this->getBoardConfig("discord." . $type . ".title")));
		$embed->setColor($this->getBoardConfig("discord." . $type . ".color"));
		$data = array_values($data);

		$i = 0;
		foreach($this->getBoardConfig($type . ".fields") as $q) {
			$embed->addField("{$q}", ($data[$i] == "" ? "unspecified.." : $data[$i]) );
			$i += 1;
		}

		$embed->setFooter(str_replace("{staffname}", $sender->getName(), $this->getBoardConfig("discord." . $type . ".footer")));
		$embed->setTimestamp(date_create());
		
		$embed->setDescription(str_replace("{position}", $position, $this->getBoardConfig("discord." . $type . ".desc")));
		$msg = new Message();
		$msg->addEmbed($embed);
		
		$webHook->send($msg);
	}

}