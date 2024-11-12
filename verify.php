<?php


if (isset($_GET['address']) && isset($_GET['amount']) && isset($_GET['id']) && $_GET['code']) {
    $address = $_GET['address'];
    $code = $_GET['code'];
    $id = $_GET['id'];
    $amount = $_GET['amount'];


    require_once(dirname(__FILE__) ."/app/http/Transaction.php");
    $transaction = new Transaction();


    if ( $code === 'btc' ) {
        $result = $transaction->checkTransactions('btc', $address, $amount);
    }
    else  {
        $result = $transaction->checkTransactions( 'eth', $address, $amount);

    }
    
    if (isset($result['verified']) && $result['verified'] === true) {
        echo json_encode(['status' => 'success', 'message'=> $result['status'] ]);
    }
    else {
        echo json_encode(['status' => 'error', 'message'=> 'No transaction made']);
    }


}