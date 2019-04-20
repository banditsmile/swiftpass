<?php
class Config{
    private $cfg = array(
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        'mchId'=>'101520000465',
        'key'=>'58bb7db599afc86ea7f7b262c32ff42f',  /* MD5密钥 */
        'version'=>'1.0',
        'sign_type'=>'MD5'
       );
    
    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}
?>