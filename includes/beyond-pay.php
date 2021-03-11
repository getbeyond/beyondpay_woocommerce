<?php
namespace BeyondPay;
use Exception, SimpleXMLElement, DateTime, Throwable;

class BeyondPayRequest {

    public $ClientIdentifier;

    public $RequestType;

    public $TransactionID;

    public $RequestDateTime;

    public $User;

    public $Password;

    public $PrivateKey;

    public $PublicKey;

    public $AuthenticationTokenId;

    //RequestMessage class
    public $requestMessage;
}

class RequestMessage {

    public $PaymentAccountNumber;

    public $ExpirationDate;

    public $MSRKey;

    public $SecureFormat;

    public $BDKSlot;

    public $Track1;

    public $Track2;

    public $Track3;

    public $EncryptionID;

    public $DeviceMake;

    public $DeviceModel;

    public $DeviceSerial;

    public $DeviceFirmware;

    public $RegistrationKey;

    public $AppHostMachineId;

    public $IntegrationMethod;

    public $OriginatingTechnologySource;

    public $SoftwareVendor;

    public $SecurityTechnology;

    public $Token;

    public $SecurityCode;

    public $MerchantCode;

    public $MerchantAccountCode;

    public $PurchaseToken;

    public $WalletPaymentMethodID;

    public $WalletToken;

    public $WalletKey;

    public $CustomerWalletID;

    public $Amount;

    public $TransactionType;

    public $TransIndustryType;

    public $TransactionMode;

    public $TransCatCode;

    public $Descriptor;

    public $VoiceAuthCode;

    public $PartialAuthorization;

    public $SwipeResult;

    public $PINBlock;

    public $PINKey;

    public $BankAccountNum;

    public $RoutingNum;

    public $AcctType;

    public $DUKPT;

    public $PINCode;

    public $VoucherNumber;

    public $InvoiceNum;

    public $PONum;

    public $CustomerAccountCode;

    public $PaymentType;

    public $AccountHolderName;

    public $HolderType;

    public $CashBackAmount;

    public $FeeAmount;

    public $TipAmount;

    public $TipRecipientCode;

    //CustomFields class
    public $CustomFields;

    public $HealthCareAmt;

    public $TransitAmt;

    public $PrescriptionAmt;

    public $VisionAmt;

    public $DentalAmt;

    public $ClinicAmt;

    public $IsQualifiedIIAS;

    public $AccountStreet;

    public $AccountZip;

    public $AccountPhone;

    public $ContractId;

    public $TaxRate;

    public $TaxAmount;

    public $TaxIndicator;

    public $ShipToName;

    public $ShipToStreet;

    public $ShipToCity;

    public $ShipToState;

    public $ShipToZip;

    public $ShipToCountryCode;

    public $ShippingOriginZip;

    public $DiscountAmount;

    public $ShippingAmount;

    public $DutyAmount;

    public $TaxInvoiceCode;

    public $LocalTaxAmount;

    public $LocalTaxIndicator;

    public $NationalTaxAmount;

    public $NationalTaxIndicator;

    public $OrderCode;

    public $OrderDate;

    public $CommodityCode;

    public $CustomerAccountTaxID;

    public $CheckImageFront;

    public $CheckImageBack;

    public $MICR;

    public $EMVTags;

    public $SettlementDelay;

    public $EntryMode;

    public $EntryMedium;

    public $EntryPINMode;

    public $TerminalCapabilities;

    public $TerminalType;

    public $ItemCount;

    //Array of Item class
    public $Item;

    public $BIN;

    public $NewPassword;

    public $ConfirmPassword;

    public $ReferenceNumber;

    public $TransactionCode;

    public $VoidReasonCode;

    public $LaneCode;

    public $AccountCity;

    public $AccountState;

    public $AccountCountryCode;

    public $AccountEmail;

    public $TransactionDate;

    public $FolioNumber;

    public $CheckInDate;

    public $GatewayTransID;

    //ServiceFee class
    public $ServiceFee;

    //<editor-fold desc="Lodging">
    public $RoomNumber;

    public $RoomRateAmount;

    public $RoomTaxAmount;

    public $LodgingChargeType;

    public $CheckOutDate;

    public $StayDuration;

    public $SpecialProgramType;

    public $DepartureAdjAmount;

    public $LodgingItemCount;

    //Array of LodgingItem class
    public $LodgingItem;
    //</editor-fold>

    //<editor-fold desc="Car Rental">
    public $RentalAgreementNumber;

    public $RentalDailyRateAmount;

    public $RentalDuration;

    public $RentalExtraChargesAmount;

    public $RentalInsuranceAmount;

    public $MaxFreeMiles;

    public $MileRateAmount;

    public $RentalName;

    public $RentalCity;

    public $RentalCountryCode;

    public $RentalDate;

    public $RentalState;

    public $RentalTime;

    public $ReturnLocationCode;

    public $ReturnCity;

    public $ReturnCountryCode;

    public $ReturnDate;

    public $ReturnState;

    public $ReturnTime;

    public $RentalSpecialProgramType;

    public $TotalMiles;

    public $RentalExtraChargeItemCount;

    //Array of RentalExtraChargeItem class
    public $RentalExtraChargeItem;
    //</editor-fold>
}

class CustomFields {
    //the names of the fields and their respective values ​​are defined by the customer
}

class Item {
    //all fields are string except some cases that we are going to specify
    public $ItemCode;

    public $ItemCommodityCode;

    public $ItemDescription;

    public $ItemQuantity;

    public $ItemUnitCostAmt;

    public $ItemUnitMeasure;

    public $ItemTaxRate;

    public $ItemTaxAmount;

    public $ItemTaxIndicator;

    public $ItemTaxCode;

    public $ItemDiscountRate;

    public $ItemDiscountAmount;

    public $ItemTotalAmount;

    public $ItemIsCredit;
}

class ServiceFee {
    //all fields are string except some cases that we are going to specify
    public $ServiceFeeID;

    public $ResellerCode;

    public $MerchantCode;

    public $MerchantAccountCode;

    public $Amount;

    public $ServiceUser;

    public $ServicePassword;
}

class LodgingItem {
    //all fields are string except some cases that we are going to specify
    public $LodgingItemType;

    public $LodgingItemAmount;
}

class RentalExtraChargeItem {
    //all fields are string except some cases that we are going to specify
    public $RentalExtraChargeItemType;

    public $RentalExtraChargeTypeAmount;
}

class BeyondPayResponse {
    //all fields are string except some cases that we are going to specify
    public $BeyondPayResponseType;

    public $TransactionID;

    public $RequestType;

    public $ResponseCode;

    public $ResponseDescription;

    //ResponseMessage class
    public $responseMessage;
}

class ResponseMessage {
    //all fields are string except some cases that we are going to specify
    public $Token;

    public $Algorithm;

    public $CreateDate;

    public $ID;

    public $KeySize;

    public $publicKey;

    public $AuthorizationCode;

    public $ReferenceNumber;

    public $GatewayResult;

    public $AuthorizedAmount;

    public $OriginalAmount;

    public $ExpirationDate;

    public $AVSResult;

    public $AVSMessage;

    public $StreetMatchMessage;

    public $ZipMatchMessage;

    public $CVResult;

    public $CVMessage;

    public $IsCommercialCard;

    public $GatewayTransID;

    public $GatewayMessage;

    public $InternalMessage;

    public $Balance;

    public $CashBackAmount;

    public $TransactionCode;

    public $TransactionDate;

    public $CardClass;

    public $CardType;

    public $CardModifier;

    public $CardHolderName;

    public $ProviderResponseCode;

    public $ProviderResponseMessage;

    public $RemainingAmount;

    public $IsoCountryCode;

    public $IsoCurrencyCode;

    public $IsoTransactionDate;

    public $IsoRequestDate;

    public $NetworkReferenceNumber;

    public $NetworkMerchantId;

    public $NetworkTerminalId;

    public $MaskedPan;

    public $WalletID;

    public $WalletPaymentMethodID;

    public $WalletResponseMessage;

    public $WalletResponseCode;

    public $ResponseTypeDescription;

    public $MerchantCategoryCode;

    public $ReceiptTagData;

    public $IssuerTagData;

    public $CardIdentifier;

    public $SecondsRemaining;

    public $MerchantCode;

    public $MerchantAccountCode;

    public $MerchantName;

    public $GatewayResults;

    public $ResponseType;

    public $TransactionType;

    public $AuthrorizationCode;

    public $ProviderAVSCode;

    public $ProviderCVCode;

    public $ProviderReferenceNumber;

    public $CycleCode;

    public $RoutingNumber;

    public $PurchaseToken;

    public $ApprovalCode;

    public $CSCResponseCode;

    public $GatewayTransId;

    public $HolderName;

    public $InvoiceNum;

    public $TransactionCategory;

    public $Amount;

    public $AVSResponseCode;

    public $Email;

    public $Holdertype;

    public $AccountType;

    public $City;

    public $Memo;

    public $Phone;

    public $AccountNumberMasked;

    public $CountryCode;

    public $State;

    public $Street;

    public $TransactionIndustryType;

    public $ZipCode;

    public $PersistedData;

    public $ResponseCode;

    public $ResponseDescription;

    public $BatchID;

    public $AuthenticationTokenId;

    //ServiceFeeResult class
    public $ServiceFeeResult;
}

class ServiceFeeResult {
    //all fields are string except some cases that we are going to specify
    public $ServiceFeeID;

    public $AuthorizationCode;

    public $ReferenceNumber;

    public $GatewayResult;

    public $AuthorizedAmount;

    public $OriginalAmount;

    public $GatewayTransID;

    public $GatewayMessage;

    public $InternalMessage;
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Tools">
class Constanst{

    const SOAP_ACTION_HEADER = "SOAPAction";
    const SOAP_ACTION_VALUE = "http://bridgepaynetsecuretx.com/requesthandler/IRequestHandler/ProcessRequest";
    const ADD_MORE_DETAIL = " More Detail: ";
    const CLIENTIDENTIFIER_DEFAULT_VALUE = "SOAP";
    const SOAP_REQUEST_HEADER = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:req="http://bridgepaynetsecuretx.com/requesthandler">
                                   <soapenv:Header/>
                                   <soapenv:Body>
                                      <req:ProcessRequest>
                                         <req:requestMsg>';
    const SOAP_REQUEST_FOOTER = '</req:requestMsg>
                                      </req:ProcessRequest>
                                   </soapenv:Body>
                                </soapenv:Envelope>';
}

class BeyondPaySDKError{

    private $errorCode;
    private $message;

    function __construct(string $errorCode, string $message){

        $this->errorCode = $errorCode;
        $this->message = $message;
    }

    public function getErrorCode(){ return $this->errorCode; }

    public function getMessage(){ return $this->message; }

    function __toString(){

        $result = "(" . $this->errorCode . "): " . $this->message;

        return $result;
    }
}

class BeyondPaySDKException extends Exception {

    //<editor-fold desc="Error Messages">
    public static function BAD_RESPONSE() { return new BeyondPaySDKError("70000", "Bad Response."); }
    public static function SERVER_ERROR() { return new BeyondPaySDKError("70001", "Could not connect to server."); }
    public static function EMPTY_NULL_FIELD(String $field) {

        $message = "The " . $field . " is null or empty.";

        return new BeyondPaySDKError("70002", $message);
    }
    public static function NULL_FIELD(String $field) {

        $message = "The " . $field . " is null.";

        return new BeyondPaySDKError("70003", $message);
    }
    public static function PROCESS_REQUEST_ERROR() { return new BeyondPaySDKError("70004", "Error processing the request."); }
    public static function INVALID_RESPONSE_FORMAT() { return new BeyondPaySDKError("70005", "The BeyondPay response is not a valid xml format. Please check the URL and the request."); }
    public static function INVALID_URL_FORMAT(String $url) {

        $message = "(" . $url . ") is malformed.";

        return new BeyondPaySDKError("70006", $message);
    }
    public static function UNSUCCESSFUL_API_RESPONSE(String $apiResponse = null)
    {
        $message = "Unsuccessful response from BeyondPay.";
        if (!empty($apiResponse)) {
            $message .= " This is the BeyondPay response: " . $apiResponse;
        }
        return new BeyondPaySDKError("70007", $message);
    }
    //</editor-fold>

    private $beyondPaySDKError;

    function __construct(BeyondPaySDKError $beyondPaySDKError, \Throwable $previous = null) {
        parent::__construct($beyondPaySDKError->getMessage(), $beyondPaySDKError->getErrorCode(), $previous);

        $this->beyondPaySDKError = $beyondPaySDKError;
    }

    public function getBeyondPaySDKError(){ return $this->beyondPaySDKError; }
}
// </editor-fold>


class BeyondPayConnection {

    const skipField = "BeyondPayResponseType";

    /**
     * @param string $beyondPayURL
     * @param BeyondPayRequest $request
     * @return BeyondPayResponse
     * @throws BeyondPaySDKException@
     */
    public function processRequest(string $beyondPayURL, BeyondPayRequest $request){

        $response = new BeyondPayResponse();

        try {
            // <editor-fold defaultstate="collapsed" desc="Validations">
            if (empty($beyondPayURL)) {

                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("url"));
            }

            if (empty($request)) {

                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("request"));
            }

            if (empty($request->TransactionID)) {

                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("transaction ID of request"));
            }

            if (empty($request->RequestType)) {

                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("type of request"));
            }
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Default Values">
            $date = new DateTime();
            $request->RequestDateTime = $date->format("YmdHis");

            $request->ClientIdentifier = Constanst::CLIENTIDENTIFIER_DEFAULT_VALUE;
            // </editor-fold>

            $requestString = self::Serialize($request);

            if (empty($requestString)) {
                $errorMessage = "the request serialization fail. Check the request object is right.";
                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("request as string"), new Exception($errorMessage));
            }

            $requestEncode = base64_encode($requestString);

            if (empty($requestEncode)) {
                $errorMessage = "the request encode fail. This is the request string: " . $requestString . ".";
                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("request encode"), new Exception($errorMessage));
            }

            $conn = new SOAPMessenger();
            $encodeResponse = $conn->sendMessage($requestEncode, $beyondPayURL);

            if (empty($encodeResponse)) {
                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("encode response"));
            }

            $responseString = base64_decode($encodeResponse);

            if (empty($responseString)) {
                $errorMessage = "the reponse decode fail. This is the encode response: " . $encodeResponse . ".";
                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("decode response"), new Exception($errorMessage));
            }

            $response = self::DeserializeStringXMLToObject($responseString, "BeyondPayResponse");

            if (empty($response)) {
                $errorMessage = "the reponse deserialization fail. This is the xml string: " . $responseString . ".";
                throw new BeyondPaySDKException(BeyondPaySDKException::EMPTY_NULL_FIELD("response"), new Exception($errorMessage));
            }

            //add the response type to the response
            $tempDoc = simplexml_load_string($responseString);
            if (!empty($tempDoc)) {
                $responeType = $tempDoc->getName();
                $response->BeyondPayResponseType = $responeType;
            }

        } catch (BeyondPaySDKException $exc) {

            $errorMessage = $exc->getBeyondPaySDKError()->getMessage();

            if (!empty($exc->getPrevious())) {
                $errorMessage .= Constanst::ADD_MORE_DETAIL . $exc->getPrevious()->getMessage();
            }

            $response = $this->createErrorResponse($request, $errorMessage, $exc->getBeyondPaySDKError()->getErrorCode());

        }catch (Throwable $exc) {

            $bc_error = BeyondPaySDKException::PROCESS_REQUEST_ERROR();
            $errorMessage = $bc_error->getMessage() . Constanst::ADD_MORE_DETAIL . $exc->getMessage();

            $response = $this->createErrorResponse($request, $errorMessage, $bc_error->getErrorCode());
        }

        return $response;
    }

    public static function DeserializeStringXMLToObject(string $stringXML, string $className){

        $result = NULL;

        try {

            if (empty($stringXML)) {
                return;
            }

            if (empty($className)) {
                return;
            }
            
	    $nsClassName = 'BeyondPay\\'.$className;
            if (!class_exists($nsClassName)) {
                return;
            }
            
            $result = new $nsClassName;
            
            $xmlDoc = simplexml_load_string($stringXML);

            if ($result instanceof BeyondPayResponse) {
                $result->BeyondPayResponseType = $xmlDoc->getName();
            }

            self::DeserializeXMLToObject($xmlDoc, $result);

        } catch (Throwable $exc) {

            $result = NULL;
        }

        return $result;

    }

    /**
     * @param type $objectToSerialize
     * @return string
     */
    public static function Serialize ($objectToSerialize){

        $result = NULL;

        try {

            if (!is_object($objectToSerialize)) {
                return;
            }

            $xmlDoc = self::SetUpRootNode($objectToSerialize);

            self::SerializeObjectToXML($objectToSerialize, $xmlDoc);

            //remove XML declaration
            //$dom = dom_import_simplexml($xmlDoc);
            //$dom->ownerDocument->preserveWhiteSpace = false;
            //$dom->ownerDocument->formatOutput = true;
            //$result = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
            $result = $xmlDoc->asXML();

        } catch (Throwable $exc) {
            $result = NULL;
        }

        return $result;
    }

    private static function SerializeObjectToXML ($objectToSerialize, SimpleXMLElement $xmlDoc){

        foreach (get_object_vars($objectToSerialize) as $key => $value){

            if ($key == self::skipField) {
                continue;
            }

            if (is_scalar($value)) {

                self::SerializeFieldToXML($key, $objectToSerialize, $xmlDoc);

            } else if (is_object($value)) {

                $objectNode = $xmlDoc->addChild($key);
                self::SerializeObjectToXML($value, $objectNode);

            } else if (is_array($value)) {

                self::SerializeArrayToXML($value, $xmlDoc, $key);
            }
        }
    }

    private static function SetUpRootNode($objectToSerialize){

        $rootName;

        if ($objectToSerialize instanceof BeyondPayRequest) {

            $rootName = "requestHeader";

        } else if ($objectToSerialize instanceof BeyondPayResponse && !empty($objectToSerialize->BeyondPayResponseType)) {

            $rootName = $objectToSerialize->BeyondPayResponseType;

        } else {

            $rootName = get_class($objectToSerialize);
        }

        $mainNode = "<" . $rootName . "></" . $rootName . ">";
        $xmlDoc = new SimpleXMLElement($mainNode);

        return $xmlDoc;
    }

    private static function SerializeArrayToXML(array $array, SimpleXMLElement $xmlNode, $field){

        foreach ($array as $key => $value){

            if (is_object($value)) {

                $objectNode = $xmlNode->addChild($field);
                self::SerializeObjectToXML($value, $objectNode);

            } else if (is_scalar($value)) {

                self::SerializeFieldToXML($key, $value, $xmlNode);
            }
        }
    }

    private static function SerializeFieldToXML(string $fieldName, $value, SimpleXMLElement $xmlNode){

        if (empty($fieldName)) {
            return;
        }

        $fieldValue;

        if (is_object($value)) {
            $fieldValue = $value->{$fieldName};

        } else if (is_scalar($value)) {
            $fieldValue = $value;
        }

        if (empty($fieldValue)) {
            return;
        }

        if (is_scalar($fieldValue)) {

	    $stripped = in_array($fieldName, array('User','Password')) ? 
		$fieldValue :
		preg_replace('/[^a-z0-9_\\- ]/i', '', $fieldValue);
            $xmlNode->addChild($fieldName, $stripped);
        }
    }

    private static function DeserializeXMLToObject(SimpleXMLElement $xmlToDeserialize, $object){

        foreach ($xmlToDeserialize->children() as $node){

            $field = $node->getName();

            if ($field == self::skipField) {
                continue;
            }

            $classVars = get_object_vars($object);

            if (!$object instanceof CustomFields && (empty($classVars) || array_key_exists($field, $classVars) === FALSE)) {
                continue;
            }

            $siblingXpathFilter = "preceding-sibling::" . $node->getName() . " | following-sibling::" . $node->getName();
            $siblings = $node->xpath($siblingXpathFilter);

            $isList = FALSE;

            if (count($siblings) > 0) {
                //the node is a List
                $isList = TRUE;
            }

            if ($node->count() > 0){
                //the node is an Object 
		$nsField = 'BeyondPay\\'.$field;
                if (!class_exists($nsField)) {
                    continue;
                }

                if ($isList) {
		    
                    $listElement = new $nsField;
                    $object->{$field}[] = $listElement;
                    self::DeserializeXMLToObject($node, $listElement);
                } else{
                    $object->{$field} = new $nsField;
                    self::DeserializeXMLToObject($node, $object->{$field});
                }

            } else {
                //the node is a Field
                if ($isList) {
                    $object->{$field}[] = $node->__toString();
                } else {
                    $object->{$field} = $node->__toString();
                }


            }
        }
    }

    private function createErrorResponse(BeyondPayRequest $request, String $message, String $errorCode){

        $response = new BeyondPayResponse();

        if (empty($request)) {

            $response->TransactionID = "";
            $response->RequestType = "";

        } else {

            $response->TransactionID = $request->TransactionID;
            $response->RequestType = $request->RequestType;
        }

        $response->ResponseDescription = $message;
        $response->ResponseCode = $errorCode;

        return $response;

    }
}

class SOAPMessenger {

    public function sendMessage(string $message_encode, string $paymentGatewayUrl){

        $response = NULL;

        try {
            $headers[Constanst::SOAP_ACTION_HEADER] = Constanst::SOAP_ACTION_VALUE;
            $headers["content-type"] = "text/xml";

            $requestBody = Constanst::SOAP_REQUEST_HEADER . $message_encode . Constanst::SOAP_REQUEST_FOOTER;
            
            $res = wp_remote_post(
		$paymentGatewayUrl, 
		[
		    'headers' => $headers,
		    'body' => $requestBody
		]
	    );
            
            if (is_array($res) && $res['response']['code'] == 200) {
                $responseDoc = simplexml_load_string($res['body']);
                $bodyNode = $responseDoc->xpath('//s:Body');

                if (empty($bodyNode[0]) || empty($bodyNode[0]->ProcessRequestResponse) || empty($bodyNode[0]->ProcessRequestResponse[0]->ProcessRequestResult)) {
                    throw new BeyondPaySDKException(BeyondPaySDKException::UNSUCCESSFUL_API_RESPONSE($res->getBody()));
                }

                $response = $bodyNode[0]->ProcessRequestResponse[0]->ProcessRequestResult;

            } else {

		$code = is_array($res) ? $res['response']['code'] : $res->get_error_code();
		$msg = is_array($res) ? $res['body'] : $res->get_error_message();
                $errorMessage = "The request response code is: " . $code . " and the cause is: " . $msg . ".";
                throw new BeyondPaySDKException(BeyondPaySDKException::BAD_RESPONSE(), new Exception($errorMessage));

            }

        } catch (Throwable $exc) {
            throw new BeyondPaySDKException(BeyondPaySDKException::SERVER_ERROR(), $exc);
        }

        return $response;
    }

}
