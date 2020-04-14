$(document).on('keyup', 'input#user_input, input#password_input', function(){
  ($(this).val() == '') ? $(this).css('color', '#A7A7A7') : $(this).css('color', '#515558');
});
$(document).on('focus', 'input#user_input, input#password_input', function(){
  $(this).closest('div.row').animate({borderBottomColor: '#FFE52D'}, 200);
});
$(document).on('blur', 'input#user_input, input#password_input', function(){
  $(this).closest('div.row').animate({borderBottomColor: '#E7E8E8'}, 200);
});
$(document).on('submit', '#auth_form', function(){
  $.ajax({
    url: '/login.php',
    dataType: 'json',
    type: 'POST',
    data: $(this).serialize(),
    beforeSend: function(){
      var h = $('.auth_block').outerHeight();
      $('.auth_form_block .block_header').after(
        $('<div class="waiting_background"><img src="/images/ajax-loader.gif"/></div>')
          .css('height', h)
          .fadeIn(200)
      );
    },
    success: function(resp) {
      if ('html' in resp) {
        $.each(resp.html, function(key, value){
          $(key).html(value);
        })
      }
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    },
    complete: function(){
      $('.auth_form_block .waiting_background').fadeOut(200, function(){
        $(this).remove();
      });
    }
  });
  return false;
});