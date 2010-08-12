// $Id$

/**
 * @file js support for term editing form for ajax saving and tree updating
 */


(function ($) {
  
//global var that holds the current term link object
var active_term = new Object();

//holds tree objects, useful in double tree interface, when both trees needs to be updated
var trees = new Object();

/** 
 * attaches term data form, used after 'Saves changes' ahah submit
 */
Drupal.behaviors.TaxonomyManagerTermData = {
  attach: function(context) {
    if (!$('#taxonomy-manager.tm-termData-processed').length) { 
      var vid = $('#edit-term-data-vid').val();
      for (var id in trees) {
        var tree = trees[id];
        if (tree.vocId == vid) {
          Drupal.attachTermDataForm(tree);
          break;
        }
      }
    }
  }
}

/**
 * attaches Term Data functionality, called by tree.js
 */
Drupal.attachTermData = function(ul, tree) {
  trees[tree.treeId] = tree;
  Drupal.attachTermDataLinks(ul, tree);
  
  if (!$('#taxonomy-manager.tm-termData-processed').length) { 	 
	  Drupal.attachTermDataForm(tree);
  }
}

/**
 * adds click events to the term links in the tree structure
 */
Drupal.attachTermDataLinks = function(ul, tree) {
  $(ul).find('a.term-data-link').click(function() {
    Drupal.activeTermSwapHighlight(this);
    var li = $(this).parents("li:first");
    var termdata = new Drupal.TermData(Drupal.getTermId(li), this.href +'/true', li, tree);
    termdata.load();
    return false;
  });
}

/**
* hightlights current term
*/
Drupal.activeTermSwapHighlight = function(link) {
  try {
    $(active_term).parent().removeClass('highlightActiveTerm');
  } catch(e) {}
  active_term = link;
  $(active_term).parent().addClass('highlightActiveTerm');
}

/**
 * attaches click events to next siblings
 */
Drupal.attachTermDataToSiblings = function(all, currentIndex, tree) {
  var nextSiblings = $(all).slice(currentIndex);
  $(nextSiblings).find('a.term-data-link').click(function() {
    var li = $(this).parents("li:first");
    var termdata = new Drupal.TermData(Drupal.getTermId(li), this.href +'/true', li, tree);
    termdata.load();
    return false;
  });
}

/**
 * adds click events to term data form, which is already open, when page gets loaded
 */
Drupal.attachTermDataForm = function(tree) {
  $('#taxonomy-manager').addClass('tm-termData-processed');
  var tid = $('#edit-term-data-tid').val();
  if (tid) {
    var li = tree.getLi(tid);
    var termLink = $(li).children("div.term-line").find("a.term-data-link");
    Drupal.activeTermSwapHighlight(termLink);
    var url = Drupal.settings.termData['term_url'] +'/'+ tid +'/true';
    var termdata = new Drupal.TermData(tid, url, li, tree);
    termdata.form();
  }  
}

/**
 * TermData Object
 */
Drupal.TermData = function(tid, href, li, tree) {
  this.href = href;
  this.tid = tid;
  this.li = li;
  this.tree = tree
  this.form_build_id = tree.form_build_id;
  this.form_id = tree.form_id;
  this.form_token = tree.form_token;
  this.vid = tree.vocId;
  this.div = $('#taxonomy-term-data');
}


/**
 * loads ahah form from given link and displays it on the right side
 */
Drupal.TermData.prototype.load = function() {
  var url = this.href;
  var termdata = this;
  var param = new Object();
  param['form_build_id'] = this.form_build_id;
  param['form_id'] = this.form_id;
  param['form_token'] = this.form_token;
 
  $.ajax({
    data: param, 
    type: "POST", 
    url: url,
    dataType: 'json',
    success: function(response, status) {
      termdata.insertForm(response.data);
      Drupal.attachBehaviors(termdata.div, response.settings);
    }
  });
}

/**
 * inserts received html data into form wrapper
 */
Drupal.TermData.prototype.insertForm = function(data) { 
  $(this.div).html(data);
  this.form(); 
}

/**
 * adds events to possible operations
 */
Drupal.TermData.prototype.form = function() {
  var termdata = this;
  
  $(this.div).find("legend").each(function() {
    var staticOffsetX, staticOffsetY = null;
    var left, top = 0;
    var div = termdata.div; 
    var pos = $(div).position();
    $(this).mousedown(startDrag);  
  
    function startDrag(e) {
      if (staticOffsetX == null && staticOffsetY == null) {
        staticOffsetX = e.pageX;
        staticOffsetY = e.pageY;
      }
      $(document).mousemove(performDrag).mouseup(endDrag);
      return false;
    }
 
    function performDrag(e) {
      left = e.pageX - staticOffsetX;
      top = e.pageY - staticOffsetY;
      $(div).css({position: "absolute", "left": pos.left + left +"px", "top": pos.top + top +"px"});
      return false;
    }
 
    function endDrag(e) {
      $(document).unbind("mousemove", performDrag).unbind("mouseup", endDrag);
    }
  });
}

})(jQuery);
