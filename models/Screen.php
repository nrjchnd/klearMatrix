<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Screen {

	const module = 'klearMatrix';

	protected $_screen;
	protected $_config;

	protected $_mapper;
	protected $_modelFile;
	protected $_routeDispatcher;
	
	protected $_modelSpec;
	
	protected $_visibleColumnWrapper;
	
	public function setConfig(Zend_Config $config) {
		
		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);
		
		$this->_mapper = $this->_config->getProperty("mapper",true);
		$this->_modelFile = $this->_config->getProperty("modelFile",true);
		
		$this->_parseModelFile();
		
		$this->_checkClasses(array("_mapper"));
				
	}

	protected function _parseModelFile() {
		$filePath = $this->_routeDispatcher->getConfig()->getConfigPath() . 'model/' . $this->_modelFile;
		
		$modelConfig = new Zend_Config_Yaml(
				$filePath,
				APPLICATION_ENV
		);
		
		$this->_modelSpec = new KlearMatrix_Model_ModelSpecification;
		$this->_modelSpec->setConfig($modelConfig);
		
		
	}
	
	protected function _checkClasses(array $properties) {
		
		foreach ($properties as $property) {
			if (!class_exists($this->{$property})) {
				Throw new Zend_Exception( $property . " no es una entidad instanciable.");
			} 
			
		}
		
	}
	
	public function setScreenName($name) {
		
		$this->_screenName = $name;
	}
	
	public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher) {
		$this->_routeDispatcher = $routeDispatcher;
	}
	
	
	public function getMapperName() {
		return $this->_mapper;
	}

	
	public function getModelName() {
		return $this->_modelSpec->getClassName();
	}
	
	
	
	/**
	 * El método filtrará las columnas del modelo con el fichero de configuración de modelo y la whitelist/blacklist de la configuración
	 * 
	 * @param KlearMatrix_Model_ColumnWrapper $_visibleColumnWrapper listado de columnas que devuelve el modelo
	 */
	public function getVisibleColumnWrapper() {
		if (isset($this->_visibleColumnWrapper)) return $this->_visibleColumnWrapper;
		
		$obj = $this->_modelSpec->getInstance();
		//$obj = new EKT_Model_Brands();
		
		$this->_visibleColumnWrapper =  new KlearMatrix_Model_ColumnWrapper;
		
		//Inicializamos blackList (los campos que no se mostrarán)
		$blacklist = array();
		
		$pk = $obj->getPrimaryKeyName();
		
		// La primary Key estará por defecto en la blackList, a excepción de encontrarse en la whitelist
		if ( ($this->_config->exists("fields->whitelist")) && ($this->_config->getRaw()->fields->whitelist->{$pk}) ) {
			// La Pk se mostrará si está en la whitelist
			// Something to do?
			
		} else {
			$blacklist[$pk] = true;
		}
		
		// Le pregunto al fichero de configuración por los campos en la blackList - no deben salir - 
		if ($this->_config->exists("fields->blacklist")) {
			if (($_blacklistConfig = $this->_config->getRaw()->fields->blacklist) !== '') {

				foreach($_blacklistConfig as $field => $value) {
					if ((bool)$value) {
						$blacklist[$field] = true;
					}
				}			
			}
		}
		
		 
		foreach($obj->getColumnsList() as $dbName => $attribute) {

			if (isset($blacklist[$dbName])) continue;
			
			$col = new KlearMatrix_Model_Column;
			
			$col->setDbName($dbName);
			
			if ($colConfig = $this->_modelSpec->getField($dbName)) {			
				$col->setConfig($colConfig);
			}
			
			$this->_visibleColumnWrapper->addCol($col);
		}
		
		if ($this->hasFieldOptions()) {
			
			$col = new KlearMatrix_Model_Column;
			$col->markAsOption();
			$col->setdbName("_fieldOptions");
			$col->setConfig($this->_config->getRaw()->fields->options);
			$this->_visibleColumnWrapper->addCol($col);
		}
		
		return $this->_visibleColumnWrapper;
		
	}

	
	public function hasFieldOptions() {
		return ($this->_config->exists("fields->options"));
	}
	
	/**
	 * gateway hacia modelo específico, para devolver el nombre de la PK 
	 */
	public function getPK() {
		return $this->_modelSpec->getInstance()->getPrimaryKeyName();
	}
	
	/**
	 * gateway hacia modelo específico, para devolver la instancia del objeto "vacío"
	 */
	public function getObjectInstance() {
		return $this->_modelSpec->getInstance();
	}
	
	
}