<?php


namespace rollun\repository\Traits;


trait ModelDataTime
{
    protected $createAtField = 'created_at';

    protected $updatedAtField = 'updated_at';

    protected $timestampFormat = 'Y-m-d H:i:s.v';

    protected $defaultTimezone = 'UTC';

    public function setCreatedAt()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->defaultTimezone));
        $this->{$this->createAtField} = $date->format($this->timestampFormat);
    }

    public function renewUpdatedAt()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->defaultTimezone));
        $this->{$this->updatedAtField} = $date->format($this->timestampFormat);
    }
}