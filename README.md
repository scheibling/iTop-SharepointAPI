# iTop-SharepointAPI
 Sharepoint Lists API for iTop


Usage:
Insert into folder SharepointAPI in Combodos iTop. Fill in passwords. Export a list of Application Solutions. Create folders with ps1-script. Insert the following bold row below the cursive one: 

## application/cmdbabstract.class.inc.php: 
<i>$oPage->AddAjaxTab(Dict::S('UI:HistoryTab'), utils::GetAbsoluteUrlAppRoot().'pages/ajax.render.php?operation=history&class='.get_class($this).'&id='.$this->GetKey());</i>
<b>if ($category == "Application Solution") {$oPage->AddAjaxTab(Dict::S('UI:SharepointTab'), utils::GetAbsoluteUrlAppRoot().'/extensions/SharepointAPI/GetSharepoint.php?id='.$this->GetKey());}</b>

Insert translation into UI-Dictionary calling the tab whatever you like. I inserted the following row: 
## dictionaries/en.dictionary.itop.ui.php
'UI:SharepointTab' => 'Documents (Sharepoint)', 
