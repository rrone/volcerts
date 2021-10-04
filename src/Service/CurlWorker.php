<?php


namespace App\Service;

define("VIEW_CERT_URL", "https://national.ayso.org/Volunteers/SelectViewCertificationInitialData");

class CurlWorker
{
    /**
     * @param $url
     * @param array|null $get
     * @return array
     */
//    Reference: https://www.toni-develops.com/2017/09/05/curl-multi-fetch/

    public function curl_multi_get($url, array $get = null): array
    {
        if (is_null($get)) {
            return array();
        }

        $ch = array();
        $mh = curl_multi_init();

        foreach ($get as $i => $id) {

            $ch[$i] = curl_init();
            curl_setopt($ch[$i], CURLOPT_URL, $url.'?'.http_build_query(['AYSOID' => $id]));
            curl_setopt($ch[$i], CURLOPT_HEADER, 0);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch[$i]);
        }

        $active = null;
        do {
            curl_multi_exec($mh, $active);
            usleep(100); // May need to limit CPU load
        } while ($active);

        $content = array();
        foreach ($ch as $i => $c) {
            $content[$i] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }

        curl_multi_close($mh);

        return $content;
    }


}