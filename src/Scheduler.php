<?php
namespace obray;

class Scheduler
{
    private $processors = [];

    public function addProcessor(int $id)
    {
        $this->proessors[$id] = new \obray\Processor($id);
    }

    public function getLowestLoadProcessor(): int
    {
        $lowestLoad = NULL; $lowestLoadProcessor = NULL;
        forEach($this->processors as $processor){
            if($lowestLoad === NULL){
                $lowestLoad = $processor->getLoad();
                $lowestLoadProcessor = $processor;
            } else if ($lowestLoad > $processor->getLoad()){
                $lowestLoad = $processor->getLoad();
                $lowestLoadProcessor = $processor;
            }
        }
        return $lowestLoadProcessor->getIndentifier();
    }

    public function incrementProcessorLoad(int $identifier, int $amount=1): void
    {
        $this->processors[$identifier]->incrementLoad($amount);
    }

    public function setProcessorCapacity(int $identifier, int $amount): void
    {
        $this->processor[$identifier]->setCapacity($amount);
    }
}