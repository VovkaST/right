$(document).on('keyup', '.ajax_search', function(I){
  var par_name = $(this).attr('id');
  var r_place = $(this).next('.ajax_search_result');
  var cell = $(this).closest('td');
  var wait = cell.next('td');
  var form = $(this).closest("form");
  
  switch(I.keyCode) {
    case 13:
    case 27:
    case 38:
    case 40:
      break;
    
    default:
      var val = $(this).val();
      if (val.length >= 1) {
        $.ajax({
          url: 'procedures.php?ajsrch=' + par_name + '&' + form.serialize(),
          dataType: 'json',
          beforeSend: function(){
            r_place.html('');
            wait.html('<img src="http://.../images/wait.gif"/>');
          },
          success: function(resp) {
            if (resp.html != '') {
              $.each(resp.html, function(key, value){
                $(r_place).append('<div class="search_variant" id="' + key + '">' + value + '</div>');
              });
              r_place.slideDown(100);
            } else {
              r_place.slideUp(100, function() { r_place.children().remove(); });
            }
          },
          complete: function() {
            wait.html('');
          }
        });
      } else {
        if (val == '') {
          r_place.slideUp(100, function() { r_place.children().remove(); });
        }
      }
    break;
  }
  if ($(this).val() == '') {
    $('input[name="'+$(this).attr('id')+'"]').val('');
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

$('html').click(function(){
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