<?php

#   ____                                          _ _   _                      
#  / ___|___  _ __ ___  _ __ ___   __ _ _ __   __| | | | |___  __ _  __ _  ___ 
# | |   / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` | | | / __|/ _` |/ _` |/ _ \
# | |__| (_) | | | | | | | | | | | (_| | | | | (_| | |_| \__ \ (_| | (_| |  __/
#  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/|___/\__,_|\__, |\___|
#                                                                   |___/      
# Fix the commands arguments seen in MCPE clients for PocketMine. 

namespace BoxOfDevs\CommandUsage;

use pocketmine\scheduler\PluginTask;

class RegisterTask extends PluginTask {
	public function __construct(Main $owner) {
		parent::__construct($owner);
	}

  /**
   * Runs when the tasks runs
   * @param int $tick
   */
	public function onRun($tick){
		foreach($this->getOwner()->getServer()->getCommandMap()->getCommands() as $command){
			$this->getOwner()->setClientUsage($command, $command->getUsage());
		}
    foreach($this->getOwner()->getServer()->getOnlinePlayers() as $p){
      $p->sendCommandData();
    }
  }
}