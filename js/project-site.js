//MAKES THE GHOST MENU APPEAR WHEN THE USER SCROLLS DOWN

window.onscroll = function() {ghostmenu2()}

var header = document.getElementById("ghost-menu");

var sticky = header.offsetTop;

function ghotsmenu2(){

  if(sticky > 100){

   alert("good one");

  }

}

function ghostmenu(){

  if (window.pageYOffset > sticky) {

    header.classList.add("apparition");

  } else {

    header.classList.remove("apparition");

  }

}
