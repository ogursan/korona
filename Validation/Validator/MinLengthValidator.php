<?php
namespace Validation\Validator;

class MinLengthValidator implements ValidatorInterface
{
    private $minLength;

    function __construct($minLength)
    {
        $this->minLength = abs((int)$minLength);
    }

    public function validate($data, $column)
    {
        return strlen($data[$column]) >= $this->minLength;
    }

    public function getErrorMessage()
    {
        return 'Длина поля {column} не должна быть меньше ' . $this->minLength;
    }
}
