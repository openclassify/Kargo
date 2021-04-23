<?php
namespace Teknomavi\Kargo\Company\Surat\Helper\Gonderi;

class WebSiparisKoduResponse
{
    /**
     * @var string
     */
    protected $WebSiparisKoduResult = null;

    /**
     * @param string $WebSiparisKoduResult
     */
    public function __construct($WebSiparisKoduResult)
    {
        $this->WebSiparisKoduResult = $WebSiparisKoduResult;
    }

    /**
     * @return string
     */
    public function getWebSiparisKoduResult()
    {
        return $this->WebSiparisKoduResult;
    }
    
    public function setWebSiparisKoduResult($WebSiparisKoduResult)
    {
        $this->WebSiparisKoduResult = $WebSiparisKoduResult;

        return $this;
    }
}
