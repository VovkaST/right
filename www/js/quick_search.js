$(document).on('keyup', '.ajax_search', function(I){
  var field = $(this);
  var par_name = field.attr('id');
  var r_place = field.next('.ajax_search_result');
  var cell = field.closest('td');
  var wait = cell.next('td');
  var form = field.closest("form");
  
  switch(I.keyCode) {
    case 13:
    case 27:
    case 38:
    case 40:
      break;
    
    default:
      var val = field.val();
      if (val.length >= 1) {
        setTimeout(function(){
          $.ajax({
            url: 'procedures.php?ajsrch=' + par_name + '&' + form.serialize(),
            dataType: 'json',
            type: 'POST',
            beforeSend: function(){
              r_place.html('&nbsp;');
              wait.html('<img src="/images/wait.gif"/>');
              if (form.attr('class') == 'organisation_data_form' && field.attr('name') == 'organisation_text') $('.popup_window_button_box.save_box').html('');
            },
            success: function(resp) {
              if ('html' in resp) {
                var variants = '';
                $.each(resp.html, function(key, value){
                  if (key != 0) {
                    variants += '<div class="search_variant" id="' + key + '">' + value + '</div>';
                  } else {
                    if (form.attr('class') == 'organisation_data_form' && field.attr('name') == 'organisation_text') { $('.popup_window_button_box.save_box').html('<button type="submit" class="popup_window_save">Сохранить</button>'); }
                  }
                });
                r_place
                  .html(variants)
                  .slideDown(100);
              }
              if ('error' in resp) {
                info_box('handling_errors', resp.error);
              }
              if ('msg' in resp) {
                info_box('handling_done', resp.msg);
              }
            },
            complete: function() {
              wait.html('');
            }
          });
        }, 1000);
      } else {
        if (val == '') {
          if (form.attr('class') == 'organisation_data_form' && field.attr('name') == 'organisation_text') $('.popup_window_button_box.save_box').html('');
          r_place.slideUp(100, function() { r_place.children().remove(); });
        }
      }
    break;
  }
  if (field.val() == '') {
    $('input[name="'+field.attr('id')+'"]').val('');
  }
});

$(document).on('keydown', '.ajax_search', function(I){
  switch(I.keyCode) {
    case 13:
    case 27:
      $('.ajax_search_result').slideUp(100);
      break;

    default:
      break;
  }
});

$('html').click(function(event){
  if (event.target) {
    var trg = event.target;
  } else {
    var trg = window.event.srcElement;
  }
  if ($(trg).attr('class') == 'ajax_search') {
    var block = $(trg).next('.ajax_search_result');
    if (block.css('display') == 'none') {
      if (block.children('.search_variant').length > 0) {
        block.slideDown(100);
      }
    }
  } else {
    $(this).find('.ajax_search_result').each(function(){
      if ($(this).css('display') != 'none') {
        var elem = $(this);
        if ($(trg).attr('class') != 'search_variant') {
          $(this).slideUp(100);
        }
      }
    });
  }
});

$(document).on('click', '.search_variant', function(){
  var block = $(this).parent('.ajax_search_result');
  var field = block.prev('.ajax_search');
  field.val($(this).text());
  $('input[name="'+field.attr('id')+'"]').val($(this).attr('id'));
  block.slideUp(100);
});