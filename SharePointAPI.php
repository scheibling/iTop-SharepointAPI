<?php
namespace SPAPI;
class API {
	
	private $returnType = 0; //Return type (default: 0) 0=Array,1=Object
	private $lower_case_indexs = TRUE; //Returns index as lowercase
	private $MAX_ROWS = 10000; //Max rows to return
	private $soapClient = NULL; //Create placeholder for soapClient
	protected $soap_trace = TRUE; //If requests should be traceable
	protected $soap_exceptions = TRUE; // Whether SOAP errors throw exception of type SoapFault
	protected $soap_keep_alive = FALSE; //HTTP Keep Alive Setting
	protected $soap_version = SOAP_1_1; //SOAP Version Number
	protected $soap_compression = 0; //Compression Example: SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
	protected $soap_cache_wsdl = WSDL_CACHE_NONE; //Cache behaviour for WSDL content (default WSDL_CACHE_NONE)
	protected $internal_encoding = 'UTF-8'; //Internal (not SOAP) encoding

	/**
	 * Constructor
	 *
	 * @param string $spUsername User account to authenticate with. (Must have read/write/edit permissions to given Lists)
	 * @param string $spPassword Password to use with authenticating account.
	 * @param string $spWsdl WSDL file for this set of lists ( sharepoint.url/subsite/_vti_bin/Lists.asmx?WSDL )
	 * @param string $mode Authenticaton method to use (Defaults to basic auth, also supports SPONLINE & NTLM)
	 * @param array $options Options for SoapClient
	 */
	public function __construct ($options = array()) {
		assert(class_exists('SoapClient'));
		require_once("passwords.php");
		$defaultOptions = array(
			'trace'        => $this->soap_trace,
			'exceptions'   => $this->soap_exceptions,
			'keep_alive'   => $this->soap_keep_alive,
			'soap_version' => $this->soap_version,
			'cache_wsdl'   => $this->soap_cache_wsdl,
			'compression'  => $this->soap_compression,
			'encoding'     => $this->internal_encoding,
		);
		$options = array_merge($defaultOptions, $options); // $options will overwrite defaults if provided
		
		if (!empty($this->spUsername)) {
			$options['login']    = $this->spUsername;
			$options['password'] = $this->spPassword;
		}
		
		try {
					if ((isset($options['login']))) {
						$this->soapClient = new \SPAPI\SharePointOnlineAuth($this->spWsdl, $options);
					} 
				} catch (\SoapFault $fault) {
					// If we are unable to create a Soap Client display a Fatal error.
					throw new \Exception('Unable to locate WSDL file. faultcode=' . $fault->getCode() . ',faultstring=' . $fault->getMessage());
				}
	}

public final function __call ($methodName, array $methodParams) {
		/*
		 * Is soapClient set? This check may look double here but in later
		 * developments it might help to trace bugs better and it avoids calls
		 * on wrong classes if $soapClient got set to something not SoapClient.
		 */
		if (!$this->soapClient instanceof \SoapClient) {
			// Is not set
			throw new \Exception('Variable soapClient is not a SoapClient class, have: ' . gettype($this->soapClient), 0xFF);
		}

		// Is it a "SOAP callback"?
		if (substr($methodName, 0, 2) == '__') {
			// Is SoapClient's method
			$returned = call_user_func_array(array($this->soapClient, $methodName), $methodParams);
		} else {
			// Call it
			$returned = $this->soapClient->__call($methodName, $methodParams);
		}

		// Return any values
		return $returned;
	}
	
public function getLimitedLists (array $keys, array $params = array('hidden' => 'False'), $isSensetive = TRUE) {
		// Get the full list back
		$lists = $this->getLists();

		// Init new list and look for all matching entries
		$newLists = array();
		foreach ($lists as $entry) {
			// Default is found
			$isFound = TRUE;

			// Search for all criteria
			foreach ($params as $key => $value) {
				// Is it found?
				if ((isset($entry[$key])) && ((($isSensetive === TRUE) && ($value != $entry[$key])) || (strtolower($value) != strtolower($entry[$key])))) {
					// Is not found
					$isFound = FALSE;
					break;
				}
			}

			// Add it?
			if ($isFound === TRUE) {
				// Generate new entry array
				$newEntry = array();
				foreach ($keys as $key) {
					// Add this key
					$newEntry[$key] = $entry[$key];
				}

				// Add this new array
				$newLists[] = $newEntry;
				unset($newEntry);
			}
		}

		// Return finished array
		return $newLists;
	}
	
public function getLists () {
		// Query Sharepoint for full listing of it's lists.
		$rawXml = '';
		try {
			$rawXml = $this->soapClient->GetListCollection()->GetListCollectionResult->any;
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Load XML in to DOM document and grab all list items.
		$nodes = $this->getArrayFromElementsByTagName($rawXml, 'List');

		// Format data in to array or object
		foreach ($nodes as $counter => $node) {
			foreach ($node->attributes as $attribute => $value) {
				$idx = ($this->lower_case_indexs) ? strtolower($attribute) : $attribute;
				$results[$counter][$idx] = $node->getAttribute($attribute);
			}

			// Make object if needed
			if ($this->returnType === 1) {
				settype($results[$counter], 'object');
			}
		}

		// Add error array if stuff goes wrong.
		if (!isset($results)) {
			$results = array('warning' => 'No data returned.');
		}

		return $results;
	}
	
public function readListMeta ($list_name, $hideInternal = TRUE, $ignoreHiddenAttribute = FALSE) {
		// Ready XML
		$CAML = '
			<GetList xmlns="http://schemas.microsoft.com/sharepoint/soap/">
				<listName>' . $list_name . '</listName>
			</GetList>
		';

		// Attempt to query Sharepoint
		$rawXml = '';
		try {
			$rawXml = $this->soapClient->GetList(new \SoapVar($CAML, XSD_ANYXML))->GetListResult->any;
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Load XML in to DOM document and grab all Fields
		$nodes = $this->getArrayFromElementsByTagName($rawXml, 'Field');

		// Format data in to array or object
		foreach ($nodes as $counter => $node) {
			// Attempt to hide none useful feilds (disable by setting second param to FALSE)
			if ($hideInternal && ($node->getAttribute('Type') == 'Lookup' || $node->getAttribute('Type') == 'Computed' || ($node->getAttribute('Hidden') == 'TRUE' && $ignoreHiddenAttribute === FALSE))) {
				continue;
			}

			// Get Attributes
			foreach ($node->attributes as $attribute => $value) {
				$idx = ($this->lower_case_indexs) ? strtolower($attribute) : $attribute;
				$results[$counter][$idx] = $node->getAttribute($attribute);
			}

			// Make object if needed
			if ($this->returnType === 1) {
				settype($results[$counter], 'object');
			}

			// If hiding internal is enabled and 'id' is not set, remove this element
			if ($hideInternal && !isset($results[$counter]['id'])) {
				// Then it has to be an "internal"
				unset($results[$counter]);
			}
		}
}

public function readFolderContents ($list_name, $folder_id){
		$xml_options = '';
		$xml_query   = '';
		$fields_xml = '';
		
		$CAML = '
			<GetListItems xmlns="http://schemas.microsoft.com/sharepoint/soap/">
				<listName>' . $list_name . '</listName>
				<rowLimit>' . $limit . '</rowLimit>
				' . $xml_options . '
				<queryOptions xmlns:SOAPSDK9="http://schemas.microsoft.com/sharepoint/soap/" >
					<QueryOptions>
						' . $options . '
					</QueryOptions>
				</queryOptions>
			</GetListItems>';
			
		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);
		$result = NULL;
		
		try {
			$result = $this->xmlHandler($this->soapClient->GetListItems($xmlvar)->GetListItemsResult->any);
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}
		
		return $result;
		
}
public function read ($list_name, $limit = NULL, $query = NULL, $view = NULL, $sort = NULL, $options = NULL) {
		// Check limit is set
		if ($limit < 1 || is_null($limit)) {
			$limit = $this->MAX_ROWS;
		}

		// Create Query XML is query is being used
		$xml_options = '';
		$xml_query   = '';
		$fields_xml = '';

		// Setup Options
		if ($query instanceof Service\QueryObjectService) {
			$xml_query = $query->getCAML();
			$xml_options = $query->getOptionCAML();
		} else {

			if (!is_null($query)) {
				$xml_query .= $this->whereXML($query); // Build Query
			}
			if (!is_null($sort)) {
				$xml_query .= $this->sortXML($sort);// add sort
			}

			// Add view or fields
			if (!is_null($view)){
				// array, fields have been specified
				if(is_array($view)){
					$xml_options .= $this->viewFieldsXML($view);
				}else{
					$xml_options .= '<viewName>' . $view . '</viewName>';
				}
			}
		}

		// If query is required
		if (!empty($xml_query)) {
			$xml_options .= '<query><Query>' . $xml_query . '</Query></query>';
		}

		/*
		 * Setup basic XML for querying a SharePoint list.
		 * If rowLimit is not provided SharePoint will default to a limit of 100 items.
		 */
		$CAML = '
			<GetListItems xmlns="http://schemas.microsoft.com/sharepoint/soap/">
				<listName>' . $list_name . '</listName>
				<rowLimit>' . $limit . '</rowLimit>
				' . $xml_options . '
				<queryOptions xmlns:SOAPSDK9="http://schemas.microsoft.com/sharepoint/soap/" >
					<QueryOptions>
						' . $options . '
					</QueryOptions>
				</queryOptions>
			</GetListItems>';

		// Ready XML
		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);
		$result = NULL;

		// Attempt to query SharePoint
		try {
			$result = $this->xmlHandler($this->soapClient->GetListItems($xmlvar)->GetListItemsResult->any);
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Return a XML as nice clean Array
		return $result;
	}
	
public function readFromFolder($listName, $folderName, $isLibrary = false, $limit = 100, $query = NULL, $view = NULL, $sort = NULL) {
		return $this->read($listName, $limit, $query, $view, $sort, "<Folder>" . ($isLibrary ? '' : 'Lists/') . $listName . '/' . $folderName . "</Folder>" );
	}

public function write ($list_name, array $data) {
		return $this->writeMultiple($list_name, array($data));
	}
	
	
public function writeToFolder ($list_name, $folderPath, array $data) {
		return $this->writeMultipleToFolder($list_name, $folderPath, array($data));
	}
	

	
public function writeMultiple ($list_name, array $items) {
		return $this->modifyList($list_name, $items, 'New');
	}
	
public function writeMultipleToFolder ($list_name, $folderPath, array $items) {
		return $this->modifyList($list_name, $items, 'New', $folderPath);
	}



public function update ($list_name, $ID, array $data) {
		// Add ID to item
		$data['ID'] = $ID;
		return $this->updateMultiple($list_name, array($data));
	}
	
public function updateMultiple ($list_name, array $items) {
		return $this->modifyList($list_name, $items, 'Update');
	}

public function delete ($list_name, $ID, array $data = array()) {
		return $this->deleteMultiple($list_name, array($ID), array($ID => $data));
	}
	
public function deleteMultiple ($list_name, array $IDs, array $data = array()) {
		/*
		 * change input "array(ID1, ID2, ID3)" to "array(array('id' => ID1),
		 * array('id' => ID2), array('id' => ID3))" in order to be compatible
		 * with modifyList.
		 *
		 * For each ID also check if we have any additional data. If so then
		 * add it to the delete data.
		 */
		$deletes = array();
		foreach ($IDs as $ID) {
			$delete = array('ID' => $ID);
			// Add additional data if available
			if (!empty($data[$ID])) {
				foreach ($data[$ID] as $key => $value) {
					$delete[$key] = $value;
				}
			}
			$deletes[] = $delete;
		}

		// Return a XML as nice clean Array
		return $this->modifyList($list_name, $deletes, 'Delete');
	}
	
public function addAttachment ($list_name, $list_item_id, $file_name) {
		// base64 encode file
		$attachment = base64_encode(file_get_contents($file_name));

		// Wrap in CAML
		$CAML = '
		<AddAttachment xmlns="http://schemas.microsoft.com/sharepoint/soap/">
			<listName>' . $list_name . '</listName>
			<listItemID>' . $list_item_id . '</listItemID>
			<fileName>' . $file_name . '</fileName>
			<attachment>' . $attachment . '</attachment>
		</AddAttachment>';

		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);

		// Attempt to run operation
		try {
			$this->soapClient->AddAttachment($xmlvar);
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Return true on success
		return true;
	}

public function deleteAttachment ($list_name, $list_item_id, $url) {
		// Wrap in CAML
		$CAML = '
		<DeleteAttachment xmlns="http://schemas.microsoft.com/sharepoint/soap/">
			<listName>' . $list_name . '</listName>
			<listItemID>' . $list_item_id . '</listItemID>
			<url>' . $url . '</url>
		</DeleteAttachment>';

		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);

		// Attempt to run operation
		try {
			$this->soapClient->DeleteAttachment($xmlvar);
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Return true on success
		return true;
	}

public function getAttachments ($list_name, $list_item_id) {
		// Wrap in CAML
		$CAML = '
		<GetAttachmentCollection xmlns="http://schemas.microsoft.com/sharepoint/soap/">
			<listName>' . $list_name . '</listName>
			<listItemID>' . $list_item_id . '</listItemID>
		</GetAttachmentCollection>';

		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);

		// Attempt to run operation
		try {
			$rawXml = $this->soapClient->GetAttachmentCollection($xmlvar)->GetAttachmentCollectionResult->any;
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Load XML in to DOM document and grab all list items.
		$nodes = $this->getArrayFromElementsByTagName($rawXml, 'Attachment');

		$attachments = array();

		// Format data in to array or object
		foreach ($nodes as $counter => $node) {
			$attachments[] = $node->textContent;
		}

		// Return Array of attachment URLs
		return $attachments;
	}

public function setReturnType ($type) {
		if (trim(strtolower($type)) == 'object') {
			$this->returnType = 1;
		} else {
			$this->returnType = 0;
		}
	}

public function lowercaseIndexs ($enable) {
		$this->lower_case_indexs = ($enable === TRUE);
	}

public function query ($table) {
		return new \SPAPI\Service\QueryObjectService($table, $this);
	}
public function CRUD ($list_name) {
		return new \SPAPI\Service\ListService($list_name, $this);
	}
	
private function getArrayFromElementsByTagName ($rawXml, $tag, $namespace = NULL) {
		// Get DOM instance and load XML
		$dom = new \DOMDocument();

		$dom->loadXML($rawXml, (LIBXML_VERSION >= 20900) ? LIBXML_PARSEHUGE : null);

		// Is namespace set?
		if (!is_null($namespace)) {
			// Use it
			$nodes = $dom->getElementsByTagNameNS($tag, $namespace);
		} else {
			// Get nodes
			$nodes = $dom->getElementsByTagName($tag);
		}

		// Return nodes list
		return $nodes;
	}

private function xmlHandler ($rawXml) { //Converts output to readable material (array or object)
		// Use DOMDocument to proccess XML
		$results = $this->getArrayFromElementsByTagName($rawXml, '#RowsetSchema', '*');
		$resultArray = array();

		// Proccess Object and return a nice clean associative array of the results
		foreach ($results as $i => $result) {
			$resultArray[$i] = array();
			foreach ($result->attributes as $attribute => $value) {
				$idx = ($this->lower_case_indexs) ? strtolower($attribute) : $attribute;
				//  Re-assign all the attributes into an easy to access array
				$resultArray[$i][str_replace('ows_', '', $idx)] = $result->getAttribute($attribute);
			}

			/*
			 * ReturnType 1 = Object.
			 * If set, change array in to an object.
			 *
			 * Feature based on implementation by dcarbone  (See: https://github.com/dcarbone/ )
			 */
			if ($this->returnType === 1) {
				settype($resultArray[$i], 'object');
			}
		}

		// Check some values were actually returned
		if (count($resultArray) == 0) {
			$resultArray = array(
				'warning' => 'No data returned.',
				'raw_xml' => $rawXml
			);
		}

		return $resultArray;
	}

private function whereXML (array $q) { //Generates XML for WHERE query
		$queryString = '';
		$counter = 0;

		foreach ($q as $col => $value) {
			$counter++;
			$queryString .= '<Eq><FieldRef Name="' . $col . '" /><Value Type="Text">' . htmlspecialchars($value) . '</Value></Eq>';

			// Add additional "and"s if there are multiple query levels needed.
			if ($counter >= 2) {
				$queryString = '<And>' . $queryString . '</And>';
			}
		}

		return '<Where>' . $queryString . '</Where>';
	}

public function getSortFromValue ($value) {
		// Make all lower-case
		$value = strtolower($value);

		// Default is descending
		$sort = 'FALSE';

		// Is value set to allow ascending sorting?
		if ($value == 'asc' || $value == 'true' || $value == 'ascending') {
			// Sort ascending
			$sort = 'TRUE';
		}

		// Return it
		return $sort;
	}

private function sortXML (array $sort) { // Get XML for sort
		// On no count, no need to sort
		if (count($sort) == 0) {
			return '';
		}

		$queryString = '';
		foreach ($sort as $col => $value) {
			$queryString .= '<FieldRef Name="' . $col . '" Ascending="' . $this->getSortFromValue($value) . '" />';
		}
		return '<OrderBy>' . $queryString . '</OrderBy>';
	}	
	
public function viewFieldsXML(array $fields){
		$xml = '';
		// Convert fields to array
		foreach($fields as $field){
			$xml .= '<FieldRef Name="'.$field.'" />';
		} 
		// wrap tags
		return  '<viewFields><ViewFields>'.$xml.'</ViewFields></viewFields>';  
	}
	
public function modifyList ($list_name, array $items, $method, $folderPath = null) {
		// Get batch XML
		$commands = $this->prepBatch($items, $method);

                $rootFolderAttr = '';
                if($folderPath != null && $folderPath != '/') {
                    $sitePath = substr($this->spWsdl, 0, strpos($this->spWsdl, '_vti_bin'));
                    $rootFolderAttr = ' RootFolder="'.$sitePath.$list_name.'/'.$folderPath.'"';
                }

		// Wrap in CAML
		$CAML = '
		<UpdateListItems xmlns="http://schemas.microsoft.com/sharepoint/soap/">
			<listName>' . $list_name . '</listName>
			<updates>
				<Batch ListVersion="1" OnError="Continue"'.$rootFolderAttr.'>
					' . $commands . '
				</Batch>
			</updates>
		</UpdateListItems>';

		$xmlvar = new \SoapVar($CAML, XSD_ANYXML);
		$result = NULL;

		// Attempt to run operation
		try {
			$result = $this->xmlHandler($this->soapClient->UpdateListItems($xmlvar)->UpdateListItemsResult->any);
		} catch (\SoapFault $fault) {
			$this->onError($fault);
		}

		// Return a XML as nice clean Array
		return $result;
	}

public function prepBatch (array $items, $method) {
		// Check if method is supported
		assert(in_array($method, array('New', 'Update', 'Delete')));

		// Get var's needed
		$batch = '';
		$counter = 1;

		// Foreach item to be converted in to a SharePoint Soap Command
		foreach ($items as $data) {
			// Wrap item in command for given method
			$batch .= '<Method Cmd="' . $method . '" ID="' . $counter . '">';

			// Add required attributes
			foreach ($data as $itm => $val) {
				// Add entry
				$batch .= '<Field Name="' . $itm . '">' . htmlspecialchars($val) . '</Field>' . PHP_EOL;
			}

			$batch .= '</Method>';

			// Inc counter
			$counter++;
		}

		// Return XML data.
		return $batch;
	}

private function onError (\SoapFault $fault) {
		$more = '';
		if (isset($fault->detail->errorstring)) {
			$more = 'Detailed: ' . $fault->detail->errorstring;
		}
		
		throw new \Exception('Error (' . $fault->faultcode . ') ' . $fault->faultstring . ',more=' . $more);
	}

public function magicLookup ($name, $list) {
		//Perform lookup for specified item on specified list
		$find = $this->read($list, null, array('Title' => $name));
		//If we get a result (and there is only one of them) return it in "Lookup" format
		if (isset($find[0]) && count($find) === 1) {
			settype($find[0], 'array');//Set type to array in case API is in object mode.
			if ($this->lower_case_indexs) {
				return static::lookup($find[0]['id'], $find[0]['title']);
			} else {
				return static::lookup($find[0]['ID'], $find[0]['Title']);
			}
		} else {
			//If we didnt find anything / got to many, throw exception
			throw new \Exception('Unable to perform automated lookup for value in ' . $list . '.');
		}
	}

public static function dateTime ($date, $timestamp = FALSE) {
		return ($timestamp) ? date('c',$date) : date('c', strtotime($date));
	}

public static function lookup ($id, $title = '') {
		return $id . (($title !== '') ? ';#' . $title : '');
	}

public function getFieldVersions ($list, $id, $field) {
	    //Ready XML
	    $CAML = '
	        <GetVersionCollection xmlns="http://schemas.microsoft.com/sharepoint/soap/">
	            <strlistID>'.$list.'</strlistID>
	            <strlistItemID>'.$id.'</strlistItemID>
	            <strFieldName>'.$field.'</strFieldName>
	        </GetVersionCollection>
	    ';

	    // Attempt to query SharePoint
	    try{
	        $rawxml = $this->soapClient->GetVersionCollection(new \SoapVar($CAML, XSD_ANYXML))->GetVersionCollectionResult->any;
	    }catch(\SoapFault $fault){
	        $this->onError($fault);
	    }

	    // Load XML in to DOM document and grab all Fields
        $dom = new \DOMDocument();
        $dom->loadXML($rawxml, (LIBXML_VERSION >= 20900) ? LIBXML_PARSEHUGE : null);
        $nodes = $dom->getElementsByTagName("Version");

        // Parse results
        $results = array();
        // Format data in to array or object
        foreach ($nodes as $counter => $node) {
            //Get Attributes
            foreach ($node->attributes as $attribute => $value) {
                $results[$counter][strtolower($attribute)] = $node->getAttribute($attribute);
            }
            //Make object if needed
            if ($this->returnType === 1) settype($results[$counter], "object");
        }
        // Add error array if stuff goes wrong.
        if (!isset($results)) $results = array('warning' => 'No data returned.');

	    return $results;
	}

	
// Alias (Identical to above)	
public function addMultiple ($list_name, array $items) { return $this->writeMultiple($list_name, $items); }
public function insertMultiple ($list_name, array $items) { return $this->writeMultiple($list_name, $items); }	
public function edit($list_name, $ID, array $data) { return $this->update ($list_name, $ID, $data); }
public function add ($list_name, array $data) { return $this->write($list_name, $data); }
public function insert ($list_name, array $data) { return $this->write($list_name, $data); }
public function editMultiple($list_name, array $items) { return $this->updateMultiple ($list_name, $items); }
public function getColumnVersions ($list, $id, $field) { return $this->getFieldVersions($list, $id, $field); }
public function getVersions ($list, $id, $field = null) {
	    return $this->getFieldVersions($list, $id, $field);
	}
}

class SharePointOnlineAuth extends \SoapClient {

// Authentication cookies
private $authCookies = false;

// Override do request method
public function __doRequest($request, $location, $action, $version, $one_way = false) {

	// Authenticate with SP online in order to get required authentication cookies
	if (!$this->authCookies) $this->configureAuthCookies($location);

	// Set base headers
	$headers = array();
	$headers[] = "Content-Type: text/xml;";
	$headers[] = "SOAPAction: \"{$action}\"";

	$curl = curl_init($location);

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);

	// Send request and auth cookies.
	curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
	curl_setopt($curl, CURLOPT_COOKIE, $this->authCookies);

	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

	// Useful for debugging
	curl_setopt($curl, CURLOPT_VERBOSE,FALSE);
	curl_setopt($curl, CURLOPT_HEADER, FALSE);

	// Add headers
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	// Init the cURL
	$response = curl_exec($curl);

	// Throw exceptions if there are any issues
	if (curl_errno($curl)) throw new \SoapFault('Receiver', curl_error($curl));
	if ($response == '') throw new \SoapFault('Receiver', "No XML returned");

	// Close CURL
	curl_close($curl);

	// Return?
	if (!$one_way) return ($response);
}

protected function configureAuthCookies($location) {

		// Get endpoint "https://somthing.sharepoint.com"
		$location = parse_url($location);
		$endpoint = 'https://'.$location['host'];

		// get username & password
		$login = $this->{'_login'};
		$password = $this->{'_password'};

		// Create XML security token request
		$xml = $this->generateSecurityToken($login, $password, $endpoint);

		// Send request and grab returned xml
		$result = $this->authCurl("https://login.microsoftonline.com/extSTS.srf", $xml);

		
		// Extract security token from XML
		$xml = new \DOMDocument();
		$xml->loadXML($result);
		$xpath = new \DOMXPath($xml);

		// Register SOAPFault namespace for error checking
		$xpath->registerNamespace('psf', "http://schemas.microsoft.com/Passport/SoapServices/SOAPFault");
		// var_dump($result);
		// Try to detect authentication errors
		$errors = $xpath->query("//psf:internalerror");
		if($errors->length > 0){
			$info = $errors->item(0)->childNodes;
			throw new \Exception($info->item(1)->nodeValue, $info->item(0)->nodeValue);
		}

		$nodelist = $xpath->query("//wsse:BinarySecurityToken");
		foreach ($nodelist as $n){
			$token = $n->nodeValue;
			break;
		}

		if(!isset($token)){
			throw new \Exception("Unable to extract token from authentiction request");
		}

		// Send token to SharePoint online in order to gain authentication cookies
		$result = $this->authCurl($endpoint."/_forms/default.aspx?wa=wsignin1.0", $token, true);

		// Extract Authentication cookies from response & set them in to AuthCookies var
		$this->authCookies = $this->extractAuthCookies($result);
	}

protected function extractAuthCookies($result){

		$authCookies = array();
		$cookie_payload = '';

		$header_array = explode("\r\n", $result);

		// Get the two auth cookies
		foreach($header_array as $header) {
			$loop = explode(":",$header);
			if (strtolower($loop[0]) == 'set-cookie') {
				$authCookies[] = $loop[1];
			}
		}

		// Extract cookie name & payload and format in to cURL compatible string
		foreach($authCookies as $payload){
			$e = strpos($payload, "=");
			// Get name
			$name = substr($payload, 0, $e);
			// Get token
			$content = substr($payload, $e+1);
			$content = substr($content, 0, strpos($content, ";"));

			// If not first cookie, add cookie seperator
			if($cookie_payload !== '') $cookie_payload .= '; ';

			// Add cookie to string
			$cookie_payload .= $name.'='.$content;
		}

	  	return $cookie_payload;
	}

protected function authCurl($url, $payload, $header = false){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,  $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	  	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		if($header)  curl_setopt($ch, CURLOPT_HEADER, true);

		$result = curl_exec($ch);

		// catch error
		if($result === false) {
			throw new \SoapFault('Sender', 'Curl error: ' . curl_error($ch));
		}

		curl_close($ch);

		return $result;
	}

protected function generateSecurityToken($username, $password, $endpoint) {
	return <<<TOKEN
    <s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"
      xmlns:a="http://www.w3.org/2005/08/addressing"
      xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  <s:Header>
    <a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</a:Action>
    <a:ReplyTo>
      <a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
    </a:ReplyTo>
    <a:To s:mustUnderstand="1">https://login.microsoftonline.com/extSTS.srf</a:To>
    <o:Security s:mustUnderstand="1"
       xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
      <o:UsernameToken>
        <o:Username>$username</o:Username>
        <o:Password>$password</o:Password>
      </o:UsernameToken>
    </o:Security>
  </s:Header>
  <s:Body>
    <t:RequestSecurityToken xmlns:t="http://schemas.xmlsoap.org/ws/2005/02/trust">
      <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
        <a:EndpointReference>
          <a:Address>$endpoint</a:Address>
        </a:EndpointReference>
      </wsp:AppliesTo>
      <t:KeyType>http://schemas.xmlsoap.org/ws/2005/05/identity/NoProofKey</t:KeyType>
      <t:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</t:RequestType>
      <t:TokenType>urn:oasis:names:tc:SAML:1.0:assertion</t:TokenType>
    </t:RequestSecurityToken>
  </s:Body>
</s:Envelope>
TOKEN;
	}
}		