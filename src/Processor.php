<?php
namespace obray;

class Processor
{
    private $indentifier;
    private $load = 0;
    private $capacity = 0;

    public function __construct(int $indentifier)
    {
        $this->indentifier = $indentifier;
    }

    public function getIndentifier(): int
    {
        return $this->indentifier;
    }

    public function getLoad(): int
    {
        return $this->load;
    }

    public function incrementLoad(int $amount=1)
    {
        $load += $amount;
    }

    public function decrementLoad(int $amount=1)
    {
        $load -= $amount;
    }

    public function setCapacity(int $amount=1)
    {
        $this->capacity = $amount;
    }

    public function incrementCapacity(int $amount=1)
    {
        $this->capacity += $amount;
    }
}