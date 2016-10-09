<?php
/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer - Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

class MPPreference {

    private $id;
    private $items;
    private $payer;
    private $payment_methods;
    private $shipments;
    private $back_urls;
    private $notification_url;
    private $init_point;
    private $sandbox_init_point;
    private $date_created;
    private $operation_type;
    private $additional_info;
    private $auto_return;
    private $external_reference;
    private $expires;
    private $expiration_date_from;
    private $expiration_date_to;
    private $collector_id;
    private $client_id;
    private $marketplace;
    private $marketplace_fee;
    private $differential_pricing;

    function __construct() {

    }

    function buildConsumablePreference() {

    }

}

?>
