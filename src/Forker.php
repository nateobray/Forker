<?php

namespace obray;

class Forker
{
    private $queueInt = 2500;
    private $minNumProcesses = 4;
    private $maxJobsPerProcess = 100;
    private $children = [];
    private $active;
    private $parentPID;
    private $messageQueue;
    private $activePID = 0;

    private $jobsRemaining;

    public function __construct()
    {
        $this->parentPID = getmypid();
        if(msg_queue_exists($this->queueInt)){
            print_r("message queue exists\n");
            $this->messageQueue = msg_get_queue($this->queueInt);
            $stats = msg_stat_queue($this->messageQueue);
            print_r($stats);
            if($stats['msg_qbytes'] >= 2048){
                print_r("Removing message queue...");
                if(msg_remove_queue($this->messageQueue)){
                    print_r("success!\n");
                } else {
                    print_r("failed!\n");
                }
            }
            $this->messageQueue = msg_get_queue($this->queueInt);
        } else {
            $this->messageQueue = msg_get_queue($this->queueInt);
        }
        
    }

    public function fork(callable $callback)
    {
        $this->callback = $callback;
        for($i=0; $i<$this->minNumProcesses; ++$i){
            $this->activePID = $this->startChildProcess();
        }
        if($this->activePID){

            $queueWatcher = new \EvPeriodic(0, 0.1, NULL, function(){
                $actualMessageType = 0; $message;
                msg_receive($this->messageQueue, $this->parentPID, $actualMessageType, 1024, $jobs, true, MSG_IPC_NOWAIT);
                print_r($jobs);
                if(!empty($jobs) && $jobs->getCount() === -1){
                    print_r("sending message on ".$this->parentPID."\n");
                    forEach($this->children as $pid => $w){
                        if(empty($loPID)) $loPID = $pid;
                        if($pid > $this->activePID){
                            $newActivePID = $pid;
                        }
                        if(empty($newActivePID)){
                            $this->activePID = $loPID;
                        } else {
                            $this->activePID = $newActivePID;
                        }
                    }
                    msg_send($this->messageQueue, $this->activePID, (new \obray\ForkerJobs($this->activePID, 10)), true, false);        
                }
            }, $this);

            // send message to specify process that is actively processing new jobs
            $initialSent = new \EvPeriodic(0, 0.1, NULL, function($w){
                print_r("sending original message on ".$this->activePID."\n");
                if(msg_send($this->messageQueue, $this->activePID, (new \obray\ForkerJobs($this->activePID, 10)), true, false)){
                    $w->stop();
                }
            }, $this);
            
            \Ev::run();
            print_r("Waiting for children to finish...\n");
            pcntl_wait($status); // Protect against Zombie children
            print_r("Terminating");
            
        }
    }

    private function startChildProcess()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // wait for singal event from child process that its done processing
            $this->children[$pid] = new \EvChild($pid, FALSE, function ($w, $revents) {
                $w->stop();
                printf("Process %d exited with status %d\n", $w->rpid, $w->rstatus);
            });
        } else {
            $pid = getmypid();
            print_r("Calling callback.\n");
            
            if(empty($count)) $count = 0;
            $data = new \stdClass();
            $data->count = &$count;
            $data->pid = $pid;
            $data->parentPID = $this->parentPID;
            $data->queue = $this->messageQueue;
            $data->process = &$this;
            $w = new \EvPeriodic(0, 0.5, NULL, function($w){
                print_r("Attempting to receive message on ".$w->data->pid."\n");
                msg_receive($w->data->queue, $w->data->pid, $actualMessageType, 1024, $job, true, MSG_IPC_NOWAIT);
                if(!empty($job)){
                    print_r($job);
                    $w->data->process->jobsRemaining = $job->getCount();
                }
                if($w->data->process->jobsRemaining > 0){
                    ++$w->data->count;
                    --$w->data->process->jobsRemaining;
                    print_r($w->data->pid . " loop " . $w->data->count . "\n");
                } else if($w->data->process->jobsRemaining === 0){
                    print_r("Sending message to : " . $w->data->parentPID . "\n");
                    if(@msg_send($w->data->queue, $w->data->parentPID, new \obray\ForkerJobs($w->data->pid, -1), true, false)){
                        $w->data->process->jobsRemaining = NULL;
                    }
                }
            }, $data);
            \Ev::run();
            exit();
        }
        return $pid;
    }

    public function handleParentIncomingMessage($message)
    {
        print_r($message);
    }

    public function setNumMinProcesses(int $minProcesses): void
    {
        $this->minNumProcesses = $minProcesses;
    }

    public function setMaxJobsPerProcess(int $maxJobs)
    {
        $this->maxJobsPerProcess = $maxJobs;
    }

}