<?php

namespace VDVT\Recaptcha;

class Recaptcha extends ReCaptchaBuilder
{
    /**
     * Version recaptcha
     *
     * @var string
     */
    const VERSION_ACTIVE = 'v3';

    /**
     * ReCaptchaBuilderV3 constructor.
     *
     * @param string $apiSiteKey
     * @param string $apiSecretKey
     */
    public function __construct(string $apiSiteKey, string $apiSecretKey)
    {
        parent::__construct($apiSiteKey, $apiSecretKey, Recaptcha::VERSION_ACTIVE);
    }

    /**
     * Write script HTML tag in you HTML code
     * Insert before </head> tag
     *
     * @param array|null $configuration
     *
     * @return string
     */
    public function htmlScriptTagJsApi( ? array $configuration = []) : string
    {
        if ($this->skip_by_ip) {
            return '';
        }

        $html = "<script src=\"" . $this->api_js_url . "?render={$this->apiSiteKey}\"></script>";

        $action = Arr::get($configuration, 'action', 'homepage');

        $js_custom_validation = Arr::get($configuration, 'custom_validation', '');

        // Check if set custom_validation. That function will override default fetch validation function
        if ($js_custom_validation) {

            $validate_function = ($js_custom_validation) ? "{$js_custom_validation}(token);" : '';
        } else {

            $js_then_callback = Arr::get($configuration, 'callback_then', '');
            $js_callback_catch = Arr::get($configuration, 'callback_catch', '');

            $js_then_callback = ($js_then_callback) ? "{$js_then_callback}(response)" : '';
            $js_callback_catch = ($js_callback_catch) ? "{$js_callback_catch}(err)" : '';

            $validate_function = "
                fetch('/" . config(
                'vdvt.recaptcha.recaptcha.default_validation_route',
                url('/')
            ) . "?" . config(
                'vdvt.recaptcha.recaptcha.default_token_parameter_name',
                'token'
            ) . "=' + token, {
                    headers: {
                        \"X-Requested-With\": \"XMLHttpRequest\",
                        \"X-CSRF-TOKEN\": csrfToken.content
                    }
                })
                .then(function(response) {
                    {$js_then_callback}
                })
                .catch(function(err) {
                    {$js_callback_catch}
                });";
        }

        $html .= "<script>
                    var csrfToken = document.head.querySelector('meta[name=\"csrf-token\"]');
                  grecaptcha.ready(function() {
                      grecaptcha.execute('{$this->apiSiteKey}', {action: '{$action}'}).then(function(token) {
                        {$validate_function}
                      });
                  });
             </script>";

        return $html;
    }
}
