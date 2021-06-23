var sendingBeyondPayProcessTokenizedOrder = false;

function beyondPayProcessTokenizedOrder(url, order_id){
  if(sendingBeyondPayProcessTokenizedOrder){
    return;
  }
  sendingBeyondPayProcessTokenizedOrder = true;
  jQuery.post('admin-ajax.php',{
      order_id,
      action: 'beyond_pay_process_tokenized_order'
    },(respRaw) => {
      var resp = JSON.parse(respRaw);
      if(resp.success){
        document.location.reload();
      }else{
        sendingBeyondPayProcessTokenizedOrder = false;
        alert('Error processing saved payment: ' + (resp.message || 'Unknown error'));
      }
    })
    .fail(function(error) {
      sendingBeyondPayProcessTokenizedOrder = false;
      alert( 'Internal error processing order (status ' + error.status + '). ' + error.statusText );
    });
}
