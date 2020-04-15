function check_email(field) {
  if(field.val() != '') {
    var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
    if (pattern.test(field.val())){
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
}

function check_fields_req(form) {
  var color = $('body').css('background-color');
  var error = false;
  form.find('[req="true"]').each(function(){
    var elem = $(this);
    if (elem.val() == "") {
      error = true;
    }
    if (elem.attr("class") == 'email') {
      if (!check_email(elem)) {
        error = true;
      }
    }
    if (error) {
      elem.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.animate({backgroundColor:color},200);
    }
  });
  if (error) {
    return false;
  } else {
    return true;
  }
}

function requirement_add(array) {
  $.each(array, function(i, v){
    if ($(v).val() != '') {
      $.each(array, function(i, v){
        $(v).attr("req", "true");
      });
      return false;
    } else {
      $.each(array, function(i, v){
        $(v).removeAttr("req");
      });
    }
  });
}

function info_box(handling_class, handling_msg) {
  $('body').append(
    '<div class="info_box"> \
      <div class="handling_result ' + handling_class + '"> \
        <p>' + handling_msg + '</p> \
        <input type="button" value="Ok" class="info_box_close"/> \
      </div> \
    </div>');
  var body = $('body');
  var win = $(window);
  var box = $('.info_box');
  box.css({
        top: ((win.height() - box.outerHeight()) / 2 + win.scrollTop()) + "px", 
        left: ((win.width() - box.outerWidth()) / 2) + "px" 
      })
     .fadeIn(200);
  $('.info_box_close').focus();
}

function info_box_show(data){
  if ('html' in data) {
    $.each(data.html, function(key, value){
      $('.' + key).html(value);
    })
  }
  if ('error' in data) {
    info_box('handling_errors', data.error);
  }
  if ('msg' in data) {
    info_box('handling_done', data.msg);
  }
}

$(document).on('reset', 'form', function(){
  var addr = ['region', 'district', 'city', 'locality', 'street', 'drug'];
  $(this).find('input[type="hidden"]').each(function(){
    if ($.inArray($(this).attr('name'), addr) != -1) {
      $(this).removeAttr('value');
    }
  });
  $(this).find('span.my_select_button_value').text('Не выбран');
  $(this).find('.my_select_list >li > a.selected').removeClass('selected');
  $(this).find('.ajax_search_result').html('');
});

$(document).on('click', '.save_str', function(event){
  if (event.target) {
    var trg = event.target;
  } else {
    var trg = window.event.srcElement;
  }
  var form = $(trg).closest("form");
  if (check_fields_req(form)) {
    if (form.find('input[type="file"]').length == 0) {
      form.submit();
    } else {
      var isFile = true;
      form.find('input[type="file"]').each(function(){
        if ($(this).val() == '') {
          isFile = false;
          return false;
        }
      });
      if (isFile) {
        form.attr({
          enctype: 'multipart/form-data',
          method: 'POST',
          action: 'procedures.php',
          target: 'frame'
        });
        form.submit();
        form
          .removeAttr('enctype')
          .removeAttr('method')
          .removeAttr('action')
          .removeAttr('target');
      } else {
        form.submit();
      }
    }
  }
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('click', '.info_box_close', function(){
  $('.info_box')
    .fadeOut(200, function(){ $(this).remove(); });
});


$(function(){
  
  $('.current_object_form').submit(function(){
    if (!$(this).attr('target')) {
      $.ajax({
        url: 'procedures.php',
        dataType: 'json',
        type: 'POST',
        data: $(this).serialize(),
        success: function(resp){
            if ('html' in resp) {
              $.each(resp.html, function(key, value){
                $('.' + key).html(value);
              })
            }
            if ('error' in resp) {
              info_box('handling_errors', resp.error);
            }
            if ('msg' in resp) {
              info_box('handling_done', resp.msg);
            }
          },
        error: function(resp){
            alert(resp);
          }
      });
      return false;
    }
  });
  
  $('.QIWI_request').submit(function(){
    $.post('download_file.php', $(this).serialize(), function(resp){
      if (resp != '') {
        window.location.href = "download_file.php?"+resp;
      }
    });
    return false;
  });
  
});



$(document).on("submit", ".data_form", function(){
  var obj = $(".object_view").attr("object");
  var form = $(this);
  if (check_fields_req(form)) {
    $.getJSON('procedures.php', form.serialize(), function(resp){
      var keysArray = [];
      $.each(resp, function(key, value){
        keysArray.push(key);
      });
      if (($.inArray('msg', keysArray) >= 0) && resp['msg'] != '') {
        info_box('handling_done', resp['msg']);
        form[0].reset();
      }
      if (($.inArray('error', keysArray) >= 0) && resp['error'] != '') {
        info_box('handling_errors', resp['error']);
      }
      if (($.inArray('html', keysArray) >= 0) && resp['html'] != '') {
        $('.related_objects').html(resp['html']);
        form[0].reset();
      }
    });
  }
  return false;
});




$(document).on('change', ".request_date, .request_number, .request_file", function(){
  requirement_add([".request_date", ".request_number"]);
});

$(document).on('change', ".response_date, .response_number_out", function(){
  requirement_add([".response_date", ".response_number_out"]);
});

$(document).on('change', ".crim_case, .crim_case_date", function(){
  requirement_add([".crim_case", ".crim_case_date"]);
});

$(function(){
  $(".susp_date").change(function(){
    var susp_resume_date = $(".susp_resume_date");
    if ($(this).val() != "") {
      susp_resume_date.css("display", "");
    } else {
      susp_resume_date.css("display", "none");
    }
  });
});


$(document).on('click', '.objects_list_block a', function(event){
  var link = $(this);
  var id = $(".object_id input[name='id']").val();
  $(".objects_list_block a").each(
    function(){
      $(this).css("color", "");
    }
  );
  var exec_script = link.attr("id") + ".php?object=" + $("table[class='object_view']").attr("object") + '&id=' + id;
  $.ajax({
    type: "POST",
    url: exec_script,
    success: function(resp) {
      $(".result_data_row").html($.trim(resp));
      link.css("color", "#BE4141");
      $(".datepicker").datepicker();
      $(".tel_num").inputmask("(999)999-99-99");
      $(".time").inputmask("99:99");
    }
  });
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});

$(document).on('click', 'button.markings', function(){
  var win = $(window);
  var markings = $('.markings_list');
  var opacity_back = $('<div class="opacity_back" style="height: '+$(document).height()+'px; display: none;"></div>');
  $('html').append(
    opacity_back.click(function(){
      opacity_back.fadeOut(300, function(){ $(this).remove(); });
      markings.fadeOut(300);
    })
  );
  markings.css({
    top: ((win.height() - markings.outerHeight()) / 2 + win.scrollTop()) + "px", 
    left: ((win.width() - markings.outerWidth()) / 2) + "px" 
  });
  markings
    .keydown(function(I){
      if (I.keyCode == 27) {
        opacity_back.fadeOut(300, function(){ $(this).remove(); });
        markings.fadeOut(300);
      }
    })
    .fadeIn(300);
  opacity_back.fadeIn(300);
  $('button.markings_close')
    .click(function(){
      opacity_back.fadeOut(300, function(){ $(this).remove(); });
      markings.fadeOut(300);
    })
    .focus();
});

$(document).on('change', 'select.drug_type', function(){
  $.ajax({
    url: 'procedures.php',
    dataType: 'json',
    type: 'POST',
    data: {type: $(this).val(), select: 'drug_type'},
    success: function(resp){
      if ('html' in resp) {
        $.each(resp.html, function(key, value){
          $(key).html(value);
        });
        $('div.my_select').my_select();
      }
      if ('error' in resp) {
        info_box('handling_errors', resp.error);
      }
      if ('msg' in resp) {
        info_box('handling_done', resp.msg);
      }
    }
  });
});

$(function(){
  $(".datepicker").datepicker();
});