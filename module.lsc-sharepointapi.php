<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'lsc-sharepointapi/1.3.0',
	array(
		// Identification
		//
		'label' => 'LSC Sharepoint API',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(			
		'itop-config-mgmt/2.0.0',	
		),
		
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'model.lsc-sharepointapi.php',
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			// Module specific settings go here, if any
			'spUsername' 	=> 'tenant@organization.onmicrosoft.com', 					//Username for your account, example larsulrich@metallicasharepoint.onmicrosoft.com
			'spPassword' 	=> '', 														//Password for above user
			'spWsdl' 		=> APPROOT.'env-production/lsc-sharepointapi/Lists.xml', 	//Absolute path to Lists.xml, DON'T change this if you've put it in extensions/lsc-sharepointapi/Lists.xml
			'spMode' 		=> 'SPONLINE', 												//Which mode of sharepoint it is connecting to, unset for most normal installations, NTLM for installations that require NTLM auth and SPONLINE for SP Online
			'spURL' 		=> 'https://************.sharepoint.com',   				// format(without trailing slash) ex. https://metallicasharepoint.sharepoint.com/
			'spSite' 		=> '', 														//Site name, as displayed in URL after /sites/, without slashes
		),
	)
);
