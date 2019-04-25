<?php
/**
 * 支付宝扫码支付
 */
namespace bandit\swiftpass\alipay\code;

/**
 * 支付宝扫码支付
 * ================================================================
 * index 进入口，方法中转
 * submitOrderInfo 提交订单信息
 * queryOrder 查询订单
 * 
 * ================================================================
 */
use bandit\swiftpass\common\Utils;
use bandit\swiftpass\common\RequestHandler;
use bandit\swiftpass\common\ResponseHandler;
use bandit\swiftpass\common\HttpClient;

/**
 * Class Request
 *
 * @property $resHandler  bandit\swiftpass\common\ResponseHandler
 * @property $reqHandler  bandit\swiftpass\common\RequestHandler
 * @property $pay  bandit\swiftpass\common\HttpClient
 * @property $cfg  bandit\swiftpass\common\Config
 * @package bandit\swiftpass\alipay\code
 */
Class Request
{
    //$url = 'http://192.168.1.185:9000/pay/gateway';

    /**
     * @var \bandit\swiftpass\common\ResponseHandler
     */
    private $resHandler = null;
    /**
     * @var \bandit\swiftpass\common\RequestHandler
     */
    private $reqHandler = null;
    /**
     * @var \bandit\swiftpass\common\HttpClient
     */
    private $pay = null;
    /**
     * @var \bandit\swiftpass\common\config
     */
    private $cfg = null;

    private $fail;


    /**
     * @var array $conf
     * $conf.mch_id
     * $conf.version
     * $conf.sign_type
     */
    private $conf = [];

    /**
     * Request constructor.
     *
     * @param array $conf 支付配置
     */
    public function __construct(array $conf)
    {
        $this->conf = $conf;
        $this->resHandler = new ResponseHandler();
        $this->reqHandler = new RequestHandler();
        $this->pay = new HttpClient();
        $this->reqHandler->setGateUrl($this->conf['url']);
    }

    /**
     * 补充参数
     *
     * @param $params
     * @param $interface
     *
     * @return null
     */
    private function _paramAppend($params, $interface)
    {
        $params = array_merge($this->conf, $params);
        $sign_type = $params['sign_type'];

        if ($sign_type == 'MD5') {
            $this->reqHandler->setKey($this->conf['key']);
            $this->resHandler->setKey($this->conf['key']);
        } else if ($sign_type == 'RSA_1_1' || $sign_type == 'RSA_1_256') {
            $this->reqHandler->setRSAKey($this->conf['private_rsa_key']);
            $this->resHandler->setRSAKey($this->conf['public_rsa_key']);
        }
        $this->reqHandler->setSignType($sign_type);
        $this->reqHandler->setReqParams($params, array('method'));
        if (empty($allParams['mch_create_ip'])) {
            $this->reqHandler->setParameter('mch_create_ip', Utils::remoteIp());
        }
        if (empty($allParams['nonce_str'])) {
            //随机字符串，必填项，不长于 32 位
            $this->reqHandler->setParameter('nonce_str', Utils::nonceStr());
        }
        //接口类型：pay.alipay.native  表示支付宝扫码
        $this->reqHandler->setParameter('service', $interface);

        //通知地址，必填项，接收威富通通知的URL，需给绝对路径，255字符内格式如:http://wap.tenpay.com/tenpay.asp
        //$notify_url = 'http://'.$_SERVER['HTTP_HOST'];

        $this->reqHandler->createSign();//创建签名


    }


    /**
     * 发起http请求
     *
     * @return mixed
     */
    private function _doRequest()
    {
        $data = Utils::toXml($this->reqHandler->getAllParameters());

        $this->pay->setReqContent($this->reqHandler->getGateURL(), $data);
        $ret = $this->pay->call();
        return $ret;
    }

    /**
     * 参数检查
     *
     * @param $params
     * @param $check
     *
     * @return array
     */
    private function _paramCheck($params, $check)
    {
        $paramKeys = array_keys($params);
        $lostParams = array_diff($check, $paramKeys);
        if (!empty($lostParams)) {
            return $lostParams;
        }
        return [];
    }
    /**
     * 提交订单信息
     *
     * @param array $params 下单
     *
     * @return array
     */
    public function order(array $params)
    {
        $interface = 'pay.alipay.native';
        //必选参数
        $requiredInput = ['mch_id','out_trade_no','body','total_fee','notify_url'];
        $allParams = array_merge($this->conf, $params);
        $lostParams = $this->_paramCheck($allParams, $requiredInput);
        if (!empty($lostParams)) {
            return ['status'=>501,'msg'=>'缺少必要参数','data'=>$lostParams];
        }

        $this->reqHandler->setReqParams($allParams, array('method'));
        $this->_paramAppend($allParams, $interface);

        $ret = $this->_doRequest();
        if (empty($ret)) {
            return ['status'=>502,'message'=>$this->pay->getErrInfo(),'data'=>[]];
        }
        $result =  $this->pay->getResContent();
        $result = $this->resHandler->parseContent($result);
        return $result;
    }

    /**
     * 请求返回结果处理
     *
     * @return array
     */
    public function dealRes()
    {
        $this->resHandler->setContent($this->pay->getResContent());
        $this->resHandler->setKey($this->reqHandler->getKey());
        $ret = $this->resHandler->isTenpaySign();
        if (empty($ret)) {
            return array(
                'status'=>500,
                'message'=>
                    'Error Code:'.$this->resHandler->getParameter('status')
                    .' Error Message:'.$this->resHandler->getParameter('message')
            );
        }
        //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
        $status = $this->resHandler->getParameter('status');
        $result_code =  $this->resHandler->getParameter('result_code');
        if ($status != 0 || $result_code != 0) {
            return array(
                'status'=>500,
                'msg'=>
                    'Error Code:'.$this->resHandler->getParameter('err_code')
                    .' Error Message:'.$this->resHandler->getParameter('err_msg')
            );
        }
        $ret = array(
            'code_img_url'=>$this->resHandler->getParameter('code_img_url'),
            'code_url'=>$this->resHandler->getParameter('code_url'),
            'code_status'=>$this->resHandler->getParameter('code_status'),
            'type'=>$this->reqHandler->getParameter('service')
        );
        return $ret;
    }


    /**
     * 查询订单
     *
     * @param array $params 请求参数
     *
     * @return array
     */
    public function queryOrder($params)
    {
        $interface = 'unified.trade.query';
        //必选参数
        $requiredInput = ['mch_id'];
        $allParams = array_merge($this->conf, $params);
        $lostParams = $this->_paramCheck($allParams, $requiredInput);
        if (!empty($lostParams)) {
            return ['status'=>501,'msg'=>'缺少必要参数','data'=>$lostParams];
        }
        if (empty($params['out_trade_no']) && empty($params['transaction_id'])) {
            return [
                'status'=>501,
                'msg'=>'out_trade_no和transaction_id至少一个必填',
                'data'=>$lostParams
            ];
        }

        $this->reqHandler->setReqParams($allParams, array('method'));
        $this->_paramAppend($allParams, $interface);

        $ret = $this->_doRequest();
        if (empty($ret)) {
            return ['status'=>502,'message'=>$this->pay->getErrInfo(),'data'=>[]];
        }
        $result =  $this->pay->getResContent();
        $result = $this->resHandler->parseContent($result);
        return $result;
    }

    /**
     * 关闭订单
     *
     * @param array $params 请求参数
     *
     * @return array
     */
    public function closeOrder($params)
    {
        $interface = 'unified.trade.close';
        //必选参数
        $requiredInput = ['mch_id','out_trade_no'];
        $allParams = array_merge($this->conf, $params);
        $lostParams = $this->_paramCheck($allParams, $requiredInput);
        if (!empty($lostParams)) {
            return ['status'=>501,'msg'=>'缺少必要参数','data'=>$lostParams];
        }

        $this->reqHandler->setReqParams($allParams, array('method'));
        $this->_paramAppend($allParams, $interface);

        $ret = $this->_doRequest();
        if (empty($ret)) {
            return ['status'=>502,'message'=>$this->pay->getErrInfo(),'data'=>[]];
        }
        $result =  $this->pay->getResContent();
        $result = $this->resHandler->parseContent($result);
        return $result;

    }

    /**
     * 提交退款
     *
     * @param array $params 请求参数
     *
     * @return array
     */
    public function submitRefund($params)
    {
        $interface = 'unified.trade.refund';
        //必选参数
        $requiredInput = ['mch_id','total_fee','refund_fee','op_user_id',
            'out_refund_no'];
        $allParams = array_merge($this->conf, $params);
        $lostParams = $this->_paramCheck($allParams, $requiredInput);
        if (!empty($lostParams)) {
            return ['status'=>501,'msg'=>'缺少必要参数','data'=>$lostParams];
        }
        if (empty($params['out_trade_no']) && empty($params['transaction_id'])) {
            return [
                'status'=>501,
                'msg'=>'out_trade_no和transaction_id至少一个必填',
                'data'=>$lostParams
            ];
        }

        $this->reqHandler->setReqParams($allParams, array('method'));
        $this->_paramAppend($allParams, $interface);

        $ret = $this->_doRequest();
        if (empty($ret)) {
            return ['status'=>502,'message'=>$this->pay->getErrInfo(),'data'=>[]];
        }
        $result =  $this->pay->getResContent();
        $result = $this->resHandler->parseContent($result);
        return $result;
    }

    /**
     * 提交退款
     *
     * @param array $params 查询退款
     *
     * @return array
     */
    public function queryRefund($params)
    {
        $interface = 'unified.trade.refundquery';
        //必选参数
        $requiredInput = ['mch_id'];
        $allParams = array_merge($this->conf, $params);
        $lostParams = $this->_paramCheck($allParams, $requiredInput);
        if (!empty($lostParams)) {
            return ['status'=>501,'msg'=>'缺少必要参数','data'=>$lostParams];
        }
        if (empty($params['out_trade_no']) && empty($params['transaction_id'])) {
            return [
                'status'=>501,
                'msg'=>'out_trade_no和transaction_id至少一个必填',
                'data'=>$lostParams
            ];
        }

        $this->reqHandler->setReqParams($allParams, array('method'));
        $this->_paramAppend($allParams, $interface);

        $ret = $this->_doRequest();
        if (empty($ret)) {
            return ['status'=>502,'message'=>$this->pay->getErrInfo(),'data'=>[]];
        }
        $result =  $this->pay->getResContent();
        $result = $this->resHandler->parseContent($result);
        return $result;
    }


    /**
     * 提交退款
     *
     * @param Closure $closure 提供给威富通的回调方法
     *
     * @return array
     */
    public function callback(Closure $closure)
    {
        $xml = file_get_contents('php://input');
        $this->resHandler->setContent($xml);
        $this->resHandler->setKey($this->cfg->C('key'));
        if (!$this->resHandler->isTenpaySign()) {
            $this->resHandler->fail = 'check sign failed';
            $this->resHandler->toResponse();
        }
        if ($this->resHandler->getParameter('status') != 0
            || $this->resHandler->getParameter('result_code') != 0
        ) {
            $this->resHandler->fail('check status and code failed');
            $this->resHandler->toResponse();
        }
        //支付成功回调逻辑
        $message = $this->resHandler->getAllParameters();
        call_user_func($closure, $message, [$this, 'fail']);
        $this->resHandler->toResponse();
    }

    /**
     * 回调检查
     *
     * @param mixed $result 回调内容
     *
     * @return null
     */
    protected function strict($result)
    {
        if (true !== $result && is_null($this->fail)) {
            $this->fail(strval($result));
        }
    }

    /**
     * 回调失败处理
     *
     * @param string $message 回调失败返回的消息
     *
     * @return null
     */
    public function fail(string $message)
    {
        $this->fail = $message;
    }
}
?>