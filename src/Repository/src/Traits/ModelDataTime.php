<?php


namespace rollun\repository\Traits;


use DateTimeInterface;

/**
 * Trait ModelDataTime
 *
 * @package rollun\repository\Traits
 */
trait ModelDataTime
{
    protected $createAtField = 'created_at';

    protected $updatedAtField = 'updated_at';

    protected $timestampFormat = 'Y-m-d H:i:s.v';

    protected $defaultTimezone = 'UTC';

    /**
     * @throws \Exception
     */
    public function setCreatedAt(?DateTimeInterface $date = null)
    {
        if ($date === null) {
            $date = new \DateTime('now', new \DateTimeZone($this->getDefaultTimezone()));
        }

        $this->{$this->getCreatedAtField()} = $date->format($this->getTimestamFormat());
    }

    public function setUpdatedAt(?DateTimeInterface $date = null)
    {
        if ($date === null) {
            $date = new \DateTime('now', new \DateTimeZone($this->getDefaultTimezone()));
        }

        $this->{$this->getUpdatedAtField()} = $date->format($this->getTimestamFormat());
    }

    /**
     * @throws \Exception
     */
    public function renewUpdatedAt()
    {
        $date = new \DateTime('now', new \DateTimeZone($this->getDefaultTimezone()));
        $this->{$this->getUpdatedAtField()} = $date->format($this->getTimestamFormat());
    }

    /**
     * @return string
     */
    public function getCreatedAtField()
    {
        return $this->createAtField;
    }

    /**
     * @return string
     */
    public function getUpdatedAtField()
    {
        return $this->updatedAtField;
    }

    /**
     * @return string
     */
    public function getTimestamFormat()
    {
        return $this->timestampFormat;
    }

    /**
     * @return string
     */
    public function getDefaultTimezone()
    {
        return $this->defaultTimezone;
    }
}