//MAKES THE GHOST MENU APPEAR WHEN THE USER SCROLLS DOWN MORE THAN 200 PIXELS

//Hides the ghost menu when the page loads
$(document).ready(function(){

  $("#ghost-menu").hide();

});

//If the user scrolls down more than 200 pixels the ghost menu will show up. if the user scrolls back up to less than 200 pixels from the top the ghost menu will hide
var apparition = false;

$(window).scroll(function(){

  var scrolled = $(window).scrollTop()>200;

  if (scrolled && !apparition) {

   apparition = true;
   $("#ghost-menu").show(50);

 } else if (apparition && !scrolled){

  apparition = false;
  $("#ghost-menu").hide();

 }

})
