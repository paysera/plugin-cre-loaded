<?php
require('includes/application_top.php');
require(DIR_WS_MODULES . 'payment/libwebtopay/WebToPay.php');


$query     = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = 'MODULE_PAYMENT_PAYSERA_PROJECT_ID'");
$data      = tep_db_fetch_array($query);
$projectID = $data['configuration_value'];

$query       = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = 'MODULE_PAYMENT_PAYSERA_PROJECT_PASS'");
$data        = tep_db_fetch_array($query);
$projectPass = $data['configuration_value'];

try {
    WebToPay::toggleSS2(true);
    $response = WebToPay::checkResponse($_REQUEST, array(
        'projectid'     => $projectID,
        'sign_password' => $projectPass,
    ));

    if ($response['status'] == 1) {

        $orderID = $response['orderid'];

        $query  = tep_db_query("SELECT `value` FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . $orderID . "'");
        $amount = tep_db_fetch_array($query);

        $query    = tep_db_query("SELECT `currency` FROM " . TABLE_ORDERS . " WHERE orders_id = '" . $orderID . "'");
        $currency = tep_db_fetch_array($query);

        $Order = array('currency' => $currency['currency'], 'total' => $amount['value']);

        if ($response['amount'] < intval(number_format($Order['total'], 2, '', ''))) {
            exit('Bad amount!');
        }
        if ($Order['currency'] != $response['currency']) {
            exit('Bad currency!');
        }

        tep_db_query('UPDATE ' . TABLE_ORDERS . ' SET orders_status = 2 WHERE orders_id = ' . $orderID);
        tep_db_query('UPDATE ' . TABLE_ORDERS_STATUS_HISTORY . ' SET orders_status_id = 2 WHERE orders_status_history_id = ' . $orderID);

        exit('OK');
    }
} catch (Exception $e) {
    exit(get_class($e) . ': ' . $e->getMessage());
}
