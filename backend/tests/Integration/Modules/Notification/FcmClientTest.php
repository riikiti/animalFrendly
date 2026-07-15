<?php

declare(strict_types=1);

use App\Modules\Notification\Domain\Exceptions\FcmRequestFailedException;
use App\Modules\Notification\Infrastructure\External\FcmClient;
use App\Modules\Notification\Infrastructure\External\NullFcmClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Тестовый RSA-ключ (сгенерирован один раз через `openssl genrsa 2048`, не используется нигде,
 * кроме подписи JWT в этих тестах) — генерировать ключ на лету через openssl_pkey_new() в
 * PHP на Windows требует настроенного openssl.cnf, которого может не быть в CI/локально.
 */
const FCM_TEST_PRIVATE_KEY = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDCHV8V16zlxAxk
bWIydqsgEVklRLIVWB+H9jcH03gayh+hDgrfk/CGZadEOc5GxXYJLU85H+zE+p/s
aGiIcoT4lZY5SKf8hD69XJG4lqp+M/juKTejV8dclMJnRXFA+Aduzpx9wyK+HdBg
OLnfjMC9X2VuxZpRXAaqHV0c4SokkOBhUIfZHjWCf6CgEbBuRn+zR5/0XYSkNiO+
hIeYI+ZEdOFsPPSqw48nWPREXEnRlDuoo8LIh70usxSR3tcvJNemyPnOOQkXaE/Z
rmgOrLSNpWuksI0PTjSVm6uq0yHXtsXX+Kojn7E84vwVFPYAFEchVXyvEfILqMQz
Aa3FPG+bAgMBAAECggEABQ83QWj9MIHtzBejxb/yZuQSMuv5nkp34UHUHl1EgKJA
X8tvZ3Ns2KaWolXFYB0ucz/IlFXA2e4ps+48Ee5g3op7lLj66T8t1B6xDPgfBSoS
n2vYmjvOo5rxqY0Vo3AI4DpSDt8YAo+MCliq05OmGb7TnycBsNmEB567T2Y5BHns
l/S9KO8Lw8/MCOAUjjr+3HBbRNZnig4NZk86sCv12AeL8dgFmhlSqwXsKPSgjBMB
CmECK8qDKQjytc6CYLznLb4I9ni674cNGpkZ+bxnTXr0q6I4vxMqEtNceRN2tiPF
E7g9jHFMrQFDNOlOMhZ9lIFI9/+noFCNyr0BOrWVbQKBgQDitJcUcUovvZbiLcGc
o1YskdvrM9EzWZ/w6BBXC6AVpuikSFH+BVbMQTEARIjHnek34bEwUN1VdHT5x6Q8
XW+cAMCfvMsUDFACF0qU47DLClSpp/yJaRE8ZRn5qMsUpT/WfQ7fhngXlTsHZYqC
A9xnAB9WrW5LxIel3riur3I7xwKBgQDbMq6LAkldVqv4a3ObQIcafXtvoj+tqpBJ
HSW75CpkIyWVOrPrg0qGwABCss0DjtSJOjbBNNjL9RMbDS/pVoyOZ247ROfDmR9A
v5YraivwbJ8kKZR1wS2JKd9LFTL4f/MEtoKXiAVjNQQeOR47bQqKR+XSr+R/sPv8
ML8yiGRljQKBgGv2KISZtwSpOvOXar6XonAzPhHyaUwVAHAPc8igRqpJBD1IG5Pn
IwC+gwFnoeO+NdRg7Krft6f8f8B77KC6tm4KhbEMjYGMo4V7Zg8lp4np74uj7kXZ
/VVPqGEWmy/HynDwuWaFeSdx79yD5MQp5oo+qq3yhbYbb6X0POsjrjj9AoGBANV1
ZI5QertfpPa1RQ6CZ07/tc3/lb18ZSgeL1nrFvEPXREW6pFF+LSCk35ges+lLwo3
24yI9zqffayRSgAUXaprxOgI8R4Epm+6YvYCXamzTcK8jyuhJMP3N9D5YvqmNzV2
unPwbTawMUNxYiSCyong/EkRKxbCdpGfswH12rcdAoGAf5Ojjqqde53uHirahRVS
DNVL+YqQtjaAZ+ueFBZzb0gNmtcfVOEWVgUmXIHWXBVZq9UqTxvrDWMHr8qppsbV
rNQ4AR7hs2TGL83PECtWKwPOFLeThQIUWWLANb5z2YrUbubyuhhO/Yyli64KSWF0
rXTBg/t1aylTciocfPX7xuA=
-----END PRIVATE KEY-----
PEM;

function fcmTestCredentialsPath(): string
{
    $path = tempnam(sys_get_temp_dir(), 'fcm-credentials').'.json';
    file_put_contents($path, json_encode([
        'client_email' => 'fcm-test@example.iam.gserviceaccount.com',
        'private_key' => FCM_TEST_PRIVATE_KEY,
    ]));

    return $path;
}

beforeEach(function (): void {
    Cache::flush();
    config(['fcm.project_id' => 'animalfriendly-test', 'fcm.credentials_path' => fcmTestCredentialsPath()]);
});

it('exchanges a JWT for an access token and sends a push message', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token'], 200),
        'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/animalfriendly-test/messages/1'], 200),
    ]);

    $client = new FcmClient;
    $client->send('device-token-1', 'Заголовок', 'Текст', ['match_id' => 'abc']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://oauth2.googleapis.com/token');
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://fcm.googleapis.com/v1/projects/animalfriendly-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && $request['message']['token'] === 'device-token-1'
            && $request['message']['notification']['title'] === 'Заголовок'
            && $request['message']['data']['match_id'] === 'abc';
    });
});

it('throws when the FCM API responds with an error', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token'], 200),
        'https://fcm.googleapis.com/*' => Http::response(['error' => 'invalid token'], 400),
    ]);

    $client = new FcmClient;
    $client->send('device-token-1', 'Заголовок', 'Текст');
})->throws(FcmRequestFailedException::class);

it('logs instead of sending via the null fallback', function (): void {
    Log::shouldReceive('info')->once()->with('fcm.send', Mockery::on(
        fn (array $context) => $context['token'] === 'device-token-1',
    ));

    (new NullFcmClient)->send('device-token-1', 'Заголовок', 'Текст');
});
