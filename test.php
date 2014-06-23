<?php
// Report all PHP errors (see changelog)
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once(dirname(__FILE__) . '/../../config.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/course_list_tbird/sample.php');
$PAGE->requires->js('/blocks/course_list_tbird/sample.js', true);
echo $OUTPUT->header();
?>

<ul id="categorytree">
  <li>
    Current Term
    <ul>
      <li>
        <span><a href="somecourse.php?id=1">Course 1</a></span>
      </li>
      <li>
        <span><a href="somecourse.php?id=2">Course 2</a></span>
      </li>
    </ul>
  </li> 
  
  <li>
    Future Terms
    <ul>
      <li>
        <span><a href="somecourse.php?id=1">Course 1</a></span>
      </li>
      <li>
        <span><a href="somecourse.php?id=2">Course 2</a></span>
      </li>
    </ul>
  </li> 
  
  <li>
    Past Terms
    <ul>
      <li>
        <span><a href="somecourse.php?id=1">Course 1</a></span>
      </li>
      <li>
        <span><a href="somecourse.php?id=2">Course 2</a></span>
      </li>
    </ul>
  </li>
</ul>

<?php
echo $OUTPUT->footer();
