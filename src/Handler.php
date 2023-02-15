<?php

namespace Hexlet\Code;

class Handler
{
    public static function isDomainAvailible($domain)
    {
        //проверка на валидность урла
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }
        //инициализация curl
        $curlInit = curl_init($domain);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        //получение ответа
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ($response) {
            return true;
        }
        return false;
    }

    public static function setChecksCreatedTime($checks): array
    {
        return array_map(function ($check) {
            $check['created_at'] = explode('.', $check['created_at'])[0] ?? null;
            return $check;
        }, $checks);
    }
}
