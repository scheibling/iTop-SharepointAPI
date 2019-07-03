[System.Reflection.Assembly]::LoadWithPartialName("Microsoft.SharePoint.Client")
[System.Reflection.Assembly]::LoadWithPartialName("Microsoft.SharePoint.Client.Runtime")

$siteUrl = "" #https://organisation.sharepoint.com/sites/[site]
$username = "" #Tenant@organisation.onmicrosoft.com
$password = Read-Host -Prompt "Enter password" -AsSecureString

$Libraries = Import-Csv -Path C:\Temp\sharepointimport.csv -Delimiter ";" #Path for file to be imported. Needs headers "id" and "desc", ; separated, two columns.

$DocLibraryName = "12701"

$ctx = New-Object Microsoft.SharePoint.Client.ClientContext($siteUrl)
$credentials = New-Object Microsoft.SharePoint.Client.SharePointOnlineCredentials($username, $password)
$ctx.Credentials = $credentials

$Lists = $ctx.Web.Lists
$ctx.Load($Lists)
$ctx.ExecuteQuery()

foreach ($DocLibraryName in $Libraries)
{

 #Check if Library name doesn't exists already and create document library
    if(!($Lists.Title -contains $DocLibraryName.id))
    { 
 
# write-host -f Green $($DocLibraryName.id)
# write-host -f Red $($DocLibraryName.desc)
 
        #create document library in sharepoint online powershell
        $ListInfo = New-Object Microsoft.SharePoint.Client.ListCreationInformation
        $ListInfo.Title = $($DocLibraryName.id)
        $ListInfo.Url = $($DocLibraryName.id)
        $ListInfo.TemplateType = 101 #Document Library
        $ListInfo.Description = $($DocLibraryName.desc)
        $List = $ctx.Web.Lists.Add($ListInfo)
        $List.Update()
        $ctx.ExecuteQuery()
  
        write-host  -f Green "New Library '$($DocLibraryName.desc)'has been created!"
    }
    else
    {
        Write-Host -f Yellow "List or Library '$DocLibraryName.desc' already exist!"
    }
 }