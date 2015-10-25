<?php

namespace Jchedev\Classes\Listing;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Listing implements Jsonable
{
    /**
     * Default values
     */
    const   DEFAULT_PAGE_SIZE = 25;
    const   DEFAULT_SORT_FIELD = 'id';
    const   DEFAULT_SORT_ORDER = 'DESC';

    /**
     * @var \Illuminate\Database\Eloquent\Builder Initial builder used to generate correct builder and such
     */
    private $_default_builder;

    /**
     * @var \Illuminate\Database\Eloquent\Collection Collection of elements loaded
     */
    private $_collection;

    /**
     * @var array Collect parameters applied to the builder during the life of the listing
     */
    private $_parameters = [];

    /**
     * @var array Configuration of the listing (list of sorting acceptable values)
     */
    private $_sort_fields = ['id', 'created_at', 'updated_at'];

    /**
     * @var \Closure List of parameters to return in toArray
     */
    protected $_fields_returned;

    /**
     * @var array List of relations to load on the objects
     */
    protected $_relations = [];

    /**
     * Initial configuration to apply to the listing from the beginning
     *
     * @param $listing_type
     * @param array $configuration
     */
    public function __construct($listing_type = null, Array $configuration = [])
    {
        // Is the listing based on a string?
        if (is_string($listing_type)) {
            $this->_default_builder = (new $listing_type())->newQuery();
        } // Is the listing based on a model object?
        elseif (is_a($listing_type, Model::class)) {
            $this->_default_builder = $listing_type->newQuery();
        } // Is the listing based on a defined builder?
        elseif (is_a($listing_type, Builder::class)) {
            $this->_default_builder = clone $listing_type;
        } // Is the listing based on a relation?
        elseif (is_a($listing_type, Relation::class)) {
            $this->_default_builder = $listing_type->getQuery();
        }

        // Initialize the default sortFields
        $this->addConfiguration('sort', $this->_sort_fields, true);

        // Apply customized configuration on the listing
        foreach ($configuration as $key => $value) {
            $this->addConfiguration($key, $value);
        }
    }

    /**
     * Add a new configuration to the list. Can also erase previous values if necessary
     *
     * @param $configuration_key
     * @param $configuration_value
     * @param bool|false $reset_values
     * @return $this
     */
    protected function addConfiguration($configuration_key, $configuration_value, $reset_values = false)
    {
        switch ($configuration_key) {
            case 'sort':
                if ($reset_values === true) {
                    $this->_sort_fields = [];
                }
                foreach ((array)$configuration_value as $key => $value) {
                    $this->_sort_fields[!is_int($key) ? $key : (string)$value] = $value;
                }
                break;

            case 'fields':
                $this->fieldsReturned($configuration_value);
                break;
        }


        return $this;
    }

    /*
     * ------> sortBy: Apply the sortBy on the listing <-------
     */

    /**
     * Define the Initial Sort by. Different than every addSortBy
     *
     * @param $field
     * @param string $order
     * @return Listing
     */
    public function orderBy($field, $order = 'ASC')
    {
        if (!isset($this->_sort_fields[$field])) {
            $this->invalidArgumentException('Invalid order value', $field, array_keys($this->_sort_fields));
        }

        return $this->setParameter('sort', [$field, $order]);
    }

    /**
     * Add extra sort by on top of the default one
     *
     * @param $field
     * @param string $order
     * @return Listing
     */
    public function addOrderBy($field, $order = 'ASC')
    {
        if (!isset($this->_sort_fields[$field])) {
            $this->invalidArgumentException('Invalid order value', $field, array_keys($this->_sort_fields));
        }

        return $this->setParameterArray('extra_sort', [$field, $order]);
    }

    /**
     * Return the initial sort value
     *
     * @return mixed
     */
    public function getOrderBy()
    {
        $sort = $this->getParameter('sort', null);
        if (is_null($sort)) {
            if (isset($this->_sort_fields[self::DEFAULT_SORT_FIELD])) {
                $sort = [$this->_sort_fields[self::DEFAULT_SORT_FIELD], self::DEFAULT_SORT_ORDER];
            } else {
                $sort = [self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_ORDER];
            }
        }

        return $sort;
    }

    /**
     * Get all the sortBy grouped together
     *
     * @return array
     */
    public function getAllOrderBy()
    {
        $all_sort = array_merge([$this->getOrderBy()], $this->getParameter('extra_sort', []));

        return array_values($all_sort);
    }

    /**
     * Apply the OrderBy on a builder
     *
     * @param Builder $builder
     */
    protected function  applyOrderBy(Builder $builder)
    {
        $sort = $this->getAllOrderBy();
        foreach ($sort as $sort_info) {
            $builder->orderBy($this->_default_builder->getModel()->getTableColumn($sort_info[0]), $sort_info[1]);
        }
    }

    /*
     * ------> PageSize : Enable/Set the use of pagination (X elements by query) <-------
     */

    /**
     * Set OR return the number of elements by page
     *
     * @param null $size_set
     * @return mixed
     */
    public function pageSize($size_set)
    {
        if (!is_int($size_set) || $size_set <= 0) {
            $this->invalidArgumentException('PageSize has to be an integer > 0', $size_set);
        }

        return $this->setParameter('page_size', $size_set);
    }

    /**
     * Return the number of elements by page (or NULL if disabled)
     *
     * @return mixed
     */
    public function getPageSize()
    {
        return $this->getParameter('page_size', self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Apply logic of pageSize on the builder
     *
     * @param Builder $builder
     */
    protected function  applyPageSize(Builder $builder)
    {
        $page_size = $this->getPageSize();
        if (!is_null($page_size)) {
            $builder->take($page_size);
        }
    }

    /*
     * ------> FirstID : Management of the pagination based on the previous ID <-------
     */

    /**
     * Set OR return the current page number
     *
     * @param null $last_id_found
     * @return mixed
     */
    public function firstId($last_id_found)
    {
        if (!is_int($last_id_found) || $last_id_found <= 0) {
            $this->invalidArgumentException('FirstId has to be an integer > 0', $last_id_found);
        }

        $this->paginationType('id');

        return $this->setParameter('first_id', $last_id_found);
    }

    /**
     * Return the current page number
     *
     * @return mixed
     */
    public function getFirstId()
    {
        if ($this->getPaginationType() != 'id') {
            return null;
        }

        return $this->getParameter('first_id', null);
    }

    /**
     * Apply the First id limitation on the builder
     *
     * @param Builder $builder
     */
    protected function applyFirstId(Builder $builder)
    {
        $first_id = $this->getFirstId();
        if (!is_null($first_id)) {
            $direction = ($this->getOrderBy()[1] == 'DESC' ? '<' : '>');
            $builder->where($this->_default_builder->getModel()->getTableColumn('id'), $direction, $first_id);
        }
    }

    /*
     * ------> Page : Management of the page number for this listing query <-------
     */

    /**
     * Set OR return the current page number
     *
     * @param null $page_number
     * @return mixed
     */
    public function page($page_number)
    {
        if (!is_int($page_number) || $page_number <= 0) {
            $this->invalidArgumentException('Page has to be an integer > 0', $page_number);
        }

        $this->paginationType('page');

        return $this->setParameter('page', $page_number);
    }

    /**
     * Return the current page number
     *
     * @return mixed
     */
    public function getPage()
    {
        if ($this->getPaginationType() != 'page') {
            return null;
        }

        return $this->getParameter('page', 1);
    }

    /**
     * Apply the Pagination by "page" logic on the builder
     *
     * @param Builder $builder
     */
    protected function applyPage(Builder $builder)
    {
        $page_number = $this->getPage();
        $page_size = $this->getPageSize();

        if (!is_null($page_number) && !is_null($page_size)) {
            $builder->skip(($page_number - 1) * $page_size);
        }
    }

    /*
     * ------> PaginationType : How do we paginate the query? By last ID or pages <-------
     */

    /**
     * Set the type of pagination
     *
     * @param $type
     * @return Listing
     */
    public function paginationType($type)
    {
        $accepted_types = ['id', 'page'];
        if (!in_array($type, $accepted_types)) {
            $this->invalidArgumentException('Invalid pagination type', $type, $accepted_types);
        }

        return $this->setParameter('pagination_type', $type);
    }

    /**
     * Return the current page number
     *
     * @return mixed
     */
    public function getPaginationType()
    {
        return $this->getParameter('pagination_type', 'page');
    }

    /**
     * Apply the type of pagination on the builder
     *
     * @param Builder $builder
     */
    protected function applyPaginationType(Builder $builder)
    {
        switch ($this->getPaginationType()) {
            case 'id':
                $this->applyFirstId($builder);
                break;

            case 'page':
                $this->applyPage($builder);
                break;
        }
    }

    /*
     * ------> Fields returned : Which fields  <-------
     */

    /**
     * Add a new ListingField object to the correct array
     *
     * @param \Closure|null $function
     * @return $this
     */
    public function    fieldsReturned(\Closure $function = null)
    {
        $this->_fields_returned = $function;

        return $this;
    }

    /**
     * Return the list of fields returned
     *
     * @return array
     */
    public function getFieldsReturned()
    {
        return $this->_fields_returned;
    }

    /*
     * ------> Fields returned : Which fields  <-------
     */

    /**
     * Set the list of fields to return in toArray
     *
     * @param array $relations
     * @return $this
     */
    public function relations(Array $relations)
    {
        $this->_relations = array_unique(array_merge($this->_relations, $relations));

        return $this;
    }

    /**
     * Return the list of relations that will be loaded
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->_relations;
    }

    /*
     * ------> Proxy methods on the underlined builder <-------
     */

    /**
     * Return the SQL associated to this pagination
     *
     * @return mixed
     */
    public function toSql()
    {
        return $this->getBuilder()->toSql();
    }

    /**
     * Return the SQL associated to this pagination
     *
     * @return mixed
     */
    public function get()
    {
        if (is_null($this->_collection)) {
            $this->_collection = $this->getBuilder()->get();
        }

        return $this->_collection;
    }

    /**
     * Return the number of total elements in the query
     *
     * @return mixed
     * @throws \Exception
     */
    public function count()
    {
        return $this->getUnPaginatedBuilder()->count();
    }

    /*
     * ------> Methods specific to the Listing object <-------
     */

    /**
     * Return the correct filtered, sanitized data based on the query of the builder
     *
     * @return array
     */
    public function getData()
    {
        $return = [];

        // Get elements and load relations if necessary
        $elements = $this->get();
        if (count($relations = $this->getRelations()) != 0) {
            $elements->load($relations);
        }

        // For each elements we keep the correct data
        foreach ($elements as $element) {
            $return[] = $this->getDataElement($element);
            unset($element);
        }
        unset ($elements);

        return $return;
    }

    /**
     * Return info about pagination
     *
     * @return array
     */
    public function getPaginationInfo()
    {
        $return = [
            'total_elements' => $this->count(),
        ];
        switch ($this->getPaginationType()) {
            case 'id':
                $return['current_id'] = $this->get()->last()->id;
                break;

            case 'page':
                $return['pages'] = [
                    'total'   => ceil($return['total_elements'] / $this->getPageSize()),
                    'current' => $this->getPage()
                ];
                break;
        }

        return $return;
    }

    /**
     * Return the info about the objects AND the pagination in a global array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data'       => $this->getData(),
            'pagination' => $this->getPaginationInfo()
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /*
     * ------> Private methods <-------
     */

    /**
     * Add a new "parameter" to the list of options and reset the builder
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function    setParameter($key, $value)
    {
        $previous_value = $this->getParameter($key);
        if ($previous_value != $value) {
            $this->_parameters[$key] = $value;
            $this->resetCollection();
        }

        return $this;
    }

    /**
     * Some parameters should accept array of values
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function    setParameterArray($key, $value)
    {
        $previous_value = $this->getParameter($key, []);

        $value_identifier = !is_array($value) ? $value : implode('-', $value);
        if (!isset($previous_value[$value_identifier])) {
            $this->_parameters[$key][$value_identifier] = $value;
            $this->resetCollection();
        }

        return $this;
    }

    /**
     * Return the value for a parameter
     *
     * @param $key
     * @param null $default_value
     * @return mixed
     */
    private function    getParameter($key, $default_value = null)
    {
        return array_get($this->_parameters, $key, $default_value);
    }

    /**
     * Apply all the defined parameters to the builder
     *
     * @return Builder|null
     * @throws \Exception
     */
    private function    getBuilder()
    {
        if (is_null($this->_default_builder)) {
            throw new \Exception('Undefined initial builder');
        }

        $builder = $this->getUnPaginatedBuilder();
        $builder->groupBy($builder->getModel()->getTableColumn('id'));

        $this->applyPageSize($builder);
        $this->applyPaginationType($builder);

        return $builder;
    }

    /**
     * Generate a builder WITHOUT any pagination logic
     *
     * @return Builder|null
     */
    private function    getUnPaginatedBuilder()
    {
        $builder = clone $this->_default_builder;

        $this->applyOrderBy($builder);

        return $builder;
    }

    /**
     * Reset the important values of the listing object (builder and loaded collection)
     *
     * @return $this
     */
    private function    resetCollection()
    {
        $this->_collection = null;

        return $this;
    }

    /**
     * Return only the correct info about an element
     *
     * @param Model $element
     * @return array
     */
    private function    getDataElement(Model $element)
    {
        if (is_null($this->_fields_returned)) {
            return $element->toArray();
        }

        $closure_name = $this->_fields_returned;

        return $closure_name($element);
    }

    /**
     * Throw an exception based on an error message and a received value
     *
     * @param $error
     * @param $value
     * @param array|null $accepted_values
     */
    private function  invalidArgumentException($error, $value, Array $accepted_values = null)
    {
        if (is_object($value)) {
            $value = get_class($value);
        }
        throw new \InvalidArgumentException($error . '. ' . (!is_null($accepted_values) ? 'Accepted: [' . implode(', ', $accepted_values) . ']. ' : '') . 'Value "' . (string)$value . '" given.');
    }
}