<?php
#   ____                                          _ _   _                      
#  / ___|___  _ __ ___  _ __ ___   __ _ _ __   __| | | | |___  __ _  __ _  ___ 
# | |   / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` | | | / __|/ _` |/ _` |/ _ \
# | |__| (_) | | | | | | | | | | | (_| | | | | (_| | |_| \__ \ (_| | (_| |  __/
#  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/|___/\__,_|\__, |\___|
#                                                                   |___/      
# Fix the commands arguments seen in MCPE clients for PocketMine. 

namespace BoxOfDevs\CommandUsage;


use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\Player;






class Main extends PluginBase implements Listener {


   public function onEnable(){
        $this->lastCheck = null;
        $this->cmds = [];
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Loading all commands.
        foreach($this->getServer()->getCommandMap()->getCommands() as $command) {
            $this->setClientUsage($command, $command->getUsage());
        }
    }


    /*
    Sets the client command usage
    @param     $cmd    \pocketmine\command\Command
    @param     $usage    string
    */
    public function setClientUsage(\pocketmine\command\Command $cmd, string $usage) {
        $cmdData = $cmd->getCommandData();
        preg_match_all("/((<(.+?)>)|(\[(.+?)\]))/", $cmd->getUsage(), $matches);
        $data = [];
        foreach($matches[0] as $match) {
            $data[] = $this->string2Std($match);
            $this->getLogger()->debug("Arg $match in command " . $cmd->getName() . " is " . json_encode($data[count($data) - 1]));
        }
        $cmdData->overloads->default->input->parameters = $data;
        $this->cmds->{$command->getName()} = new \stdClass();
        $this->cmds->{$command->getName()}->versions = [];
        $this->cmds->{$command->getName()}->versions[0] = $cmdData;
    }



    /*
    Process a string like "<player>" or "[x]" and return a command data stdClass.
    @param     $data    string
    @return \stdClass|null
    */
    protected function string2Std(string $data) {
        $return = new \stdClass();
        if(preg_match("/^<(.+?)>$/", $data, $m) > 0) {
            $return->optional = false;
        } elseif(preg_match("/^\[(.+?)\]$/", $data, $m) > 0) {
            $return->optional = true;
        } else {
            $return->name = $data;
            $return->type = "rawtext";
            $return->optional = true;
            return $return;
        }
        $return->name = $m[0];
        switch(true) {
            case strpos(strtolower($m[0]), "player") || strpos(strtolower($m[0]), "target"):
            $return->type = "target";
            break;
            case ($this->lastCheck == "y" && strpos(strtolower($m[0]), "z")) || strpos(strtolower($m[0]), "x y z") || strpos(strtolower($m[0]), "coords") || strpos(strtolower($m[0]), "pos"):
            $return->type = "x y z";
            break;
            case $this->lastCheck == "x" && strpos(strtolower($m[0]), "y"):
            $this->lastCheck = "y";
            return null;
            break;
            case strpos(strtolower($m[0]), "x"):
            $this->lastCheck = "x";
            return null;
            break;
            case strpos(strtolower($m[0]), "number") || strpos(strtolower($m[0]), "int") || strpos(strtolower($m[0]), "id"):
            $return->type = "int";
            break;
            case strpos(strtolower($m[0]), "block") || strpos(strtolower($m[0]), "tile"):
            $return->type = "block";
            break;
            case strpos(strtolower($m[0]), "entity"):
            $return->type = "entity";
            break;
            case strpos(strtolower($m[0]), "gamemode"):
            $return->type = "gamemode";
            break;
            default:
            $return->type = "rawtext";
            break;
        }
        return $return;
    }


    /*
    Checks when a command packet is sent
    @param     $event    \pocketmine\event\server\DataPacketSendEvent
    */
    public function onDataPacketSend(\pocketmine\event\server\DataPacketSendEvent $event) {
        if($event->getPacket() instanceof \pocketmine\network\protocol\AvailableCommandsPacket) {
            $event->getPacket()->commands = json_encode($this->cmds);
        }
    }


}