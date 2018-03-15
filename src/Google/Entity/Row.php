<?php
/**
 * Created by PhpStorm.
 * User: again
 * Date: 12/03/2018
 * Time: 12:13
 */

namespace Google\Entity;

/**
 * Class InterestOverTimeRow
 * @package Google\Entity
 */
class Row
{
    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $value;

    /**
     * Row constructor.
     * @param int $timestamp
     * @param int $value
     */
    public function __construct(int $timestamp, int $value)
    {
        $this->timestamp = $timestamp;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return \DateTime
     */
    public function getDate() : \DateTime
    {
        $date = new \DateTime();
        $date->setTimestamp($this->timestamp);

        return $date;
    }
}