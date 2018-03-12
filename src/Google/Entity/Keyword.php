<?php
/**
 * Created by PhpStorm.
 * User: again
 * Date: 12/03/2018
 * Time: 12:18
 */

namespace Google\Entity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Keyword
 * @package Google\Entity
 */
class Keyword
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var Row[]
     */
    private $rows;

    /**
     * Keyword constructor.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
        $this->rows = [];
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param Row $row
     */
    public function addRow(Row $row) : void
    {
        $this->rows[] = $row;
    }

    /**
     * @return Row[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

}