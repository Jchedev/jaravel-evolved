<?php

namespace Jchedev\Classes\Listings;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Jchedev\Classes\Transformers\Transformer;

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
     * @var \Closure Callback applied to the collection once retrieved
     */
    private $_on_collection;

    /**
     * @var \Closure Callback applied to the array of data returned
     */
    private $_on_item;

    /**
     * @var array Collect parameters applied to the builder during the life of the listing
     */
    private $_builder_parameters = [];

    /**
     * @var array Configuration of the listing (list of sorting acceptable values)
     */
    protected $_sort_fields = ['id', 'created_at', 'updated_at'];

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
        // Based on the listing_type variable, we generate the correct _default_builder
        if (!is_null($listing_type)) {
            $this->_default_builder = $this->convertAsBuilder($listing_type);
        }

        // Apply customized configuration on the listing
        foreach ($configuration as $key => $value) {
            $this->addConfiguration($key, $value);
        }
    }

    /**
     * Convert a variable into the correct builder based on the variable type, etc...
     *
     * @param $listing_type
     * @return mixed
     */
    protected function  convertAsBuilder($listing_type)
    {
        if (is_string($listing_type)) {
            /*
             * Type: String
             * We consider the string as the name of the Model used and get the builder for it
             */
            return (new $listing_type())->newQuery();

        } elseif (is_a($listing_type, Model::class)) {
            /*
             * Type: \Eloquent\Model
             * We get the builder associated
             */
            return $listing_type->newQuery();

        } elseif (is_a($listing_type, Builder::class)) {
            /*
             * Type: \Eloquent\Builder
             * It is already a builder, so we just clone it
             */
            return clone $listing_type;

        } elseif (is_a($listing_type, Relation::class)) {
            /*
             * Type: \Eloquent\Relations
             * The listing is a relation, so we transform it as a builder
             */
            return $listing_type->getQuery();
        }
    }


    /**
     * Add a new configuration to the list. Can also erase previous values if necessary
     *
     * @param $configuration_key
     * @param $configuration_value
     * @return $this
     */
    protected function addConfiguration($configuration_key, $configuration_value)
    {
        switch ($configuration_key) {

            // Add configuration about which sort fields are accepted
            case 'sort':
                foreach ((array)$configuration_value as $key => $value) {
                    $this->sortField($value, $key);
                }
                break;

            // Define the orderBy used in the query
            case 'order':
                $this->sortField($configuration_value);
                $this->orderBy($configuration_value);
                break;

            // Add configuration about which fields are returned for the call
            case 'fields':
                $this->fieldsReturned($configuration_value);
                break;

            // Define the onCollection method from the beginning
            case 'onCollection':
                $this->onCollection($configuration_value);
                break;

            // Define the onReturn method from the beginning
            case 'onItem':
                $this->onItem($configuration_value);
                break;

            // Overwrite the number of elements to return
            case 'limit':
                $this->limit($configuration_value);
                break;
        }

        return $this;
    }

    /*
     * ------> sortFields: Which fields can be used to sort <-------
     */

    /**
     * Add a new sort fields
     *
     * @param $field
     * @param null $as
     * @return $this
     */
    public function sortField($field, $as = null)
    {
        if (is_string($field)) {
            if (is_null($as)) {
                $this->_sort_fields[] = $field;
            } else {
                $this->_sort_fields[$as] = $field;
            }
        }

        return $this;
    }

    /**
     * Return the sort fields correctly grouped key/value
     *
     * @return array
     */
    public function getSortFields()
    {
        $fields = [];
        foreach ($this->_sort_fields as $key => $field) {
            if (is_int($key)) {
                $key = $field;
            }
            $fields[$key] = $field;
        }

        return $fields;
    }

    /*
     * ------> orderBy: Apply the sortBy on the listing <-------
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
        $allowed_columns = array_keys($this->getSortFields());

        if (!in_array($field, $allowed_columns) && !is_a($field, \Illuminate\Database\Query\Expression::class)) {
            $this->invalidArgumentException('Invalid order value', $field, $allowed_columns);
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
        $allowed_columns = array_keys($this->getSortFields());

        if (!in_array($field, $allowed_columns)) {
            $this->invalidArgumentException('Invalid order value', $field, $allowed_columns);
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
            $sort_fields = $this->getSortFields();

            if (isset($sort_fields[self::DEFAULT_SORT_FIELD])) {
                $sort = [$sort_fields[self::DEFAULT_SORT_FIELD], self::DEFAULT_SORT_ORDER];
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
            if (method_exists($this->_default_builder->getModel(), 'getTableColumn')) {
                $sort_info[0] = $this->_default_builder->getModel()->getTableColumn($sort_info[0]);
            }

            $builder->orderBy($sort_info[0], $sort_info[1]);
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
    public function limit($size_set = null)
    {
        if (!is_null($size_set) && (!is_int($size_set) || $size_set <= 0)) {
            $this->invalidArgumentException('Limit has to be an integer > 0', $size_set);
        }

        return $this->setParameter('limit', $size_set);
    }

    /**
     * Return the number of elements by page (or NULL if disabled)
     *
     * @return mixed
     */
    public function getLimit()
    {
        return $this->getParameter('limit', self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Apply logic of pageSize on the builder
     *
     * @param Builder $builder
     */
    protected function  applyLimit(Builder $builder)
    {
        $page_size = $this->getLimit();
        if (!is_null($page_size)) {
            $builder->take($page_size);
        }
    }

    /*
     * ------> PageSize : Enable/Set the use of pagination (X elements by query) <-------
     */

    /**
     * Set OR return the number of elements by page
     *
     * @param $offset
     * @return mixed
     */
    public function offset($offset)
    {
        if (!is_int($offset) || $offset < 0) {
            $this->invalidArgumentException('Offset has to be an integer >= 0', $offset);
        }

        return $this->setParameter('offset', $offset);
    }

    /**
     * Return the number of elements by page (or NULL if disabled)
     *
     * @return mixed
     */
    public function getOffset()
    {
        return $this->getParameter('offset', 0);
    }

    /**
     * Apply logic of pageSize on the builder
     *
     * @param Builder $builder
     */
    protected function  applyOffset(Builder $builder)
    {
        $builder->skip($this->getOffset());
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
    public function    fieldsReturned($function = null)
    {
        if (is_a($function, Transformer::class)) {
            $function = $function->getClosure();
        }

        if (is_callable($function) === false) {
            $this->invalidArgumentException('Input needs to be a Closure or a Transformer object', $function);
        }

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
    * ------> onCollection : Closure applied on the collection  <-------
    */

    /**
     * Define the closure (or null) that will be applied to the collection
     *
     * @param \Closure|null $closure
     * @return $this
     */
    public function onCollection(\Closure $closure = null)
    {
        $this->_on_collection = $closure;

        return $this;
    }

    /**
     * Return the closure to apply
     *
     * @return \Closure
     */
    public function getOnCollectionClosure()
    {
        return $this->_on_collection;
    }

    /*
     * ------> onReturn : Closure applied on the collection  <-------
     */

    /**
     * Define the closure (or null) that will be applied to the collection
     *
     * @param \Closure|null $closure
     * @return $this
     */
    public function onItem(\Closure $closure = null)
    {
        $this->_on_item = $closure;

        return $this;
    }

    /**
     * Return the closure to apply
     *
     * @return \Closure
     */
    public function getOnItemClosure()
    {
        return $this->_on_item;
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
            if (!is_null($closure = $this->getOnCollectionClosure())) {
                $closure($this->_collection);
            }
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
        return $this->getBuilder()->skip(null)->count();
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

        // For each elements we keep the correct data
        foreach ($elements as $element) {
            $element_data = $this->getDataItem($element);
            if (is_callable($closure = $this->getOnItemClosure())) {
                $element_data = $closure($element_data, $element);
            }
            $return[] = $element_data;

            unset($element, $element_data);
        }
        unset ($elements);

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
            'collection' => $this->getData(),
            'total'      => $this->count(),
            'end'        => $this->isAtTheEnd()
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

    /**
     * Test if this request is the last to do
     *
     * @return bool|null
     */
    public function isAtTheEnd()
    {
        if (is_null($this->_collection)) {
            return null;
        }

        $size = $this->getLimit();

        return is_null($size) || (count($this->_collection) < $size);
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
            $this->_builder_parameters[$key] = $value;
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
            $this->_builder_parameters[$key][$value_identifier] = $value;
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
        return array_get($this->_builder_parameters, $key, $default_value);
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

        $builder = clone $this->_default_builder;

        $this->applyOrderBy($builder);
        $this->applyLimit($builder);
        $this->applyOffset($builder);

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
    private function    getDataItem(Model $element)
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