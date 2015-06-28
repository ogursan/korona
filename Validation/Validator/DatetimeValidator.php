<?php
namespace Validation\Validator;

class DatetimeValidator implements ValidatorInterface
{
    public function validate($data, $column)
    {
        return preg_match('~\d{4}-\d{2}-\d{2} \d{1,2}:\d{1,2}:\d{1,2}~', $data[$column]);
    }

    public function getErrorMessage()
    {
        return 'Поле {column} не соответствует формату даты и времени';
    }
}