<?php
namespace Http;

class Response
{
    const STATUS_OK             = 200;
    const STATUS_FORBIDDEN      = 403;
    const STATUS_NOT_FOUND      = 404;
    const STATUS_INTERNAL_ERROR = 500;

    private $status;
    private $data;

    public function __construct($status = self::STATUS_OK, $data = [])
    {
        $this->status = (int)$status;
        $this->data   = array_merge(['status' => $this->getStatusText(), 'time' => date('Y-m-d H:i:s')], $data);
    }

    public function __toString()
    {
        $statusText = $this->getStatusText();

        header('HTTP/1.1 ' . $this->status . ' ' . $statusText);
        header('Content-type: application/json');

        return json_encode($this->data);
    }

    private function getStatusText()
    {
        $codes = [
            self::STATUS_OK             => 'OK',
            self::STATUS_FORBIDDEN      => 'Forbidden',
            self::STATUS_NOT_FOUND      => 'Not Found',
            self::STATUS_INTERNAL_ERROR => 'Internal Server Error',
        ];
        if (!isset($codes[$this->status])) {
            $this->status = self::STATUS_INTERNAL_ERROR;
        }

        return $codes[$this->status];
    }
}
