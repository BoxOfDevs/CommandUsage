<?php

#   ____                                          _ _   _                      
#  / ___|___  _ __ ___  _ __ ___   __ _ _ __   __| | | | |___  __ _  __ _  ___ 
# | |   / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` | | | / __|/ _` |/ _` |/ _ \
# | |__| (_) | | | | | | | | | | | (_| | | | | (_| | |_| \__ \ (_| | (_| |  __/
#  \____\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/|___/\__,_|\__, |\___|
#                                                                   |___/      
# Fix the commands arguments seen in MCPE clients for PocketMine. 

namespace BoxOfDevs\CommandUsage;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;

class Main extends PluginBase implements Listener {
	
	public function onEnable(){
		$this->lastCheck = null;
		$this->cmds = new \stdClass();
        $this->aliases = [];
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Loading all commands.
        $this->getScheduler()->scheduleRepeatingTask(new RegisterTask($this), 20 * 5); // Registers after all commands are loaded
    }

    /*
    Sets the client command usage
    @param     $cmd    \pocketmine\command\Command
    @param     $usage    string
    */
    public function setClientUsage(Command $cmd, string $usage){
        $cmdData =  json_decode(file_get_contents($this->getServer()->getFilePath() . "src/pocketmine/resources/command_default.json"));
        //Getting the usage.
        if(substr($cmd->getUsage(), 0,1) == "%"){
            $usage = $this->getServer()->getLanguage()->translateString(substr($cmd->getUsage(), 1), []);
        }else{
            $usage = $cmd->getUsage();
        }
        // Parsing arguments
        preg_match_all("/((<(.+?)>)|(\[(.+?)\]))/", $usage, $matches);
        $data = [];
        foreach($matches[0] as $key => $match){
            $data[] = $this->string2Std($match);
            if(count($matches[0]) - 1 == $key && isset($data[$key]->type) && $data[$key]->type == "string") $data[$key]->type = "rawtext";
        }
        // Setting the command data
        foreach($cmd->getAliases() as $alias){
            $this->aliases[$alias] = $cmd->getName();
        }
        $cmdData->aliases = $cmd->getAliases();
        $cmdData->description = $cmd->getDescription();
        $cmdData->permission = $cmd->getPermission();
        $cmdData->overloads->default->input->parameters = $data;
        $this->cmds->{$cmd->getName()} = new \stdClass();
        $this->cmds->{$cmd->getName()}->versions = [];
        $this->cmds->{$cmd->getName()}->versions[0] = $cmdData;
    }

    /*
    Process a string like "<player>" or "[x]" and return a command data stdClass.
    @param     $data    string
    @return \stdClass|null
    */
    protected function string2Std(string $data){
		$return = new \stdClass();
        if(preg_match("/^<(.+?)>$/", $data, $m) > 0){
            $return->optional = false;
        }elseif(preg_match("/^\[(.+?)\]$/", $data, $m) > 0){
            $return->optional = true;
        }else{
            $return->name = $data;
            $return->type = "rawtext";
            $return->optional = true;
            return $return;
        }
        $return->name = $m[1];
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
            case strpos(strtolower($m[0]), "..."):
            $return->type = "rawtext";
            break;
            default:
            $return->type = "string";
            break;
        }
        return $return;
    }

    /*
    Checks when a command packet is sent
    @param     $event    DataPacketSendEvent
    */
    public function onDataPacketSend(DataPacketSendEvent $event){
        if($event->getPacket() instanceof AvailableCommandsPacket){
            $event->getPacket()->commands = json_encode($this->cmds);
        }
    }

    /*
    Properly set args back to their original position
    @param     $event    DataPacketReceiveEvent
    */
    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        if($event->getPacket() instanceof CommandStepPacket){
            if($event->getPacket()->args !== null){
                $ordered = "";
                $args = $event->getPacket()->args;
                if(!isset($this->cmds->{$event->getPacket()->command})){
                    $cmd = $this->cmds->{$this->aliases[$event->getPacket()->command]};
                }else{
                    $cmd = $this->cmds->{$event->getPacket()->command};
                }
                $args2 = $cmd->versions[0]->overloads->{$event->getPacket()->overload}->input->parameters;
                foreach($args2 as $key => $arg){
                    if(isset($args->{$arg->name})){
                        var_dump($args->{$arg->name});
                        if($args->{$arg->name} instanceof \stdClass){ 
                            if(isset($args->{$arg->name}->rules)){
                                $args->{$arg->name} = $args->{$arg->name}->rules[0]->value;
                            }else{
                                switch($args->{$arg->name}->selector){
                                    case "randomPlayer":
                                    $args->{$arg->name} = "@r";
                                    break;
                                    case "nearestPlayer":
                                    $args->{$arg->name} = "@p";
                                    break;
                                    case "allPlayers":
                                    $args->{$arg->name} = "@a";
                                    break;
                                }
                            }
                        }
                        $ordered .= $args->{$arg->name} . " ";
                    }
                }
                $event->getPacket()->args = ["args" => substr($ordered, 0, strlen($ordered) - 1)];
            }
        }
    }
}
