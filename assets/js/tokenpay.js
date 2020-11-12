window.TokenPay = function(publicKey) {
  if (!publicKey) {
    console.error('Key is required');
    return;
  }

  var payFrame = {
    publicKey: publicKey,
    useACH: false
  };
  var iframe;
  var dataElement;
  var errorMessage;
  var onSuccess;
  var onFailure;

  window.addEventListener('message', function(event) {
      switch (event.data.type) {
        case 'validation':
          if (errorMessage) {
            if (event.data.data.errorMessage) {
              errorMessage.textContent = event.data.data.errorMessage;
              errorMessage.style.display = "block";
			  if (onFailure) {
                onFailure(event.data.data);
              }
            } else {
              errorMessage.style.display = "none";
            }
          }
          break;
        case 'success':
          if (onSuccess) {
            onSuccess(event.data.data);
          }
		  errorMessage.style.display = "none";
          break;
        case 'error':
          if (onFailure) {
            onFailure(event.data.data);
          }
		  errorMessage.textContent = "Error submitting payment.";
          errorMessage.style.display = "block";
          break;
      };
  });

  var _createIframe = function() {
    var iframe = document.createElement('iframe');
    iframe.id = "payFrame";
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allowtransparency", "true");
    iframe.style.cssText = "height:100%;width:100%";
    iframe.scrolling = "no";
    iframe.onload = function() {
      this.contentWindow.postMessage({_payFrame: payFrame}, '*');
    };

    const currentIframeHref = new URL(document.location.href);
    const urlOrigin = currentIframeHref.origin;
    iframe.src = "https://www.bridgepaynetsecuretest.com/Bridgepay.WebSecurity/TokenPay/js/dataValidator.html";

    // iframe.src = "TokenPay/plain-js/dataValidator.html"; // For dev

    return iframe;
  };

  return {
    initialize: function(config) {
      if (iframe && document.body.contains(iframe)) {
        console.error('TokenPay is already initialized');
        return;
      };

      if (config && config.dataElement) {
        dataElement = document.querySelector(config.dataElement);
        errorMessage = document.querySelector(config.errorElement);
        
        if(!dataElement){
          throw new Error('TokenPay: can\'t find element by selector: ' + config.dataElement);
        }
        
        if(!errorMessage){
          console.warn('TokenPay: can\'t find element by selector: ' + config.errorElement);
        }

        if (document.getElementById("customStyles")) {
           payFrame.customStyles = document.getElementById("customStyles").textContent;
        }

        if (config.useACH) {
          payFrame.useACH = config.useACH;
        }
        
        payFrame.disableZip = true;

        dataElement.innerHTML = "";
        iframe = _createIframe();
        payFrame.added = true;
        dataElement.appendChild(iframe);
      } else {
        console.error('Card data element is required');
      }
    },
    createToken: function(success, error) {
      onSuccess = success;
      onFailure = error;
      iframe.contentWindow.postMessage({ action: "submit" }, '*');
    }
  };
};
