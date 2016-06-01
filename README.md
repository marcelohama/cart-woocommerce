# Woo Mercado Pago Module

* [Features](#features)
* [Available versions](#available_versions)
* [Installation](#installation)
* [Configuration](#configuration)

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
*Available for Argentina, Brazil, Colombia, Mexico and Venezuela*

**Tickets**<br />
This option enables merchants to give their customers the option to pay via tickets.<br />
*Available for Argentina, Brazil, Chile, Colombia, Mexico and Venezuela*

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
      <td>v2.0.0</td>
      <td>Stable (Current version)</td>
      <td>WooCommerce 2.1.x - 2.5.x</td>
    </tr>
  </tbody>
</table>

<a name="installation"></a>
##Installation##

You have two way to install this module: from your WordPress Store, or by downloading and manually copying the module directory.

**Install from WordPress**<br />
1. In the left side menu, go to *Plugins > Add New*;
2. Type "Woo Mercado Pago Module" in the *Search Plugins* text field. Press Enter;
3. You should find the module read to be installed. Click install.

**Manual Download**<br />
1. Get the module sources from a repository (<a href="https://github.com/mercadopago/cart-woocommerce/archive/master.zip">Github</a> or <a href="https://downloads.wordpress.org/plugin/woo-mercado-pago-module.2.0.0.zip">WordPress Plugin Directory</a>);
2. Unzip the folder and find "woo-mercado-pago-module" directory;
3. Copy "woo-mercado-pago-module" directory to *[WordPressRootDirectory]/wp-content/plugins/* directory.

To confirm that your module is really installed, you can click in **Plugins** item at the left side menu.

![Features](https://raw.github.com/marcelohama/cart-woocommerce/dev_a/README.img/plugin_adm.png)



-----------
-----------


1. Copy **cart-woocommerce/woo-mercado-pago-module** folder to **[WordPressRootDirectory]/wp-content/plugins/** folder.

2. On your store administration, go to **Plugins** option in sidebar.

3. Search by **WooCommerce Mercado Pago** and click enable. <br />
You will receive the following message: "Plugin enabled." as a notice in your WordPress.

<a name="configuration"></a>
##Configuration##

1. Go to **WooCommerce > Configuration > Checkout Tab** and look for **Mercado Pago - Standard Checkout** or **Mercado Pago - Custom Checkout**. <br />
For **Mercado Pago - Standard Checkout**, you need to configure your credentials **Client_id** and **Client_secret** in Standard Checkout Credentials section.
	
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_credentials.png) <br />
	
	You can obtain your **Client_id** and **Client_secret**, accordingly to your country, in the following links:

	* Argentina: https://www.mercadopago.com/mla/herramientas/aplicaciones
	* Brazil: https://www.mercadopago.com/mlb/ferramentas/aplicacoes
	* Chile: https://www.mercadopago.com/mlc/herramientas/aplicaciones
	* Colombia: https://www.mercadopago.com/mco/herramientas/aplicaciones
	* Mexico: https://www.mercadopago.com/mlm/herramientas/aplicaciones
	* Venezuela: https://www.mercadopago.com/mlv/herramientas/aplicaciones

For **Mercado Pago - Custom Checkout**, you need to configure your credentials **Public Key**  and **Access Token** in Custom Checkout Credentials section.

	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_credentials_custom.png) <br />
	
	You can obtain your **Public Key** and **Access Token**, accordingly to your country, in the following links:

	* Argentina: https://www.mercadopago.com/mla/account/credentials?type=custom
	* Brazil: https://www.mercadopago.com/mlb/account/credentials?type=custom
	* Chile: https://www.mercadopago.com/mlc/account/credentials?type=custom
	* Colombia: https://www.mercadopago.com/mco/account/credentials?type=custom
	* Mexico: https://www.mercadopago.com/mlm/account/credentials?type=custom
	* Venezuela: https://www.mercadopago.com/mlv/account/credentials?type=custom
	
2. Common configurations for **Standard Checkout** and **Custom Checkout**. <br />
	* **Instant Payment Notification (IPN) URL**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_ipn.png) <br />
	The highlighted URL is where you will get notified about payment updates.<br /><br />
	* **Checkout Options**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_checkout.png) <br />
	**Title**: This is the title of the payment option that will be shown to your customers;<br />
	**Description**: This is the description of the payment option that will be shown to your customers;<br />
	**Store Category**: Sets up the category of the store;<br />
	**Store Identificator**: A prefix to identify your store, when you have multiple stores for only one Mercado Pago account;<br /><br />
	* **Payment Options**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_payment.png) <br />
	**Max Installments**: The maximum installments allowed for your customers;<br />
	**Exclude Payment Methods**: Select the payment methods that you want to not work with Mercado Pago.<br /><br />
	* **Test and Debug Options**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_testdebug.png) <br />
	**Mercado Pago Sandboxs**: Test your payments in Mercado Pago sandbox environment;<br />
	**Debug and Log**: Enables/disables system logs.<br />
	
3. Specific configurations for **Standard Checkout**. <br />
	* **Checkout Options**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_checkout_standard.png) <br />
	**Integration Method**: How your customers will interact with Mercado Pago to pay their orders;<br />
	**iFrame Width**: The width, in pixels, of the iFrame (used only with iFrame Integration Method);<br />
	**iFrame Height**: The height, in pixels, of the iFrame (used only with iFrame Integration Method);<br />
	**Auto Return**: If set, the platform will return to your store when the payment is approved.<br />

4. Specific configurations for **Custom Checkout**. <br />
	* **Checkout Options**
	![Installation Instructions](https://raw.github.com/mercadopago/cart-woocommerce/master/README.img/wc_setup_checkout_custom.png) <br />
	**Ticket**: Enable this option to let your customer to pay via ticket;<br />