' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
on error resume next
'WMI disksinfo

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>HD</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colHDDs = objWMIService.ExecQuery("Select * from win32_diskdrive")

For Each disco In colHDDs
  If ((not IsNull(disco.size)) AND (disco.size > 0)) then
	  Wscript.StdOut.Write "<data><![CDATA[" & disco.caption _ 
		& ";" & Abs(Round((disco.size/(1024*1024*1024)),2)) & " GB"
	  If (not IsNull(disco.serialnumber)) then
		Wscript.StdOut.Write ";" & disco.serialnumber
	  Else
		Wscript.StdOut.Write ";" & disco.signature
	  End If
		Wscript.StdOut.WriteLine "]]></data>"
  End If
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

