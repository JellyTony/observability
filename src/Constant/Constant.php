<?php

namespace JellyTony\Observability\Constant;

interface Constant
{
    /**
     * @var string
     */
    public const TRACE_ID = 'trace_id';


    public const HTTP_MP_DEBUG = 'HTTP_MP_DEBUG';

    public const HTTP_X_REQUEST_ID = 'HTTP_X_REQUEST_ID';

    // b3 protocol
    public const HTTP_X_B3_TRACE_ID = 'HTTP_X_B3_TRACEID';
    public const HTTP_X_B3_SPAN_ID = 'HTTP_X_B3_SPANID';
    public const HTTP_X_B3_PARENT_SPAN_ID = 'HTTP_X_B3_PARENTSPANID';
    public const HTTP_X_B3_SAMPLED = 'HTTP_X_B3_SAMPLED';
    public const HTTP_X_B3_FLAGS = 'HTTP_X_B3_FLAGS';

    public const BIZ_CODE = 'biz_code';

    public const BIZ_MSG = 'biz_msg';

    public const BIZ_CODE_SUCCESS = 1000;
}