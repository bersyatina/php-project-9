<?php

namespace Hexlet\Code;

class Handler
{
    public static function isDomainAvailible(string $domain)
    {
        $url = str_contains($domain, 'http') ? $domain : "http://" . $domain;
        //проверка на валидность урла
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        //инициализация curl
        $curlInit = curl_init($url);
        if (!empty($curlInit)) {
            curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curlInit, CURLOPT_HEADER, true);
            curl_setopt($curlInit, CURLOPT_NOBODY, true);
            curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
            //получение ответа
            $response = curl_exec($curlInit);
            curl_close($curlInit);
        }
        if (isset($response)) {
            return true;
        }
        return false;
    }

    public static function setChecksCreatedTime(array $checks): array
    {
        return array_map(function ($check) {
            $check['created_at'] = explode('.', $check['created_at'])[0] ?? null;
            return $check;
        }, $checks);
    }
}
