<?php
function BRNGBOKGPTDY_creating_booking($data){

  try {

    // check admin
    if (!BRNGBOKGPTDY_Utils::restrict_to_admin()) {
      throw new Exception('You do not have permission to access this endpoint');
    }

    $req_body = $data->get_params();

    // validate input
    if(
      !BRNGBOKGPTDY_Utils::is_integer($req_body['order_id']) ||
      !$req_body['shipping_time'] || !is_array($req_body['shipping_time'])
    ) {
      throw new Exception('Invalid input data');
    }

    $shipping_time = $req_body['shipping_time'];

    // validate shipping_time
    if(
      !BRNGBOKGPTDY_Utils::is_integer($shipping_time['year']) ||
      !BRNGBOKGPTDY_Utils::is_integer($shipping_time['month']) ||
      !BRNGBOKGPTDY_Utils::is_integer($shipping_time['day']) ||
      !BRNGBOKGPTDY_Utils::is_integer($shipping_time['hour']) ||
      !BRNGBOKGPTDY_Utils::is_integer($shipping_time['minute'])
    ) {
      throw new Exception('Invalid input data');
    }

    $shipping_time = [
      'year' => (int)$shipping_time['year'],
      'month' => (int)$shipping_time['month'],
      'day' => (int)$shipping_time['day'],
      'hour' => (int)$shipping_time['hour'],
      'minute' => (int)$shipping_time['minute']
    ];

    if(
      $shipping_time['year'] < 2000 || $shipping_time['year'] > 3000 ||
      $shipping_time['month'] < 1 || $shipping_time['month'] > 12 ||
      $shipping_time['day'] < 1 || $shipping_time['day'] > 31 ||
      $shipping_time['hour'] < 0 || $shipping_time['hour'] > 24 ||
      $shipping_time['minute'] < 0 || $shipping_time['minute'] > 60
    ) {
      throw new Exception('Invalid input data');
    }

    // generate shipping date
    if(strlen("".$shipping_time['month']) < 2) {
      $shipping_time['month'] = "0".$shipping_time['month'];
    }

    if(strlen("".$shipping_time['day']) < 2) {
      $shipping_time['day'] = "0".$shipping_time['day'];
    }

    if(strlen("".$shipping_time['hour']) < 2) {
      $shipping_time['hour'] = "0".$shipping_time['hour'];
    }

    if(strlen("".$shipping_time['minute']) < 2) {
      $shipping_time['minute'] = "0".$shipping_time['minute'];
    }

    $shipping_date = $shipping_time['year']."-".$shipping_time['month']."-".$shipping_time['day'].
    "T".$shipping_time['hour'].":".$shipping_time['minute'].".000+02:00";


    // throw new Exception('test error from rest');
    // return new WP_REST_Response(array('status' => 'Booking Created'), 201);



    $booking_res = BRNGBOKGPTDY_Order::bring_order_handler([
      'order_id' => $req_body['order_id'],
      'shipping_time' => $shipping_date,
      'weight' => 1
    ]);

    if($booking_res !== 'ORDER_COMPLETED') {
      throw new Exception($booking_res);
    }

    return new WP_REST_Response(array('status' => 'Booking Created'), 201);

  } 
  catch (Exception $e) {
    return new WP_Error(
      'rest_forbidden',
      $e->getMessage(),
      array('status' => 400)
    );
  }
}