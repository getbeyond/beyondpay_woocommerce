/**
 * Initializes and attaches the TokenPay iframe.
 * @param {TokenPay} tokenpay
 * @returns {undefined}
 */
function attachBeyondPay(tokenpay){
  tokenpay.initialize({
    dataElement: '#card', 
    errorElement: '#errorMessage', 
    useStyles: false
  });
}

jQuery(function() {
  var checkout_form = jQuery(document.forms.checkout);
  var token_used = false;
  var is_processing = false;
  checkout_form.on( 'checkout_place_order', () => {
      if(is_processing){
        return false;
      }
      is_processing = true;
      if(document.forms.checkout.payment_method.value !== 'beyondpay'){
        return true;
      }
      var token_input = document.getElementById("beyond_pay_token");
      if(token_used){
        token_input.value = "";
      }
      if(token_input.value){
          token_used = true;
          is_processing = false;
          return true;
      } else {
        is_processing = false;
        tokenpay.createToken(
          function(res) {
            token_used = false;
            token_input.value = res.token;
            document.getElementById('place_order').click();
          },
          function() {
            token_used = false;
            is_processing = false;
            token_input.value = "";
          }
        );
      }
      return false;
  });
});