<?php
namespace Validation\Validator;

interface ValidatorInterface
{
    public function validate($data, $column);

    public function getErrorMessage();
}
