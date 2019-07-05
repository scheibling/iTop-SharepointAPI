# iTop-SharepointAPI
 Sharepoint Lists API for iTop


Usage:
Insert into folder SharepointAPI in Combodos iTop. Fill in passwords. Export a list of Application Solutions. Create folders with ps1-script. Insert the following bold row below the cursive one: 

## application/cmdbabstract.class.inc.php: 
<i>$oPage->AddAjaxTab(Dict::S('UI:HistoryTab'), utils::GetAbsoluteUrlAppRoot().'pages/ajax.render.php?operation=history&class='.get_class($this).'&id='.$this->GetKey());</i><br><br>
<b>$category = MetaModel::GetName(get_class($this));<br>
if ($category == "Application Solution") {$oPage->AddAjaxTab(Dict::S('UI:SharepointTab'), utils::GetAbsoluteUrlAppRoot().'/extensions/SharepointAPI/GetSharepoint.php?id='.$this->GetKey());}</b>

You can leave out the if-clause in case you want the tab to be displayed on other CIs as well.
Insert translation into UI-Dictionary calling the tab whatever you like. I inserted the following row: 
## dictionaries/en.dictionary.itop.ui.php
'UI:SharepointTab' => 'Documents (Sharepoint)', 
