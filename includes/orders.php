<?php

if (!class_exists('BRNGBOKGPTDY_Order')) :

  class BRNGBOKGPTDY_Order {

    private $meta_booking_res;
    private static $meta_booking_res_ = 'brngbokgptdy_bring_booking';

    function __construct() {
      
      $this->meta_booking_res = 'brngbokgptdy_bring_booking';

      // order action
      add_action(
        'woocommerce_admin_order_data_after_shipping_address', 
        array($this, 'admin_dashboard_ui')
      );

      add_action(
        'woocommerce_order_details_after_order_table', 
        array($this, 'user_detail_dashboard_ui')
      );
      
    }


    // ================= dashboard =================
    public function admin_dashboard_ui($order) {


      if (!$order) return null;

      $order_id = $order->get_id();

      if (!$order_id) return null;


      $booking_html = "";

      // check order is already booked
      $booking_saved_meta = get_post_meta(
        $order_id, 
        $this->meta_booking_res, 
        true
      );

      if($booking_saved_meta) {

        try {

          $booking_saved_meta = json_decode($booking_saved_meta);

          if(!$booking_saved_meta) {
            throw new Exception('invalid saved data');
          }

          $booking_html = "
            <p> 
              Label: <a href='$booking_saved_meta->labels' > Check Label </a> 
            <p>
          ";

        }
        catch(Exception $e) {
          $booking_html = "
            <p> Corrupted booking data <p>
          ";
        }

      }
      else {

        // Button HTML code
        $booking_html = "
        <div style='margin-top: 12px;' >
          <span class='button button-primary' 
            id='bringBooking_create_btn'
            orderid='$order_id'
          >
            Create Booking
          </span>
        </div>";
      }


      echo "
        <div  style='margin-top: 15px;' >
          <h3> Bring Booking </h3>
          $booking_html
        </div>
      ";

    }

    // ================= user dashboard detail =================
    public function user_detail_dashboard_ui($order) {

      if (!$order) return null;

      $order_id = $order->get_id();

      $booking_html = "";

      // check order is already booked
      $booking_saved_meta = get_post_meta(
        $order_id, 
        $this->meta_booking_res, 
        true
      );

      if(!$booking_saved_meta) return null;

      try {

        $booking_saved_meta = json_decode($booking_saved_meta);

        if(!$booking_saved_meta || !$booking_saved_meta->consignmentNumber) {
          throw new Exception('invalid booking data');
        }

        $tracking_id = $booking_saved_meta->consignmentNumber;

        $track_res = BRNGBOKGPTDY_Api_Fetch::get_tracking($tracking_id);

        if($track_res['status'] !== 'SUCCESS') {
          throw new Exception($track_res['msg']);
        }

        $packageNumber = $track_res['packageNumber'];

        $event_html = "";

        foreach($track_res['eventList'] as $evenItem) {

          if($evenItem->insignificant) continue;

          $date = DateTime::createFromFormat('d.m.Y', $evenItem->displayDate);
          $formattedDate = $date->format('j F Y');

          $current_html = "
            <li>
              <div class='bring_booking_ol_deliverRightCol'>
                <div class='bring_booking_ol_circle'></div>
                <div class='bring_booking_ol_line'></div>
              </div>
              <div class='bring_booking_ol_deliverLeftCol'>
                <h4> $evenItem->description </h4>
                <p class='date' > $formattedDate at $evenItem->displayTime </p>
                <p class='place' > $evenItem->postalCode $evenItem->city </p>
              </div>
            </li>
          ";

          $event_html = $event_html.$current_html;

        }

        $booking_html = "
          <h3 class='bring_booking_detail_dt'> Parcel number: $packageNumber </h3>
          <h3 class='bring_booking_detail_dt'> Shipment number: $tracking_id </h3>

          <ul class='bring_booking_timeline'>
            $event_html
          </ul>
        ";


      }
      catch(Exception $e) {

        $err_msg = 'invalid booking data';

        if($e->getMessage()) {
          $err_msg = $e->getMessage();
        }

        $booking_html = "
          <h3 class='bring_booking_err_msg'> $err_msg </h3>
        ";
      }

      echo "
      <div class='bring_booking_contentRoot'>
        <h2 class='bring_booking_rootTitle' > Shipment Detail </h2>
        $booking_html
      </div>
      ";

    }

    // ================= create bring order =================
    public static function bring_order_handler($params) {
      try {

        $booking_delivary_id = null;

        if(
          !BRNGBOKGPTDY_Utils::check_arr_property($params, 'order_id') ||
          !BRNGBOKGPTDY_Utils::is_integer($params['order_id'])
        ) {
          throw new Exception('Invalid Order Id');
        }


        $order_id = $params['order_id'];

        // check if booking is already completed
        $booking_saved_meta = get_post_meta(
          $order_id, 
          self::$meta_booking_res_, 
          true
        );

        if($booking_saved_meta) {
          return 'This Order already booked';
        }


        if(
          !BRNGBOKGPTDY_Utils::check_arr_property($params, 'shipping_time')
        ) {
          throw new Exception('Shipping time is missing');
        }

        $order = wc_get_order($order_id);
        $total_weight = 0;
        $weight_unit = get_option('woocommerce_weight_unit');

        if($weight_unit !== 'kg') {
          throw new Exception('Woocommerce weight unit have to be in KG');
        }

        if (!$order) {
          throw new Exception('order not found');
        }

        $order_data = $order->get_data();


        // check shipping delivary process
        $shipping_lines = $order->get_shipping_methods();

        foreach ( $shipping_lines as $shipping_line ) {

          $shipping_method_id = $shipping_line->get_method_id();
          $shipping_method_title = $shipping_line->get_name();


          if($shipping_method_id === 'flexible_shipping') {

            // pickup
            if( $shipping_method_title === "Levering til nærmeste post i butikk" ) {
              $booking_delivary_id = "5800";
            }
            // mailbox
            else if( $shipping_method_title === "Rett i postkassen" ) {
              $booking_delivary_id = "3584";
            }

          }

          if($booking_delivary_id) break;
          
        }

        if(!$booking_delivary_id) {
          throw new Exception('Only Mail and Pickup delivary method is acceptable');
        }

        $shipping_data = $order_data['shipping'];
        $billing_data = $order_data['billing'];


        $order_item_list = $order->get_items();

        foreach ($order_item_list as $item) {
         
          $quantity = $item->get_quantity();

          $product_id = $item->get_product_id();
          $variation_id = $item->get_variation_id();
          
          $item_id = $product_id;

          if($variation_id) {
            $item_id = $variation_id;
          }

          $product = wc_get_product($item_id);

          if(!$product) {
            throw new Exception('invalid woocommerce product (id:'.$item_id.")");
          }

          $product_weight = $product->get_weight();

          if(!$product_weight) {
            throw new Exception("Weight is not provided for product (id:".$item_id.")");
          }

          $total_weight = $total_weight + (floatval($product_weight) * $quantity);

        }


        if($total_weight > 1) {
          $total_weight = floatval(number_format($total_weight, 1));
        }


        // ============== generate receiver data
        $receiver_data = [];

        // ----------- validate address
        if (
          !array_key_exists('address_1', $shipping_data) ||
          !$shipping_data['address_1']
        ) {
          throw new Exception('Receiver Address is missing');
        }

        if (strlen($shipping_data['address_1']) > 35) {
          throw new Exception('Max length of Receiver Address is 35');
        }

        $receiver_data['addressLine'] = $shipping_data['address_1'];

        // ----------- validate addressLine2
        if (
          array_key_exists('address_2', $shipping_data) &&
          $shipping_data['address_2'] &&
          strlen($shipping_data['address_2']) > 35
        ) {
          throw new Exception('Max length of Receiver Address 2 is 35');
        }

        if (
          array_key_exists('address_2', $shipping_data) &&
          $shipping_data['address_2']
        ) {
          $receiver_data['addressLine2'] = $shipping_data['address_2'];
        }

        // ----------- validate city
        if (
          !array_key_exists('city', $shipping_data) ||
          !$shipping_data['city']
        ) {
          throw new Exception('Receiver City is missing');
        }

        if (strlen($shipping_data['city']) > 35) {
          throw new Exception('Max length of Receiver City is 35');
        }

        $receiver_data['city'] = $shipping_data['city'];

        // ----------- validate country
        if (
          !array_key_exists('country', $shipping_data) ||
          !$shipping_data['country']
        ) {
          throw new Exception('Receiver Country is missing');
        }

        if (strlen($shipping_data['country']) !== 2) {
          throw new Exception('The length of Receiver Country has to be 2');
        }

        $receiver_data['countryCode'] = $shipping_data['country'];

        // ----------- validate name
        if (
          (
            !array_key_exists('first_name', $shipping_data) ||
            !$shipping_data['first_name']
          )
          &&
          (
            !array_key_exists('last_name', $shipping_data) ||
            !$shipping_data['last_name']
          )
        ) {
          throw new Exception('Receiver Name is missing');
        }

        $shipping_data_name = '';
        if (array_key_exists('first_name', $shipping_data) && $shipping_data['first_name']) {
          $shipping_data_name = $shipping_data['first_name'];
        }
        if (array_key_exists('last_name', $shipping_data) && $shipping_data['last_name']) {
          $shipping_data_name = $shipping_data_name . " " . $shipping_data['last_name'];
        }

        $receiver_data['name'] = $shipping_data_name;
        $receiver_data['contact']['name'] = $shipping_data_name;


        // ----------- validate postalCode
        if (
          !array_key_exists('postcode', $shipping_data) ||
          !$shipping_data['postcode']
        ) {
          throw new Exception('Receiver postalCode is missing');
        }

        $receiver_data['postalCode'] = $shipping_data['postcode'];


        // ----------- validate email
        if (
          !array_key_exists('email', $billing_data) ||
          !$billing_data['email']
        ) {
          throw new Exception('Receiver Contact Email is missing');
        }

        $receiver_data['contact']['email'] = $billing_data['email'];


        // ----------- validate phone
        if (
          array_key_exists('phone', $billing_data) &&
          $billing_data['phone']
        ) {
          $receiver_data['contact']['phoneNumber'] = $billing_data['phone'];
        }

        // ----------- add reference (user_id) if exist

        $correlationId = 'wc-' . $order_id;

        $booking_data = array(
          "consignments" => array(
            array(
              "correlationId" => $correlationId,
              "packages" => array(
                array(
                  "containerId" => null,
                  "correlationId" => $correlationId,
                  "dimensions" => new stdClass(),
                  "goodsDescription" => "",
                  "packageType" => null,
                  "weightInKg" => $total_weight,
                ),
              ),
              "parties" => array(
                "pickupPoint" => null,

                // recipient shipping data
                "recipient" => $receiver_data,

                // sender shipping data
                "sender" => array(
                  "addressLine" => "Espeværvegen 57",
                  "addressLine2" => null,
                  "city" => "ESPEVÆR",
                  "contact" => array(
                    "email" => BRNGBOKGPTDY_Api_Fetch::$api_uid, // add the email
                    "name" => "--",
                    "phoneNumber" => "--",
                  ),
                  "countryCode" => "NO",
                  "name" => "--",
                  "postalCode" => "--",
                  "reference" => $correlationId
                ),
              ),
              "product" => array(
                "additionalServices" => [],
                "customerNumber" => BRNGBOKGPTDY_Api_Fetch::$api_clientNumber,
                "id" => $booking_delivary_id,
              ),
              "shippingDateTime" => $params['shipping_time'],
              // "shippingDateTime" => "2024-04-28T12:30:00.000+02:00",
            ),
          ),
          "schemaVersion" => 1,
          // "testIndicator" => true,
          "testIndicator" => false,
        );

        $booking_created_res = BRNGBOKGPTDY_Api_Fetch::create_booking($booking_data);


        if($booking_created_res['status'] === 'ERROR') {
          throw new Exception($booking_created_res['msg']);
        }

        if(
          $booking_created_res['status'] !== 'SUCCESS' ||
          !$booking_created_res['data']
        ) {
          throw new Exception('Booking Completed, But Failed to save response data');
        }

        update_post_meta(
          $order_id, 
          self::$meta_booking_res_, 
          $booking_created_res['data'] 
        );

        return 'ORDER_COMPLETED';
       
      } 
      catch (Exception $e) {

        $err_res = 'Failed to create Booking';

        if($e->getMessage()) {
          $err_res = $e->getMessage();
        }

        return $err_res;

      }
    }
  }

endif;
