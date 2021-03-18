<?php

namespace VDVT\Recaptcha;

use Illuminate\Support\Arr;

/**
 * Class ReCaptchaBuilder
 * @package Biscolab\ReCaptcha
 */
class ReCaptchaBuilder
{
    /**
     * @var string
     */
    const DEFAULT_API_VERSION = 'v3';

    /**
     * @var int
     */
    const DEFAULT_CURL_TIMEOUT = 10;

    /**
     * @var string
     */
    const DEFAULT_ONLOAD_JS_FUNCTION = 'biscolabOnloadCallback';

    /**
     * @var string
     */
    const DEFAULT_RECAPTCHA_RULE_NAME = 'recaptcha';

    /**
     * @var string
     */
    const DEFAULT_RECAPTCHA_FIELD_NAME = 'g-recaptcha-response';

    /**
     * @var string
     */
    const DEFAULT_RECAPTCHA_API_DOMAIN = 'www.google.com';

    /**
     * The Site key
     * please visit https://developers.google.com/recaptcha/docs/start
     * @var string
     */
    protected $apiSiteKey;

    /**
     * The Secret key
     * please visit https://developers.google.com/recaptcha/docs/start
     * @var string
     */
    protected $apiSecretKey;

    /**
     * The chosen ReCAPTCHA version
     * please visit https://developers.google.com/recaptcha/docs/start
     * @var string
     */
    protected $version;

    /**
     * Whether is true the ReCAPTCHA is inactive
     * @var boolean
     */
    protected $skipByIp = false;

    /**
     * The API domain (default: retrieved from config file)
     * @var string
     */
    protected $apiDomain = '';

    /**
     * The API request URI
     * @var string
     */
    protected $apiUrl = '';

    /**
     * The URI of the API Javascript file to embed in you pages
     * @var string
     */
    protected $apiJsUrl = '';

    /**
     * ReCaptchaBuilder constructor.
     *
     * @param string      $apiSiteKey
     * @param string      $apiSecretKey
     * @param null|string $version
     */
    public function __construct(
        string $apiSiteKey,
        string $apiSecretKey,
        ? string $version = self::DEFAULT_API_VERSION
    ) {

        $this->setApiSiteKey($apiSiteKey);
        $this->setApiSecretKey($apiSecretKey);
        $this->setVersion($version);
        $this->setSkipByIp($this->skipByIp());
        $this->setApiDomain();
        $this->setApiUrls();
    }

    /**
     * @param string $apiSiteKey
     *
     * @return ReCaptchaBuilder
     */
    public function setApiSiteKey(string $apiSiteKey) : ReCaptchaBuilder
    {

        $this->apiSiteKey = $apiSiteKey;

        return $this;
    }

    /**
     * @param string $apiSecretKey
     *
     * @return ReCaptchaBuilder
     */
    public function setApiSecretKey(string $apiSecretKey): ReCaptchaBuilder
    {

        $this->apiSecretKey = $apiSecretKey;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurlTimeout(): int
    {

        return config('recaptcha.curl_timeout', self::DEFAULT_CURL_TIMEOUT);
    }

    /**
     * @param string $version
     *
     * @return ReCaptchaBuilder
     */
    public function setVersion(string $version): ReCaptchaBuilder
    {

        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {

        return $this->version;
    }

    /**
     * @param bool $skipByIp
     *
     * @return ReCaptchaBuilder
     */
    public function setSkipByIp(bool $skipByIp): ReCaptchaBuilder
    {

        $this->skipByIp = $skipByIp;

        return $this;
    }

    /**
     * @param null|string $apiDomain
     *
     * @return ReCaptchaBuilder
     */
    public function setApiDomain( ? string $apiDomain = null) : ReCaptchaBuilder
    {

        $this->apiDomain = $apiDomain ?? config('recaptcha.api_domain', self::DEFAULT_RECAPTCHA_API_DOMAIN);

        return $this;
    }

    /**
     * @return string
     */
    public function getApiDomain(): string
    {

        return $this->apiDomain;
    }

    /**
     * @return ReCaptchaBuilder
     */
    public function setApiUrls(): ReCaptchaBuilder
    {

        $this->apiUrl = 'https://' . $this->apiDomain . '/recaptcha/api/siteverify';
        $this->apiJsUrl = 'https://' . $this->apiDomain . '/recaptcha/api.js';

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getIpWhitelist()
    {

        $whitelist = config('recaptcha.skip_ip', []);

        if (!is_array($whitelist)) {
            $whitelist = explode(',', $whitelist);
        }

        $whitelist = array_map(function ($item) {

            return trim($item);
        }, $whitelist);

        return $whitelist;
    }

    /**
     * Checks whether the user IP address is among IPs "to be skipped"
     *
     * @return boolean
     */
    public function skipByIp(): bool
    {

        return (in_array(request()->ip(), $this->getIpWhitelist()));
    }

    /**
     * Write script HTML tag in you HTML code
     * Insert before </head> tag
     *
     * @param array|null $configuration
     *
     * @return string
     * @throws \Exception
     */
    public function htmlScriptTagJsApi( ? array $configuration = []) : string
    {

        $query = [];
        $html = '';

        // Language: "hl" parameter
        // resources $configuration parameter overrides default language
        $language = Arr::get($configuration, 'lang');
        if (!$language) {
            $language = config('recaptcha.default_language', null);
        }
        if ($language) {
            Arr::set($query, 'hl', $language);
        }

        // Onload JS callback function: "onload" parameter
        // "render" parameter set to "explicit"
        if (config('recaptcha.explicit', null) && $this->version === 'v2') {
            Arr::set($query, 'render', 'explicit');
            Arr::set($query, 'onload', self::DEFAULT_ONLOAD_JS_FUNCTION);

            /** @scrutinizer ignore-call */
            $html = $this->getOnLoadCallback();
        }

        // Create query string
        $query = ($query) ? '?' . http_build_query($query) : "";
        $html .= "<script src=\"" . $this->apiJsUrl . $query . "\" async defer></script>";

        return $html;
    }

    /**
     * Call out to reCAPTCHA and process the response
     *
     * @param string $response
     *
     * @return boolean|array
     */
    public function validate($response)
    {

        if ($this->skipByIp) {
            if ($this->returnArray()) {
                // Add 'skip_by_ip' field to response
                return [
                    'skip_by_ip' => true,
                    'score' => 0.9,
                    'success' => true,
                ];
            }

            return true;
        }

        $params = http_build_query([
            'secret' => $this->apiSecretKey,
            'remoteip' => request()->getClientIp(),
            'response' => $response,
        ]);

        $url = $this->apiUrl . '?' . $params;

        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->getCurlTimeout());
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $curl_response = curl_exec($curl);
        } else {
            $curl_response = file_get_contents($url);
        }

        if (is_null($curl_response) || empty($curl_response)) {
            if ($this->returnArray()) {
                // Add 'error' field to response
                return [
                    'error' => 'cURL response empty',
                    'score' => 0.1,
                    'success' => false,
                ];
            }

            return false;
        }
        $response = json_decode(trim($curl_response), true);

        if ($this->returnArray()) {
            return $response;
        }

        return $response['success'];
    }

    /**
     * @return string
     */
    public function getApiSiteKey(): string
    {

        return $this->apiSiteKey;
    }

    /**
     * @return string
     */
    public function getApiSecretKey(): string
    {

        return $this->apiSecretKey;
    }

    /**
     * @return bool
     */
    protected function returnArray(): bool
    {
        return ($this->version == 'v3');
    }
}
