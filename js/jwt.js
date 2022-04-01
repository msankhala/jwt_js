(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.UserJWT = {
    attach: function (context, settings) {
      $(document).ready(function() {
        console.log('User JWT : ', drupalSettings);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
