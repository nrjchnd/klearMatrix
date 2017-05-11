<?php
class KlearMatrix_Model_ModelSpecification
{

    protected $_config;
    /** @deprecated **/
    protected $_class;

    protected $_entity;

    protected $_pk;
    protected $_instance;

    public function __construct(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        /** @deprecated **/
        $this->_class = $this->_config->getRequiredProperty("class");

        /** @todo replace with getRequiredProperty **/
        $this->_entity = $this->_config->getProperty("entity");
        $this->_instance = new $this->_class;
    }

    public function getInstance()
    {
        if ($this->_instance->getPrimaryKey() != $this->_pk) {

            $this->_instance = $this->_instance->getMapper()->find($this->_pk);
        }

        return $this->_instance;
    }

    /** @deprecated **/
    public function getClassName()
    {
        return $this->_class;
    }

    public function getEntityName()
    {
        return $this->_entity;
    }

    public function getField($fName)
    {
        if ($this->_config->exists("fields->" . $fName)) {
            return $this->_config->getRaw()->fields->{$fName};
        }
        return false;
    }

    public function getFields()
    {
        $fields = array();
        $aFields = $this->_config->getRaw()->fields;
        if (is_array($aFields) || $aFields instanceof Zend_Config) {
            foreach ($aFields as $key => $field) {
                $fields[$key] = $field;
            }
        }
        return $fields;
    }

    public function setPrimaryKey($pk)
    {
        $this->_pk = $pk;
        return $this;
    }
}
