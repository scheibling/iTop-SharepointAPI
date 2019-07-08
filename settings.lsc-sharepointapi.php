<?php
		$this->spUsername = ''; //Username for your account, often tenant@organization.onmicrosoft.com
		$this->spPassword = ''; //Password for above user
		$this->spWsdl     = ''; //Path to your lists.xml
		$this->spMode     = ''; //Which mode of sharepoint it is connecting to, unset for most normal installations, NTLM for installations that require NTLM auth and SPONLINE for SP Online
		$this->spURL	  = ''; // format(without trailing slash): https://organization.onmicrosoft.com/
		$this->spSite     = ''; //Site name, as displayed in URL after /sites/
		
		//Translations
		$this->dictDocFolder = 'Link to Document Folder';
		$this->dictFolderFile = 'Folders and Files';
		$this->dictPleaseAcc = 'Please only access this file through the iTop mainframe';
		$this->dictNoFiles = 'There are no files or folders in this directory, or the Document Library does not exist. Please check the following Document Library in Sharepoint Online:';
	
?>