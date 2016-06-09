# Woo Mercado Pago Module

* [Features](#features)
* [Available versions](#available_versions)
* [Installation](#installation)
* [Standard Checkout Configuration](#std_configuration)
* [Custom Checkout Configuration](#cst_configuration)
* [Ticket Configuration](#ticket_configuration)

-----------

<a name="features"></a>
##Features##

**Standard checkout**<br />
This feature allows merchants to have a standard checkout. It includes features like
customizations of title, description, category, external reference, integrations via
iframe, modal, and redirection, with configurable auto-returning, max installments,
payment method exclusion setup, and sandbox/debug options.<br />
*Available for Argentina, Brazil, Chile, Colombia, Mexico and Venezuela*

**Custom checkout**<br />
This feature enables merchants to have the custom checkout, a more integrated type of
checkout with customized views and more intuitive flow from the cart to the payment page.<br />
*Available for Argentina, Brazil, Chile, Colombia, Mexico and Venezuela*

**Tickets**<br />
This option enables merchants to give their customers the option to pay via tickets.<br />
*Available for Argentina, Brazil, Chile, Colombia, Mexico and Venezuela*

-----------

<a name="available_versions"></a>
##Available versions##

<table>
  <thead>
    <tr>
      <th>Plugin Version</th>
      <th>Status</th>
      <th>WooCommerce Compatible Versions</th>
    </tr>
  <thead>
  <tbody>
    <tr>
      <td>v2.0.1</td>
      <td>Stable (Current version)</td>
      <td>WooCommerce 2.1.x - 2.5.x</td>
    </tr>
  </tbody>
</table>

-----------

<a name="installation"></a>
##Installation##

You have two way to install this module: from your WordPress Store, or by downloading and manually copying the module directory.

**Install from WordPress**

1. On your store administration, go to *Plugins* option in sidebar;

2. Click in *Add New* button and type "Woo Mercado Pago Module" in the *Search Plugins* text field. Press Enter;

3. You should find the module read to be installed. Click install.

**Manual Download**

1. Get the module sources from a repository (<a href="https://github.com/mercadopago/cart-woocommerce/archive/master.zip">Github</a> or <a href="https://downloads.wordpress.org/plugin/woo-mercado-pago-module.2.0.0.zip">WordPress Plugin Directory</a>);

2. Unzip the folder and find "woo-mercado-pago-module" directory;

3. Copy "woo-mercado-pago-module" directory to *[WordPressRootDirectory]/wp-content/plugins/* directory.

To confirm that your module is really installed, you can click in *Plugins* item in the store administration menu, and check your just installed module. Just click *enable* to activate it and you should receive the message "Plugin enabled." as a notice in your WordPress.

![Features](https://raw.github.com/marcelohama/cart-woocommerce/dev_a/README.img/plugin_adm.png)

-----------

<a name="std_configuration"></a>
##Standard Checkout Configuration##

On your store administration, go to *WooCommerce > Settings > Checkout* tab. In *Checkout Options*, click in *Mercado Pago - Standard Checkout*. You should get the following page:

![Installation Instructions](https://raw.github.com/marcelohama/cart-woocommerce/dev_a/README.img/standard_checkout.png)

1. **Solution Header**: This part is the header, where you can enable/disable the solution;

2. **Mercado Pago Credentials**: In this part, you should configure your credentials *Client_id* and *Client_secret*;

	Remember that you can obtain your *Client_id* and *Client_secret*, accordingly to your country, in the following links:

	* Argentina: https://www.mercadopago.com/mla/herramientas/aplicaciones
	* Brazil: https://www.mercadopago.com/mlb/ferramentas/aplicacoes
	* Chile: https://www.mercadopago.com/mlc/herramientas/aplicaciones
	* Colombia: https://www.mercadopago.com/mco/herramientas/aplicaciones
	* Mexico: https://www.mercadopago.com/mlm/herramientas/aplicaciones
	* Venezuela: https://www.mercadopago.com/mlv/herramientas/aplicaciones

3. **Instant Payment Notification (IPN) URL**: In this part, you can check your IPN URL, where you will get notified about payment updates;

4. **Checkout Options**: This part allows you to customize your general checkout fields;

	*Title*: This is the title of the payment option that will be shown to your customers;<br />
	*Description*: This is the description of the payment option that will be shown to your customers;<br />
	*Store Category*: Sets up the category of the store;<br />
	*Store Identificator*: A prefix to identify your store, when you have multiple stores for only one Mercado Pago account;<br />
	*Integration Method*: How your customers will interact with Mercado Pago to pay their orders;<br />
	*iFrame Width*: The width, in pixels, of the iFrame (used only with iFrame Integration Method);<br />
	*iFrame Height*: The height, in pixels, of the iFrame (used only with iFrame Integration Method);<br />
	*Auto Return*: If set, the platform will return to your store when the payment is approved.

5. **Payment Options**: This part allows you to customize how the payment should be made;

	*Max Installments*: The maximum installments allowed for your customers;<br />
	*Exclude Payment Methods*: Select the payment methods that you want to not work with Mercado Pago.

6. **Test and Debug Options**: This part allows you to configure debug and test features.

	*Mercado Pago Sandbox*: Test your payments in Mercado Pago sandbox environment;<br />
	*Debug and Log*: Enables/disables system logs.

-----------

<a name="cst_configuration"></a>
##Custom Checkout Configuration##

On your store administration, go to *WooCommerce > Settings > Checkout* tab. In *Checkout Options*, click in *Mercado Pago - Custom Checkout*. You should get the following page:

![Installation Instructions](https://raw.github.com/marcelohama/cart-woocommerce/dev_a/README.img/custom_checkout.png)

1. **Solution Header**: This part is the header, where you can enable/disable the solution;

2. **Mercado Pago Credentials**: In this part, you should configure your credentials *Public Key* and *Access Token*;

	Remember that you can obtain your *Public Key* and *Access Token*, accordingly to your country, in the following links:

	* Argentina: https://www.mercadopago.com/mla/account/credentials?type=custom
	* Brazil: https://www.mercadopago.com/mlb/account/credentials?type=custom
	* Colombia: https://www.mercadopago.com/mco/account/credentials?type=custom
	* Mexico: https://www.mercadopago.com/mlm/account/credentials?type=custom
	* Venezuela: https://www.mercadopago.com/mlv/account/credentials?type=custom

3. **Instant Payment Notification (IPN) URL**: In this part, you can check your IPN URL, where you will get notified about payment updates;

4. **Checkout Options**: This part allows you to customize your general checkout fields;

	*Title*: This is the title of the payment option that will be shown to your customers;<br />
	*Description*: This is the description of the payment option that will be shown to your customers;<br />
	*Statement Descriptor*: The description that will be shown in your customer's invoice;<br />
	*Binary Mode*: When charging a credit card, only [approved] or [reject] status will be taken;<br />
	*Store Category*: Sets up the category of the store;<br />
	*Store Identificator*: A prefix to identify your store, when you have multiple stores for only one Mercado Pago account.

5. **Test and Debug Options**: This part allows you to configure debug and test features.

	*Mercado Pago Sandbox*: Test your payments in Mercado Pago sandbox environment;<br />
	*Debug and Log*: Enables/disables system logs.

-----------

<a name="ticket_configuration"></a>
##Ticket Configuration##

On your store administration, go to *WooCommerce > Settings > Checkout* tab. In *Checkout Options*, click in *Mercado Pago - Ticket*. You should get the following page:

![Installation Instructions](https://raw.github.com/marcelohama/cart-woocommerce/dev_a/README.img/ticket.png)

1. **Solution Header**: This part is the header, where you can enable/disable the solution;

2. **Mercado Pago Credentials**: In this part, you should configure your credential *Access Token*;

	Remember that you can obtain your *Access Token*, accordingly to your country, in the following links:

	* Argentina: https://www.mercadopago.com/mla/account/credentials?type=custom
	* Brazil: https://www.mercadopago.com/mlb/account/credentials?type=custom
	* Chile: https://www.mercadopago.com/mlc/account/credentials?type=custom
	* Colombia: https://www.mercadopago.com/mco/account/credentials?type=custom
	* Mexico: https://www.mercadopago.com/mlm/account/credentials?type=custom
	* Venezuela: https://www.mercadopago.com/mlv/account/credentials?type=custom

3. **Instant Payment Notification (IPN) URL**: In this part, you can check your IPN URL, where you will get notified about payment updates;

4. **Ticket Options**: This part allows you to customize your general ticket fields;

	*Title*: This is the title of the payment option that will be shown to your customers;<br />
	*Description*: This is the description of the payment option that will be shown to your customers;<br />
	*Statement Descriptor*: The description that will be shown in your customer's invoice;<br />
	*Store Category*: Sets up the category of the store;<br />
	*Store Identificator*: A prefix to identify your store, when you have multiple stores for only one Mercado Pago account.

5. **Test and Debug Options**: This part allows you to configure debug and test features.

	*Mercado Pago Sandbox*: Test your payments in Mercado Pago sandbox environment;<br />
	*Debug and Log*: Enables/disables system logs.
	
-----------
