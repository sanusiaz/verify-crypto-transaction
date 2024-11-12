<?php

if ( isset( $_GET['id'] ) ) {
    $id = $_GET['id'];
    $amount = $_GET['amount'];
    $code = $_GET['code'];
    $address = $_GET['address'];

    $rate =  0.015;
    $fee = $amount * $rate;
    $fee = number_format($fee, 8, '.', '');

    require_once(dirname(__FILE__) ."/app/http/Transaction.php");
    $transaction = new Transaction();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .payment-container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        .payment-container h2 {
            margin-top: 0;
            text-align: center;
            color: #333;
        }
        .payment-details {
            margin: 20px 0;
        }
        .payment-details p {
            margin: 10px 0;
            font-size: 16px;
            color: #555;
        }
        .crypto-address {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .crypto-address input {
            width: 70%;
            padding: 8px;
            font-size: 14px;
        }
        .crypto-address button {
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 4px;
        }
        .countdown {
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
            color: #e74c3c;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <div class="payment-container">
        <h2>Receive Payment</h2>

        <div id="status"></div>
        <div class="payment-details">
            <p><strong>Cryptocurrency:</strong> <span id="crypto-type"><?= $code;?></span></p>
            <div class="crypto-address">
                <input type="text" id="crypto-address" value="<?= $address;?>" readonly>
                <button onclick="copyAddress()">Copy</button>
            </div>
            <p><strong>Amount:</strong> <span id="amount"><?= $amount;?></span> <?= $code;?></p>
            <p id="fee-container"><strong>Fee:</strong> <span id="fee"><?= $fee . ' ' . $code;?></span></p>
        </div>
        <div class="countdown" id="cc">
            Payment expires in: <span id="timer">60:00</span>
        </div>
    </div>

    <script>

        // Function to copy the crypto address to clipboard
        function copyAddress() {
            const addressField = document.getElementById('crypto-address');
            addressField.select();
            addressField.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');
            alert('Address copied to clipboard!');
        }

        // Countdown Timer
        function startCountdown(duration, display) {
            let timer = duration, minutes, seconds;
            const countdownInterval = setInterval(() => {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(countdownInterval);
                    display.textContent = "EXPIRED";
                    // Optionally, disable payment or notify the user
                }
            }, 1000);
        }

        window.onload = function () {
            const duration = 60 * 60; // 60 minutes in seconds
            const display = document.getElementById('timer');
            startCountdown(duration, display);
        };

        // Example: Hide fee if not applicable
        // To hide the fee section, uncomment the following lines:
        
        // window.onload = function () {
        //     const feeContainer = document.getElementById('fee-container');
        //     const hasFee = false; // Set to true if fee is applicable

        //     if (!hasFee) {
        //         feeContainer.classList.add('hidden');
        //     }

        //     const duration = 60 * 60; // 60 minutes in seconds
        //     const display = document.getElementById('timer');
        //     startCountdown(duration, display);
        // };
        

    // Function to check transaction status by making an AJAX request to verify.php
    function checkTransactionStatus() {
        fetch('verify.php?id=<?= $id;?>&address=<?= $address;?>&amount=<?= $amount;?>&code=<?= $code;?>&fee=<?= $fee;?>')
            .then(response => response.json())
            .then(data => {
                console.log(data)
                // Update status based on the server response
                if (data.status === 'success') {
                    document.getElementById('status').innerText = "Transaction successful!";
                    document.getElementById('cc').innerText = "";
                    clearInterval(statusInterval); // Stop the interval once the transaction is successful
                } else {
                    document.getElementById('status').innerText = "Transaction not confirmed yet. Checking again...";
                }
            })
            .catch(error => {
                console.error("Error checking transaction:", error);
            });
    }

    // Set an interval to check the transaction status every 10 seconds
    let statusInterval = setInterval(checkTransactionStatus, 10000);
</script>



</body>
</html>
