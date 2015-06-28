<?php
namespace Http;

use Device\Helper as DeviceHelper;
use Validation\Validator;
use \Exception;

class RequestHandler
{
    private $response;

    public function receive($data)
    {
        $this->response = null;

        $validator = (new Validator())
            ->setData($data)
            ->setRule('serial', 'required')
            ->setRule('serial', ['length' => 15])
            ->setRule('time', 'required')
            ->setRule('time', 'datetime')
            ->setRule('connect_freq', ['min_length' => 1])
            ->setRule('connect_freq', ['max_length' => 2])
            ->setRule('firmware', ['max_length' => 32]);

        if ($validator->validate()) {
            $deviceHelper = new DeviceHelper();

            try {
                $deviceHelper->handle($data);

                $this->response = new Response(Response::STATUS_OK);
            } catch (Exception $e) {
                $this->response = new Response(Response::STATUS_NOT_FOUND, ['errors' => [$e->getMessage()]]);
            }
        } else {
            $this->response = new Response(Response::STATUS_OK, ['errors' => $validator->getErrors()]);
        }

        return $this;
    }

    public function response()
    {
        return $this->response;
    }
}
