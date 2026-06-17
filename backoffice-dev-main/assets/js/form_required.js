const formSubmit = document.querySelectorAll("button[type=submit]");
if (formSubmit.length > 0) {
  formSubmit[0].addEventListener("click", getRequiredMissing);
}

function getRequiredMissing() {
  const invalidFields = document.querySelectorAll(
    ":invalid:not(form):not(fieldset)"
  );
  let fieldNames = [];
  invalidFields.forEach((element) => {
    fieldNames.push(element.getAttribute("id"));
  });
  let alertElement = document.getElementById("required-fields-alert");
  if (!alertElement) {
    alertElement = document.createElement("div");
    alertElement.setAttribute("id", "required-fields-alert");
    alertElement.setAttribute("class", "alert alert-warning mt-3");
    alertElement.setAttribute("role", "alert");
  }
  alertElement.textContent =
    "Required fields that are empty: " +
    fieldNames.join(", ");

  let form = document.querySelectorAll("form");
  if (form.length > 0 && fieldNames.length > 0) {
    form[0].prepend(alertElement);
  }
}
