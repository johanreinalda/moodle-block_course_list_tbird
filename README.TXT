This is a customized Course List HTML Block
(c) 2010-2014, Thunderbird School of Global Management
Written by Johan Reinalda,  johan at reinalda dot net

DESCRIPTION:
This block functions like the regular Moodle course_list block, but can render courses
in a tree view, instead of long list of courses.

Additionally, it is customized to render information about the course meeting days and times,
and course start and end dates. (start is already part of normal moodle course data)

This added course information is read from a custom table,
which are processed by the customized "imsenterprise" enrolment module.

If you do not store any data in that table, this module behaves just fine as is.


NOTE:
This block is tested in Moodle v2.3 - 2.7 only!

INSTALLATION:
Unzip these files to the appropriate directories under your Moodle install
Then as Moodle admin, go to the Notifications entry of your Admin block.
The block should be found and added to the list of available block.

We use the extension capability of the IMS-XML format and import the following elements
in the custom Thunderbird IMS module "imsenterprise". 

<group>
    <timeframe>
        <begin>2010-03-01</begin>
        <end>2010-05-31</end>
    </timeframe>
	<extension>
		<meeting-info>MWF 9:30-11AM Snell Classroom 23</meeting-info>
	</extension>
</group>

The meeting info is stored in a new table, created by this block, and used by the IMS code,
as well as the custom "course_meeting_info" block.
See the custom IMS enrolment module for more at
https://github.com/johanreinalda/moodle-enrol_imsenterprise,
and the Moodle modules site.


VERSION CHANGES:

2014061800 - Updated for Moodle 2.6 & 2.7 with course list collapsible at the category level
             and sorted according to the global category sort order.
2012100300 - Updated for Moodle 2.3
2010052500 - Initial version
