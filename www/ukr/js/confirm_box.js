function close_confirm_box() {
    var back = document.getElementById("opacity_back");
    var form = document.getElementById("confirm_form");
    document.body.removeChild(back);
    document.body.removeChild(form);
}