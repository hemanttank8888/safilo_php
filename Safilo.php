<?php

require_once 'DatabaseHandler.php';

class SafiloSpider
{
    private $data_list = [];
     
    private function getCookie()
    {
        $loginUrl = "https://www.mysafilo.com/US/login/login";

        $session = curl_init($loginUrl);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($session, CURLOPT_COOKIEJAR, "cookies.txt");
        curl_setopt($session, CURLOPT_COOKIEFILE, "cookies.txt");
        curl_setopt($session, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3");
        curl_setopt($session, CURLOPT_COOKIEFILE, "");
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($session);

        $dom = new DOMDocument;
        @$dom->loadHTML($response);
        $xpath = new DOMXPath($dom);

        $form = $xpath->query('//form[@class="login-form"]')->item(0);
        $loginData = array();

        foreach ($form->getElementsByTagName('input') as $input) {
            $name = $input->getAttribute('name');
            $value = $input->getAttribute('value');
            if ($name && $value) {
                $loginData[$name] = $value;
            }
        }

        $loginData["Identifier"] = "0001119227";
        $loginData["Password"] = "Joel@6840";

        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($loginData));
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        $cookies = curl_getinfo($session, CURLINFO_COOKIELIST);

        curl_close($session);
        return $cookies;
    }

    private function parseCookies($cookies)
    {
        $parsedCookies = [];

        foreach ($cookies as $cookie) {
            $cookieParts = explode("\t", $cookie);
            $cookieName = $cookieParts[5];
            $cookieValue = $cookieParts[6];
            $parsedCookies[$cookieName] = $cookieValue;
        }

        return $parsedCookies;
    }

    private function prepareHeaders($cookies)
    {
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36";
        $cookies_str = implode("; ", array_map(function ($name, $value) {
            return "$name=$value";
        }, array_keys($cookies), $cookies));
        return ["User-Agent: $userAgent", "cookie: $cookies_str"];
    }

    private function extractBrandNames($response)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($response);

        $xpath = new DOMXPath($doc);
        $elements = $xpath->query("//ul[@class='nav-ul nav-ul-brands b2b']//li//text()");

        $brandNames = [];

        if ($elements) {
            foreach ($elements as $element) {
                $brandNames[] = $element->nodeValue;
            }
        } else {
            echo "XPath query did not match any elements.";
        }

        return $brandNames;
    }




    private function requestData($url, $cleanedBrand, $new_cookies)
    {
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36";

        $dataUrl = "https://www.mysafilo.com/US/api/CatalogAPI/filter";
        $body = [
            "Collections" => [$cleanedBrand],
            "ColorFamily" => [],
            "Price" => [
                "min" => -1,
                "max" => -1
            ],
            "Shapes" => [],
            "FrameTypes" => [],
            "Genders" => [],
            "FrameMaterials" => [],
            "FrontMaterials" => [],
            "HingeTypes" => [],
            "RimTypes" => [],
            "TempleMaterials" => [],
            "NewStyles" => false,
            "BestSellers" => false,
            "RxAvailable" => false,
            "InStock" => false,
            "ASizes" => [
                "min" => -1,
                "max" => -1
            ],
            "BSizes" => [
                "min" => -1,
                "max" => -1
            ],
            "EDSizes" => [
                "min" => -1,
                "max" => -1
            ],
            "DBLSizes" => [
                "min" => -1,
                "max" => -1
            ],
            "brandName" => $cleanedBrand
        ];

        $headers = [
            "Content-Type: application/json",
            "User-Agent: $userAgent"
        ];

        $data = json_encode($body);

        $ch = curl_init($dataUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIEFILE, "");
        foreach ($new_cookies as $cookie) {
            $cookieParts = explode("\t", $cookie);

            $cookieName = $cookieParts[5];
            $cookieValue = $cookieParts[6];

            $cookieStr = $cookieName . '=' . $cookieValue;
            curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function fetchData($jsonData)
    {   
        $databaseHandler = new DatabaseHandler("localhost", "root", "", "ezcontact_x_datacenter");
        foreach ($jsonData as $product) {
            foreach ($product['colorGroup'] as $colors) {
                foreach ($colors['sizes'] as $sizes) {
                    $upc = $sizes['frameId'];
                    $color = $sizes['color'];
                    $size = $sizes['size'];
                    $lens = $sizes['lens'];
                    $lens_material = $sizes['lensMaterial'];
                    $availability = $sizes['availableStatus'];
                    $available_date = $sizes['availableDate'];
                    $best_seller = $sizes['isBestSeller'];
                    $frame_type = $sizes['frameType'];
                    $gender = $sizes['gender'];
                    $can_RX = $sizes['canRX'];
                    $front_material = $sizes['frontMaterial'];
                    $temple_material = $sizes['templeMaterial'];
                    $shape = $sizes['shape'];
                    $hinge_type = $sizes['hingeType'];
                    $rim_type = $sizes['rimType'];
                    $a = $sizes['a'];
                    $b = $sizes['b'];
                    $ed = $sizes['ed'];
                    $dbl = $sizes['dbl'];
                    $base_curve = $sizes['baseCurve'];
                    $frame_material = $sizes['material'];

                    $styleName = $sizes['styleName'];
                    $images = $sizes['imageIds'];

                    $data_dict = [
                        'upc' => $upc,
                        'color' => $color,
                        'size' => $size,
                        'lens' => $lens,
                        'lens material' => $lens_material,
                        'availability' => $availability,
                        'available_date' => $available_date,
                        'best seller' => $best_seller,
                        'frame type' => $frame_type,
                        'gender' => $gender,
                        'can RX' => $can_RX,
                        'front material' => $front_material,
                        'temple material' => $temple_material,
                        'shape' => $shape,
                        'hinge type' => $hinge_type,
                        'rim type' => $rim_type,
                        'a' => $a,
                        'b' => $b,
                        'ed' => $ed,
                        'dbl' => $dbl,
                        'base curve' => $base_curve,
                        'frame material' => $frame_material,
                        'styleName' => $styleName
                    ];
                    $databaseHandler->insertData([$data_dict]);
                    $this->data_list[] = $data_dict;
                    $no = 0;
                    foreach ($images as $imageId) {
                        $id = $imageId['id'];
                        $url = "https://www.mysafilo.com/US/api/catalogapi/imagebyid/$id";
                        $no++;
                        $this->downloadImage($url, $styleName, $upc, $no);
                    }
                }
            }
        }
        $databaseHandler->closeConnection();

    }

    private function downloadImage($url, $styleName, $upc, $no)
    {
        $image_data = file_get_contents($url);
        $image_filename = "image_output/{$styleName}/{$upc}_{$no}.jpg";
        if (!is_dir(dirname($image_filename))) {
            mkdir(dirname($image_filename), 0777, true);
        }
        file_put_contents($image_filename, $image_data);
    }


    public function startRequests()
    {
        $new_cookies = $this->getCookie();
        $url = 'https://www.mysafilo.com/US/catalog/index';

        $cookies = $this->parseCookies($new_cookies);
        $headers = $this->prepareHeaders($cookies);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIEFILE, "");

        foreach ($new_cookies as $cookie) {
            $cookieParts = explode("\t", $cookie);

            $cookieName = $cookieParts[5];
            $cookieValue = $cookieParts[6];
            $cookieStr = $cookieName . '=' . $cookieValue;
            curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $brandNames = [];
        $response = curl_exec($ch);

        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            $brandNames = $this->extractBrandNames($response);
        }

        curl_close($ch);

        foreach ($brandNames as $brand) {
            if (!empty($brand)) {
                $cleanedBrand = trim($brand);
                if ($cleanedBrand === "adensco") {
                    $jsonData = $this->requestData($url, $cleanedBrand, $new_cookies);
                    $this->fetchData($jsonData);
                }
            }
        }
    }

    public function saveDataToFile()
    {
        file_put_contents('output_data.json', json_encode($this->data_list, JSON_PRETTY_PRINT));
    }
}
?>