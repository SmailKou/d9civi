(function ($, Drupal) {
  Drupal.behaviors.designerMakerModal = {
    attach: function (context, settings) {
      $('.designer-maker-link', context).once('designerMakerModal').click(function (e) {
        e.preventDefault();
        var designerMakerId = $(this).data('id');
        console.log("got the id: ", designerMakerId);
        $.ajax({
          url: '/designer-maker-modal/' + designerMakerId,
          type: 'GET',
          dataType: 'json',
          success: function (data) {
            var modalContent = '<div class="designer-maker-details">';
            modalContent += '<h2>' + data.display_name + '</h2>';
            modalContent += '<p><strong>Email:</strong> ' + data.email + '</p>';
            modalContent += '<a href="/designer-makers/' + data.id + '">Visit full profile</a>';
            modalContent += '</div>';

            $('#designer-maker-modal').html(modalContent).dialog({
              title: 'Designer Maker Details',
              modal: true,
              width: 'auto',
              buttons: {
                Close: function () {
                  $(this).dialog('close');
                }
              }
            });
          }
        });
      });
    }
  };
})(jQuery, Drupal);
