<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'lsc-SharepointAPI/1.1.0',
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
			'spUsername' => '', //Username for your account, often tenant@organization.onmicrosoft.com
			'spPassword' => '', //Password for above user
			'spWsdl    ' => '', //Path to your lists.xml
			'spMode    ' => '', //Which mode of sharepoint it is connecting to, unset for most normal installations, NTLM for installations that require NTLM auth and SPONLINE for SP Online
			'spURL	 ' => '',   // format(without trailing slash): https://organization.onmicrosoft.com/
			'spSite    ' => '', //Site name, as displayed in URL after /sites/
		),
	)
);
