<?php
namespace bandit\swiftpass\common;

class Utils{
    /**
     * 将数据转为XML
     */
    public static function toXml($array){
        $xml = '<xml>';
        forEach($array as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }
    
    public static function dataRecodes($title,$data){
        $handler = fopen('result.txt','a+');
        $content = "================".$title."===================\n";
        if(is_string($data) === true){
            $content .= $data."\n";
        }
        if(is_array($data) === true){
            forEach($data as $k=>$v){
                $content .= "key: ".$k." value: ".$v."\n";
            }
        }
        $flag = fwrite($handler,$content);
        fclose($handler);
        return $flag;
    }

    public static function parseXML($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = Utils::getXmlEncode($xmlSrc);

        if($xml && $xml->children()) {
			foreach ($xml->children() as $node){
				//有子节点
				if($node->children()) {
					$k = $node->getName();
					$nodeXml = $node->asXML();
					$v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
					
				} else {
					$k = $node->getName();
					$v = (string)$node;
				}
				
				if($encode!="" && $encode != "UTF-8") {
					$k = iconv("UTF-8", $encode, $k);
					$v = iconv("UTF-8", $encode, $v);
				}
				$array[$k] = $v;
			}
		}
        return $array;
    }

    //获取xml编码
	public static function getXmlEncode($xml) {
		$ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
		if($ret) {
			return strtoupper ( $arr[1] );
		} else {
			return "";
		}
	}

    /**
     * 生成随机字符串
     * @return int
     */
	public static function nonceStr()
    {
        return mt_rand(time(),time()+rand());
    }

    /**
     * 获取终端ip
     * @return mixed|string
     */
    public static function remoteIp()
    {
        $arr_ip_header = array(
            "HTTP_CDN_SRC_IP",
            "HTTP_PROXY_CLIENT_IP",
            "HTTP_WL_PROXY_CLIENT_IP",
            "HTTP_CLIENT_IP",
            "HTTP_X_FORWARDED_FOR",
            "REMOTE_ADDR",
        );

        $client_ip = "";
        foreach ($arr_ip_header as $key) {
            if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != "unknown") {
                $client_ip = $_SERVER[$key];
                break;
            }
        }
        if (false !== strpos($client_ip, ",")) {
            $client_ip = preg_replace("/,.*/", "", $client_ip);
        }
        return $client_ip;
    }
}
?>