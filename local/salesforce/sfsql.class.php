<?php
ini_set("soap.wsdl_cache_enabled", "0");
require_once ('soapclient/SforcePartnerClient.php');
require_once ('soapclient/SforceHeaderOptions.php');

class sfsql{
	public $mySforceConnection = null;
	
	function connect($userName, $password, $token){
		try
		{
		    $this->mySforceConnection->setCallOptions(new CallOptions('Moodle-Sync', null));
			$loginResult = $this->mySforceConnection->login($userName, $password . $token);
		} catch(Exception $e) {
			die( '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>' );
		}
	}
	
	function __construct($wsdl, $userName, $password, $token) {
		
		if (!preg_match('~.xml$~', $wsdl))
		{
			die( '<pre dir=ltr style=text-align:left>' . "Your wsdl '$wsdl' filename doesn't end in '.xml'. Path to real XML File required. Script halted.<br>You need to donwload one from you management panel. In the side menu, under 'Develop' click on 'API'. In the screen that appears, choose 'Generate Partner WSDL'." . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>' );
		}

		if (!file_exists($wsdl))
		{
			die( '<pre dir=ltr style=text-align:left>' . "Your wsdl '$wsdl' filename cannot be found. Path to real XML File required. Script halted.<br>You need to donwload one from you management panel. In the side menu, under 'Develop' click on 'API'. In the screen that appears, choose 'Generate Partner WSDL'." . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>' );
		}

		$this->mySforceConnection = new SforcePartnerClient();
		$this->mySforceConnection->createConnection($wsdl);
		$this->connect($userName, $password, $token);
	}
	
	function get_all_fields_of_table_as_comma_separated_string($tablename){
		try
		{
			$describeSObjectResults = $this->mySforceConnection->describeSObjects(array("Course__c"));
			$fields = array_map(function($a){return $a->name;}, $describeSObjectResults[0]->fields);
			return "$tablename." . implode(", $tablename.", $fields);
		} catch(Exception $e) {
			echo '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
	}
	
	function query($query, $debug = false){
		if (preg_match('~\b\*\b~', $query))
		{
			die( '<pre dir=ltr style=text-align:left>' . "Your query '$query' contains a wildcard '*' with no table specifier. Wildcards are only allowed with table specifiers. E.g. 'Course__c.*'. Script halted." . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>' );
		}
		//The Object Name field can only contain underscores and alphanumeric characters. It must be unique, begin with a letter, not include spaces, not end with an underscore, and not contain two consecutive underscores
		preg_match_all('~\b([a-zA-Z0-9_]+?)\b\.\*~', $query, $wildcard_table_names);

		if (isset($wildcard_table_names[1]) && $wildcard_table_names[1])
		{
			foreach($wildcard_table_names[1] as $table_name)
			{
				$query = preg_replace("~\\b$table_name\.\*~", $this->get_all_fields_of_table_as_comma_separated_string($table_name), $query); 
			}
		}
		
		if ($debug)
		{
			echo '<pre dir=ltr style=text-align:left>' . print_r( $query , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
		
		try
		{
			$response = $this->mySforceConnection->query($query);
			$queryResult = new QueryResult($response);
			return new sfsql_result($queryResult);
		} catch(Exception $e) {
			echo '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
	}
	
	public function __call($name, $arguments) // will fallback to standard Salesforce API methods if not defined in this class
    {
		try
		{
			if ($return = call_user_func_array(array($this->mySforceConnection, $name), $arguments))
			{
				return $return;
			}
		} catch(Exception $e) {
			echo '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
    }
}

class sfsql_result{
	public $queryResult = null;
	
	function __construct(QueryResult $queryResult){
		try
		{
			$queryResult->rewind();
			$this->queryResult = $queryResult;
		} catch(Exception $e) {
			echo '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
	}
	
	function fetch_assoc(){
		try
		{
			if($this->queryResult->pointer < $this->queryResult->size) {
				$current = $this->queryResult->current();
				$this->queryResult->next();
				return (isset($current->Id) ? array('Id' => $current->Id) : array()) + (array) $current->fields;
			}
			
			return false;
		} catch(Exception $e) {
			echo '<pre dir=ltr style=text-align:left>' . print_r( $e->getMessage() , 1) . '<br>File: ' . __FILE__ . ' Line: ' . __LINE__ . '</pre>';
		}
	}
	
	function fetch_row(){
		$row = $this->fetch_assoc();
		
		if ($row)
		{
			return array_values($row);
		}
			
		return false;
	}
	
	function fetch_object(){
		$row = $this->fetch_assoc();
		
		if ($row)
		{
			return (object)$row;
		}
		
		return false;
	}
}
