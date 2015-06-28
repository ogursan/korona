<?php
namespace Validation\Validator;

class MaxLengthValidator implements ValidatorInterface
{
    private $maxLength;

    function __construct($maxLength)
    {
        $this->maxLength = abs((int)$maxLength);
    }

    public function validate($data, $column)
    {
        return strlen($data[$column]) <= $this->maxLength;
    }

    public function getErrorMessage()
    {
        return 'Поле {column} не должно быть больше ' . $this->maxLength;
    }
}
