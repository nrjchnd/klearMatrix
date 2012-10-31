<?php

abstract class KlearMatrix_Model_Field_Abstract
{
    /**
     * @var KlearMatrix_Model_Column
     */
    protected $_column;
    protected $_config;
    protected $_isSearchable = true;
    protected $_isSortable = true;

    protected $_propertyMaster = array(
            "required",
            "pattern",
            "placeholder",
            "nullIfEmpty",
            "maxLength",
            "expandable",
            "defaultValue" // Valor por defecto en caso de new
            );

    protected $_properties = array();

    /*valid error index */
    protected $_errorIndex = array(
        'patternMismatch',
        'rangeOverflow',
        'rangeUnderflow',
        'stepMismatch',
        'tooLong',
        'typeMismatch',
        'valueMissing'
    );
    protected $_errorMessages = array();

    protected $_js = array();
    protected $_css = array();

    /**
     * Constructor must not be directly called from outside. Use the factory method instead
     */
    private function __construct(KlearMatrix_Model_Column $column)
    {
        $this->setColumn($column);
        $this->_config = $this->_column->getKlearConfig();

        if (is_object($this->_config)) {

            foreach ($this->_propertyMaster as $property) {

                $this->_properties[$property] = $this->_config->getProperty($property);
            }

            $this->_parseErrorMessages();
        }

        $this->_initSortable();

        $this->_init();
    }

    protected function _initSortable()
    {
        $this->_sortable = is_object($this->_config)
                           && $this->_config->exists("sortable")
                           && (bool)$this->_config->getProperty('sortable');
    }

    public function setColumn($column)
    {
        $this->_column = $column;
        return $this;
    }

    public function getColumn($column)
    {
        return $this->_column;
    }

    protected function _init()
    {
        // Leave this body empty in the abstract class
    }

    protected function _parseErrorMessages()
    {
        $_errorMsgs = $this->_config->getProperty("errorMessages");

        if (!$_errorMsgs) {
            return;
        }

        $errorConfig = new Klear_Model_ConfigParser;
        $errorConfig->setConfig($_errorMsgs);

        foreach ($this->_errorIndex as $errorIndex) {
            if (isset($_errorMsgs->$errorIndex)) {
                $this->_errorMessages[$errorIndex] = $errorConfig->getProperty($errorIndex);
            }
        }
    }

    /**
     * Returns array with field's view configuration
     * @return array
     */
    public function getConfig()
    {
        return false;
    }

    public function getProperties()
    {
        if (sizeof($this->_properties) <= 0) {
            return false;
        }

        return $this->_properties;
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value, $original)
    {
        if ($this->_isNullIfEmpty()) {
            if (empty($value)) {
                return NULL;
            }
        }

        return $value;
    }

    protected function _isNullIfEmpty()
    {
        return  isset($this->_properties['nullIfEmpty']) && (bool)$this->_properties['nullIfEmpty'];
    }

    /**
     * Prepara el valor de un campo, después del getter
     * @param mixed $value Valor devuelto por el getter del model
     * @param object $model Modelo cargado
     * @return unknown
     */
    public function prepareValue($value, $model)
    {
        return $value;
    }

    /**
     * Returns paths to extra javascript to be loaded
     * @return array
     */
    public function getExtraJavascript()
    {
        return $this->_js;
    }

    /**
     * Returns paths to extra css to be loaded
     * @return array
     */
    public function getExtraCss()
    {
        return $this->_css;
    }

    public function isSearchable()
    {
        return (bool)$this->_isSearchable;
    }

    //Si existe sortable en la configuración del campo en el model de yaml, lo devuelve. Sino, devuelve true.
    public function isSortable()
    {
        return $this->_isSortable;
    }

    public function getCustomErrors()
    {
        if (sizeof($this->_errorMessages) == 0) {
            return false;
        }

        return $this->_errorMessages;
    }

    /**
     * Factory method to create any of KlearMatrix_Model_Field_Abstract subtypes
     *
     * @param string $fieldType Name of Field Type to construct
     * @param KlearMatrix_Model_Column $column
     * @return KlearMatrix_Model_Field_Abstract
     */
    public static function create($fieldType, KlearMatrix_Model_Column $column)
    {
        $fieldClassName = 'KlearMatrix_Model_Field_' . ucfirst($fieldType);
        $field = new $fieldClassName($column);
        return $field;
    }
}

//EOF