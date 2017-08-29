/**
 * @file
 * JS Cart.
 */

(function ($) {
  $(document).ready(function () {

    $('.syncart-link a').click(function (e) {
      e.preventDefault();

      count = 1;
      if ($('.node-product .to-card input').length) {
        count = $('.node-product .to-card input').val();
          if (count == '') {
            count = 1;
          }
      }

      $.ajax({
        type: "POST",
        url: $(this).prop('href'),
        data: "count=" + count,
        success: function (data) {
          $(data.block).appendTo('body');
          $('#modal-answer-cart-add').modal();

          $('#block-views-block-commerce-cart-form-block-2').remove();
          $('#main-content .region.region-content').prepend(data.block_cart);
        }
      });

      $(document).on("click", "#answer-cart-add .cart-close", function () {
        $('#modal-answer-cart-add').modal('hide');
      });

    });

    $('#cart-total a.checkout').click(function (e) {
      e.preventDefault();
      $('.view-commerce-cart-form.view-id-commerce_cart_form #edit-checkout').click();
    });

  });
})(this.jQuery);
