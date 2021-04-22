<?php
namespace Teknomavi\Kargo\Company\Surat\Helper\Gonderi;

class WebSiparisKodu
{
    /**
     * @var string
     */
    protected $GonderenCariKodu = null;

    /**
     * @var string
     */
    protected $Sifre = null;

    /**
     * @var WebSiparisKodu
     */
    protected $WebSiparisKodu = null;

    /**
     * WebSiparisKoduToplu constructor.
     * @param $GonderenCariKodu
     * @param $WebSiparisKodu
     * @param $Sifre
     */
    public function __construct($GonderenCariKodu, $WebSiparisKodu, $Sifre)
    {
        $this->GonderenCariKodu = $GonderenCariKodu;
        $this->WebSiparisKodu = $WebSiparisKodu;
        $this->Sifre = $Sifre;
    }

    /**
     * @return string
     */
    public function getSifre()
    {
        return $this->Sifre;
    }


    public function setSifre($Sifre)
    {
        $this->Sifre = $Sifre;

        return $this;
    }

    /**
     * @param $GonderenCariKodlari
     * @return $this
     */
    public function setGonderenCariKodu($GonderenCariKodu)
    {
        $this->GonderenCariKodu = $GonderenCariKodu;

        return $this;
    }

    /**
     * @return string
     */
    public function getGonderenCariKodu()
    {
        return $this->GonderenCariKodu;
    }


    /**
     * @param $WebSiparisKodu
     * @return $this
     */
    public function setWebSiparisKodu($WebSiparisKodu)
    {
        $this->WebSiparisKodu = $WebSiparisKodu;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebSiparisKodu()
    {
        return $this->WebSiparisKodu;
    }

}
