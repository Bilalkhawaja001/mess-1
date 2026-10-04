<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static function normalize(string $n): ?string
    {
        $n = preg_replace('/\D/', '', $n);
        if (str_starts_with($n, '92')) return $n;
        if (str_starts_with($n, '0'))  return '92' . substr($n, 1);
        if (str_starts_with($n, '3'))  return '92' . $n;
        return null;
    }

    public static function sendTemplate(string $to, string $template, array $params): bool
    {
        $num = self::normalize($to);
        if (!$num) return false;

        $res = Http::withToken(config('whatsapp.token'))
            ->post("https://graph.facebook.com/v20.0/".config('whatsapp.phone_id')."/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => $num,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'en'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn($p) => ['type'=>'text','text'=>(string)$p], $params),
                    ]],
                ],
            ]);

        if (!$res->successful()) {
            Log::error('WA fail', ['to'=>$num, 'body'=>$res->body()]);
            return false;
        }
        return true;
    }
}
