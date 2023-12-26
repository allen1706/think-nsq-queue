<?php
declare(strict_types=1);

namespace annon\queue\request;

class Http
{
    /**
     * 发送post请求
     * @param string $url
     * @param array $data
     * @param array $header
     * @return bool|string
     */
    public static function post(string $url, array $data = [], array $header = []): bool|string
    {
        // 判断是否安装了guzzle
        if (class_exists("GuzzleHttp\Client")) {
            return self::GuzzlePost($url, $data, $header);
        }
        $header = array_merge([
            'Content-Type' => 'application/json',
        ], $header);
        return self::CurlPost($url, $data, $header);
    }

    /**
     * 使用guzzle发送post请求
     * @param string $url
     * @param array $data
     * @param array $header
     * @return mixed
     */
    public static function GuzzlePost(string $url, array $data = [], array $header = []): mixed
    {
        $header = array_merge([
            'User-Agent' => 'think-nsq-queue/1.0',
        ], $header);
        // 使用guzzle发送post请求
        $class  = "GuzzleHttp\Client";
        $client = new $class();
        $response = $client->request('POST', $url, [
            'headers' => $header,
            'json' => $data,
        ]);
        return $response->getBody()->getContents();
    }

    /**
     * 使用file_get_contents发送post请求
     * @param string $url
     * @param array $data
     * @param array $header
     * @return bool|string
     */
    public static function CurlPost(string $url, array $data = [], array $header = []): bool|string
    {
        $header = array_merge([
            'User-Agent' => 'think-nsq-queue/1.0',
        ], $header);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => self::buildHeader($header),
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            ],
        ]);
        return file_get_contents($url, false, $context);
    }

    /**
     * 构建header
     * @param array $header
     * @return string
     */
    private static function buildHeader(array $header = []): string
    {
        $str = '';
        foreach ($header as $key => $value) {
            $str .= "{$key}: {$value}\r\n";
        }
        return $str;
    }
}