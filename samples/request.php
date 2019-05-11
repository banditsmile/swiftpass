<?php
/**
 * 支付接口调测例子
 * ================================================================
 * index 进入口，方法中转
 * submitOrderInfo 提交订单信息
 * queryOrder 查询订单
 * 
 * ================================================================
 */
include_once  '../vendor/autoload.php';

use bandit\swiftpass\alipay\Qrcode;

Class Controller{
    //$url = 'http://192.168.1.185:9000/pay/gateway';

    private $resHandler = null;
    private $reqHandler = null;
    private $pay = null;
    private $cfg = null;
    
    public function __construct(){
        $this->cfg =  array(
            'url'=>'https://pay.swiftpass.cn/pay/gateway',
            'mch_id'=>'101520000465',
            'key'=>'58bb7db599afc86ea7f7b262c32ff42f',  /* MD5密钥 */
            'version'=>'1.0',
            'sign_type'=>'MD5'
        );
    }
    
    public function index(){
        $method = isset($_REQUEST['method'])?$_REQUEST['method']:'submitOrderInfo';
        switch($method){
            case 'submitOrderInfo'://提交订单
                $this->submitOrderInfo();
            break;
            case 'queryOrder'://查询订单
                $this->queryOrder();
            break;
            case 'closeOrder'://关闭订单
                $this->closeOrder();
            break;
            case 'submitRefund'://提交退款
                $this->submitRefund();
            break;
            case 'queryRefund'://查询退款
                $this->queryRefund();
            break;
            case 'callback':
                $this->callback();
            break;
        }
    }
    
    /**
     * 提交订单信息
     */
    public function submitOrderInfo(){
        $request = new Qrcode($this->cfg);
        $param = [
            'out_trade_no'=>'1111184328042380',
            'body'=>'test',
            'total_fee'=>1,
            'notify_url'=>'http://111.baidu.com'
        ];
        $result = $request->order($param);
        echo json_encode($result);
        exit();
    }

    /**
     * 查询订单
     */
    public function queryOrder(){
        $request = new Qrcode($this->cfg);
        $param = [
            'out_trade_no'=>$this->get_post('out_trade_no'),
            'transaction_id'=>$this->get_post('transaction_id'),
        ];
        $result = $request->queryOrder($param);
        echo json_encode($result);
        exit();
    }

   /* 关闭订单*/
    
    public function closeOrder() {
        $request = new Qrcode($this->cfg);
        $param = [
            'out_trade_no'=>$this->get_post('out_trade_no')
        ];
        $result = $request->closeOrder($param);
        echo json_encode($result);
        exit();
    }
    /**
     * 提交退款
     */
    public function submitRefund(){
        $request = new Qrcode($this->cfg);
        $param = [
            'out_trade_no'=>$this->get_post('out_trade_no'),
            'out_refund_no'=>$this->get_post('out_refund_no'),
            'total_fee'=>1,
            'refund_fee'=>1,
            'op_user_id'=>$this->cfg['mch_id']
        ];
        $result = $request->submitRefund($param);
        echo json_encode($result);
        exit();

    }

    /**
     * 查询退款
     */
    public function queryRefund(){
        $request = new Qrcode($this->cfg);
        $param = [
            'out_trade_no'=>$this->get_post('out_trade_no'),
            'transaction_id'=>$this->get_post('transaction_id'),
        ];
        $result = $request->queryRefund($param);
        echo json_encode($result);
        exit();
    }
    
    /**
     * 提供给威富通的回调方法
     */
    public function callback(){
        $xml = file_get_contents('php://input');
        //$res = Utils::parseXML($xml);
        $this->resHandler->setContent($xml);
		//var_dump($this->resHandler->setContent($xml));
        $this->resHandler->setKey($this->cfg->C('key'));
        if($this->resHandler->isTenpaySign()){
            if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
				//echo $this->resHandler->getParameter('status');
				// 11;
				//更改订单状态
				
				
                Utils::dataRecodes('接口回调收到通知参数',$this->resHandler->getAllParameters());
                echo 'success';
                exit();
            }else{
                echo 'failure1';
                exit();
            }
        }else{
            echo 'failure2';
        }
    }

    private function get_post($key, $default=null)
    {
        if(isset($_GET[$key])){
            return $_GET[$key];
        }

        if(isset($_POST[$key])){
            return $_POST[$key];
        }
        return $default;
    }
}

$req = new Controller();
$req->index();

?>
