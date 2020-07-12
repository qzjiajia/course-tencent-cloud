<?php

namespace App\Listeners;

use App\Caches\ConsultCounter as CacheConsultCounter;
use App\Models\Consult as ConsultModel;
use App\Services\Syncer\ConsultCounter as ConsultCounterSyncer;
use Phalcon\Events\Event;

class ConsultCounter extends Listener
{

    protected $counter;

    public function __construct()
    {
        $this->counter = new CacheConsultCounter();
    }

    public function incrLikeCount(Event $event, $source, ConsultModel $consult)
    {
        $this->counter->hIncrBy($consult->id, 'like_count');

        $this->syncConsultCounter($consult);
    }

    public function decrLikeCount(Event $event, $source, ConsultModel $consult)
    {
        $this->counter->hDecrBy($consult->id, 'like_count');

        $this->syncConsultCounter($consult);
    }

    protected function syncConsultCounter(ConsultModel $consult)
    {
        $syncer = new ConsultCounterSyncer();

        $syncer->addItem($consult->id);
    }

}