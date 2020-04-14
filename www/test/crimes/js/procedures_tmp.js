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

function check_fields2(form) {
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
      var color = elem.css('background-color');
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

$(document).on('click', '.info_box_close', function(){
  $('.info_box')
    .fadeOut(200, function(){ $(this).remove(); });
})

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


$(function(){
  $('.current_object_form').submit(function(){
    if (!$(this).attr('target')) {
      $.getJSON('procedures.php', $(this).serialize(), function(resp){
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
      });
      return false;
    }
  });
});


$(document).on('click', '.save_str', function(){
  if (event.target) {
    var trg = event.target;
  } else {
    var trg = window.event.srcElement;
  }
  var form = $(trg).closest("form");
  if (check_fields2(form)) {
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
  return false;
});


$(document).on("submit", ".data_form", function(){
  var obj = $(".object_view").attr("object");
  var form = $(this);
  if (check_fields2(form)) {
    var pars = form.serialize();
    $.getJSON('procedures.php', form.serialize(), function(resp){
      var keysArray = [];
      $.each(resp, function(key, value){
        keysArray.push(key);
      });
      if (($.inArray('msg', keysArray) >= 0) && resp['msg'] != '') {
        info_box('handling_done', resp['msg']);
      }
      if (($.inArray('error', keysArray) >= 0) && resp['error'] != '') {
        info_box('handling_errors', resp['error']);
      }
      if (($.inArray('html', keysArray) >= 0) && resp['html'] != '') {
        $('.related_objects').html(resp['html']);
      }
    });
    form[0].reset();
  }
  return false;
});

$(document).on('change', ".request_date, .request_number, .request_file", function(){
  requirement_add([".request_date", ".request_number", ".request_file"]);
});

$(document).on('change', ".response_date, .response_number_out", function(){
  requirement_add([".response_date", ".response_number_out"]);
});

$(document).on('change', ".crim_case, .crim_case_date, .crim_article", function(){
  requirement_add([".crim_case", ".crim_case_date", ".crim_article"]);
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


$(document).on('click', '.objects_list_block a', function(){
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
    }
  });
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
});


$(function(){
  $(".datepicker").datepicker();
});