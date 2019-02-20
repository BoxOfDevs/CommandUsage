<?php

#   ____                                          _ _   _                      
#  / ___|___  _ __ ___  _ __ ___   __ _ _ __   __| | | | |___  __ _  __ _  ___ 
# | |   / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` | | | / __|/ _` |/ _` |/ _ \
# | |__| (_) | | | | | | | | | | | (_| | | | | (_| | |_| \__ \ (_| | (_| |  __/
#  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/|___/\__,_|\__, |\___|
#                                                                   |___/      
# Fix the commands arguments seen in MCPE clients for PocketMine. 

namespace BoxOfDevs\CommandUsage;

use pocketmine\scheduler\Task;

class RegisterTask extends Task {
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	/*
	RUns when the tasks runs.
	@param     $tick    int
	*/
	public function onRun($tick){
		foreach($this->plugin->getServer()->getCommandMap()->getCommands() as $command){
			$this->plugin->setClientUsage($command, $command->getUsage());
        }
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
			$p->sendCommandData();
	}
	}
}
