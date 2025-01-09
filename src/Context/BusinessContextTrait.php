<?php

namespace JellyTony\Observability\Context;

trait BusinessContextTrait
{
    private $bizCode = 1000;
    private $bizMsg = 'success';

    /**
     * set biz code.
     * @param $code
     */
    public function setBizCode($code)
    {
        if ($this->bizCode > 1000) {
            return;
        }
        $this->bizCode = $code;
    }

    /**
     * set biz msg.
     * @param $msg
     */
    public function setBizMsg($msg)
    {
        if (!empty($this->bizMsg) && $this->bizMsg !== 'success') {
            return;
        }
        $this->bizMsg = $msg;
    }


    /**
     * set biz result.
     * @param $code
     * @param $msg
     */
    public function setBizResult($code, $msg)
    {
        if ($this->bizCode > 1000) {
            return;
        }
        $this->bizCode = $code;
        $this->bizMsg = $msg;
    }

    /**
     * set biz content.
     * @param $content
     * @return void
     */
    public function setBizContent($content)
    {
        if (empty($content) || !is_array($content)) {
            return;
        }
        if (!empty($content['code']) && !empty($content['msg'])) {
            $this->setBizResult($content['code'], $content['msg']);
        }
    }

    /**
     * get biz code.
     * @return int
     */
    public function getBizCode(): int
    {
        return $this->bizCode;
    }

    /**
     * get biz msg.
     * @return string
     */
    public function getBizMsg(): string
    {
        return $this->bizMsg;
    }

    public function isError(): bool
    {
        return $this->bizCode !== 1000 && $this->bizCode !== 0;
    }
}