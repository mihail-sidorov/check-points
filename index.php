<?php

    error_reporting(0);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';



    $start_row = 2;
    $end_row = 1000;
    $spreadsheetId = '14d5nxcGTJaMtRbEg0TkjhlSA7XHazT8V3AyQyOVA5Es';
    $sheet_id = 0;



    /**
    * Получаем данные из БД
    *******************************************************/
    $mysqli = new mysqli('localhost', 'root', '', 'map');
    if ($mysqli->connect_errno) {
        die('Не удалось подключиться к MySQL');
    }
    $mysqli->set_charset("utf8");
    $db_response = $mysqli->query("SELECT * FROM `points` ORDER BY `id` ASC");



    /**
    * Получаем данные из Google cheets
    *******************************************************/
    // Путь к файлу ключа сервисного аккаунта
    $googleAccountKeyFilePath = $_SERVER['DOCUMENT_ROOT'] . '/check-points-914cb303b3f9.json';
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope('https://www.googleapis.com/auth/spreadsheets');
    $service = new Google_Service_Sheets($client);

    $range = "Лист1!A$start_row:I$end_row";
    $sheet_response = $service->spreadsheets_values->get($spreadsheetId, $range);



    // Сравниваем номер телефона из БД и гугл таблицы
    function compare_phones(array $phones_arr) {
        $phone1 = '';
        if (isset($phones_arr[0]) && !empty($phones_arr[0])) {
            $phones_arr[0] = str_replace('+7', '8', $phones_arr[0]);
            $phone1_array = preg_split('//iu', $phones_arr[0], NULL, PREG_SPLIT_NO_EMPTY);
            foreach($phone1_array as $value) {
                if (preg_match('/^[0-9]$/iu', $value)) {
                    $phone1 .= $value;
                }
            }
        }

        $phone2 = '';
        if (isset($phones_arr[1]) && !empty($phones_arr[1])) {
            $phones_arr[1] = str_replace('+7', '8', $phones_arr[1]);
            $phone2_array = preg_split('//iu', $phones_arr[1], NULL, PREG_SPLIT_NO_EMPTY);
            foreach($phone2_array as $value) {
                if (preg_match('/^[0-9]$/iu', $value)) {
                    $phone2 .= $value;
                }
            }
        }

        if ($phone1 === $phone2) {
            return 100;
        }
        else {
            return 0;
        }
    }



    /**
    * Сравниваем данные между Google sheets и БД и выделяем уже занесенные точки
    *******************************************************/
    $requests = [];
    $data_arr_rows = [];
    foreach($sheet_response->values as $index => $row_sheet) {
        $city_sheet = '';
        if (isset($row_sheet[0]) && !empty($row_sheet[0])) {
            $city_sheet_array = preg_split('//iu', $row_sheet[0], NULL, PREG_SPLIT_NO_EMPTY);
            foreach($city_sheet_array as $value) {
                if (preg_match('/^[абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ0-9]$/iu', $value)) {
                    $city_sheet .= $value;
                }
            }
        }

        $street_sheet = '';
        if (isset($row_sheet[3]) && !empty($row_sheet[3])) {
            $street_sheet_array = preg_split('//iu', $row_sheet[3], NULL, PREG_SPLIT_NO_EMPTY);
            foreach($street_sheet_array as $value) {
                if (preg_match('/^[абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ0-9]$/iu', $value)) {
                    $street_sheet .= $value;
                }
            }
        }

        $house_sheet = '';
        if (isset($row_sheet[4]) && !empty($row_sheet[4])) {
            $house_sheet_array = preg_split('//iu', $row_sheet[4], NULL, PREG_SPLIT_NO_EMPTY);
            foreach($house_sheet_array as $value) {
                if (preg_match('/^[0-9]$/iu', $value)) {
                    $house_sheet .= $value;
                }
                else {
                    break;
                }
            }
        }

        $compare_result_from_sheet = mb_strtolower($city_sheet . $street_sheet . $house_sheet);

        $db_response->data_seek(0);
        $data_arr_cols = [];
        while($row = $db_response->fetch_assoc()) {
            $city_db = '';
            if (isset($row['city']) && !empty($row['city'])) {
                $city_db_array = preg_split('//iu', $row['city'], NULL, PREG_SPLIT_NO_EMPTY);
                foreach($city_db_array as $value) {
                    if (preg_match('/^[абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ0-9]$/iu', $value)) {
                        $city_db .= $value;
                    }
                }
            }
    
            $street_db = '';
            if (isset($row['street']) && !empty($row['street'])) {
                $street_db_array = preg_split('//iu', $row['street'], NULL, PREG_SPLIT_NO_EMPTY);
                foreach($street_db_array as $value) {
                    if (preg_match('/^[абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ0-9]$/iu', $value)) {
                        $street_db .= $value;
                    }
                }
            }
    
            $house_db = '';
            if (isset($row['house']) && !empty($row['house'])) {
                $house_db_array = preg_split('//iu', $row['house'], NULL, PREG_SPLIT_NO_EMPTY);
                foreach($house_db_array as $value) {
                    if (preg_match('/^[0-9]$/iu', $value)) {
                        $house_db .= $value;
                    }
                    else {
                        break;
                    }
                }
            }
    
            $compare_result_from_db = mb_strtolower($city_db . $street_db . $house_db);

            if ($compare_result_from_sheet === $compare_result_from_db) {
                // Формируем массив номеров для визуальной проверки
                $procent = compare_phones([$row_sheet[7], $row['phone']]);
                $data_arr_cols[] = $procent . '%';

                $phone_db = '';
                if (isset($row['phone']) && !empty($row['phone'])) {
                    $phone_db = $row['phone'];
                }
                $data_arr_cols[] = $phone_db;

                // Формируем запросы на подсвечивание уже добавленных точек
                $startRowIndex = $index + $start_row - 1;
                $endRowIndex = $startRowIndex + 1;
                if ($procent === 100) {
                    $background_color = ["green" => 1, "red" => 0, "blue" => 0];
                }
                else {
                    $background_color = ["green" => 1, "red" => 0, "blue" => 1];
                }

                $requests[] = new Google_Service_Sheets_Request([
                    'repeatCell' => [
            
                        // Диапазон, который будет затронут
                        "range" => [
                            "sheetId"          => $sheet_id, // ID листа
                            "startRowIndex"    => $startRowIndex,
                            "endRowIndex"      => $endRowIndex,
                            "startColumnIndex" => 0,
                            "endColumnIndex"   => 9
                        ],
            
                        // Формат отображения данных
                        // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#CellFormat
                        "cell"  => [
                            "userEnteredFormat" => [
                                // Фон (RGBA)
                                // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#Color
                                "backgroundColor" => $background_color
                            ]
                        ],
            
                        "fields" => "UserEnteredFormat(backgroundColor)"
                    ]
                ]);

                if ($procent === 100) {
                    break;
                }
            }
        }

        $data_arr_rows[] = $data_arr_cols;
    }

    // Выполняем запрос на внесение номеров в гугл таблицу
    sleep(1);
    $service->spreadsheets_values->update($spreadsheetId, "Лист1!J$start_row", new Google_Service_Sheets_ValueRange(['values' => $data_arr_rows]), array('valueInputOption' => 'RAW'));
    
    // Выполняем запрос на подсветку строк гугл таблицы
    if ($requests) {
        sleep(1);
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
    }



    echo 'Проверка точек завершена!';
?>