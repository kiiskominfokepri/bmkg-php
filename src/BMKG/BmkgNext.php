<?php

namespace BmkgSdk;

class BMKGNext 
{

    private $baseUrl = "";
    private $referer = "";
    private $userAgent = "";
    private $codeMapper = [];

    public function __construct()
    {
        $this->baseUrl = "https://api.bmkg.go.id/publik/prakiraan-cuaca";
        $this->referer = "https://api.bmkg.go.id/";
        $this->userAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0.1) Gecko/20100101 Firefox/52.0.1";
        $this->codeMapper = array(
		    '0' => "Cerah",
            '1' => "Cerah Berawan",
            '2' => "Cerah Berawan",
            '3' => "Berawan",
            '4' => "Berawan Tebal",
            '5' => "Udara Kabur",
            '10' => "Asap",
            '45' => "Kabut",
            '60' => "Hujan Ringan",
            '61' => "Hujan Sedang",
            '63' => "Hujan Lebat",
            '80' => "Hujan Lokal",
            '95' => "Hujan Petir",
            '97' => "Hujan Petir",
		);
    }

    private $queryParam = "";
    public function setParams($param = [])
    {
        $this->queryParam = $this->baseUrl . "?" . http_build_query($param);
    }

    private function fetchRemoteData()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_URL, $this->queryParam);
        if (!$html = curl_exec($ch)) {
            return null;
        } else {
            curl_close($ch);
            return $html;
        }
    }

    public function getForecast($loadAll = FALSE)
    {
        date_default_timezone_set('Asia/Jakarta');
        $data = $this->fetchRemoteData();
        if ($data) {
            $arrayData = json_decode($data, TRUE);
            $forecast = $arrayData['data'] ?? null;
            $dt = [];
            $issue = "";
            foreach($forecast as $k=>$v) {
                $temp['nama'] = $v['lokasi']['kotkab'] ?? "";
                $hourly = [];
                $sekarang = [];

                foreach($v['cuaca'] as $daily) {
                    for($i=0; $i<sizeof($daily); $i++){
                        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', $daily[$i]['local_datetime']);
                        $datetime_next3 = (clone $datetime)->add(new \DateInterval('PT3H'));
                        $datetime_now = new \DateTime();
                        $prakiraan = array(
                            "datetime" => $datetime->format('Y-m-d H:i') ?? "",
                            "jam" => $datetime->format('H:i') ?? "",
                            "celcius" => $daily[$i]['t'] ?? "",
                            "fahrenheit" => "",
                            "cuaca" => $daily[$i]['weather_desc'] ?? "",
                            "icon" => $daily[$i]['weather'] ?? "",
                            "image" => $daily[$i]['image'] ?? "",
                            "humidity" => $daily[$i]['hu'] ?? "",
                        );

                        if($loadAll || $datetime->format('Y-m-d') == $datetime_now->format('Y-m-d')){
                            array_push($hourly, $prakiraan);
                        }

                        if($datetime <= $datetime_now && $datetime_now <= $datetime_next3){
                            $prakiraan["humax"] = "";
                            $prakiraan["humin"] = "";
                            $prakiraan["tmax"]  = array(
                                "celcius" => "",
                                "fahrenheit" => "",
                            );
                            $prakiraan["tmin"]  = array(
                                "celcius" => "",
                                "fahrenheit" => "",
                            );
                            array_push($sekarang, $prakiraan);
                        }

                        $issueDate = new \DateTime($daily[$i]['analysis_date']);
                        $issue = $issueDate->format('Y-m-d H:i:s');
                    }
                }

                $temp['prakiraan'] = $hourly;
                $temp['sekarang'] = $sekarang;
                array_push($dt, $temp);
            }
            return array(
                "status"=>true,
                "message"=>"Mendapatkan data cuaca dari BMKG",
                "issue"=> $issue,
                "data"=> $dt,
            );
        } else {
            return array(
                "status"=>false,
                "message"=>"Status Offline",
                "issue"=> "",
                "data"=>[],
            );
        }
        
    }
}
