<?php

namespace App\Services;

class TranslationService
{
    private $service;
    private $spreadsheetId;

    public function __construct()
    {
        $client = $this->getClient();
        $this->service = new \Google_Service_Sheets($client);
        $this->spreadsheetId = config('app.spreadsheet_id');
    }

    public function getTranslation()
    {
        $language = 'en';
        $backendResponse = $this->service->spreadsheets_values->get($this->spreadsheetId, "backend!A2:C");

        $backendValues = $backendResponse->getValues();
        $backend = [];
        foreach ($backendValues ?? [] as $row) {
            if (count($row)) {
                $backend[$row[0]] = $row[$this->getColumns($language) ?? 1] ?? '';
            }
        }
        
        return $backend;
    }

    public function getColumns($language)
    {
        $languages = [
            'en' => 1,
            'ph' => 2,
        ];

        return $languages[$language];
    }

    public function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Test Application');
        $client->setScopes(\Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(storage_path('g-sheet/credentials.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $tokenPath = storage_path('g-sheet/token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                if (array_key_exists('error', $accessToken)) {
                    throw new \Exception(join(', ', $accessToken));
                }
            }
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}
