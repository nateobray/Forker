<?php

namespace obray;

class Forker
{
    private $queueInt = 2555;
    private $minNumProcesses = 4;
    private $maxJobsPerProcess = 100;
    private $children = [];
    private $active;
    private $parentPID;
    private $messageQueue;

    public function __construct()
    {
        $this->parentPID = getmypid();
        $this->messageQueue = msg_get_queue($this->queueInt);
    }

    public function fork(callable $callback)
    {
        $this->callback = $callback;
        for($i=0; $i<$this->minNumProcesses; ++$i){
            $pid = $this->startChildProcess();
        }
        if($pid){

            $queueWatcher = new \EvPeriodic(0, 0.1, NULL, function(){
                $actualMessageType = 0; $message;
                msg_receive($this->messageQueue, $this->parentPID, $actualMessageType, 1024, $message, true, MSG_IPC_NOWAIT);
                $this->handleParentIncomingMessage($message);
            });

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
            // we are the parent
            $this->children[$pid] = 0;
            // wait for singal event from child process that its done processing
            $this->children[$pid] = new \EvChild($pid, FALSE, function ($w, $revents) {
                $w->stop();
                printf("Process %d exited with status %d\n", $w->rpid, $w->rstatus);
            });
        } else {
            $pid = getmypid();
            $jobs = new \stdClass();
            $jobs->pid = $pid;
            $jobs->count = 10;
            msg_send($this->messageQueue, $this->parentPID, $jobs, true)
            ($this->callback)($pid);
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