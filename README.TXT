This is a customized Course List HTML Block
(c) 2010-2012, Thunderbird School of Global Management
Written by Johan Reinalda,  johan dot reinalda at thunderbird dot edu

DESCRIPTION:
This block functions like the regular Moodle course_list block.
It os customized to render information about the course meeting days and times.
Content is read from custom tables, which are loaded via IMS enterprise XML data processed
by the customized "imsenterprisetbird" enrolment module.
Database tables are NOT automatically created, since this is highly customized.
Database creation should eventually go in the IMS Enterprise enrolment module, since that is where data is created.

NOTE:
This block is tested in Moodle v2.3 - 2.6 only!

INSTALLATION:
Unzip these files to the appropriate directories under your Moodle install
Then as Moodle admin, go to the Notifications entry of your Admin block.
The block should be found and added to the list of available block.

We use the extension capability of the IMS-XML format and import the following element
in the custom IMS module "imsenterprisetbird"

<group>
	<extension>
		<meeting-info>DATA</meeting-info>
	</extension>
</group>

This is stored in a new table, created by this block, and used by the IMS code, as well as the custom
"course_meeting_info" block. 


VERSION CHANGES:

2012100300 - Updated for Moodle 2.3
2010052500 - Initial version
