<?php
if (!class_exists('BRNGBOKGPTDY_Api_Fetch')) :

  class BRNGBOKGPTDY_Api_Fetch {

    public static $api_uid = '--';
    public static $api_key = '--';
    public static $api_clientUrl = '--';
    public static $api_clientNumber = '--';
    public static $api_clientName = '--';

    function __construct() {}


    // ==================================================================
    // ========================= create booking =========================
    public static function create_booking($booking_data) {

      $err_msg = 'Failed to create booking';

      try {

        $url = 'https://api.bring.com/booking-api/api/booking';

        $api_uid = self::$api_uid;
        $api_key = self::$api_key;
        $api_clientUrl = self::$api_clientUrl;

        $headers = array(
          "Content-Type: application/json",
          "Accept: application/json",
          "X-Mybring-API-Uid: $api_uid",
          "X-Mybring-API-Key: $api_key",
          "X-Bring-Client-URL: $api_clientUrl",
        );

        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($booking_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (curl_errno($ch)) {
          throw new Exception('Failed to create booking');
        }

        $response = json_decode($response);

        // check for error code
        if($status_code !== 200) {

          if(count($response->consignments) > 0) {
            $res_consignments = $response->consignments[0];

            if(count($res_consignments->errors) > 0) {
              $res_error = $res_consignments->errors[0];

              if(
                count($res_error->messages) > 0 &&
                $res_error->messages[0]->message
              ) {
                $err_msg = $res_error->messages[0]->message;
              }

            }
            
          }

          throw new Exception($err_msg);

        }

        $booking_res = null;


        if(count($response->consignments) > 0) {

          $res_data = $response->consignments[0]->confirmation;

          $booking_res = [
            'consignmentNumber' => $res_data->consignmentNumber,
            'labels' => $res_data->links->labels,
            // 'tracking' => $res_data->links->tracking,
          ];

          // if(count($res_data->packages) > 0) {
          //   $booking_res['packageNumber'] = $res_data->packages[0]->packageNumber;
          // }

        }

        if(!$booking_res) {
          throw new Exception('Booking Completed, But Failed to save response data');
        }

        return [
          'status' => 'SUCCESS',
          'data' => json_encode($booking_res)
        ];

      }
      catch(Exception $e) {

        if($e->getMessage()) {
          $err_msg = $e->getMessage();
        }

        return [
          'status' => 'ERROR',
          'msg' => $err_msg
        ];
      }
    }


    // ==================================================================
    // ========================= create booking =========================
    public static function get_tracking($consignmentNumber) {

      try {

        $url = "https://api.bring.com/tracking/api/v2/tracking.json?q=$consignmentNumber";

        $api_uid = self::$api_uid;
        $api_key = self::$api_key;
        $api_clientUrl = self::$api_clientUrl;

        $headers = array(
          "Content-Type: application/json",
          "Accept: application/json",
          "X-Mybring-API-Uid: $api_uid",
          "X-Mybring-API-Key: $api_key",
          "X-Bring-Client-URL: $api_clientUrl",
        );

        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (curl_errno($ch)) {
          throw new Exception('Failed to track the package');
        }

        $response = json_decode($response);

        // check for error code
        if($status_code !== 200) {
          throw new Exception('Failed to track the package');
        }


        if(count($response->consignmentSet) < 1) {
          throw new Exception('No package found');
        }

        $consignmentSet = $response->consignmentSet[0];

        // check for error
        if(property_exists($consignmentSet, 'error')) {
          throw new Exception($consignmentSet->error->message);
        }

        if(count($consignmentSet->packageSet) < 1) {
          throw new Exception('No package found');
        }

        $package_data = $consignmentSet->packageSet[0];

       
        return [
          'status' => 'SUCCESS',
          'packageNumber' => $package_data->packageNumber,
          'eventList' => $package_data->eventSet
        ];

      }
      catch(Exception $e) {

        if($e->getMessage()) {
          $err_msg = $e->getMessage();
        }

        return [
          'status' => 'ERROR',
          'msg' => $err_msg
        ];

      }
    }


  }

endif;