<?php


namespace App\Sniffer;


use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Sniffer
{
    private $serializer;
    private $validator;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function sniff($data, $dtoClass, $format = 'json', $options = [])
    {
        $dto = $format === 'array'
            ? $this->safeDenormalize($data, $dtoClass, $options)
            : $this->safeDeserialize($data, $dtoClass, $format, $options);

        $this->safeValidate($dto);

        return $dto;
    }

    public function safeDenormalize($data, $dtoClass, $options = [])
    {
        try {
            $dto = $this->serializer->denormalize($data, $dtoClass, null, $options);
        } catch (\Exception $e) {
            throw new SnifferException('Internal invalid DTO');
        }

        return $dto;
    }

    public function safeDeserialize($data, $dtoClass, $format = 'json', $options = [])
    {
        try {
            $dto = $this->serializer->deserialize($data, $dtoClass, $format, $options);
        } catch (\Exception $e) {
            throw new SnifferException('Internal invalid DTO');
        }

        return $dto;
    }

    public function safeValidate($dto)
    {
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new SnifferException(json_encode([
                'status' => 'INCORRECT_PAYLOAD',
                'fields' => $this->formatErrors($errors)
            ]));
        }
    }

    private function formatErrors($errors)
    {
        $incorrectValues = [];
        foreach ($errors as $violation) {
            $incorrectValueName = strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $violation->getPropertyPath()));
            $incorrectValues[$incorrectValueName] = $violation->getMessage();
        }

        return $incorrectValues;
    }
}