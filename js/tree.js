// $Id$

/**
 * @files js for collapsible tree view with some helper functions for updating tree structure
 */

Drupal.behaviors.TaxonomyManagerTree = function(context) {
  var settings = Drupal.settings.taxonomytree || [];
  var id, vid;
  
  if (settings['id']) {
    if (!(settings['id'] instanceof Array)) {
       id = settings['id'];
       vid = settings['vid'];
       if (!$('#'+ id + '.tm-processed').size()) {
         new Drupal.TaxonomyManagerTree(id, vid);
       }
    }
    else {
      for (var i = 0; i < settings['id'].length; i++) {
        id = settings['id'][i];
        vid = settings['vid'][i];
        if (!$('#'+ id + '.tm-processed').size()) {
          new Drupal.TaxonomyManagerTree(id, vid); 
        }
      }
    }
  }
  
  //only add throbber for TM sites
  var throbberSettings = Drupal.settings.TMAjaxThrobber || [];
  if (throbberSettings['add']) {
    if (!$('#taxonomy-manager-toolbar' + '.tm-processed').size()) {
      $('#taxonomy-manager-toolbar').addClass('tm-processed');
      Drupal.attachThrobber();
      Drupal.attachResizeableTreeDiv();
    } 
  }
}


Drupal.TaxonomyManagerTree = function(id, vid) {
  this.div = $("#"+ id);
  this.ul = $(this.div).find("ul");
  this.form = $(this.ul).parents('form');
  this.form_build_id = $(this.form).find(':input[name="form_build_id"]').val();
  this.form_id = $(this.form).find(' :input[name="form_id"]').val();
  this.treeId = id;
  this.vocId = vid; 

  $(this.div).addClass("tm-processed");
  this.attachTreeview(this.ul);
  this.attachSiblingsForm(this.ul);
  this.attachSelectAllChildren(this.ul);
}

/**
 * adds collapsible treeview to a given list
 */
Drupal.TaxonomyManagerTree.prototype.attachTreeview = function(ul, currentIndex) {
  var tree = this;
  if (currentIndex) {
    ul = $(ul).slice(currentIndex);
  }
  var expandableParent = $(ul).find("div.hitArea");
  $(expandableParent).click(function() {
    var li = $(this).parent();
    tree.toggleTree(li);
    tree.loadChildForm(li);
  });
  $(expandableParent).parent("li.expandable, li.lastExpandable").find("ul").hide();
}

/**
 * toggles a collapsible/expandable tree element by swaping classes
 */
Drupal.TaxonomyManagerTree.prototype.toggleTree = function(node) {
  $(node).children("ul:first").toggle();
  this.swapClasses(node, "expandable", "collapsable");
  this.swapClasses(node, "lastExpandable", "lastCollapsable");
}

/**
 * helper function for swapping two classes
 */
Drupal.TaxonomyManagerTree.prototype.swapClasses = function(node, c1, c2) {
  if ($(node).hasClass(c1)) {
    $(node).removeClass(c1).addClass(c2);
  } 
  else if ($(node).hasClass(c2)) {
    $(node).removeClass(c2).addClass(c1);
  } 
}


/**
 * loads child terms and appends html to list
 * adds treeview, weighting etc. js to inserted child list
 */
Drupal.TaxonomyManagerTree.prototype.loadChildForm = function(li, update) {
  var tree = this;
  if ($(li).is(".has-children") || update == true) {
    var parentId = Drupal.getTermId(li);
    if (!(Drupal.settings.childForm['url'] instanceof Array)) {
      url = Drupal.settings.childForm['url'];
    }
    else {
      url = Drupal.settings.childForm['url'][0];
    }
    url += '/'+ this.treeId +'/'+ this.vocId +'/'+ parentId;
    var param = new Object();
    param['form_build_id'] = this.form_build_id;
    param['form_id'] = this.form_id;
    param['tree_id'] = this.treeId;
    
    $.get(url, param, function(data) {
      $(li).append(data);
      var ul = $(li).find("ul");
      tree.attachTreeview(ul);
      tree.attachSiblingsForm(ul);
      tree.attachSelectAllChildren(ul);
      
      //only attach other features if enabled!
      var weight_settings = Drupal.settings.updateWeight || [];
      if (weight_settings['up']) {
        Drupal.attachUpdateWeightTerms(li);
      }
      var term_data_settings = Drupal.settings.termData || [];
      if (term_data_settings['url']) {
        Drupal.attachTermData(ul);
      }
      $(li).removeClass("has-children");
    });     
  }
}

/**
 * function for reloading root tree elements
 */
Drupal.TaxonomyManagerTree.prototype.loadRootForm = function() {
  if (!(Drupal.settings.childForm['url'] instanceof Array)) {
    url = Drupal.settings.childForm['url'];
  }
  else {
    url = Drupal.settings.childForm['url'][0];
  }
  var tree = this;
  url += '/'+ this.treeId +'/'+ this.vocId +'/0/true';
  $.get(url, null, function(data) {
    $('#'+ tree.treeId).html(data); 
    var ul = $('#'+ tree.treeId).find("ul");
    tree.attachTreeview(ul);
    tree.attachSiblingsForm(ul);
    tree.attachSelectAllChildren(ul);
    Drupal.attachUpdateWeightTerms(ul);
    Drupal.attachTermData(ul);
  });
}


/**
 * adds link for loading next siblings terms, when click terms get loaded through ahah
 * adds all needed js like treeview, weightning, etc.. to new added terms
 */
Drupal.TaxonomyManagerTree.prototype.attachSiblingsForm = function(ul) {
  var tree = this;
  if (!(Drupal.settings.siblingsForm['url'] instanceof Array)) {
    url = Drupal.settings.siblingsForm['url'];
  }
  else {
    url = Drupal.settings.siblingsForm['url'][0];
  }
  var list = "li.has-more-siblings div.term-has-more-siblings";
  if (ul) {
    list = $(ul).find(list);
  }
  
  $(list).bind('click', function() {
    $(this).unbind("click");
    var li = this.parentNode.parentNode;
    var all = $('li', li.parentNode);
    var currentIndex = all.index(li);

    var page = Drupal.getPage(li);
    var prev_id = Drupal.getTermId(li);
    var parentId = Drupal.getParentId(li);
    
    url += '/'+ tree.treeId +'/'+ page +'/'+ prev_id +'/'+ parentId;
    
    var param = new Object();
    param['form_build_id'] = this.form_build_id;
    param['form_id'] = this.form_id;
    param['tree_id'] = this.treeId;
    
    $.get(url, param, function(data) {
      $(li).find(".term-has-more-siblings").remove();
      $(li).after(data);
      tree.attachTreeview($('li', li.parentNode), currentIndex);
      tree.attachSelectAllChildren($('li', li.parentNode), currentIndex);
      
      //only attach other features if enabled!
      var weight_settings = Drupal.settings.updateWeight || [];
      if (weight_settings['up']) {
        Drupal.attachUpdateWeightTerms($('li', li.parentNode), currentIndex);
      }
      var term_data_settings = Drupal.settings.termData || [];
      if (term_data_settings['url']) {
        Drupal.attachTermDataToSiblings($('li', li.parentNode), currentIndex);
      }
      
      $(li).removeClass("last").removeClass("has-more-siblings");
      $(li).find('.term-operations').hide();
      tree.swapClasses(li, "lastExpandable", "expandable");
      tree.attachSiblingsForm($(li).parent());
    });
  });
}


/**
 * helper function for getting out the current page
 */
Drupal.getPage = function(li) { 
  return $(li).find("input:hidden[class=page]").attr("value");
}


/**
 * returns terms id of a given list element
 */
Drupal.getTermId = function(li) {
  return $(li).find("input:hidden[class=term-id]").attr("value");
}

/**
 * return term id of a prent of a given list element
 * if no parent exists (root level), returns 0
 */
Drupal.getParentId = function(li) {
  var parentId;
  try {
    var parentLi = $(li).parent("ul").parent("li");
    parentId = Drupal.getTermId(parentLi);
  } catch(e) {
    return 0;
  }
  return parentId;
}

/**
 * update classes for tree view, if list elements get swaped
 */
Drupal.updateTree = function(upTerm, downTerm) {  
  if ($(upTerm).is(".last")) {
    $(upTerm).removeClass("last");
    Drupal.updateTreeDownTerm(downTerm); 
  }
  else if ($(upTerm).is(".lastExpandable")) {
    $(upTerm).removeClass("lastExpandable").addClass("expandable");
    Drupal.updateTreeDownTerm(downTerm); 
  }
  else if ($(upTerm).is(".lastCollapsable")) {
    $(upTerm).removeClass("lastCollapsable").addClass("collapsable");
    Drupal.updateTreeDownTerm(downTerm);  
  }
}

/**
 * update classes for tree view for a list element moved downwards
 */
Drupal.updateTreeDownTerm = function(downTerm) {
  if ($(downTerm).is(".expandable")) {
    $(downTerm).removeClass("expandable").addClass("lastExpandable");
  }
  else if ($(downTerm).is(".collapsable")) {
    $(downTerm).removeClass("collapsable").addClass("lastCollapsable");
  }
  else {
    $(downTerm).addClass("last");
  }
}

/**
 * Adds button next to parent term to select all available child checkboxes
 */
Drupal.TaxonomyManagerTree.prototype.attachSelectAllChildren = function(parent, currentIndex) {
  var tree = this;
  if (currentIndex) {
    parent = $(parent).slice(currentIndex);
  }
  $(parent).find('span.select-all-children').click(function() {
    tree.SelectAllChildrenToggle(this);
  });
}

/**
 * (un-)selects nested checkboxes
 */
Drupal.TaxonomyManagerTree.prototype.SelectAllChildrenToggle = function(span) {
  if ($(span).hasClass("select-all-children")) {
    /*var li = $(span).parents("li:first");
    if ($(li).hasClass("has-children")) {
      this.toggleTree(li);
      this.loadChildForm(li);
    }*/
    $(span).removeClass("select-all-children").addClass("unselect-all-children");
    $(span).attr("title", Drupal.t("Unselect all children"));
    $(span).parents("li:first").find('ul:first').each(function() {
      var first_element = $(this).find('.term-line:first');
      $(first_element).parent().siblings("li").find('div.term-line:first :checkbox').attr('checked', true);
      $(first_element).find(' :checkbox').attr('checked', true);
    });
  }
  else {
    $(span).removeClass("unselect-all-children").addClass("select-all-children");
    $(span).parents(".term-line").siblings("ul").find(':checkbox').attr("checked", false);
    $(span).attr("title", Drupal.t("Select all children"));
  }
}



/**
 * attaches a throbber element to the taxonomy manager
 */
Drupal.attachThrobber = function() {
  var div = '#taxonomy-manager';
  var throbber = $('<img src="'+ Drupal.settings.taxonomy_manager['modulePath'] +'images/ajax-loader.gif" alt="" height="25">');
  throbber.appendTo("#taxonomy-manager-toolbar-throbber").hide();
  throbber.ajaxStart(function(){
      $(this).show();
      $(div).css('opacity', '0.5');
    })
    .ajaxStop(function(){
      $(this).hide();
      $(div).css('opacity', '1');
    });
}

/**
* makes the div resizeable
*/
Drupal.attachResizeableTreeDiv = function() {
  var div = $('#taxonomy-manager-tree-outer-div'), staticOffset = null;
 
  $('#taxonomy-manager-tree-size .div-grippie').mousedown(startDrag);
 
  function startDrag(e) {
    staticOffset = div.width() - e.pageX;
    div.css('opacity', 0.5);
    $(document).mousemove(performDrag).mouseup(endDrag);
    return false;
  }
 
  function performDrag(e) {
    div.width(Math.max(200, staticOffset + e.pageX) + 'px');
    return false;
  }
 
  function endDrag(e) {
    $(document).unbind("mousemove", performDrag).unbind("mouseup", endDrag);
    div.css('opacity', 1);
  }
}
