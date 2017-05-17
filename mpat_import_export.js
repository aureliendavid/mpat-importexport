
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


});


console.log("About to define ctx");

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
