
document.addEventListener("DOMContentLoaded", function(){

  document.getElementById("page-btn").onclick = function() {
      document.getElementById("page-fileinput").click();
  }
  document.getElementById("page-fileinput").onchange = function() {
      document.getElementById("page-form").submit();
      document.getElementById("page-fileinput").value = "";
  }
  document.getElementById("layout-btn").onclick = function() {
      document.getElementById("layout-fileinput").click();
  }
  document.getElementById("layout-fileinput").onchange = function() {
      document.getElementById("layout-form").submit();
      document.getElementById("layout-fileinput").value = "";
  }

  var checkboxes = document.getElementsByName("pageid[]");
  for (var i = 0; i < checkboxes.length; i++) {

    checkboxes[i].onchange = function() {

      var shouldDisable = true;
      var ch = document.getElementsByName("pageid[]");
      for (var i = 0; i < ch.length; i++) {
        if (ch[i].checked) {
          shouldDisable = false;
          break;
        }
      }

      document.getElementById("btn-exportpages").disabled = shouldDisable;
      document.getElementById("btn-addmedia").disabled = shouldDisable;
      document.getElementById("btn-exportlayouts").disabled = shouldDisable;

    };
  }


});


function fireChange(e) {

  if (document.createEvent) {
    event = document.createEvent("HTMLEvents");
    event.initEvent("change", true, true);
  } else {
    event = document.createEventObject();
    event.eventType = "change";
  }

  event.eventName = "change";

  if (document.createEvent) {
    e.dispatchEvent(event);
  } else {
    e.fireEvent("on" + event.eventType, event);
  }

}

function checkAll(ele) {
     var checkboxes = document.getElementsByName("pageid[]")
     if (ele.checked) {
         for (var i = 0; i < checkboxes.length; i++) {
             if (checkboxes[i].type == 'checkbox') {
                 checkboxes[i].checked = true;
                 fireChange(checkboxes[i]);
             }
         }
     } else {
         for (var i = 0; i < checkboxes.length; i++) {
             if (checkboxes[i].type == 'checkbox') {
                 checkboxes[i].checked = false;
                 fireChange(checkboxes[i]);
             }
         }
     }
 }


function getCtx(id) {

  var c = document.getElementById('canvas-'+id);
  return c.getContext('2d');

}

function drawComponent(ctx, x, y, w, h, text="") {

  ctx.fillStyle = '#333';
  ctx.fillRect(x, y, w, h);

  if (text != "") {

    ctx.textBaseline = 'middle';
    ctx.font = '11px Arial';
    ctx.fillStyle = 'red';
    textX = x+w/2 - ctx.measureText(text).width/2;
    textY = y+h/2;
    ctx.fillText(text, textX, textY);

  }



}
