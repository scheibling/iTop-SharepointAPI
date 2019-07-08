<?php

// PHP Data Model definition file

// WARNING - WARNING - WARNING
// DO NOT EDIT THIS FILE (unless you know what you are doing)
// If you use supply a datamodel.xxxx.xml file with your module
// the this file WILL BE overwritten by the compilation of the
// module (during the setup) if the datamodel.xxxx.xml file
// contains the definition of new classes or menus.
//
// The recommended way to define new classes (for iTop 2.0) is via the XML definition.
// This file remains in the module's template only for the cases where there is:
// - either no new class or menu defined in the XML file
// - or no XML file at all supplied by the module


class SharepointAPI implements iApplicationUIExtension
{
	public function OnDisplayProperties($oObject, WebPage $oPage, $bEditMode = false)
		{
		}

	public function OnDisplayRelations($oObject, WebPage $oPage, $bEditMode = false)
	{
		if (get_class($oObject) == "ApplicationSolution"){
			// Add content in an async tab
			$sPreviousTab = $oPage->GetCurrentTab();
						
			$oPage->AddAjaxTab(Dict::S('Class:SharepointAPI/Attribute:SharepointTab'), 'main.lsc-sharepointapi.php?id='.$oObject->GetKey());
						
			// Put tab cursor back to previous to make sure nothing breaks our tab (other extension for example)
			$oPage->SetCurrentTab($sPreviousTab);
			return;
		}
    }

	public function OnFormSubmit($oObject, $sFormPrefix = '')
	{
	}

	public function OnFormCancel($sTempId)
	{
	}

	public function EnumUsedAttributes($oObject)
	{
		return array();
	}

	public function GetIcon($oObject)
	{
		return '';
	}

	public function GetHilightClass($oObject)
	{
		// Possible return values are:
		// HILIGHT_CLASS_CRITICAL, HILIGHT_CLASS_WARNING, HILIGHT_CLASS_OK, HILIGHT_CLASS_NONE	
		return HILIGHT_CLASS_NONE;
	}

	public function EnumAllowedActions(DBObjectSet $oSet)
	{

		return array();
	}
}