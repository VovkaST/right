//проверка на число
function isNumeric(n){
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function myTrim(str, charlist) {
  charlist = !charlist ? ' \\s\xA0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\$1');
  var re = new RegExp('^[' + charlist + ']+|[' + charlist + ']+$', 'g');
  return str.replace(re, '');
};

//проверка заполненности полей, чекбоксов
function check_fields(par){
  var form = $(par);
  var error = false;
  var check = false;
  form.find('input[type="file"], input[type="text"], select').each(function() {
    var elem = $(this);
    var color = elem.css("background-color");
    if ((elem.attr("type") == "text") && (this.className.indexOf("datepicker") == -1)) {
      var i = 0;
      while (i < elem.val().split('.').length+1) {
        this.value = this.value.replace(".", "");
        i++;
      }
    }
    if ((elem.attr("type") == "text") && (elem.attr("id") != "kusp_num") && (elem.val().length < 2)) {
      elem.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.animate({backgroundColor:color},200);
      error = true;
    }
    if ((elem.attr("type") == "text") && (elem.attr("id") == "kusp_num") && (elem.val().length < 1)) {
      elem.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.animate({backgroundColor:color},200);
      error = true;
    }
    if (((elem.attr("type") == "file") || (elem.is("select"))) && (elem.val() == '')) {
      elem.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.animate({backgroundColor:color},200);
      error = true;
    }
  });
  if (form.find('input[type="checkbox"]').attr("id") !== undefined) {
      form.find('input[type="checkbox"]').each(function() {
      var elem = $(this);
      if (elem.prop("checked")) {
        check = true;
      }
    });
    if (!check) {
      var elem = form.find('input[type="checkbox"]');
      elem.closest("div").animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
      elem.closest("div").animate({backgroundColor:"transparent"},200);
    } 
  } else {
    check = true;
  }
  if (!error && check) {
    return true;
  } else {
    return false;
  }    
}

//запуск фильтра
function filter(address, user_data, response_place, callback){
  callback = callback || false;
  $.ajax({
    type: "POST",
    data: user_data,
    url: address,
    beforeSend: function(){
      if ((response_place != "") || (response_place != 0)) {
        response_place.empty();
      }
    },
    success: function(response) {
      if (!callback) {
        if ((response_place != "") || (response_place != 0)) {
          response_place.append($.trim(response));
          response_place.slideDown(100);
        }
      } else {
        callback;
      }
    }
  });
}

//плагин добавления ссылки очистки поля
(function($){
  $.fn.clearLink = function(){
    $(this).each(function(i){
      $(this)
       .after(
         $('<span class="clear_link" id="clear_link_'+i+'">&times;</span>')
          .css("display", "none")
          .click(function(){
            $(this).prev().val("");
            $("#clear_link_"+i).hide();
          })
       )
      $(this).bind("change keyup", function(){
        if ($(this).val().length > 0) {
          $(this).next("#clear_link_"+i).css("display", "inline")
        } else {
          $("#clear_link_"+i).hide();
        }
      })
      if ($(this).val().length > 0) {
        $(this).next("#clear_link_"+i).css("display", "inline");
      }
    })
  };
})($);

(function($){
  $.fn.onlyNumbers = function(message){
    function numButton(button) {
      if ((button == 32) || (button >= 65 && button <= 90) || (button >= 106 && button <= 111) || (button >= 186 && button <= 222)) { //[48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105]
        return false;
      } else {
        return true;
      }
    }
    var elem = this;
    elem.keydown(function(){
      if (!numButton(event.keyCode)) {
        //var hintLeft = Math.round(elem.offset().left+elem.outerWidth()+11); //справа
        var hintTop = Math.round(elem.offset().top); //сверху
        //var hintTop = Math.round(elem.offset().top - elem.innerHeight()/2); //справа
        var hintLeft = Math.round(elem.offset().left - (elem.outerWidth()-elem.innerWidth())); //сверху
        if (!$("div").is(".numberHint")) {
          elem.after(
            $('<div class="numberHint">'+message+'</div>')
              .css({"left": hintLeft+"px", "top": (elem.offset().top - $(this).outerHeight()-30)+"px"})
              .fadeIn(200)
          );
        }
        return false;
      } else {
        if ($("div").is(".numberHint")) {
          $(".numberHint")
            .fadeOut(200, function(){ $(this).remove(); })
        }
      }
    });
    elem.focusout(function(){
      if ($("div").is(".numberHint")) {
        $(".numberHint")
          .fadeOut(200, function(){ $(this).remove(); })
      }
      var val = elem.val();
      var newVal = '';
      var i = 0;
      var flag = false;
      while (i < val.length) {
        if (isNumeric(val.charAt(i))) {
          newVal += val.charAt(i);
          flag = true;
        } else {
          if (flag) {
            break;
          }
        }
        i++;
      }
      elem.each(function(){
        elem.val(newVal);
      });
    });
  }
})(jQuery);


(function($){
  $.fn.onlyLetters = function(message, parts, wordList){
    function numLetters(button) {
      if ((button >= 48 && button <= 57) || (button >= 96 && button <= 105) || (button >= 106 && button <= 109) || (button >= 110 && button <= 111) || (button >= 186 &&  button <= 192) || (button >= 219 &&  button <= 221)) { //[цифры, знаки препинания]
        if ((button == 186) || (button == 188) || (button == 190) || (button == 219) || (button == 221)) {
          if (button == 186) return 'ж';
          if (button == 188) return 'б';
          if (button == 190) return 'ю';
          if (button == 219) return 'х';
          if (button == 221) return 'ъ';
        }
      } else {
        return true;
      }
    }
    function hintHide() {
      if ($("div").is(".numberHint")) {
        $(".numberHint")
          .fadeOut(200, function(){ $(this).remove(); })
      }
    }
    var elem = this;
    elem.keydown(function(){
      if (!numLetters(event.keyCode)) {
        //var hintLeft = Math.round(elem.offset().left+elem.outerWidth()+11); //справа
        var hintTop = Math.round(elem.offset().top); //сверху
        //var hintTop = Math.round(elem.offset().top - elem.innerHeight()/2); //справа
        var hintLeft = Math.round(elem.offset().left - (elem.outerWidth()-elem.innerWidth())); //сверху
        if (!$("div").is(".numberHint")) {
          elem.after(
            $('<div class="numberHint">'+message+'</div>')
              .css({"left": hintLeft+"px", "top": (elem.offset().top - $(this).outerHeight()-30)+"px"})
              .fadeIn(200)
          );
        }
        return false;
      } else {
        hintHide();
      }
    });
    elem.focusout(function(){
      hintHide();
      var val = myTrim(this.value.toLowerCase());
      val = val.replace(/[\da-zA-Z\_\-\$\#\*\/\+\.\;\:\"\'\,\{\}\(\)\\]/g, '');
      val = val.replace(/\s{2,}/g, ' ');
      if (!wordList) {
        var wrongWords = ["нет", "отсут", "устан", "неустан", "не устан", "н/у", "ну", "нз", "хз", "неизве", "работ"];
      } else {
        var wrongWords = wordList;
      }      
      for (var i = 0; i < wrongWords.length; i++) {
        if (val.indexOf(wrongWords[i]) == 0) {
          val = '';
        }
      }
      val = myTrim(val);
      var splitStr = val.split(" ");
      if (splitStr.length > parts) {
        val = '';
        for (var i = 0; i < parts; i++) {
          val += splitStr[i];
          if (i != parts-1) val += ' ';
        }
      }
      this.value = val;
    });
  }
})($);



//плагин проверки корректировки даты
(function($){
  $.fn.dateFormat = function(){
    var elem = $(this);
    function check_year(year){
      var cur_year = String(new Date().getFullYear());
      if (year.length == 2) { // если год в 2 знака
        if (parseFloat(cur_year.substr(0, 2)+year) > cur_year) { // если год с подставленными первыми знаками текущего года больше текущего года
          year = cur_year.substr(0, 2)-1+year; // прибавляем 2 цифры годов прошлого века
        } else {
          year = cur_year.substr(0, 2)+year; // иначе прицепляем первые 2 цифры текущего года
        }
      }
      if (year.length == 3) {
        if (parseFloat(cur_year.substr(0, 1)+year) > cur_year) {  // если год с подставленным первым знаком текущего года больше текущего года
          year = cur_year.substr(0, 1)-1+year; // прибавляем 1 цифру прошлого тысячелетия
        } else {
          year = cur_year.substr(0, 1)+year; // иначе прицепляем первую цифру текущего года
        }
      }
      if (year.length == 1) {
        year = "1900";
      }
      return year;
    }
    function check_month(month){
      if (month > 12) { // если месяц больше 12
        month = 12; // присваиваем ему значение 12
      };
      if (month.length == 1) { //если месяц состоит из одной цифры
        month = "0"+month; // добавляем перед ней 0
      }
      if (month == 0) { // если он равен 0
        month = "01"; // ставим 01
      }
      return month;
    }
    function check_day(day, month, year){
      if ((year % 4 == 0) && (year % 100 != 0 || year % 400 == 0)) { // проверяем не високосный ли год
        var feb = 29;
      } else {
        var feb = 28;
      }
      var array = ["",31,feb,31,30,31,30,31,31,30,31,30,31]; // число дней в месяцах
      if (day > array[parseFloat(month)]) { // если дней больше, чем календарных
        day = array[parseFloat(month)] // берем последний день месяца
      }
      if (day.length == 1) { //если месяц состоит из одной цифры
        day = "0"+day; // добавляем перед ней 0
      }
      if (day.length == 0) {
        day = "01";
      }
      return day;
    }
    elem.keydown(function(){
      var nums = [48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105]; // коды цифровых клавиш
      var text = this.value; // значение
      var len = text.length; // длина значения
      var btn = event.keyCode; // код нажатой клавиши
      if (len > 9 && $.inArray(btn, nums) != -1) { // ограничиваем длину ввода 9 симолами
        return false;
      } else {
        if (text.length == 0 && $.inArray(btn, [110,190]) != -1) { // если строка пустая, запрещаем точку
          return false;
        } else {
          if (text.charAt(text.length-1) == "." && $.inArray(btn, [110,190]) != -1) { // запрещаем последовательный ввод двух точек
            return false;
          } else {
            if (text.split(".").length == 3) { // если дата состоит из 3 частей через точку
              if (len > 9 && $.inArray(btn, nums) != -1) { // если длина больше 9 символов и нажимаются цифры
                return false; // ничего не делаем
              }
            }
            if (text.split(".").length == 2) {
              if (len > 4 && $.inArray(btn, nums) != -1) {
                return false;
              }
            }
            if (text.split(".").length == 1) {
              if (len > 7 && $.inArray(btn, nums) != -1) {
                return false;
              }
            }
          }
        }
      }
    });
    elem.focusout(function(){
      var text = this.value; // значение поля
      var parts = text.split(".").length; //количество частей, разделенный точкой
      if (parts < 4) { // если частей меньше 4
        if (parts == 3) { // если дата состоит из 3 частей
          var year = check_year(text.split(".")[2]); // день
          var month = check_month(text.split(".")[1]); // месяц
          var day = check_day(text.split(".")[0], month, year); // год
          if (text !== day) {
            elem.val(day+"."+month+"."+year); // записываем в поле
          }
        }
        if (parts == 2) {
          elem.val(""); // очищаем поле
        }
        if (parts == 1) {
          if (text.length == 6) { // если строка из 6 символов
            var year = check_year(text.substr(4, 2));
            var month = check_month(text.substr(2, 2));
            var day = check_day(text.substr(0, 2), month, year);
            elem.val(day+"."+month+"."+year);
          } else {
            elem.val("");
          }
        }
      } else {
        elem.val("");
      }
    });
  }
})($);


//форма выбора ОВД и службы
$(function(){
  $(".sel_service").click(function(){
    $("#service").val(this.id);
    $("#sel_service_form").submit();
  });
});

//--------форма поиска отказных--------
//автозаполнение поля ОВД из основной формы
$(function(){
  $("#yours_ovd").change(function(){
    $("#search_reg #ovd_otkaz option[value = "+$(this).val()+"]").attr("selected", true);
  });
});

//вызов формы поиска
$(function(){
  $("#refusal_view_upload_sel #search").click(function(e){
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
    $("#search_block").css({"left": e.pageX+"px", "top": e.pageY-100+"px"}).fadeIn(200);
  });
});

//баян разделов
$(function(){
  $(".search_list h3").click(function(){
    var search_face = $("#search_face");
    var search_reg = $("#search_reg");
    $(".search_list h3").attr("style", "");
    if (this.id == "reg_head") {
      if (search_reg.css("display") != "none") {
        search_reg.slideUp(200);
      } else {
        $(this).css("background", "url(http://.../images/White_background.png)");
        search_reg.slideDown(200);
        if (search_face.css("display") != "none") {
          search_face.slideUp(200);
        }
      }
    } else {
      if (search_face.css("display") != "none") {
        search_face.slideUp(200);
      } else {
        $(this).css("background", "url(http://.../images/White_background.png)");
        search_face.slideDown(200);
        if (search_reg.css("display") != "none") {
          search_reg.slideUp(200);
        }
      }
    }
  });
});

//поиск
$(function(){
  $("#start_search").click(function(){
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
    if ($("#search_block #search_reg").css("display") == "block") {
      var form = $("#search_reg");
    } 
    if ($("#search_block #search_face").css("display") == "block") {
      var form = $("#search_face");
    }
    if (form) {
      form.submit();
    }
  });
});
$(function(){
  $("#search_block #search_reg, #search_block #search_face").submit(function(){
    var form = $(this);
    $(".response_place").slideUp(100, function(){
      filter("search.php", form.serialize(), $(".response_place"));
    });
    return false;
  });
});

//--------закрыть форму поиска отказных--------
$(document).click(function(event){
  if ($(event.target).closest("#search").length || $(event.target).closest("#search_block").length) {
    if ($(event.target).closest("#start_search").length == 0) {
      return
    } else {
      $("#search_block").fadeOut(200);
      event.stopPropagation();
    }
  } else {
    $("#search_block").fadeOut(200);
    event.stopPropagation();
  }
});
$(function(){
  $("#close_link").click(function(){
    $(this).parent().fadeOut(200);
  });
});
//--------закрыть форму поиска отказных--------
//--------форма поиска отказных--------


//просмотр зарегистрированных
$(function(){
  $("#refusal_view_upload_sel #view").children().click(function(){
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  var ovd = $("#yours_ovd");
  var response_place = $(".response_place");
    if (ovd.val() !== "") {
      response_place.slideUp(100, function(){
        filter("search.php?id_ovd="+ovd.val(), "", response_place);
      });
    } else {
      response_place.slideUp(200, function(){
        response_place.empty();
        response_place.append('<div class="error" id="error_main">Не выбран ОВД!</div>');
        response_place.slideDown(200);
      });
    }
  });
});

//клики по листалке
$(document).on("click", ".response_place .listing a", function(){
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  var elem = $(this);
  $(".response_place").slideUp(200, function(){
    filter(elem.attr("href"), "", $(".response_place"));
  });
});


$(document).on('click', '.add_str, .save_str', function(){
  var form = $(event.target).closest("form");
  if (form.attr("id") != "upload_form") {
    if (form.attr('class') != 'current_object_form') {
      form.submit();
    }
  } else {
    uploadFile();
  }
  return false;
});


function uploadFile(){
  var form = $("#upload_form");
  var link = $("#upload_form .add_str");
  var frame = $("#frame");
  var file_form = $("#file_form");
  if (check_fields(form)) {
    file_form.children().remove();
    file_form
      .css("display", "block")
      .append('<img id="wait" src="http://.../images/ajax-loader.gif"/>');
    form.submit();
    var answer = '';
    var tag = '';
    var interval = setInterval(function(){
      tag = frame.contents().find("div");
      if (tag.attr("id") == "file" || tag.attr("id") == "error") {
        file_form.children().remove();
        if (tag.attr("id") == "file") {
          file_form
            .append(
              '<form id="uploaded_file" enctype="multipart/form-data" method="POST">'+
                'Файл: <b>'+tag.html()+'</b>'+
                '<label class="del_str"><input type="image" src="#" class="del" /><span class="del_str_but"><strong>&times;</strong>Удалить</span></label>'+
              '</form>'
            );
          link
            .removeAttr("class")
            .addClass("add_str_dis")
            .children("input[type=image]")
              .attr("src", "http://.../images/plus_disabled.png")
              .prop("disabled", true);
          $("#UpLoadFile")
            .prop("disabled", true)
            .css({backgroundColor: "transparent", color: "transparent"});
        } else {
          file_form
            .append('<div class="error" id="error_file">'+tag.html()+'</div>');
        }
        clearInterval(interval);
      }
      tag = '';
    }, 500);
    form[0].reset();
  }
}

  
//удаление загруженного файла
$(document).on("submit", "#uploaded_file", function(){
  $.ajax({
    type: "POST",
    url: "delete_uploaded_file.php",
    success: function(resp) {
      var file_form = $("#file_form");
      var fileField = $("#UpLoadFile");
      var link = $("#upload_form > .add_str_dis");
      var send_file = $("#send_file");
      file_form.children().remove();
      //fileField.attr("disabled", false).css("color", "black");
      link.children("input[type=image]").attr("src", "http://.../images/plus.png").prop("disabled", false);
      fileField.css({backgroundColor: "transparent", color: "black"}).prop("disabled", false);
      link.removeAttr("class");
      link.addClass("add_str");
      file_form.append(resp).fadeOut(1000);
      var interval = setInterval(function(){
        file_form.children().remove();
        clearInterval(interval);
      }, 1000);
    }
  });
  return false;
});
  
//добавление полей сотрудника и основания
  $(function() {
    $(document).on("change blur", "#form_kusp input, #form_kusp select", function(){
      $("#form_kusp").submit();
    });
  });
  //по БВП
  $(function(){
    $("#bp").change(function(){
      $("#form_kusp").submit();
      var field = $(this);
      var criminal_add = $("#criminal_add");
      if(field.prop("checked")){
        criminal_add.slideUp(200);
      } else {
        criminal_add.slideDown(200);
      }
    });
  });
  $(function(){
    $("#form_kusp").submit(function(){
    $("#date_error").remove();
    var form = $(this);
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: form.serialize(),
        success: function(response){
          if (($.trim(response) == 'Вводимая дата не может быть больше текущей') || ($.trim(response) == 'Дату необходимо вводить в формате "00.00.0000"')) {
            $("#form_kusp").after('<div class="error" id="date_error">'+$.trim(response)+'</div>');
          }
        }
      });
      return false;
    });
  });

  
//добавление КУСП
  $(function(){
    $("#add_kusp_form").submit(function(){
      $("#kusp_error").remove();
      if (check_fields(this)) {
        var val1 = $("#datepicker_1").val();
        var val2 = $("#datepicker_2").val();
        var regDate = new Date(val1.split(".")[2], val1.split(".")[1]-1, val1.split(".")[0]);
        var kuspDate = new Date(val2.split(".")[2], val2.split(".")[1]-1, val2.split(".")[0]);
        if (kuspDate <= regDate) {
          var added_kusp = $(".added_kusp");
          var form = $(this);
          $.ajax({
            type: "GET",
            url: "otkaz_send.php",
            data: form.serialize(),
            success: function(response) {
              $("#added_kusp_list").remove();
              $(".added_kusp").append($.trim(response));
            }
          });
          form[0].reset();
        }
        if (kuspDate > regDate) {
          $(".added_kusp").after('<div class="error" id="kusp_error">Дата КУСП не может быть больше даты вынесения решения!</div>');
        }
      }
      return false;
    });
  });
  //удаление КУСП
  $(document).on("submit", ".kusp_delete", function(){
    var kusp = $(this);
    $.ajax({
      type: "POST",
      url: "otkaz_send.php",
      data: {kusp_delete: kusp.attr("id")},
      success: function(response) {
        $("#added_kusp_list, #kusp_error").remove();
        $(".added_kusp").append($.trim(response));
      }
    });
    return false;
  });
  
  //добавление статей
  $(function(){
    $("#criminal_st_add").submit(function(){
      if(check_fields(this)) {
        $.ajax({
          type: "POST",
          url: "otkaz_send.php",
          data: $(this).serialize(),
          success: function(response){
            $("#criminal_added, #criminal_error").remove();
            $("#criminal_add").append($.trim(response));
          }
        });
      }
      $("#criminal_st_add")[0].reset();
      return false;
    });
  });
  //удаление статей
  $(document).on("submit", ".criminal_added_st_form", function(){
    var criminal = $(this);
    $.ajax({
      type: "POST",
      url: "otkaz_send.php",
      data: {criminal_delete: criminal.attr("id")},
      success: function(response){
        $(".criminal_added_st_form, #criminal_added div").remove();
        $("#criminal_added").append($.trim(response));
      }
    });
    return false;
  });
  
function checkClassEnable(classList) {
  var classArray = [];
  var prop = true;
  if ($.isArray(classList)) {
    classArray = classList;
  } else {
    classArray[0] = classList;
  }
  for (var i in classArray) {
    classArray[i].each(function(){
      if ($(this).prop("checked") == true) {
        prop = false;
      }
    });
  }
  return prop;
}

//чекбоксы заявителей, потерпевших, БВП 
$(function(){
  $('.declarer, .victim').change(function(){
    if (!checkClassEnable([$(".declarer"), $(".victim")])) {
      $("#anonymous").prop("disabled", true);
    } else {
      
      $("#anonymous").prop("disabled", false);
    }
    if ($("#av_declarer").prop("checked") || $("#av_victim").prop("checked")) {
      $(".missing").prop("disabled", true);
    } else {
      $(".missing").prop("disabled", false);
    }
  });
});

$(function(){
  $(".missing").change(function(){
    if ($(this).prop("checked")) {
      $("#av_declarer").prop("disabled", true);
      $("#av_victim").prop("disabled", true);
    } else {
      if (($("#decl_emp").prop("checked") == false) && ($("#anonymous").prop("checked") == false)) {
        $("#av_declarer").prop("disabled", false);
      }
      if ($("#anonymous").prop("checked") == false) {
        $("#av_victim").prop("disabled", false);
      }
    }
  });
});

$(function(){
  $("#anonymous").change(function(){
    if ($("#anonymous").prop("checked")) {
      $("#decl_emp").prop("disabled", true);
      $(".declarer").prop("disabled", true);
      $(".victim").prop("disabled", true);
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: {anonymous: 1}
      });
    } else {
      $("#decl_emp").prop("disabled", false);
      if (($("#av_missing").prop("checked") == false) && ($("#decl_emp").prop("checked") == false)) {
        $("#av_declarer").prop("disabled", false);
      }
      if ($("#av_missing").prop("checked") == false) {
        $("#av_victim").prop("disabled", false);
      }
      
      if ($("#decl_emp").prop("checked") == false) {
        $("#av_org_declarer").prop("disabled", false);
      }
      $("#av_org_victim").prop("disabled", false);
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: {anonymous: 0}
      });
    }
  });
});

$(function(){
  $("#decl_emp").change(function(){
    var declarer = $(".declarer");
    if ($(this).prop("checked")) {
      $("#anonymous").prop("disabled", true);
      declarer.prop("disabled", true);
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: {decl_emp: 1}
      });
    } else {
      $("#anonymous").prop("disabled", false);
      if (($("#anonymous").prop("checked") == false) && ($("#av_missing").prop("checked") == false)) {
        $("#av_declarer").prop("disabled", false);
        $("#av_victim").prop("disabled", false);
      }
      if ($("#anonymous").prop("checked") == false) {
        $("#av_org_declarer").prop("disabled", false);
        $("#av_org_victim").prop("disabled", false);
      }
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: {decl_emp: 0}
      });
    }
  });
});
  
//добавление заявителей, потерпевших, БВП
$(function(){
  $("#add_applicant_victim, #add_av_org").submit(function(){
    var added_av = $(".added_av");
    if(check_fields(this)) {
    var form = $(this);
      $.ajax({
        type: "POST",
        url: "otkaz_send.php",
        data: form.serialize(),
        success: function(response){
          added_av.children().remove();
          added_av.append($.trim(response));
          form[0].reset();
          form.find('input[type="checkbox"]').attr("disabled", false);
        }
      });
    }
    return false;
  });
});
  
  
  //удаление заявителей, потерпевших, БВП
  $(document).on("submit", ".added_av_str, .v_org_added", function(){
    var face = $(this);
    if (this.className == "v_org_added") {
      //var Class = this.className;
      var data = {org_delete: face.attr("id")};
    }
    if (this.className == "added_av_str") {
      //var Class = this.className;
      var data = {face_delete: face.attr("id")};
    }
    $.ajax({
      type: "POST",
      url: "otkaz_send.php",
      data: data,
      success: function(response){
        $('.added_av').children().remove();
        var added_av = $(".added_av");
        added_av.append($.trim(response));
        /*if (Class == "v_org_added") {
          $(".v_org_added").remove();
          $("#av_org").attr("disabled", false).css("color", "black");
          var attr = {
            src: "http://.../images/plus.png",
            disabled: false
          };
          $("#add_av_org #add_dv").attr(attr).css("cursor", "pointer");
          $("#add_av_org .add_str").append('<span class="add_str_but">Добавить</span>');
        }*/
      }
    });
    return false;
  });
  
  //добавление причастных лиц
  $(function(){
    $("#add_offender").submit(function(){
      if(check_fields(this)) {
      var form = $(this);
        $.ajax({
          type: "POST",
          url: "otkaz_send.php",
          data: form.serialize(),
          success: function(response){
            var added_av = $(".added_offender");
            added_av.children().remove();
            added_av.append($.trim(response));
            form[0].reset();
            form.find('input[type="checkbox"]').attr("disabled", false)
          }
        });
      }
      return false;
    });
  });
  //удаление заявителей, потерпевших, БВП
  $(document).on("submit", ".added_offender_str", function(){
    var face = $(this);
    $.ajax({
      type: "POST",
      url: "otkaz_send.php",
      data: {offender_delete: face.attr("id")},
      success: function(response){
        var added_offender = $(".added_offender");
        added_offender.children().remove();
        added_offender.append($.trim(response));
      }
    });
    return false;
  });
  
  //сформировать файл отказного
  $(function(){
    $("#reg_button").click(function(){
      $('body, html').animate({scrollTop:0},400);
      $.ajax({
      type: "POST",
      url: "otkaz_send.php",
      data: {reg: 1},
      beforeSend: function(){
        $("#error_main").remove();
      },
      success: function(response){
          var response = $.trim(response);
          if (!isNumeric(response)) {//если не число
            $(".header").after('<div class="error" id="error_main">'+response+'</div>');
          } else {
            $('body').append('<div id="opacity_back" class="opacity_back" style="height: '+$(document).height()+'px;"></div> \
              <div id="confirm_form" class="confirm_box"> \
              <h3>Регистрационный номер электронного документа:</h3> \
              <span id="reg_otkaz">'+response+'</span> \
              <span id="reg_otkaz_prim">Данный номер необходимо указать на титульном листе отказного материала.</span> \
              <input type="button" value="Ok" id="reg_otkaz_confirm"/> \
              </div> \
              ');
            $('#error_record_confirm').focus();
          }
        }
      });
    });
  });
  //перенаправление с формы
  $(document).on("click", "#reg_otkaz_confirm", function(){
    location.replace('http://.../refusal/refusal_view_upload.php');
  });
 

$(document).on("click", ".download_link", function(event){
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  //alert("http://.../refusal/download_file.php?id="+$(this).attr("id"));
  window.location.href = "http://.../refusal/download_file.php?id="+$(this).attr("id");
});

//-------- окно подтверждения удаления отказного --------//
$(document).on("click", ".delete_link", function(event){
  event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  $("#msg_box").remove();
  var elem = $(this).closest("a");
  elem.after('<form id="msg_box" method="POST"> \
      <img class="attention_img" src="http://.../images/error_record.png" height="40" width="40"> \
      <span class="msg_txt"><strong>Вы действительно хотите удалить этот материал?</strong></span> \
      <ul class="reason"> \
        <li> \
          <span style="display: block;">Причина:</span>  \
          <textarea rows="2" name="reason" id="reason"></textarea>\
        </li> \
        <li class="deleter_str"> \
          Сотрудник: <input type="text" name="deleter" id="deleter"/>  \
          <input type="hidden" value="'+$(this).attr("id")+'" name="id"/>\
        </li> \
      </ul> \
      <div class="button_box"> \
        <input type="button" value="Да" id="yes"/> \
        <input type="button" value="Нет" id="no"/> \
      </div> \
    </form> \
  ');
  var msg_box = $("#msg_box");
  var height = msg_box.outerHeight();
  var width = msg_box.outerWidth();
  msg_box.css({"left": event.pageX-(width*0.75)+"px", "top": event.pageY-height+"px"}).fadeIn(200);
  $("#msg_box #reason").focus();
});
//-------- окно подтверждения удаления отказного --------//
$(document).on("click", "#msg_box .button_box input", function(){
  var elem = $(this);
  var id = elem.attr("id");
  if(id == "no") {
    $("#msg_box").fadeOut(200);
  }
  if (id == "yes") {
    var index = elem.parents("tr").index();
    var str = $("#refusal_view_table tr").slice(index-1, index+1);
    var form = $("#msg_box");
    var reason = $("#reason");
    var reason_l = reason.val().length;
    var emp = $("#deleter");
    var emp_l = emp.val().length;
    if (reason_l >= 10 && emp_l >= 5) {
      $.ajax({
        type: "POST",
        url: "error_mark.php",
        data: form.serialize(),
        beforeSend: function(){
            form.fadeOut(200);
          },
        success: function(){
            str.fadeOut(200, function(){
              $(this).remove();
            });
          },
        error: function(error){
          alert(error);
        }
      });
    } else {
      if (reason_l < 10) {
        reason.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
        reason.animate({backgroundColor:"transparent"},200, function(){$(this).removeAttr("style");});
      }
      if (emp_l < 5) {
        emp.animate({backgroundColor:"rgba(255,66,45,0.5)"},200);
        emp.animate({backgroundColor:"transparent"},200, function(){$(this).removeAttr("style");});
      }      
    }
  }
});
//-------- окно подтверждения удаления отказного --------//
//-------- баян документов --------//
$(function(){
  $(".document").hover(function(){
    var elem = $(this);
    var document = elem.children(".document");
    if (document.css("display") === "none") {
      document.slideDown(200);
    } else {
      document.slideUp(200);
    }
  });
});
//-------- баян документов --------//

//-------- перенаправление на ввод долга --------//
$(function(){
  $('.update_link_cell .add_report').click(function(){
    var elem = $(this);
    $.ajax({
      type: 'POST',
      url: 'otkaz_send.php',
      data: {debt_id: elem.attr('id')},
      beforeSend: function(){
        elem.replaceWith('<img id="par_send" src="http://.../images/ajax-loader.gif"/>');
      },
      success: function(){
        var interval = setInterval(function(){
          window.location = 'http://.../refusal/upload.php';
          clearInterval(interval);
        }, 600);
      }
    });
    event.preventDefault ? event.preventDefault() : (event.returnValue = false);
  });
});
//-------- перенаправление на ввод долга --------//

$(function(){
  $(".datepicker").datepicker();
});

$(function(){
  $('#sel_service_form select, #search_reg select, #search_reg input:not([type="checkbox"]), #search_face input').clearLink();
});
















