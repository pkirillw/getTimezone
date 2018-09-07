<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class TimezoneController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $url = '';
    private $smscLogin = '';
    private $smscPass = '';
    private $amoLogin = '';
    private $amoHash = '';
    private $amoDomain = '';

    public function __construct()
    {
        $this->url = 'https://smsc.ru/sys/info.php?get_operator=1&login=' . $this->smscLogin . '&psw=' . $this->smscPass . '&fmt=3&phone=';

    }

    public function getTimezone($amoId, $card)
    {
        $user = array(
            'USER_LOGIN' => $this->amoLogin, #Ваш логин (электронная почта)
            'USER_HASH' => $this->amoHash #Хэш для доступа к API (смотрите в профиле пользователя)
        );
        $subdomain = $this->amoDomain; #Наш аккаунт - поддомен
#Формируем ссылку для запроса
        $link = 'https://' . $subdomain . '.amocrm.ru/private/api/auth.php?type=json';
        /* Нам необходимо инициировать запрос к серверу. Воспользуемся библиотекой cURL (поставляется в составе PHP). Вы также
        можете
        использовать и кроссплатформенную программу cURL, если вы не программируете на PHP. */
        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
#Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($user));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
        curl_close($curl); #Завершаем сеанс cURL

        /* Теперь подготовим данные, необходимые для запроса к серверу */
        $subdomain = $this->amoDomain; #Наш аккаунт - поддомен
#Формируем ссылку для запроса
        if ($card == 'ccard') {
            $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/contacts/?id=' . $amoId;
        } else {
            $link = 'https://' . $subdomain . '.amocrm.ru/api/v2/companies/?id=' . $amoId;
        }

        /* Нам необходимо инициировать запрос к серверу. Воспользуемся библиотекой cURL (поставляется в составе PHP). Подробнее о
        работе с этой
        библиотекой Вы можете прочитать в мануале. */
        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
#Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        //curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contacts));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную\
        //dd(json_decode($out));
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = array(
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable'
        );
        try {
            #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
            if ($code != 200 && $code != 204) {
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
            }
        } catch (\Exception $E) {
            die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
        }
        $out = json_decode($out, true);
        if (!empty($out['_embedded']['items'][0]['custom_fields'])) {
            $phoneKey = array_search(1513980, array_column($out['_embedded']['items'][0]['custom_fields'], 'id'));
            if (($phoneKey === false) and (!empty($out['_embedded']['items'][0]['custom_fields'][$phoneKey]['values'][0]['value']))) {
                return response()->json(['error' => 'В карточке не найден телефон']);
            }
            $phone = $out['_embedded']['items'][0]['custom_fields'][$phoneKey]['values'][0]['value'];
        }
        $phone = preg_replace("/[^0-9]/", '', $phone);
        $gmtMoscow = '3';
        if ($phone == '') {
            return response()->json(['error' => 'Нет данных']);
        }
        $client = new Client();
        $res = $client->request('GET', $this->url . $phone);
        $response = $res->getBody();
        if (empty($response)) {
            return response()->json(['error' => 'Ошибка взаимодействия с API']);
        }
        //echo $phone;
        $response = json_decode($response);
        //dd($response);
        if (isset($response->status) && ($response->status == 'error')) {
            return response()->json(['error' => 'Неправильный формат телефона']);
        }
        $gmt = $response->tz;
        $contacts['update'] = [
            [
                'id' => $amoId,
                'updated_at' => time(),
                'custom_fields' => [
                    [
                        'id' => "1970926",
                        'values' => [
                            [
                                'value' => $this->solveTimezone($gmtMoscow, $gmt)
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
#Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($contacts));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = array(
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable'
        );
        try {
            #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
            if ($code != 200 && $code != 204) {
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
            }
        } catch (\Exception $E) {
            die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
        }


        return response()->json(['data' => $this->solveTimezone($gmtMoscow, $gmt)]);
    }

    public function getTimezonePhone($phone)
    {
        $gmtMoscow = '3';
        $this->url = 'https://smsc.ru/sys/info.php?get_operator=1&login=' . $this->smscLogin . '&psw=' . $this->smscPass . '&fmt=3&phone=';
        $client = new Client();
        $res = $client->request('GET', $this->url . $phone);
        $response = $res->getBody();
        if (empty($response)) {
            return response()->json(['error' => 'Ошибка взаимодействия с API']);
        }
        //echo $phone;
        $response = json_decode($response);
        //dd($response);
        if (isset($response->status) && ($response->status == 'error')) {
            return response()->json(['error' => 'Неправильный формат телефона']);
        }
        $gmt = $response->tz;
        return response()->json(['data' => $this->solveTimezone($gmtMoscow, $gmt)]);
    }

    public function solveTimezone($a, $b)
    {
        if ($b[0] == '-') {
            $flagPlusB = false;
            $tempB = mb_substr($b, 1);
        } else {
            $flagPlusB = true;
            $tempB = $b;
        }

        $tempA = $a;

        $response = 0;
        if ($flagPlusB) {
            $response = ((int)$tempB - (int)$tempA);
        } else {
            $response = '-' . ((int)$tempB + (int)$tempA);
        }
        if ((int)$response >= 0) {
            return '+' . $response;
        }
        return $response;
    }
    /*
     * position: absolute;
        right: 15px;
        top: 8px;
        cursor: pointer;
        width: 14px;
        height: 14px;
        background-position: 0 -1485px;
     */
}
