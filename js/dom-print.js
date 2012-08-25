
function print(elem, msg) {
  document.getElementById(elem).innerHTML = msg;

  return;
}

function printPlus(elem, msg) {
  document.getElementById(elem).innerHTML = msg + document.getElementById(elem).innerHTML;

  return;
}
