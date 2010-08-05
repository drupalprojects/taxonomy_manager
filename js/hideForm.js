// $Id$

/**
 * @file shows / hides form elements
 */
 
(function ($) {

Drupal.behaviors.TaxonomyManagerHideForm = {
  attach: function(context, settings) {
    settings = settings.hideForm || [];
    if (settings['div']) {
      if (!$('#taxonomy-manager-toolbar-buttons.tm-hideForm-processed').length) {
        $('#taxonomy-manager-toolbar-buttons').addClass('tm-hideForm-processed');
        if (!(settings['div'] instanceof Array)) {
          Drupal.attachHideForm(settings['div'], settings['show_button'], settings['hide_button']);
        }
        else {
          for (var i=0; i<settings['div'].length; i++) {
            Drupal.attachHideForm(settings['div'][i], settings['show_button'][i], settings['hide_button'][i]); 
          }
        }
      }
    }
  }
}

/**
 * adds click events to show / hide button
 */
Drupal.attachHideForm = function(div, show_button, hide_button) {
  var hide = true;
  div = $("#"+ div);
  show_button = $("#"+ show_button);
  hide_button = $("#"+ hide_button);

  //don't hide if there is an error in the form
  $(div).find("input").each(function() {
    if($(this).hasClass("error")) {
      hide = false;
    }
  });
  
  if (!hide) { 
    $(div).show();
  }
  $(show_button).click(function() {
    $(div).toggle();
    return false;
  });
  
  $(hide_button).click(function() {
    $(div).hide();
    return false;
  });
}

})(jQuery);
