FormDataWorkable = true;
try {
  new FormData();
}
catch (error) {
  var FormDataWorkable = false;
}
var block_form_wth_pb = getElementsByClass('block_form_with_progressbar');

for (var i = 0; i < block_form_wth_pb.length; i++) {
  var form = block_form_wth_pb[i].getElementsByTagName('form')[0];
  if (form) {
    form.setAttribute('order', i);
    if (!getElementsByClass('added_files_list', block_form_wth_pb[i]).length) {
      var response_place = document.createElement('div');
      addClass(response_place, 'response_place');
      block_form_wth_pb[i].appendChild(response_place);
    }
    var hidden = document.createElement('input');
    hidden.setAttribute('type', 'hidden');
    hidden.setAttribute('class', 'itemIdInput');
    hidden.setAttribute('name', 'item_id');
    form.appendChild(hidden);
    
    if (FormDataWorkable) {
      form.onsubmit = function(){
        var form = this;
        var order = form.getAttribute('order');
        var item = getRandom();
        var response_place = getElementsByClass('response_place', block_form_wth_pb[order])[0];
        var itemIdInput = getElementsByClass('itemIdInput', form)[0].setAttribute('value', item);
        var input = document.getElementsByClassName('files_input')[0];
        
        var filesArr = new Array();
        for (var f = 0; f < input.files.length; f++) {
          filesArr[f] = {
                          name: input.files[f].name, 
                          size: input.files[f].size, 
                          sizeStr: (input.files[f].size + '').replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1&thinsp;')
                        };
        }
        
        var hidden = form.querySelector('input[name="browser"]');
        if (!hidden) {
          hidden = document.createElement('input');
          hidden.setAttribute('type', 'hidden');
          hidden.setAttribute('name', 'browser');
          form.appendChild(hidden);
          hidden.setAttribute('value', 'good');
        }

        if (!getElementsByClass('added_files_list', response_place).length) {
          var added_files_list = document.createElement('div');
          addClass(added_files_list, 'added_files_list');
          added_files_list.innerHTML = '<table rules="none" border="0" width="100%" cellpadding="5" cellspacing="0" cols="3"> <tr><th>Файл</th><th width="250px">Прогресс</th><th width="230px">Статус</th></tr></table>'
          response_place.appendChild(added_files_list);
        }
        var FilesList = response_place.getElementsByClassName('added_files_list')[0].getElementsByTagName('table')[0];
        
        for (var f = 0; f < filesArr.length; f++) {
          var row = document.createElement('tr');
          var cell_1 = document.createElement('td');
          var cell_2 = document.createElement('td');
          var cell_3 = document.createElement('td');        

          addClass(row, 'item');
          addClass(cell_1, 'file_info_block');
          addClass(cell_2, 'progress_block');
          addClass(cell_3, 'status_block');
          row.setAttribute('id', item);
          cell_3.setAttribute('align', 'center');
          cell_1.innerHTML = ' \
            <span class="name"><b>Файл:</b> ' + filesArr[f].name + '</span> \
            <span class="size"><b>Размер:</b> ' + filesArr[f].sizeStr + ' байт</span> \
          ';
          cell_2.innerHTML = '<div class="progress_bar"><div class="progress"></div><span class="proc">0%</span></div>';
          cell_3.innerHTML = '<i>Загружаю...</i>';

          FilesList.appendChild(row);
          row.appendChild(cell_1);
          row.appendChild(cell_2);
          row.appendChild(cell_3);
        }

        var eprog = document.getElementsByClassName('progress');
        var eproc = document.getElementsByClassName('proc');
        xhr = getXMLHttpRequest();

        xhr.upload.onprogress = function(e) {
          var proc = Math.round((e.loaded / e.total) * 100);
          eprog[0].style.width = proc + '%';
          eproc[0].innerHTML = proc + '%';
        };

        xhr.onreadystatechange = function(){
          if (this.readyState == 4) {
            var resp = JSON.parse(this.responseText);
            if ('html' in resp) {
              $.each(resp.html, function(key, value){
                cell_3.innerHTML = value;
              });
              delete resp.html;
            }
            json_response_handling(resp);
            clearInterval(timer);
          }
        }

        //var fdata = new FormData(form);
        /*
        timer = setInterval(function(){
          if ()
        }, 100);
        */
        for (var f = 0; f < input.files.length; f++) {
          xhr = getXMLHttpRequest();

          xhr.upload.onprogress = function(e) {
            var proc = Math.round((e.loaded / e.total) * 100);
            eprog[f].style.width = proc + '%';
            eproc[f].innerHTML = proc + '%';
          };

          xhr.onreadystatechange = function(){
            if (this.readyState == 4) {
              if (this.responseText != "") {
                var resp = JSON.parse(this.responseText);
                if ('html' in resp) {
                  $.each(resp.html, function(key, value) {
                    cell_3.innerHTML = value;
                  });
                  delete resp.html;
                }
                json_response_handling(resp);
              }
             //clearInterval(timer);
            }
          }
          var fdata = new FormData();
          fdata.append('files', input.files[f]);
          xhr.open('POST', 'procedures.php', true);
          xhr.send(fdata);
          xhr = null;
        }
        return false;    
        
        
      };
    } else {
      
      form.onsubmit = function() {
        var form = this;
        var order = form.getAttribute('order');
        var response_place = getElementsByClass('response_place', block_form_wth_pb[order])[0];
        var item = getRandom();
        var itemIdInput = getElementsByClass('itemIdInput', form)[0].setAttribute('value', item);
        
        var hidden = form.querySelector('input[type="browser"]');
        if (!hidden) {
          hidden = document.createElement('input');
          hidden.setAttribute('type', 'hidden');
          hidden.setAttribute('name', 'browser');
          form.appendChild(hidden);
        }
        hidden.setAttribute('value', 'shitty');
        
        if (!getElementsByClass('added_files_list', response_place).length) {
          var added_files_list = document.createElement('div');
          addClass(added_files_list, 'added_files_list');
          added_files_list.innerHTML = '<table rules="none" border="0" width="100%" cellpadding="5" cellspacing="0" cols="3"> <tr><th>Файл</th><th width="250px">Прогресс</th><th width="230px">Статус</th></tr></table>'
          response_place.appendChild(added_files_list);
        }
        
        var FilesList = getElementsByClass('added_files_list', response_place)[0].getElementsByTagName('table')[0];
        var row = document.createElement('tr');
        var cell_1 = document.createElement('td');
        var cell_2 = document.createElement('td');
        var cell_3 = document.createElement('td');

        addClass(row, 'item');
        addClass(cell_1, 'file_info_block');
        addClass(cell_2, 'progress_block');
        addClass(cell_3, 'status_block');
        row.setAttribute('id', item);
        cell_3.setAttribute('align', 'center');
        cell_1.innerHTML = ' \
          <span class="name"><b>Файл:</b> ... </span> \
          <span class="size"><b>Размер:</b> ... </span> \
        ';
        cell_2.innerHTML = wait_block;
        cell_3.innerHTML = '<i>Загружаю...</i>';

        row.appendChild(cell_1);
        row.appendChild(cell_2);
        row.appendChild(cell_3);
        FilesList.appendChild(row);
      }
    }
  }
}