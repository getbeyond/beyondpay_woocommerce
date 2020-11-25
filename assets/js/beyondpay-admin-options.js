function onBeyondPayTestModeChanged(isTestMode) {
  var fields = ["public_key", "private_key", "login", "password"];
  var trs = jQuery("tr");
  var testAction = isTestMode ? 'show' : 'hide';
  var liveAction = isTestMode ? 'hide' : 'show';
  fields.forEach(function (f) {
    trs.has("#woocommerce_beyondpay_test_" + f)[testAction]();
    trs.has("#woocommerce_beyondpay_" + f)[liveAction]();
  });
}
function onBeyondPayUseCustomStylingChanged(useCustomStyling) {
  jQuery('tr').has('#woocommerce_beyondpay_styling')[useCustomStyling ? 'show' : 'hide']();
}
var testModeCheckbox = document.getElementById('woocommerce_beyondpay_testmode');
var customStylingCheckbox = document.getElementById('woocommerce_beyondpay_use_custom_styling');
testModeCheckbox.addEventListener(
  'change',
  function (e) {
    onBeyondPayTestModeChanged(e.target.checked);
  }
);
customStylingCheckbox.addEventListener(
  'change',
  function (e) {
    onBeyondPayUseCustomStylingChanged(e.target.checked);
  }
);
onBeyondPayTestModeChanged(testModeCheckbox.checked);
onBeyondPayUseCustomStylingChanged(customStylingCheckbox.checked);