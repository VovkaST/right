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