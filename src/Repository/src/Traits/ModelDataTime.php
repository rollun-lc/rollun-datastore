<?php


namespace rollun\repository\Traits;


trait ModelDataTime
{
    protected $createAtField = 'created_at';

    protected $updatedAtField = 'updated_at';

    protected $timestampFormat = 'Y-m-d H:i:s.v';

    public function setCreatedAt()
    {
        $date = new \DateTime();
        $this->{$this->createAtField} = $date->format($this->timestampFormat);
    }

    public function renewUpdatedAt()
    {
        $date = new \DateTime();
        $this->{$this->updatedAtField} = $date->format($this->timestampFormat);
    }
}