<?php
namespace Wow\Einvoice\Helper;

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
use Wow\Einvoice\Helper\Configuration as ConfigHelper;
use Wow\Einvoice\Helper\Data as DataHelper;

class Generator extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    private $logoWidth = 230;

    private $imgMargin = 5;

    private $directoryList;

    private $configHelper;

    private $dataHelper;


    public function __construct(
        DirectoryList $directoryList,
        ConfigHelper $configHelper,
        DataHelper $dataHelper
    ){
        $this->directoryList = $directoryList;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
    }

    public function generate($qrValue, $returnUri = false, $type = "invoice")
    {

        $writer = new PngWriter();

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

        $filePath = $this->directoryList->getPath('media').'/email/email_img/dynamic_'.$type.'_qr.png';

        $this->configHelper->writeLog($filePath);

        $result->saveToFile($filePath);

        $dataUri = $result->getDataUri();
        
        // Directly output the QR code
        if($returnUri==false){
            header('Content-Type: '.$result->getMimeType());
            echo $result->getString();
        }else{
            return array(
                "dataUri" => $dataUri,
                "filePath" => '/email/email_img/dynamic_'.$type.'_qr.png',
            );
        }
    }

    public function clearImage()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $filesystem = $objectManager->get('\Magento\Framework\Filesystem');
        $file = $objectManager->get('\Magento\Framework\Filesystem\Driver\File');

        $mediaDirectory = $filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaRootDir = $mediaDirectory->getAbsolutePath();
        $mediaRootDir = rtrim($mediaRootDir,"/");

        $successSyncData = $this->dataHelper->getSyncSuccess();
        if(count($successSyncData) > 0){
            foreach ($successSyncData as $einvoiceStatus){
                $incrementId = $einvoiceStatus->getRequestIncrementId();
                $filePath = $mediaRootDir . "/email/email_img/dynamic_customer_email_".$einvoiceStatus->getRequestType()."_".$incrementId."_qr.png";
                if ($file->isExists($filePath)){
                    $file->deleteFile($filePath);
                }
            }
        }
    }
}