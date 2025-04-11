(function ($, Drupal) {
  Drupal.behaviors.customPager = {
    attach: function (context, settings) {
      // Check if console logs are enabled in the module configuration using JS optional chaining operator (?.).
      const enableLogs = settings.listado_usuarios?.enable_console_logs || false;

      // Explicitly target the updated #listado-usuarios-wrapper container.
      const wrapper = $('[data-drupal-selector="edit-listado-usuarios-wrapper"]', context);

      if (wrapper.length === 0) {
        if (enableLogs) {
          console.log('custom-pager.js: #listado-usuarios-wrapper not found in context.');
        }
        return; // Exit if the wrapper is not found.
      }

      if (enableLogs) {
        console.log('custom-pager.js: Found #listado-usuarios-wrapper in context.');
      }

      // Find the real select element and custom buttons within the wrapper.
      const realSelect = wrapper.find('[data-drupal-selector="edit-pager"]').get(0);
      const customButtons = wrapper.find('#customSelect button');

      if (!realSelect) {
        if (enableLogs) {
          console.log('custom-pager.js: realSelect not found in #listado-usuarios-wrapper.');
        }
      }

      if (customButtons.length === 0) {
        if (enableLogs) {
          console.log('custom-pager.js: No customButtons found in #listado-usuarios-wrapper.');
        }
      }

      if (!realSelect || customButtons.length === 0) {
        return; // Exit if the elements are not found.
      }

      if (enableLogs) {
        console.log('custom-pager.js: Attaching behavior to custom buttons.');
      }

      // Attach click event to each button.
      customButtons.each(function () {
        const button = $(this);

        // Remove any previously attached click event to avoid duplicate bindings.
        button.off('click').on('click', function () {
          if (enableLogs) {
            console.log('custom-pager.js: Button clicked, data-value =', button.data('value'));
          }

          // Remove "active" class from all buttons.
          customButtons.removeClass('active');

          // Add "active" class to the clicked button.
          button.addClass('active');

          // Update the value of the hidden select element.
          realSelect.value = button.data('value');

          // Trigger the change event on the select element to invoke AJAX.
          $(realSelect).trigger('change');

          if (enableLogs) {
            console.log('custom-pager.js code executed!');
          }
        });
      });

      if (enableLogs) {
        console.log('custom-pager.js behavior attached!');
      }
    },
  };
})(jQuery, Drupal);
