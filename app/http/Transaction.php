<?php


use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Transaction\Factory\TransactionBuilder;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;

class Transaction
{
    protected $services = [
        'eth' => [
            'url' => 'https://api.etherscan.io/api?module=account&action=txlist&address={address}&startblock=0&endblock=99999999&sort=desc&apikey=YOUR_API_KEY_HERE',
        ],

        'btc' => [
            'url' => 'https://blockstream.info/api/address/{address}/txs'
        ],
        'rpc' => [
            'eth' => [
                'url' => 'https://eth.public-rpc.com'
            ]
        ],
    ];
    public function __construct(){}
    public function getKeys()
    {
        $network = Bitcoin::getNetwork();
        $privKeyFactory = new PrivateKeyFactory();
        $privateKey = $privKeyFactory->create(true);
        $publicKey = $privateKey->getPublicKey();

        return [
            'private' => [
                'wif' => $privateKey->toWif($network),
                'hex' => $privateKey->getHex(),
                'dec' => gmp_strval($privateKey->getSecret(), 10)
            ],
            'public' => [
                'hex' => $publicKey->getHex(),
                'hash' => $publicKey->getPubKeyHash()->getHex(),
                'obj_hash' => $publicKey->getPubKeyHash()
            ],

        ];
    }

    public function generateAddress($useXpub = false, $xpubKey = null)
    {

        if (!$useXpub) {
            $keys = $this->getKeys(); // generates keys, public keys and private keys
            $address = new PayToPubKeyHashAddress($keys['public']['obj_hash']);
            return $address->getAddress();
        }

        return $this->generateAdressFromXpub($xpubKey);
    }

    public function generateAdressFromXpub($xpub)
    {
        $path = '0/0';
        $pubkeytype = substr($xpub, 0, 4);
        $bitcoin_prefixes = new BitcoinRegistry();
        $adapter = Bitcoin::getEcAdapter();
        $slip132 = new Slip132(new KeyToScriptHelper($adapter));
        if ($pubkeytype == 'xpub') {
            $pubPrefix = $slip132->p2pkh($bitcoin_prefixes);
        }
        if ($pubkeytype == 'ypub') {
            $pubPrefix = $slip132->p2shP2wpkh($bitcoin_prefixes);
        }
        if ($pubkeytype == 'zpub') {
            $pubPrefix = $slip132->p2wpkh($bitcoin_prefixes);
        }
        if (is_array($path)) {
            $path = '0/' . $path[0];
        }
        $config = new GlobalPrefixConfig([new NetworkConfig(NetworkFactory::bitcoin(), [$pubPrefix])]);
        $serializer = new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter, $config));
        $key = $serializer->parse(NetworkFactory::bitcoin(), $xpub);
        $child_key = $key->derivePath($path);
        $address = $child_key->getAddress(new AddressCreator())->getAddress();
        return $address;

    }

    public function checkTransactions(string $currency_code = 'btc', $address = '', string $expectedAmount = '')
    {
        $services = $this->services[$currency_code];

        $url = str_replace('{address}',$address, $services['url']);
        if ($currency_code === 'btc') {
            return $this->btcTransactionCheck($url, $address, $expectedAmount);

        } else {
            // return $this->ethTransactionCheck($url, $address, $expectedAmount); // this will be slower than RPC due to large transactions made 
            $blockData =  $this->ethRpcGetBlockTransaction($address);
            return $this->checkEthRpcIncomingTransactions($blockData, $address, $expectedAmount);

        }
    }

    private function btcTransactionCheck($url = '', $address = '', string $expectedAmount = '')
    {
        // Initialize cURL to fetch data from the API
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response to get transactions
        $transactions = json_decode($response, true);

        foreach ($transactions as $tx) {
            // Check if the transaction has the expected amount sent to the address
            foreach ($tx['vout'] as $output) {

                $output_address = $output['scriptpubkey_address'] ?? '';
                $output_value_btc = $output['value'] / 100000000; // Convert from satoshis to BTC

                // Check if this output matches the recipient address and expected amount
                if ($output_address === $address && abs($output_value_btc - $expectedAmount) < 0.00000001) {
                    return [
                        'status' => 'Payment received',
                        'txid' => $tx['txid'],
                        'amount' => $expectedAmount,
                        'verified' => true
                    ];
                }
            }
        }


        return ['status' => 'No matching payment found'];
    }

    private function ethTransactionCheck($url = '', $address = '', string $expectedAmount = '')
    {

        // Fetch transaction data
        $response = file_get_contents($url);
        $transactionData = json_decode($response, true);

        // Check if API returned a successful response
        if ($transactionData['status'] != "1") {
            return "Error retrieving transaction data or no transactions found.";
        }

        // Loop through transactions and check for incoming matches
        foreach ($transactionData['result'] as $transaction) {
            if (strtolower($transaction['to']) === strtolower($address) && $transaction['isError'] === "0") {
                // Convert the amount from Wei to Ether
                $amountInEth = hexdec($transaction['value']) / 1e18;

                // Check if the received amount matches expected amount
                if ($amountInEth == $expectedAmount) {
                    return [
                        'status' => 'Payment received',
                        'amount' => $expectedAmount,
                        'verified' => true
                    ];
                }
            }
        }


        return ['status' => 'No matching payment found'];



    }

    public function ethRpcGetBlockTransaction($address = '', $blockNumber = 'latest')
    {

        $rpcUrl = $this->services['rpc']['eth']['url'];

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getBlockByNumber',
            'params' => [$blockNumber, true],
            'id' => 1
        ];

        $ch = curl_init($rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }


    public function checkEthRpcIncomingTransactions($blockData, $walletAddress, $expectedAmountInEth)
    {
        foreach ($blockData['result']['transactions'] as $transaction) { // Convert from Wei to Ether
            if (strtolower(string: $transaction['to']) === strtolower($walletAddress)) {
                // Convert from Wei to Ether
                $amountInEth = hexdec($transaction['value']) / 1e18;
                if ($amountInEth == $expectedAmountInEth) {
                    return [
                        'status' => 'Payment received',
                        'amount' => $amountInEth,
                        'verified' => true
                    ];
                }
            }
        }

        return ['status' => 'No matching payment found'];
    }

}
