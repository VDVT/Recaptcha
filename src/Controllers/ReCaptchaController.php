<?php

namespace VDVT\Recaptcha\Controllers;

use Illuminate\Routing\Controller;
use VDVT\Recaptcha\Facades\Recaptcha;

/**
 * Class ReCaptchaController
 *
 * @package VDVT\Recaptcha\Controllers
 */
class ReCaptchaController extends Controller
{
    /**
     * @return array
     */
    public function validateV3(): array
    {
        $token = request()->input(config('vdvt.recaptcha.recaptcha.default_token_parameter_name', 'token'), '');

        return Recaptcha::validate($token);
    }
}
