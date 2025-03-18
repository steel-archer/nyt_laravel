<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

abstract class AbstractNytApiController extends Controller
{
    protected const DEFAULT_VERSION = 3;
    protected const SUCCESS_CODE = 200;
    protected const ERROR_CODE_VALIDATION = 422;
    protected const ERROR_CODE_UNKNOWN = 500;

    abstract protected function getValidationRules(): array;

    protected function validate(Request $request): MessageBag
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules(),
        );

        if ($validator->fails()) {
            return $validator->errors();
        }

        return new MessageBag();
    }
}
