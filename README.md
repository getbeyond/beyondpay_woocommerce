# Beyond Pay Gateway for WooCommerce
Securely accept credit cards directly on your [WooCommerce](https://woocommerce.com) store using [Beyond](https://getbeyond.com) with this [WordPress](https://wordpress.org/) plugin.

## Features
- Accept Visa, MasterCard, American Express, Discover, JCB, and Diners Club brand cards directly on your website
- No redirect to a third-party hosted payment page, reducing checkout friction and cart abandonment
- Card data is securely captured with Beyond Pay Gateway's hosted payment fields presented via inline frame (iframe) and tokenized before reaching your server
- Simplifies merchant PCI compliance obligations to the shorter [Self-Assessment Questionnaire "A" (SAQ-A)](https://www.pcisecuritystandards.org/pci_security/completing_self_assessment)
- Support either pre-authorization and later capture when WooCommerce order status changes, or authorization and capture at once (the combined "sale" transaction type)
- Optimize B2B card acceptance costs by automatically sending additional transaction data elements (also known as ["Level II" and "Level III" information](https://www.getbeyond.com/b2b-payments/)
- Custom CSS styling support for the hosted payment fields so that you can create your ideal checkout experience
- Customizable gateway response and error messaging
- Test/sandbox mode for development and staging

## Installation

There are 2 primary ways to install this plugin: **Automatic** or **Manual**

### Automatic Installation
1. From your WordPress **/wp-admin** page, navigate to **Plugins > Add New**.
1. Enter "Beyond Pay" in the search form in the top right of the screen.
1. Select *Beyond Pay for WooCommerce* from the search results.
1. Click the **Install Now** button to install the plugin.
1. Click **Activate** to activate the plugin.

### Manual Installation
1. Make sure WooCommerce is [installed and enabled on your WordPress instance](https://docs.woocommerce.com/document/installing-uninstalling-woocommerce/).
1. Download the **beyondpay-gateway.zip** from [the latest release](https://github.com/getbeyond/beyondpay_woocommerce/releases/latest).
1. From your WordPress **/wp-admin** page, navigate to **Plugins > Add New**.
1. Click the **Upload Plugin** button at the top of the screen.
1. Select the **beyondpay-gateway.zip** file from your local filesystem that was obtained earlier.
1. Click **Install Now**.
1. When the installation is complete you will see the message "Plugin installed successfully."
1. Click the **Activate Plugin** button at the bottom of the page.
    - *For more information on managing WordPress plugins, see https://wordpress.org/support/article/managing-plugins/*

## Configuration

1. From your WordPress **/wp-admin** page, navigate to **WooCommerce > Settings**.
1. Select the **Payments** tab at the top of the screen.
1. Click the **Manage** button for the Beyond Pay Gateway payment method.
1. Proceed to configure payment method options available on this page (see [screenshot](#beyond-pay-for-woocommerce-configuration-settings)):
  - **Enable/Disable** - toggle to control whether this payment method is enabled or disabled
  - **Title** - this controls how this payment method is listed to the consumer during checkout; defaults to "Credit/Debit Card"
  - **Description** - expanded description of this payment method when selected by consumer; defaults to "Pay with your credit or debit card."
  - **Detailed Error Messages** - controls the message returned to the consumer when there is a problem with their payment; defaults to "Something went wrong: %S. Please try again." where **%S** represents the [raw response or error message](https://developer.getbeyond.com/#gateway-result-codes) returned by the gateway
  - **Enable Test Mode** - controls whether transactions are sent to the Test/Sandbox or the Live/Production Beyond Pay Gateway environment and which type of API keys are expected; defaults to Live    
  - **Username, Password, PublicKey, PrivateKey, MerchantCode,** and **MerchantAccountCode** - these are the credentials by which the plugin authenticates to the Beyond Pay Gateway in order to process payments; for Test Mode, you can [request Beyond Pay Gateway sandbox API keys](https://forms.office.com/Pages/ResponsePage.aspx?id=Q9V6UxGq3USJSkGsz2Jk7yRG7q939HJFkFXKp4lfZo1URUJXWFhEMDlDTUs3OVlROEMxOExJQzZGNSQlQCN0PWcu) while live credentials are provided by Beyond once the merchant processing account is approved
  - **Transaction Mode** - controls how authorizations and payment captures are managed
    - Set this to ***Authorization*** to perform only an authorization ("pre-auth") when an order is placed which requires the Order Status to be changed to **Completed** in order for the payment to be captured (usually when an order is shipped)
    - Set this to ***Sale*** to authorize and capture the payment immediately (usually used for digital products)
    - Learn more about best practices for authorization and capture/settlement from the [Visa E-Commerce Risk Management Best Practices document](https://usa.visa.com/dam/VCOM/download/merchants/visa-risk-management-guide-ecommerce.pdf)
  - **Level II/III Data** - controls which extended data elements are automatically sent with transaction requests in order to [optimize interchange rates on B2B cards](https://www.getbeyond.com/b2b-payments/); Level II includes reference number and tax amount, while Level III includes line-item details. Set to Level III to ensure you always qualify for the best rates on eligible corporate purchasing cards. (Tax-exempt transactions are not eligible for Level II interchange rates but may be eligibile for Level III.)
  - **Advanced Styling** - allows for customized styling of the Beyond Pay card collection iframe via CSS
5. Click the **Save Changes** button once you have completed configuration; the page will refresh and a message reading "Your settings have been saved" will display at the top.

You are now ready to accept payments through Beyond Pay Gateway on your WooCommerce store!

## Frequently Asked Questions

**Is it secure and/or compliant to accept credit cards directly on my website?**

Yes! Beyond Pay Gateway secures card data by hosting the actual payment fields and presenting them in an iframe so that the fields only *appear* to be part of the WooCommerce checkout form. 

Once card data is collected, then the information is further secured by *tokenization*: a process in which the sensitive card data is exchanged for a non-sensitive representation, or "token." This ensures that cardholder data is not sent from the consumer's browser to the merchant's web server, and only the surrogate token value comes into contact with the merchant's systems.

**Do I have to have an SSL/TLS certificate?**

Yes. All submission of sensitive payment data by the Beyond Pay is made via a secure HTTPS connection from the cardholder's browser. However, to protect yourself from man-in-the-middle attacks and to prevent your users from experiencing mixed content warnings in their browser, you MUST serve the page with your payment form over HTTPS. You can obtain a free certificate from [LetsEncrypt.org](https://letsencrypt.org).

**Does this gateway plugin support a sandbox or test option?**

Yes. For Test Mode, you can [request Beyond Pay Gateway sandbox API keys](https://forms.office.com/Pages/ResponsePage.aspx?id=Q9V6UxGq3USJSkGsz2Jk7yRG7q939HJFkFXKp4lfZo1URUJXWFhEMDlDTUs3OVlROEMxOExJQzZGNSQlQCN0PWcu) while production (live) API keys are provided by Beyond once the merchant processing account is approved.

**Does this gateway plugin support WooCommerce Subscriptions?**

Not yet, but this feature is coming soon!

**How can I get further support?**

Contact [BeyondPayIntegrations@getbeyond.com](mailto:BeyondPayIntegrations@getbeyond.com), [submit a support ticket via WordPress](https://wordpress.org/support/plugin/beyond-pay-for-woocommerce/), or [submit an issue via GitHub](https://github.com/getbeyond/beyondpay_woocommerce/issues). For basic support and troubleshooting of your credit card authorizations and sales, you may also contact our Service Center at 1-888-480-1571.

## Uninstalling

### Automatic Uninstallation
1. From your WordPress **/wp-admin** page, navigate to **Plugins**.
1. Click the **Deactivate** link for Beyond Pay for WooCommerce.
1. Click the **Delete** link for Beyond Pay for WooCommerce.

### Manual Uninstallation

***Note: this procedure requires manual deletion of files from your WordPress server. Please ensure you have made a complete back up of your site before proceeding with manual uninstallation.***

1. From your WordPress **/wp-admin** page, navigate to **Plugins**.
1. Click the **Deactivate** link for Beyond Pay for WooCommerce.
1. Connect to your WordPress server via [SFTP client](https://en.wikipedia.org/wiki/SFTP).
1. On your server, navigate to your WordPress installation directory and then to the **/wp-content/plugins** folder.
1. Delete the plugin folder named **beyondpay-gateway**.
1. From your WordPress **/wp-admin** page, navigate to **Plugins** and confirm that Beyond Pay for WooCommerce is no longer installed.

## Screenshots

### Beyond Pay for WooCommerce Checkout Page

**WooCommerce Checkout Beyond Pay enabled as the payment method (in test mode) and default form styling:**
![WooCommerce Checkout page with Beyond Pay](https://github.com/getbeyond/beyondpay_woocommerce/blob/master/assets/screenshot-1.png)

### Beyond Pay for WooCommerce Configuration Settings

**Plugin configuration screen in WooCommerce Settings > Payments ([see above](#configuration) for descriptions of these settings):**
![Beyond Pay for WooCommerce configuration settings](https://github.com/getbeyond/beyondpay_woocommerce/blob/master/assets/screenshot-2.png)
![Additional configuration settings for Beyond Pay plugin](https://github.com/getbeyond/beyondpay_woocommerce/blob/master/assets/screenshot-3.png)

## Changelog

### 1.3.3
* Don't strip chars on User and Password fields;
* Improved error handling on capture failures.

### 1.3.2
* Strip unsupported chars for transaction request.

### 1.3.1
* Namespace updates, library changes, and general cleanup.

### 1.3.0
* Added advanced CSS styling options for iframe.
* Updated default payment form styling.

### 1.2.0
* Enhanced mapping of data elements from WooCommerce to Beyond Pay.

### 1.1.1
* Added automatic defaulting of Level II/III data elements as a configuration option.
* Corrected validation issue after token generation.

### 1.1.0
* Added support for Level II and Level III data elements.
