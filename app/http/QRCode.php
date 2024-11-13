<?php
use Endroid\QrCode\QrCode as code;
use Endroid\QrCode\Writer\PngWriter;

class QRCode
{

    protected $address;
    public function __construct($address = '')
    {
        $this->address = $address;
    }

    public function parseQRCode(): string
    {
        $walletAddress = $this->address;
        $qrCode = new code($walletAddress);
        $writer = new PngWriter();
        $qrCodeImage = $writer->write($qrCode)->getString();
        return base64_encode($qrCodeImage);
    }
}