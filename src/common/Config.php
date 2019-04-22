<?php
namespace bandit\swiftpass\common;

/**
 * Class Config
 *
 * @package bandit\swiftpass\common
 */
class Config{
    private $cfg = array(
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        'mch_id'=>'101520000465',
        'key'=>'58bb7db599afc86ea7f7b262c32ff42f',  /* MD5密钥 */
        'version'=>'1.0',
        'sign_type'=>'MD5'
       );

    /**
     * Config constructor.
     *
     * @param $conf
     */
    public function __construct($conf)
    {
        $this->cfg = $conf;
    }

    /**
     * @param $cfgName
     * @return mixed
     */
    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}
?>