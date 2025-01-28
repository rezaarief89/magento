<?php
namespace Wow\QrGenerator\Helper;

use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color as FgColor;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\ValidationException;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Magento\Framework\App\Filesystem\DirectoryList;

class Generator extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    private $logoWidth = 230;

    private $imgMargin = 5;

    private $directoryList;

    public function __construct(
        DirectoryList $directoryList
    ){
        $this->directoryList = $directoryList;
    }

    public function generate($qrValue, $returnUri = false, $type = "invoice")
    {
        $writerLog = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writerLog);

        $writer = new PngWriter();

        // Create QR code
        $qrCode = QrCode::create($qrValue)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh)
            ->setSize($this->logoWidth)
            ->setMargin($this->imgMargin)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin)
            ->setForegroundColor(new FgColor(0, 0, 0))
            ->setBackgroundColor(new FgColor(255, 255, 255));

        $result = $writer->write($qrCode);
        
        $date = date("Y_m_d_h_i");


        // $logger->info("before saveToFile");

        // Save it to a file

        $filePath = $this->directoryList->getPath('media').'/email/email_img/dynamic_'.$type.'_qr.png';

        // $logger->info("filePath : $filePath");

        $result->saveToFile($filePath);

        // $logger->info("after saveToFile");

        $dataUri = $result->getDataUri();
        
        // Directly output the QR code
        if($returnUri==false){
            header('Content-Type: '.$result->getMimeType());
            echo $result->getString();
        }else{
            // $logger->info("dataUri : $dataUri");
            return array(
                "dataUri" => $dataUri,
                "filePath" => '/email/email_img/dynamic_'.$type.'_qr.png',
            );
            // return $dataUri;
        }
    }
}