<?php

namespace Context\Page\Base;

use Behat\Mink\Element\NodeElement;

/**
 * Page object for datagrid generated by the OroGridBundle
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Grid extends Index
{
    /**
     * {@inheritdoc}
     */
    public function __construct($session, $pageFactory, $parameters = array())
    {
        parent::__construct($session, $pageFactory, $parameters);

        $this->elements = array_merge(
            $this->elements,
            array(
                'Grid'         => array('css' => 'table.grid'),
                'Grid content' => array('css' => 'table.grid tbody'),
                'Filters'      => array('css' => 'div.filter-item'),
            )
        );
    }

    /**
     * Get a row from the grid containing the value asked
     * @param string $value
     *
     * @throws \InvalidArgumentException
     * @return NodeElement
     */
    public function getGridRow($value)
    {
        $value = str_replace('"', '', $value);
        $gridRow = $this->getElement('Grid content')->find('css', sprintf('tr:contains("%s")', $value));

        if (!$gridRow) {
            throw new \InvalidArgumentException(
                sprintf('Couldn\'t find a row for value "%s"', $value)
            );
        }

        return $gridRow;
    }

    /**
     * @param string $element
     * @param string $actionName
     */
    public function clickOnAction($element, $actionName)
    {
        $rowElement = $this->getGridRow($element);
        $rowElement->find('css', 'a.dropdown-toggle')->click();

        $action = $rowElement->find('css', sprintf('a.action[title=%s]', $actionName));

        if (!$action) {
            throw new \Exception(sprintf('Could not find action "%s".', $actionName));
        }

        $action->click();
    }

    /**
     * Filter the filter name by the value
     * @param string $filterName
     * @param string $value
     */
    public function filterBy($filterName, $value)
    {
        $filter = $this->getFilter($filterName);
        $this->openFilter($filter);

        if ($elt = $filter->find('css', 'select')) {
            $elt->selectOption($value);
        } elseif ($elt = $filter->find('css', 'div.filter-criteria')) {
            $elt->fillField('value', $value);
            $filterCriteria->find('css', 'button.filter-update')->click();
        } else {
            throw new \InvalidArgumentException(sprintf('Filtering by "%s" is not yet implemented"', $filterName));
        }
    }

    /**
     * Count all rows in the grid
     * @return integer
     */
    public function countRows()
    {
        return count($this->getElement('Grid content')->findAll('css', 'tr'));
    }

    /**
     * Get the text in the specified column of the specified row
     * @param string $column
     * @param string $row
     * @param string $expectation
     *
     * @return string
     */
    public function getColumnValue($column, $row, $expectation)
    {
        return $this->getRowCell($this->getGridRow($row), $this->getColumnPosition($column))->getText();
    }

    /**
     * @param string $column
     *
     * @return integer
     */
    protected function getColumnPosition($column)
    {
        $headers = $this->getElement('Grid')->findAll('css', 'thead th');
        foreach ($headers as $position => $header) {
            if ($column === $header->getText()) {
                return $position;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Couldn\'t find a column "%s"', $column)
        );
    }

    /**
     * @param string $row
     * @param string $position
     *
     * @return NodeElement
     */
    protected function getRowCell($row, $position)
    {
        $cell = $row->findAll('css', 'td');
        if (!isset($cell[$position])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Trying to access cell %d of a row which has %d cell(s).',
                    $position,
                    count($cell)
                )
            );
        }

        return $cell[$position];
    }

    /**
     * Open the filter
     * @param NodeElement $filter
     *
     * @throws \InvalidArgumentException
     */
    protected function openFilter(NodeElement $filter)
    {
        if ($element = $filter->find('css', 'button')) {
            $element->click();
        } else {
            throw new \InvalidArgumentException(
                'Impossible to open filter or maybe its type is not yet implemented'
            );
        }
    }

    /**
     * Get grid filter from label name
     * @param string $filterName
     *
     * @throws \InvalidArgumentException
     * @return NodeElement
     */
    public function getFilter($filterName)
    {
        $filter = $this->getElement('Filters')->find('css', sprintf(':contains("%s")', $filterName));

        if (!$filter) {
            throw new \InvalidArgumentException(
                sprintf('Couldn\'t find a filter for name "%s"', $filterName)
            );
        }

        return $filter;
    }
}
