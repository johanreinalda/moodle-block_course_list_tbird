// YUI code for sample.php
// see http://yuilibrary.com/gallery/show/yui3treeview

YUI().use("gallery-yui3treeview", function(Y)
{
  //alert("TreeView called");
  var categoryview = new Y.TreeView({
   srcNode: '#categorytree',
   contentBox: '#categorytree',
   boundingBox: '#categorytree',
   type : "TreeView"
  });
categoryview.render();
});