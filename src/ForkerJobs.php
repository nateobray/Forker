<?php
namespace obray;

class ForkerJobs
{
    private $pid;
    private $count;

    public function __construct(int $pid, int $count)
    {
        $this->pid = $pid;
        $this->count = $count;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getPID()
    {
        return $this->pid;
    }
}