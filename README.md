# iTop-SharepointAPI
Sharepoint Lists API for iTop. The PHP-API itself is based on Thybag's https://github.com/thybag/PHP-SharePoint-Lists-API

# What does it do?
Firstly, the included file creates Sharepoint Online Document Libraries from an excel file (csv, needs to have the headings "id" and "desc", separated by ;)
The script is then designed to work on the Application Solution part of iTop. For each AS, it gets the current ID of the site, passes it onto a display script which displays the data inline on a tab calles Sharepoint Files. 
This can be expanded to work with other CIs, like Servers, Computers etc.

# What languages are there?
So far English and French, more to come!

# How do I use it?
- Download, unpack and insert the module into your iTop-extensions Folder
- Download your WSDL-file from Sharepoint online (usually sharepoint.url/subsite/_vti_bin/Lists.asmx?WSDL), place it in the extension folder
- Edit the module.lsc-sharepointapi.php settings-part to your specifications (If you've placed the Lists.xml in your extension folder, it's sufficient to put just Lists.xml in the spWsdl property)
- Run the setup process and install the extension (see iTop website for more information)


# Where does it work?
This script is designed to work with iTop 2.4.0 and newer

# What does it need?
Combodo's iTop and a Sharepoint installation

# What's there still to do?
- It would be nice if someone other than me looked over the code

# Thanks to
- Molkobain, for contributing code to this project to integrate it better with iTop
- Pierre Goiffon over on the Sourceforge iTop forum
- Combodo for a great product
