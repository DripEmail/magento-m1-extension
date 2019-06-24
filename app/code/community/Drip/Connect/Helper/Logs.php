<?php

class Drip_Connect_Helper_Logs extends Mage_Core_Helper_Abstract
{
    const SENDLOGS_RESPONSE_OK = 1;
    const SENDLOGS_RESPONSE_FAIL = 2;

    const MAX_TOAL_ZIP_SIZE = 20971520; // 20Mb

    protected $logFiles = [];

    public function __construct()
    {
        $this->logFiles = [
            Mage::getBaseDir('log') . '/' . 'drip' . '/' . 'apiclient' . '/' . 'drip.log',
            Mage::getBaseDir('log') . '/' . 'exception.log',
            Mage::getBaseDir('log') . '/' . 'system.log',
        ];
    }

    /**
     * @param int $storeId
     * @throws \Exception
     */
    public function sendLogs($storeId = null)
    {
        /**
         * @var array [
         *      [
         *          'path' => log file path,
         *          'zip_path' => tmp zip file path,
         *          'size' => zip file size (bytes),
         *      ],
         *      [...],
         *      [...],
         * ]
         */
        $dataToSend = [];

        foreach ($this->logFiles as $logFile) {

            if (file_exists($logFile)) {

                $zip = new ZipArchive;

                $zipPath = Mage::getBaseDir('log') . '/' . basename($logFile).'.zip';

                if (file_exists($zipPath)) {
                    $zipOpen = $zip->open($zipPath, ZipArchive::OVERWRITE);
                } else {
                    $zipOpen = $zip->open($zipPath, ZipArchive::CREATE);
                }

                if ($zipOpen !== TRUE) {
                    Mage::log("can't creta zip file for ".$zipPath);
                    continue;
                }

                $zip->addFile($logFile, basename($logFile));
                $zip->close();

                $dataToSend[] = [
                    'path' => $logFile,
                    'zip_path' => $zipPath,
                    'size' => filesize($zipPath),
                ];
            }
        }

        if (!count($dataToSend)) {
            Mage::throwException($this->__('Logs not found'));
        }

        do {
            $total = 0;
            foreach ($dataToSend as $fileData) {
                $total += $fileData[size];
            }

            if ($total > self::MAX_TOAL_ZIP_SIZE) {
                unset($dataToSend[count($dataToSend)-1]);
            }
        } while ($total > self::MAX_TOAL_ZIP_SIZE);

        $senderName = Mage::getStoreConfig('trans_email/ident_general/name');
        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');

        $toEmail = Mage::getStoreConfig('dripconnect_general/log_settings/support_email');

        $subject = $this->__('Logs from Magento server').' '.$this->getServerName();
        $emailTemplateVariables = [
            'subject' => $subject,
        ];

        $emailTemplate = Mage::getModel('core/email_template')->loadDefault('drip_connect_sendlogs_template')
            ->setSenderName($senderName)
            ->setSenderEmail($senderEmail);


        foreach ($dataToSend as $fileData) {
            $emailTemplate
                ->getMail()
                ->createAttachment(
                    file_get_contents($fileData['zip_path']),
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    basename($fileData['zip_path'])
                );
        }

        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $emailTemplate->send($toEmail, 'Drip Support', $emailTemplateVariables);
        $translate->setTranslateInline(true);
    }

    /**
     * @return string
     */
    protected function getServerName()
    {
        $name = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        return $name;
    }
}
