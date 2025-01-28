<?php
namespace Wow\DigitalPrinting\Controller\Adminhtml\Upload;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

class Image extends \Magento\Backend\App\Action
{
    /**
     * Image uploader
     *
     * @var \Wow\DigitalPrinting\Model\ImageUploader
     */
    protected $imageUploader;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileIo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Upload constructor.
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param Wow\DigitalPrinting\Model\ImageUploader $imageUploader
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Wow\DigitalPrinting\Model\ImageUploader $imageUploader,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileIo,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->filesystem = $filesystem;
        $this->fileIo = $fileIo;
        $this->storeManager = $storeManager;
    }

    /**
     * Upload file controller action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        try {
            $imageResult = $this->imageUploader->saveFileToTmpDir('product[options]');

            // $logger->info("imageResult : ".print_r($imageResult,true));

            $imageName = $imageResult['name'];
            $firstName = substr($imageName, 0, 1);
            $secondName = substr($imageName, 1, 1);

            $basePath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'catalog/product/file/';
            $mediaRootDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'catalog/product/file/' . $firstName . '/' . $secondName . '/';

            if (!is_dir($mediaRootDir)) {
                $this->fileIo->mkdir($mediaRootDir, 0775);
            }
            $newImageName = $this->updateImageName($mediaRootDir, $imageName);
            // $this->fileIo->mv($basePath . $imageName, $mediaRootDir . $newImageName);
            $this->fileIo->mv($mediaRootDir . $newImageName, $basePath . $imageName);

            $this->imageUploader->moveFileFromTmp($imageName);

            $imageResult['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];

            // $logger->info("imageResult : ".print_r($imageResult,true));

        } catch (\Exception $e) {
            $imageResult = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($imageResult);
    }
    
    public function updateImageName($path, $file_name)
    {
        if ($position = strrpos($file_name, '.')) {
            $name = substr($file_name, 0, $position);
            $extension = substr($file_name, $position);
        } else {
            $name = $file_name;
        }
        $new_file_path = $path . '/' . $file_name;
        $new_file_name = $file_name;
        $count = 0;
        while (file_exists($new_file_path)) {
            $new_file_name = $name . '_' . $count . $extension;
            $new_file_path = $path . '/' . $new_file_name;
            $count++;
        }
        return $new_file_name;
    }

    private function _saveOptionImage()
    {
        $tableColumn = ['id', 'name', 'age'];
        $tableData[] = [5, 'xyz', '20'];
        $connection->insertArray($table, $tableColumn, $tableData);
        $query = "INSERT INTO `" . $table . "`(`id`, `name`, `age`) VALUES (7,'mtm',33)";
        $connection->query($query);
    }
}