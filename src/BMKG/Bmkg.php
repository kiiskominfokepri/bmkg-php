<?php

namespace BmkgSdk;

class BMKG 
{

    private $baseUrl = "";
    private $referer = "";
    private $userAgent = "";
    private $codeMapper = [];

    public function __construct()
    {
        $this->baseUrl = "https://data.bmkg.go.id/DataMKG/MEWS/DigitalForecast/";
        $this->referer = "https://data.bmkg.go.id/";
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

    private $dataPath = "";
    public function setDataPath($dataPath)
    {
        $this->dataPath = $this->baseUrl . $dataPath;
    }

    private function fetchRemoteData()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_URL, $this->dataPath);
        if (!$html = curl_exec($ch)) {
            return null;
        } else {
            curl_close($ch);
            return $html;
        }
    }

    public function getForecast($loadAll = FALSE)
    {
        $data = $this->fetchRemoteData();
        if ($data) {
            $xml = simplexml_load_string($data);
            if ($xml === false) {
                return array(
                    "status"=>false,
                    "message"=>"Gagal mendapatkan data cuaca dari BMKG",
                    "issue"=> "",
                    "data"=>[],
    	  	    );
            } else {
                $jsonData = json_encode($xml);
                $arrayData = json_decode($jsonData, TRUE);
                $forecast = $arrayData['forecast'] ?? null;
                $issue = \DateTime::createFromFormat('YmdHis', $forecast['issue']['timestamp']);
                $dt = [];
                foreach($forecast['area'] as $k=>$v){
                    if($k < 7){
                        $temp['nama'] = $v['name'][0] ?? "";
                        $humidity = $v['parameter'][0]['timerange'] ?? null;
                        $hu_max = $v['parameter'][1]['timerange'] ?? null;
                        $hu_min = $v['parameter'][3]['timerange'] ?? null;
                        $t_max = $v['parameter'][2]['timerange'] ?? null;
                        $t_min = $v['parameter'][4]['timerange'] ?? null;
                        $temperature = $v['parameter'][5]['timerange'] ?? null;
                        $weather = $v['parameter'][6]['timerange'] ?? null;
                        $hourly = [];
                        $sekarang = [];
                        for($i=0; $i<12; $i++){
                            $datetime = \DateTime::createFromFormat('YmdHi', $temperature[$i]['@attributes']['datetime']);
                            $datetime_next6 = (clone $datetime)->add(new \DateInterval('PT6H'));
                            $datetime_now = new \DateTime();
                            $prakiraan = array(
                                "datetime" => $datetime->format('Y-m-d H:i') ?? "",
                                "jam" => $temperature[$i]['@attributes']['h'] ?? "",
                                "celcius" => $temperature[$i]['value'][0] ?? "",
                                "fahrenheit" => $temperature[$i]['value'][1] ?? "",
                                "cuaca" => $codeMapper[$weather[$i]['value']] ?? "",
                                "icon" => $weather[$i]['value'] ?? "",
                                "humidity" => $humidity[$i]['value'] ?? "",
                            );
                            
                            if($loadAll || $datetime->format('Y-m-d') == $datetime_now->format('Y-m-d')){
                                array_push($hourly, $prakiraan);
                            }

                            if($datetime <= $datetime_now && $datetime_now <= $datetime_next6){
                                $prakiraan["humax"] = "";
                                for($j=0; $j<3; $j++){
                                    $v_datetime = \DateTime::createFromFormat('YmdHi', $hu_max[$j]['@attributes']['datetime']);
                                    if($v_datetime->format('Y-m-d') == $datetime_now->format('Y-m-d')){
                                        $prakiraan["humax"] = $hu_max[$j]['value'] ?? "";
                                        $prakiraan["humin"] = $hu_min[$j]['value'] ?? "";
                                        $prakiraan["tmax"]  = array(
                                            "celcius" => $t_max[$j]['value'][0] ?? "",
                                            "fahrenheit" => $t_max[$j]['value'][1] ?? "",
                                        );
                                        $prakiraan["tmin"]  = array(
                                            "celcius" => $t_min[$j]['value'][0] ?? "",
                                            "fahrenheit" => $t_min[$j]['value'][1] ?? "",
                                        );
                                    }
                                }
                                array_push($sekarang, $prakiraan);
                            }
                        }
                        $temp['prakiraan'] = $hourly;
                        $temp['sekarang'] = $sekarang;
                        array_push($dt, $temp);
                    }
                }
                return array(
                    "status"=>true,
                    "message"=>"Mendapatkan data cuaca dari BMKG",
                    "issue"=> $issue->format('Y-m-d H:i:s'),
                    "data"=> $dt,
    	  	    );
            }
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
