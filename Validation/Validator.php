<?php
namespace Validation;

use \Exception;
use Validation\Validator\ValidatorInterface;

class Validator
{
    private $data;
    private $rules;
    private $errors;

    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function setRule($column, $rule)
    {
        if (!isset($this->rules[$column])) {
            $this->rules[$column] = [];
        }

        if (!is_array($rule)) {
            $rule = [$rule];
        }

        $this->rules[$column] = array_merge($this->rules[$column], $rule);

        return $this;
    }

    public function validate()
    {
        $this->clearErrors();

        foreach ($this->rules as $column => $rules) {
            foreach ($rules as $key => $value) {
                if (is_int($key)) {
                    $validatorCode  = $value;
                    $validatorValue = null;
                } else {
                    $validatorCode  = $key;
                    $validatorValue = $value;
                }

                try {
                    $validator = $this->getValidator($validatorCode, $validatorValue);

                    if (!$validator->validate($this->data, $column)) {
                        $error = str_replace('{column}', $column, $validator->getErrorMessage());
                        $this->addError($error);
                    }
                } catch (Exception $e) {
                    $this->addError($e->getMessage());
                }
            }
        }

        return !$this->hasErrors();
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $code
     * @param null $value
     *
     * @return \Validation\Validator\ValidatorInterface
     * @throws \Exception
     */
    private function getValidator($code, $value = null)
    {
        $code = strtolower($code);

        $validators = [
            'required'   => 'Validation\Validator\RequiredValidator',
            'length'     => 'Validation\Validator\LengthValidator',
            'min_length' => 'Validation\Validator\MinLengthValidator',
            'max_length' => 'Validation\Validator\MaxLengthValidator',
            'datetime'   => 'Validation\Validator\DatetimeValidator',
        ];

        if (!isset($validators[$code])) {
            throw new Exception(sprintf('Undefined validator "%s"', $code));
        }

        $validator = new $validators[$code]($value);

        if (!($validator instanceof ValidatorInterface)) {
            throw new Exception('Validator must be an instance of ValidatorInterface');
        }

        return $validator;
    }

    private function addError($error)
    {
        array_push($this->errors, $error);
    }

    private function hasErrors()
    {
        return count($this->errors) > 0;
    }

    private function clearErrors()
    {
        $this->errors = [];
    }
}
