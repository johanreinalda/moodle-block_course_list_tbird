// module.js handles the menu tree for the list of courses
YUI().use("yui2-treeview", function(Y)
{
  var YAHOO = Y.YUI2;
  var categoryView = new YAHOO.widget.TreeView("categoryContainer");
  categoryView.render();
});