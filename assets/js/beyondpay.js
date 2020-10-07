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
  checkout_form.on( 'checkout_place_order', () => {
      if(document.forms.checkout.payment_method.value !== 'beyondpay'){
        return true;
      }
      var token_input = document.getElementById("beyond_pay_token");
      if(token_input.value){
          return true;
      } else {
          tokenpay.createToken(res => {
              token_input.value = res.token;
              document.getElementById('place_order').click();
          });
      }
      return false;
  });
});