// PHP Validation script
var formUrl = "mail/validate.php?value=";

// Your form's id
var formid = "contact-form";
var formError =  [];

// Launch the loadForm function while page is loading
window.onload = loadForm;

function loadForm() {
	if(document.getElementById(formid)!=null) 
	{
		var formButt = document.getElementById("form-submit");
		if(formButt) formButt.onclick = function() { sendEmail(); return false; }
	}
}

http = postHTTPObject();

function postHTTPObject() {

  var xmlhttp;
 
 
  if(!xmlhttp && typeof XMLHttpRequest != 'undefined'){
    try {
      xmlhttp = new XMLHttpRequest();
    }catch(e){
      xmlhttp = false;
    }
  }
 
  return xmlhttp;

}


function sendEmail()
{
	http.open("POST", "mail/validate.php");
	http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    http.send("sendmail=1" + "&name=" + (document.getElementById("name").value) + "&mail=" + (document.getElementById("mail").value) + "&phone=" + (document.getElementById("phone").value) + "&message=" + (document.getElementById("message").value));

	http.onreadystatechange = handleHttpResponse;
}

function handleHttpResponse() {

	if(http.readyState == 4) {

		if(http.responseText == "false") {

			var formInput = document.getElementById(formId);
			formInput.style.border = "1px solid #d12f19";
			formError.push(formId);
		}
		else if(http.responseText) {
			
			document.getElementById("form-result").innerHTML= http.responseText;
			if( ( http.responseText ).indexOf( 'class="success' ) > -1 ) {
				document.getElementById(formid).reset();
			}
		}
	}
}

