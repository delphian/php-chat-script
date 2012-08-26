
function print(elem_id, msg) {
  elem = document.getElementById(elem_id);
  if ('value' in elem) {
    console.log('value');
    elem.value = msg;
  } else if ('innerHTML' in elem) {
    console.log('innerHTML');
    elem.innerHTML = msg;
  }

  return;
}

function printPlus(elem, msg) {
  document.getElementById(elem).innerHTML = msg + document.getElementById(elem).innerHTML;

  return;
}
