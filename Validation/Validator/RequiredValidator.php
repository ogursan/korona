<?php
namespace Validation\Validator;

class RequiredValidator implements ValidatorInterface
{
    public function validate($data, $column)
    {
        return isset($data[$column]) && !is_null($data[$column]);
    }

    public function getErrorMessage()
    {
        return 'Не заполнено обязательное поле {column}';
    }
}
