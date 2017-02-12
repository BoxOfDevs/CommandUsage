<?php
#   ____                                          _ _   _                      
#  / ___|___  _ __ ___  _ __ ___   __ _ _ __   __| | | | |___  __ _  __ _  ___ 
# | |   / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` | | | / __|/ _` |/ _` |/ _ \
# | |__| (_) | | | | | | | | | | | (_| | | | | (_| | |_| \__ \ (_| | (_| |  __/
#  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/|___/\__,_|\__, |\___|
#                                                                   |___/      
#

namespace BoxOfDevs\CommandUsage;


use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;






class Main extends PluginBase implements Listener {


   public function onEnable(){


        $this->reloadConfig();


        $this->getServer()->getPluginManager()->registerEvents($this, $this);


    }




    public function onLoad(){


        $this->saveDefaultConfig();


    }




    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){


        switch($cmd->getName()){


            case 'default':


            break;


        }


     return false;


    }


}