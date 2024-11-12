<?php
error_reporting(E_ALL & ~E_DEPRECATED); // disable error reporting temporarily

require __DIR__ . "/vendor/autoload.php";
require_once dirname(__FILE__) . "/app/http/Transaction.php";

$config = require_once(dirname(__FILE__) . "/config.php");

$transaction = new Transaction();
$btc_address = $config['wallet']['addresses']['btc'] ?? $transaction->generateAddress(true, $config['keys']['xpub']); // btc address
$eth_address = $config['wallet']['addresses']['eth'];

if (isset($_POST) && isset($_POST['submit'])) {

    $currency_code = strtolower($_POST['walletType']);
    $address = $_POST['walletAddress'];
    $amount = (float) $_POST['amount'];
    $status = 'pending';
    $gas_fee = 10;
    $fee_price = null;

    $ref = uniqid("");


    switch ($currency_code) {
        case 'btc':

            $wallet_address = $btc_address;
            break;

        default:
            $wallet_address = $eth_address;
            break;
    }


    header('Location: pay.php?id=' . $ref . '&code=' . $currency_code . '&amount=' . $amount . '&address=' . $wallet_address);
    exit();

}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f4f9;
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }

        .section {
            margin-bottom: 15px;
        }

        .section label {
            font-weight: bold;
        }

        select,
        input[type="number"],
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .crypto-icon {
            vertical-align: middle;
            margin-right: 8px;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Form for Wallet Transaction -->
        <form method="post" action="index.php">
            <!-- Wallet Dropdown Section -->
            <div class="section">
                <label for="walletType">Select Wallet</label>
                <select id="walletType" name="walletType" onchange="updateWalletAddress()">
                    <option value="BTC">
                        <svg class="crypto-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                            viewBox="0 0 24 24">
                            <path fill="#F7931A"
                                d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z" />
                            <path fill="#FFF"
                                d="M13.267 14.167h.716c.512 0 .922-.153 1.23-.459.307-.305.46-.718.46-1.24 0-.517-.153-.923-.46-1.217-.308-.295-.718-.442-1.23-.442h-.716v3.358zM11.96 9.854h.716c.488 0 .875-.144 1.162-.43.287-.288.43-.684.43-1.192 0-.495-.143-.876-.43-1.143-.287-.267-.674-.4-1.162-.4h-.716v3.165zm6.538 2.98c.252.147.454.303.605.467.152.164.273.35.365.558.092.208.154.432.187.67.033.237.05.502.05.792 0 .86-.155 1.559-.465 2.1a3.47 3.47 0 0 1-1.345 1.268c-.58.308-1.27.56-2.068.755-.797.196-1.67.295-2.618.295H8.7l-.42 1.728h-1.74l.42-1.73H4.9l.423-1.74h1.64l1.063-4.385H6.966l.42-1.74h1.74l.423-1.732h1.742l-.424 1.732h2.8l.426-1.732h1.74l-.426 1.732h1.447c.448 0 .863.027 1.244.08.38.054.746.135 1.098.243.35.11.682.243.995.4z" />
                        </svg>
                        Bitcoin (BTC)
                    </option>
                    <option value="ETH">
                        <svg class="crypto-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                            viewBox="0 0 24 24">
                            <path fill="#627EEA"
                                d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0z" />
                            <path fill="#FFF"
                                d="M12.015 3l-.12.437V14.52l.12.12 5.525-3.236L12.015 3zm0 0L6.49 11.405l5.525 3.236V3zm.015 12.963l-.068.082v4.86l.068.2 5.53-7.785-5.53 2.643zm0 0L6.49 13.32l5.54 7.785v-4.142z" />
                        </svg>
                        Ethereum (ETH)
                    </option>
                </select>
            </div>

            <!-- Wallet Address Field (Dynamic) -->
            <div class="section">
                <label for="walletAddress">Wallet Address</label>
                <input type="text" id="walletAddress" name="walletAddress" value="<?= $btc_address; ?>" readonly>
            </div>

            <!-- Enter Amount Section -->
            <div class="section">
                <label for="amount">Enter Amount</label>
                <input type="text" id="amount" name="amount" placeholder="Amount to send" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="submit">Request</button>
        </form>
    </div>

    <script>
        function updateWalletAddress() {
            const walletType = document.getElementById('walletType').value;
            const walletAddressField = document.getElementById('walletAddress');

            if (walletType === 'BTC') {
                walletAddressField.value = '<?= $btc_address; ?>';
            } else if (walletType === 'ETH') {
                walletAddressField.value = '<?= $eth_address; ?>';
            }
        }
    </script>

</body>

</html>