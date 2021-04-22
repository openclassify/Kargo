<?php

namespace Teknomavi\Kargo\Company\Surat;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknomavi\Kargo\Company\ServiceAbstract;
use Teknomavi\Kargo\Company\ServiceInterface;
use Teknomavi\Kargo\Company\Surat\Helper\Gonderi\CreateShipment;
use Teknomavi\Kargo\Company\Surat\Helper\Gonderi\Gonderi;
use Teknomavi\Kargo\Company\Surat\Helper\Gonderi\GonderiyiKargoyaGonder;
use Teknomavi\Kargo\Company\Surat\Helper\Gonderi\WebSiparisKodu;
use Teknomavi\Kargo\Model\Package;
use Teknomavi\Kargo\Response\PackageInfo;

/**
 * Class Service.
 */
class Service extends ServiceAbstract implements ServiceInterface
{
    /**
     * @var CreateShipment
     */
    private $shipmentService;

    /**
     * @return CreateShipment
     */
    private function initShipmentService(): CreateShipment
    {
        if (!$this->shipmentService) {
            $this->shipmentService = new CreateShipment([
                'trace' => 1,
                'connection_timeout' => 60,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            ]);
        }

        return $this->shipmentService;
    }


    /**
     * @var CreateShipment
     */
    private $TakipService;

    /**
     * @return CreateShipment
     */
    private function initTakipService(): CreateShipment
    {
        if (!$this->TakipService) {
            $this->TakipService = new CreateShipment([
                'trace' => 1,
                'connection_timeout' => 60,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            ], 'http://webservices.suratkargo.com.tr/services.asmx?WSDL');
        }

        return $this->TakipService;
    }


    public function addPackage(Package $package)
    {
        $gonderi = new Gonderi();
        $gonderi
            ->setKisiKurum($package->getConsigneeName())
            ->setAliciAdresi($package->getConsigneeAddress())
            ->setIl($package->getConsigneeCity())
            ->setIlce($package->getConsigneeTown())
            ->setTelefonCep($package->getConsigneeMobilPhone())
            ->setOzelKargoTakipNo($package->getReferenceNo())
            ->setAdet($package->getNumberOfPackages());

        if ($package->getPaymentType() == Package::PAYMENT_TYPE_SHIPPER_PAY) {
            $gonderi->setOdemetipi(1);
        } else {
            $gonderi->setOdemetipi(2);
        }

        if ($package->getPackageType() == Package::PACKAGE_TYPE_BOX) {
            $gonderi->setKargoTuru(3);
        } else {
            $gonderi->setKargoTuru(1);
        }

        if (!$package->getShipmentType() == Package::SHIPMENT_TYPE_PREPAID) {
            $gonderi->setKapidanOdemeTutari($package->getValueOfGoods());
            if ($package->getShipmentType() == Package::SHIPMENT_TYPE_CARD_ON_DELIVERY) {
                $gonderi->setKapidanOdemeTahsilatTipi(2);
            } elseif ($package->getShipmentType() == Package::SHIPMENT_TYPE_CASH_ON_DELIVERY) {
                $gonderi->setKapidanOdemeTahsilatTipi(1);
            }
            if ($package->getInvoiceNo()) {
                $irsaliye = explode('-', $package->getInvoiceNo());
            } else {
                $irsaliye = ['AA', time()];
            }

            $seriNo = $irsaliye[0] ?? 'AA';
            $siraNo = $irsaliye[1] ?? time();
            $gonderi->setIrsaliyeSeriNo($seriNo);
            $gonderi->setIrsaliyeSiraNo($siraNo);
        }
        $this->packages[] = $gonderi;
    }


    public function addWebSiparisKod($webSiparisKod)
    {
        $this->WebSiparisKodlari[] = $webSiparisKod;
    }

    public function addGonderenCariKod($gonderenCariKod)
    {
        $this->GonderenCariKodlari[] = $gonderenCariKod;
    }


    public function getPackageInfoByReferenceNumber(string $referenceNumber): PackageInfo
    {
        $packageInfo = new PackageInfo();
        $packageInfo->setReferenceNumber($referenceNumber);
        $packageInfo->setPaymentType(PackageInfo::PAYMENT_TYPE_SENDER);

        $service = $this->initTakipService();

        $shipment_detail = new WebSiparisKodu($this->options['GonderenCariKodu'], $referenceNumber, $this->options['Sifre']);

        try {
            $result = $service->WebSiparisKodu($shipment_detail)->getWebSiparisKoduResult();
            $result = $this->reponseXSDToArray($result);
            $isError = $this->isErrorMessage($result, $packageInfo);

            if (!$isError) {
                dd($result);
                //Takip Kodu geldiğinde response PackageInfo ya eklenecek
//                $packageInfo->setTrackingNumber($tracking_code);
            }
        } catch (\Exception $exception) {
            $packageInfo->setErrorMessage($exception->getMessage());
        }

        return $packageInfo;
    }

    /**
     * @return array
     */
    public function sendPackages()
    {
        $response = [];
        $service = $this->initShipmentService();
        foreach ($this->packages as $package) {
            /**
             * @var Gonderi $package
             */
            $createShipmentResponse = new \Teknomavi\Kargo\Response\CreateShipment();
            $createShipmentResponse->setReferenceNumber($package->getOzelKargoTakipNo());

            $gonder = new GonderiyiKargoyaGonder($this->options['KullaniciAdi'], $this->options['Sifre'], $package);

            try {
                $result = $service->GonderiyiKargoyaGonder($gonder);
                if ($result->getGonderiyiKargoyaGonderResult() == 'Tamam') {
                    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                    $png = $generator->getBarcode($package->getOzelKargoTakipNo(), $generator::TYPE_CODE_128, 2, 90);
                    $base64 = base64_encode($png);
                    $createShipmentResponse->setSuccess(true);
                    $createShipmentResponse->setLabelStrings([$base64]);
                } else {
                    $createShipmentResponse
                        ->setErrorCode('SURAT')
                        ->setErrorDescription($result->getGonderiyiKargoyaGonderResult())
                        ->setSuccess(false);
                }
            } catch (\Exception $exception) {
                $createShipmentResponse
                    ->setErrorCode('SOAP' . $exception->getCode())
                    ->setErrorDescription($exception->getMessage())
                    ->setSuccess(false);
            }

            $response[$package->getOzelKargoTakipNo()] = $createShipmentResponse;
        }

        return $response;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'KullaniciAdi' => '',
            'Sifre' => '',
            'GonderenCariKodu' => ''
        ]);
    }

    protected function reponseXSDToArray($response)
    {
        $response = $response->any;
        $sxe = new \SimpleXMLElement($response);
        $sxe->registerXPathNamespace('d', 'urn:schemas-microsoft-com:xml-diffgram-v1');
        $result = $sxe->xpath("//NewDataSet");
        $response_parameters = array();

        foreach ($result[0] as $title) {
            $response_parameters = array_merge($response_parameters, get_object_vars($title));
        }

        return $response_parameters;
    }

    private function isErrorMessage($res, $packageInfo)
    {
        if (isset($res['Mesaj'])) {
            $packageInfo->setErrorMessage($res['Mesaj']);

            return  true;
        }
        return  false;
    }
}
