<?php
namespace Validation\Validator;

class LengthValidator implements ValidatorInterface
{
    private $length;

    function __construct($length)
    {
        $this->length = abs((int)$length);
    }

    public function validate($data, $column)
    {
        return strlen($data[$column]) == $this->length;
    }

    public function getErrorMessage()
    {
        return 'Длина поля {column} должна быть равна ' . $this->length;
    }
}
