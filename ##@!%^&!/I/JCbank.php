<?php
namespace jR\I;
use jR\I\Interfaces\bankInterface;
class JCbank implements bankInterface
{ # PC端 服务类

	# 金城银行资金存管交互类
	# js@jsran.cn
	# by 2018-02-07 司丙然
	# PHP 5.5+
	
	use Traits\bankTrait{ getAutoStatus as private ast; }
	# 环境地址
	static private $host		= 'http://116.239.4.195:8090/';
	# 商户号
	static private $mchnt_cd	= '0003310F0352403';
	# PC 0 APP 1
	static private $client_tp	= 1;
	# 私钥
	static private $prikey		= PATH.DS.'O'.DS.'bctg'.DS.'php_prkeyc.pem';
	# 公钥
	static private $pubkey		= PATH.DS.'O'.DS.'bctg'.DS.'php_pbkeyc.pem';
	# 操作账户 最多可2个
	static public $Fuid = array();

	public function __construct($Fuid = array())
	{ # 需要操作的账户
		self::$Fuid = $Fuid;
	}
	public function RegPer($in)
	{
		/*
		功能介绍:
			1、 三码开户：个人用户在存管系统开户页面填写个人信息，设置交易密码后，开通网贷平台资金存管账户；
			2、 四码开户：个人用户在存管系统开户页面填写个人信息，设置交易密码，验证银行卡预留手机号的短信验证码后，开通网贷平台资金存管账户并绑卡；
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1、 网贷平台调用【个人开户】接口（仅四码开户（出借人，借款人，代偿户）可请求上传用户授权信息）引导个人用户跳转到存管页面进行三码/四码开户；
			2、 三码开户：用户在存管系统页面填写实名信息，银行卡信息并设置交易密码；
			3、 四码开户:用户在存管系统页面填写实名信息，银行卡信息，设置交易密码并且填写短信验证码（四码认证用户，接受验证码手机号需为银行预留手机号）；
			4、 存管系统验证用户信息，回调平台开户结果、相关参数；
		规则描述: 
			1、 开户手机号作为用户登录名不得修改，但接受短信验证码手机号可以变更--四码开户用户变更时要求同银行预留手机保持一致
			2、 三码开户用户可正常使用更换手机号，绑卡操作，其他可使用交易类型待监管确认；
			3、 四码开户用户才能使用充值、提现、转账交易类、解绑、授权等操作，四码开户成功可正常使用所有功能；
		 */
		
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 三码开户 regUserByFour 四码开户 regUserByFive
			'code'				=> $in['code'],
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# N 手机号
			'mobile_no'			=> self::$Fuid[0],
			# N 用户姓名
			'cust_nm'			=> args($in['rname']),
			# N 证件类型 0 - 身份证
			'certif_tp'			=> 0,
			# N 证件号码
			'certif_id'			=> args($in['real_id']),
			# Y 用户属性 1 出借人 2 借款人 5 代偿户 9 担保方手续费户
			'usr_attr'			=> $in['utype'],
			# N 邮箱地址
			'email'				=> null,
			# N 开户行地区代码
			'city_cd'			=> null,
			# N 开户行行别
			'parent_bank_id'	=> null,
			# N 开户行支行名
			'bank_nm'			=> null,
			# N 银行卡号
			'card_no'			=> null,
			# N 授权状态
			'auth_st'			=> self::ast($in['utype']),
			# N 自动出借授权期限
			'auto_lend_term'	=> $in['utype'] == 1 ? '20301231' : null,
			# N 自动出借授权额度
			'auto_lend_amt'		=> $in['utype'] == 1 ? '1000000000': null,
			# N 自动还款授权期限
			'auto_repay_term'	=> $in['utype'] == 2 ? '20301231' : null,
			# N 自动还款授权额度
			'auto_repay_amt'	=> $in['utype'] == 2 ? '1000000000': null,
			# N 自动代偿授权期限
			'auto_compen_term'	=> $in['utype'] == 5 ? '20301231' : null,
			# N 自动代偿授权额度
			'auto_compen_amt'	=> $in['utype'] == 5 ? '1000000000': null,
			# N 缴费授权期限
			'auto_fee_term'		=> '20301231',
			# N 缴费授权额度
			'auto_fee_amt'		=> '1000000000',
			# Y 商户返回地址
			'page_notify_url'	=> url('main','back'),
			# Y 后台通知地址
			'back_notify_url'	=> url('main','back2'),
		);
		$data['signature']	=	$this->rsaSV($data);
		return $this->Form('control.action',$data,false);
	}

	public function RegEnt($in)
	{ # 企业 法人 开户
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 regLegalUser
			'code'				=> $in['code'],
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# N 企业名称
			'cust_nm'			=> args($in['name']),
			# N 法人姓名
			'artif_nm'			=> args($in['rname']),
			# N 手机号
			'mobile_no'			=> self::$Fuid[0],
			# N 证件类型 0 - 身份证
			'certif_tp'			=> 0,
			# N 证件号码
			'certif_id'			=> args($in['real_id']),
			# Y 用户属性 1 出借人 2 借款人 3 营销户 4 手续费户 5 代偿户 6 消费金融商户 7 平台自由资金账户 9 担保方手续费户
			'usr_attr'			=> $in['utype'],
			# N 邮箱地址
			'email'				=> null,
			# N 开户行地区代码
			'city_cd'			=> null,
			# N 开户行行别
			'parent_bank_id'	=> null,
			# N 开户行支行名
			'bank_nm'			=> null,
			# N 对公账号
			'card_no'			=> null,
			# N 授权状态
			'auth_st'			=> self::ast($in['utype']),
			# N 自动出借授权期限
			'auto_lend_term'	=> $in['utype'] == 1 ? '20301231' : null,
			# N 自动出借授权额度
			'auto_lend_amt'		=> $in['utype'] == 1 ? '10000000000': null,
			# N 自动还款授权期限
			'auto_repay_term'	=> $in['utype'] == 2 ? '20301231' : null,
			# N 自动还款授权额度
			'auto_repay_amt'	=> $in['utype'] == 2 ? '10000000000': null,
			# N 自动代偿授权期限
			'auto_compen_term'	=> $in['utype'] == 5 ? '20301231' : null,
			# N 自动代偿授权额度
			'auto_compen_amt'	=> $in['utype'] == 5 ? '10000000000': null,
			# N 缴费授权期限
			'auto_fee_term'		=> '20301231',
			# N 缴费授权额度
			'auto_fee_amt'		=> '10000000000',
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function BindCard($in)
	{
		/*
		功能介绍: 
			仅三码认证用户或四码解绑用户，可进行绑定银行卡操作；
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1. 个人用户通过网贷平台发起绑卡申请，网贷平台调用【绑卡】接口引导用户跳转至存管系统绑卡页面；
			2. 用户在存管页面填写银行卡信息并且输入银行卡预留手机号以及短信验证码，交易密码；
			3. 存管系统验证用户信息，绑卡成功后，四码认证通过
		规则描述: 
			仅三码认证用户或解绑用户，可进行绑定银行卡操作；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 bindCard
			'code'				=> 'bindCard',
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# 用户登录名
			'login_id'			=> $in['phone'],
			# N 开户行地区代码
			'city_cd'			=> null,
			# N 开户行行别
			'parent_bank_id'	=> null,
			# N 开户行支行名
			'bank_nm'			=> null,
			# N 银行卡号
			'card_no'			=> null,
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function UnBindCard($in)
	{
		/*
		功能介绍: 
			仅个人用户，四码开户认证通过，可对账户绑定的银行卡进行解绑；
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1. 个人用户通过网贷平台进行解绑申请，网贷平台调用【卡解绑】接口引导用户至存管系统卡解绑页面；
			2. 用户在存管页面填写短信验证码，交易密码；
			3. 存管系统验证用户填写的信息，验证通过后解绑成功
		规则描述: 
			1. 仅个人用户，四码开户认证通过后可进行卡解绑操作；
			2. 解绑后，不允许充值提现操作；
			3. 解绑后，不影响用户授权信息；
			4. 解绑后，从四码开户认证通过变更为解绑状态，解绑状态可再绑卡；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 unbindCard
			'code'				=> 'unbindCard',
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function mobileChange($in)
	{
		/*
		功能介绍: 
			更换银行卡预留手机号
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1、 用户在网贷平台申请变更银行卡预留手机号，网贷平台调用【更换手机号】接口引导用户至存管系统页面，进行手机号变更
			2、 用户在存管页面填写新老手机号的短信验证码，交易密码；
			3、 存管系统验证用户信息，返回结果；
		规则描述: 四码认证用户，接受验证码手机号需为银行预留手机号
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 mobileChange
			'code'				=> 'mobileChange',
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function passwordModify($in)
	{
		/*
		功能介绍: 用于修改或重置登录密码，重置交易密码
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1、 用户在网贷平台申请修改/重置密码，网贷平台调用【密码重置】接口跳转至存管系统页面；
			2、 用户在存管页面输入新密码以及短信验证码；
			3、 存管系统验证用户信息返回结果；
		规则描述: 
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 passwordModify
			'code'				=> 'passwordModify',
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# 用户登录名
			'login_id'			=> self::$Fuid[0],
			# 业务类型
			'busi_tp'			=> $in['b_tp'],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function smsNotifyGrant($in)
	{
		/*
		功能介绍: 
			用于配置短信是否接收
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1、 用户通过网贷平台选择授权类型，网贷平台将用户的授权请求发送给存管系统，引导用户至存管授权页面，进行授权；
			2、 用户勾选短信通知类型，填写短信验证码，交易密码
			3、 存管系统完成配置后，回调返回结果；
		规则描述: 
			1、 勾选为授权，取消勾选为取消授权；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 smsNotifyGrant
			'code'				=> 'smsNotifyGrant',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function userCancel($in)
	{
		/*
		功能介绍: 
			个人/法人用户注销
		接口模式: 网页
		异步通知: 是
		交互流程: 
			1、 用户在网贷平台申请注销，网贷平台调用【销户】接口引导用户至存管系统页面；
			2、 用户填写短信验证码，交易密码；
			3、 存管系统受理注销申请，返回受理结果；
			4、 网贷平台复核员审核；
			5、 异步通知销户结果；
		规则描述: 
			1、 如果账户有余额无法销户，必须先全额提现后才能注销；
			2、 注销后，原账户交易记录无法查询；
			3、 申请注销后，须网贷平台复核员复核；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 userCancel
			'code'				=> 'userCancel',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function quickRecharge($in)
	{
		/*
		功能介绍: 
			用于个人用户开户绑定的银行卡对网贷平台资金存管账户进行充值
		接口模式: 网页
		异步通知: 是
		交互流程: 
			1、 用户在网贷平台页面填写充值金额并确认，网贷平台调用【充值接口引导用户至存管系统页面；
			2、 用户确认金额并在该页面上输入交易密码以及短信验证码进行充值操作；
			3、 存管系统完成充值后，回调网贷平台返回结果（后台及页面回调）；
		规则描述: 
			1、 支持银行列表及限额详见附录文档；
			2、 page_notify_url 和 back_notify_url 是同步回调通知地址，通过请求该接口时传过来；
			3、 异步回调通知：（回调地址由商户提供），存管系统授权该回调地址，仅通知充值成功的充值交易；
			4、 异步通知结果，为交易最终结果，网贷平台响应成功，不再通知，否则按一定时间间隔重复回调通知，一共 6 次；
			5、 异步通知以流水号确认唯一一笔交易； 
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 quickRecharge
			'code'				=> 'quickRecharge',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 金额
			'amt'				=> $in['money'] * 100,
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function withdraw($in)
	{
		/*
		功能介绍: 
			用于用户将网贷平台资金存管账户可用余额，提现到绑定的银行账户
		接口模式: 网页
		异步通知: 是
		交互流程: 
			1、 用户在网贷平台提现页面，填写提现金额、并确认；
			2、 网贷平台请求该接口，跳转到存管系统提现页面；
			3、 用户输入交易密码，短信验证码并确认，存管系统受理提现请求
			4、 存管系统受理后，回调网贷平台返回结果（后台及页面回调）；
			5、 网贷平台根据受理结果，进行业务处理；
			6、 提现请求完成；
		规则描述: 
			1、 提现到绑定的银行账户；
			2、 提现金额须小于等于可用余额；
			3、 提现失败，退回对应资金存管账户；
			4、 page_notify_url 和 back_notify_url 是同步回调通知地址，通过请求该接口时传过来；
			5、 异步回调通知：（回调地址由商户提供），存管系统授权该回调地址，仅通知受理成功的提现交易；
			6、 异步通知结果，为交易最终结果，网贷平台响应成功，不再通知否则按一定时间间隔重复回调通知，一共 6 次；
			7、 异步通知以流水号确认唯一一笔交易；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 withdraw
			'code'				=> 'withdraw',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 用户登录名
			'login_id'			=> self::$Fuid[0],
			# Y 金额
			'amt'				=> $in['money'] * 100,
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function projectAdd($in)
	{
		/*
		功能介绍: 
			借款人项目报备给存管系统
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1.网贷平台调用接口，发起项目报备请求；
			2.存管系统接收请求，返回报备结果；
		规则描述: 
			筹标天数+项目天数要在项目起始日期范围内
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 projectAdd
			'code'				=> 'projectAdd',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 项目名称
			'project_nm'		=> $in['title'],
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# Y 项目类型 00 - 正常项目 01 - 消费金融项目
			'project_usage'		=> '00',
			# Y 金额
			'amt'				=> bcmul($in['money'],100),
			# Y 预计收益率
			'return_rate'		=> $in['rate'],
			# N 筹标期限 单位 天
			'raise_days'		=> null,
			# Y 项目起始日期 YMD
			'start_dt'			=> $in['start_day'],
			# Y 项目结束日期 YMD
			'end_dt'			=> $in['end_day'],
			# Y 项目天数
			'project_days'		=> $in['p_days'],
			# Y 还款方式 
			'repay_type'		=> $in['repay_type'],
			# Y 还款期数
			'num_periods'		=> $in['deadlines'],
			# Y 借款人
			'bor_login_id'		=> self::$Fuid[0],
			# Y 借款人名称
			'bor_nm'			=> $in['real_name'],
			# N 项目概述
			'project_memo'		=> null,
			# N 商家名称
			'business_nm'		=> null,
			# N 商家在系统的标志
			'business_login_id'	=> null,
			'attach1'			=> null,
			'attach2'			=> null,
		);
		$data['signature'] = $this->rsaSV($data);
		dump($data);
		return $this->cUrl('control.action',$data);
	}

	public function projectUpdate($in)
	{
		/*
		功能介绍: 
			更新项目当前状态
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1.网贷平台调用接口，发起项目更新请
			2.存管系统接收请求，返回更新结
		规则描述: 
			项目有以下状态：
			00 筹标中
			01 满标放款
			02 还款（需先确认都放款成功才可更新为“还款”，防止误操作，否
			则“还款”状态不可再做放款交易）
			03 完结 （正常项目完结）
			04 逾期
			05 流标（已放款本金=0 才能流标）
			06 逾期完结（逾期项目完结）
			状态更新规则：
			1、00→01/05
			2、01→02/05
			3、02→03（待还款本金=0）
			4、02→04（当前日期＞项目结束日期）
			5、04→06（待还款本金=0）
			03 05 06 不得更新项目状态
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 指定 projectUpdate
			'code'				=> 'projectUpdate',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# Y 项目更新状态
			'project_st'		=> $in['p_st'],
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function Grant($in)
	{
		/*
		功能介绍: 
			1.授权类型分为：出借人授权、借款人授权、代偿授权；
			2.提供用户授权操作，在用户授权的额度和有效期限内，可直接操作用户账户中资金，进行自动验密冻结；
		接口模式: 网页
		异步通知: 否
		交互流程: 
			1．开户成功后，可调用授权接口，跳转至授权页面，验证交易密码后进行授权操作；
			2．出借人在授权页面可授权：
				授权自动出借，自动出借授权期限，自动出借授权额度；
				授权自动缴费，缴费授权期限，缴费授权额度；
			3．借款人在授权页面可授权：
				授权自动还款，自动还款授权期限，自动还款授权额度；
				授权自动缴费，缴费授权期限，缴费授权额度；
			4．代偿人在授权页面可授权：
				授权自动代偿，自动代偿授权期限，自动代偿授权额度；
				授权自动缴费，缴费授权期限，缴费授权额度；
			5．授权成功；
		规则描述: 
			1. 授权成功后，在用户授权的额度和有效期限内，可直接操作用户账户中资金，进行直连冻结；
			2. 授权期限到期后，授权失效；
			3. 解绑银行卡不影响用户的授权信息；
			4. 销户后，授权信息清零；
			5. 出借授权：自动投资的回款会恢复出借额度；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 出借人 lenderGrant 借款人授权 borrowerGrant 代偿户授权 compenGrant
			'code'				=> 'projectUpdate',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# Y 用户登录名
			'login_id'			=> self::$Fuid[0],
			# N 授权状态
			'auth_st'			=> self::ast($in['utype']),
			# N 自动出借授权期限
			'auto_lend_term'	=> $in['utype'] == 1 ? '20301231' : null,
			# N 自动出借授权额度
			'auto_lend_amt'		=> $in['utype'] == 1 ? '10000000000': null,
			# N 自动还款授权期限
			'auto_repay_term'	=> $in['utype'] == 2 ? '20301231' : null,
			# N 自动还款授权额度
			'auto_repay_amt'	=> $in['utype'] == 2 ? '10000000000': null,
			# N 自动代偿授权期限
			'auto_compen_term'	=> $in['utype'] == 5 ? '20301231' : null,
			# N 自动代偿授权额度
			'auto_compen_amt'	=> $in['utype'] == 5 ? '10000000000': null,
			# N 缴费授权期限
			'auto_fee_term'		=> '20301231',
			# N 缴费授权额度
			'auto_fee_amt'		=> '10000000000',
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function passwordFreeze($in)
	{
		/*
		功能介绍: 
			1.若用户未进行自动授权，在投标、还款、债权转让、代偿、缴费需要用户在存管系统页面验证交易密码；
		接口模式: 网页
		异步通知: 是
		交互流程: 
			1.个人用户通过网贷平台，转至存管系统验密冻结页面；
			2.用户在存管系统页面填写交易密码；
			3.存管系统回调冻结结果、相关参数；
		规则描述: 
			投标：
				验证交易密码，使用预授权冻结，生成预授权合同号；
				出账人用户属性=1；
				入账人用户属性=2，且为项目借款人；
				项目状态=00 或 01；
			还款：
				验证交易密码，使用冻结交易；
				冻结的用户属性=2，且为项目借款人；
				项目状态=02 或 04；
			债权转让：
				验证交易密码，使用预授权冻结，生成预授权合同号；
				出、入账人用户属性=1；
				本金字段必填；
			直接代偿还款：
				验证交易密码，使用预授权冻结，生成预授权合同号；
				出账人用户属性为 5；
				入账人用户属性为 1，且为项目债权人；
				项目状态=02/04
			缴费：
				验证交易密码，使用预授权冻结，生成预授权合同号；
				出账人用户属性可以为 1、2、5；
				入账人用户属性为 4 或 9；
			间接代偿还款：
				验证交易密码，使用预授权冻结，生成预授权合同号；
				出账人用户属性=5；
				入账人用户属性=2，为项目借款人；
				项目状态=02/04；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 passwordFreeze
			'code'				=> 'passwordFreeze',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 冻结出账用户名
			'login_id'			=> self::$Fuid[0],
			# Y 交易金额
			'amt'				=> bcmul($in['money'],100),
			# N 本金 债转时必填
			'amt_pincipal'		=> args($in['benjin'])*100,
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# N 入账用户名 预授权冻结必填
			'login_id_in'		=> args(self::$Fuid[1]),
			# Y 业务类型 00 投标 01 还款 02 债转 03 直接代偿 04 缴费 05 间接代偿
			'busi_tp'			=> $in['b_tp'],
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
			# N 备注
			'remark'			=> args($in['remark']),
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function freeze($in)
	{
		/*
		功能介绍: 
			1.若用户已进行自动授权，在授权额度和有效期内，可使用自动冻结；
		接口模式: 直连
		异步通知: 是
		交互流程": 
			1.用户通过网贷平台，上送冻结信息至存管系统提出冻结申请
			2.存管系统回调冻结结果、相关参数；
		规则描述: 
			投标：
				使用预授权冻结，生成预授权合同号；
				出账人用户属性=1；
				入账人用户属性=2，且为项目借款人；
				项目状态=00 或 01；
			还款：
				使用冻结交易；
				冻结的用户属性=2，且为项目借款人项目状态=02 或 04；
			债权转让：
				使用预授权冻结，生成预授权合同号；
				出、入账人用户属性=1；
				本金字段必填；
			直接代偿还款：
				使用预授权冻结，生成预授权合同号出账人用户属性为 5；
				入账人用户属性为 1，且为项目债权人；
				项目状态=02/04；
			缴费：
				使用预授权冻结，生成预授权合同号；
				出账人用户属性可以为 1、2、5；
				入账人用户属性为 4 或 9；
			间接代偿还款：
				使用预授权冻结，生成预授权合同号；
				出账人用户属性=5；
				入账人用户属性=2，为项目借款人；
				项目状态=02/04；
			其他的业务类型：
				如营销，资金迁移，平台账户间交易，冻结用户不允许提现等操作，使用直接冻结，业务类型选择其他；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 freeze
			'code'				=> 'freeze',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 冻结出账用户名
			'login_id'			=> self::$Fuid[0],
			# Y 交易金额
			'amt'				=> bcmul($in['money'],100),
			# N 本金 债转时必填
			'amt_pincipal'		=> args($in['benjin'])*100,
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# N 入账用户名 预授权冻结必填
			'login_id_in'		=> args(self::$Fuid[1]),
			# Y 业务类型 00 投标 01 还款 02 债转 03 直接代偿 04 缴费 05 间接代偿 06 其他
			'busi_tp'			=> $in['b_tp'],
			# N 备注
			'remark'			=> args($in['remark']),
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);

	}

	public function passwordUnfreeze($in)
	{
		/*
		功能介绍: 
			解冻验密冻结接口冻结的金额；
		接口模式: 网页
		异步通知: 是
		交互流程: 
			1.个人用户通过网贷平台，转至存管系统验密解冻页面；
			2.用户在存管系统页面填写交易密码并确认；
			3.存管系统回调解冻结果、相关参数；
		规则描述: 
			1.网页的验密解冻只能解冻网页验密冻结接口冻结的金额，和直的冻结解冻不通用；
			2.在以下业务场景下可使用：投标撤销，还款撤销，债权转让撤销代偿撤销，缴费撤销；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 passwordUnfreeze
			'code'				=> 'passwordUnfreeze',
			# Y 网页类型 0 - PC  1 - APP
			'client_tp'			=> self::$client_tp,
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 交易用户名
			'login_id'			=> self::$Fuid[0],
			# Y 解冻金额
			'amt'				=> bcmul($in['money'],100),
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# N 预授权合同号 
			'contract_no'		=> null,
			# Y 业务类型 00 投标 01 还款 02 债转 03 代偿 04 缴费 
			'busi_tp'			=> $in['b_tp'],
			# N 原交易流水
			'origin_txn_ssn'	=> null,
			# N 原交易日期
			'origin_txn_date'	=> null,
			# Y 商户返回地址
			'page_notify_url'	=> $in['purl'],
			# Y 后台通知地址
			'back_notify_url'	=> $in['burl'],
			# N 备注
			'remark'			=> args($in['remark']),
		);
		$data['signature'] = $this->rsaSV($data);
		$this->Form('control.action',$data);
	}

	public function unfreeze($in)
	{
		/*
		功能介绍: 解冻所有的冻结
		接口模式: 直连
		异步通知: 是
		交互流程: 
			1.网贷平台上送用户解冻信息至存管系统提出解冻申请；
			2.存管系统回调解冻结果、相关参数；
		规则描述: 
			直连的解冻能解冻所有的冻结；
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 unfreeze
			'code'				=> 'unfreeze',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 交易用户名
			'login_id'			=> self::$Fuid[0],
			# Y 解冻金额
			'amt'				=> bcmul($in['money'],100),
			# Y 项目编号
			'project_no'		=> $in['bid'],
			# N 预授权合同号 
			'contract_no'		=> null,
			# N 原交易流水
			'origin_txn_ssn'	=> null,
			# N 原交易日期
			'origin_txn_date'	=> null,
			# Y 业务类型 00 投标 01 还款 02 债转 03 代偿 04 缴费 
			'busi_tp'			=> $in['b_tp'],
			# N 备注
			'remark'			=> args($in['remark']),
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function transferPay($in)
	{
		/*
		功能介绍: 账户之间资金转账
		接口模式: 直连
		异步通知: 是
		交互流程: 
			1.用户通过网贷平台，上送转账交易信息至存管系统提出申请
			2.存管系统回调转账交易结果，相关参数
		规则描述: 
			以下业务类型项目编号必填： 
				01–满标放款：走预授权完成交易，资金由冻结到可用
				02–还款：直接转账，资金由冻结到冻结
				03–债权转让：走预授权完成交易，资金由冻结到可用
				04–消费金融放款：资金由可用到可用
				05–直接代偿还款（至出借人）：走预授权完成交易，资金由冻结到可用
				06–间接代偿还款（至借款人）：走预授权完成交易，资金由冻结到可用
				07–收取代偿款：资金由冻结到冻结
			以下业务类型不关联项目、项目编号为空： 
				08–平台营销：直接转账，资金由冻结到冻结
				09–平台手续费：走预授权完成交易，资金由冻结到可用
				10–平台交易：直接转账，资金由冻结到冻结
				11–资金迁移：直接转账，资金由冻结到冻结
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 transferPay
			'code'				=> 'transferPay',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# N 项目编号
			'project_no'		=> args($in['bid']),
			# Y 出账用户名
			'login_id_out'		=> self::$Fuid[0],
			# Y 入账用户名
			'login_id_in'		=> self::$Fuid[1],
			# Y 交易金额
			'amt'				=> bcmul($in['money'],100),
			# N 本金 01 满标放款 02 还款 03 债权 05 直接代偿 时必填
			'amt_pincipal'		=> bcmul(args($in['benjin'],0,'f'),100),
			# N 还款利息 02 还款 05 直接代偿 时必填
			'interest'			=> bcmul(args($in['lixi'],0,'f'),100),
			# Y 业务类型
			'busi_tp'			=> $in['b_tp'],
			# N 原交易流水
			'origin_txn_ssn'	=> null,
			# N 原交易日期
			'origin_txn_date'	=> null,
			# N 预授权合同号 
			'contract_no'		=> null,
			# N 备注
			'remark'			=> args($in['remark']),
		);
		echo "<pre>";
		print_r($data);
		#dump($data);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}
	public function projectQuery($in)
	{
		/*
		功能介绍: 查询一个/多个项目的借款人信息，还款信息等
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1、 网贷平台请求查询项目信息；
			2、 存管系统返回项目信息；
		规则描述: 
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 creditQuery
			'code'				=> 'projectQuery',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 项目编号
			'project_no'		=> args($in['bid']),
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function creditQuery($in)
	{
		/*
		功能介绍: 查询一个/多个债权的的出借人信息以及已收还款信息
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1.网贷平台请求查询债权信息；
			2.存管系统返回债权信息；
		规则描述: 
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 creditQuery
			'code'				=> 'creditQuery',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 项目编号
			'project_no'		=> args($in['bid']),
			# Y 出借人用户名
			'login_id'			=> self::$Fuid[0],
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function txnQuery($in)
	{
		/*
		功能介绍: 根据某一商户流水号，查询存管系统对应的交易状态
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1、 网贷平台通过交易流水号，请求查询交易信息；
			2、 存管系统返回交易信息及状态；
		规则描述: 
		 */
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 txnQuery
			'code'				=> 'txnQuery',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# N 项目编号
			'project_no'		=> args($in['bid']),
			# Y 交易代码 PW13 预授权 PWCF 预授权撤销 PWDJ 冻结 PWJD 解冻 PW03 转账(预授权完成，冻结到可用) PW19 转账(冻结到冻结)
			'busi_cd'			=> $in['b_cd'],
			# Y 业务类型 00 –投标（PW13）01 –还款（PWDJ）02 – 债权转让（PW13）03 – 直接代偿还款（PW13）04 – 缴费（PW13）05 – 间接代偿还款（PW13）06 – 直接冻结（PWDJ）00–投标撤销（PWCF）01–还款撤销（PWJD）02 – 债权转让撤销（PWCF）03 – 代偿撤销（PWCF）04 – 缴费撤销（PWCF）05- 直接解冻（PWJD) 01–满标放款（PW03）02–还款（PW19）03–债权转让（PW03）04–消费金融放款（PW03）05–直接代偿还款（PW03）06–间接代偿还款（PW03）07–收取代偿款（PW03）08–平台营销（PW19）09–平台手续费（PW03）10–平台交易（PW19）11–资金迁移（PW19)
			'busi_tp'			=> $in['b_tp'],
			# Y 起始时间
			'start_day'			=> $in['start_day'],
			# Y 截止时间
			'end_day'			=> $in['end_day'],
			# N 交易流水
			'txn_ssn'			=> args($in['txn_ssn']),
			# N 交易用户
			'login_id'			=> self::$Fuid[0],
			# N 交易状态 1 成功 2 失败
			'txn_st'			=> args($in['txn_st']),
			# N 备注
			'remark'			=> args($in['remark']),
			# N 页码
			'page_no'			=> args($in['page']),
			# N 每页条数 [10,100]
			'page_size'			=> args($in['page_size']),
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function userQuery()
	{
		/*
		功能介绍: 查询用户信息
		接口模式: 直连
		异步通知: 否
		交互流程: 
			1、 网贷平台请求查询用户信息；
			2、 存管系统返回用户信息；
		规则描述: 
			1、 每次调用最多可以查询 10 个用户；
		 */
		
		$data = array(
			# Y 接口版本
			'ver'				=> '1.00', 
			# Y 接口编号 userQuery
			'code'				=> 'userQuery',
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			# Y 待查询的存管用户名 最多10个
			'login_id'			=> join('|',self::$Fuid),
		);
		$data['signature'] = $this->rsaSV($data);
		return $this->cUrl('control.action',$data);
	}

	public function queryUserMoney()
	{
		$data = array(
			# Y 商户号
			'mchnt_cd'			=> self::$mchnt_cd,
			# Y 交易流水
			'mchnt_txn_ssn'		=> self::GetOrderId(__function__),
			'mchnt_txn_dt'		=> '20180302',
			# Y 待查询的存管用户名 最多10个
			'cust_no'			=> join('|',self::$Fuid),
		);
		$data['signature'] = $this->rsaSV($data);

		return $this->cUrl('BalanceAction.action',$data);
	}

	public function webCharge($in)
	{ 
		$data	=	array(
			'mchnt_cd'		=>	self::$mchnt_cd,
			'mchnt_txn_ssn'	=>	self::GetOrderId(__function__),
			'login_id'		=>	self::$Fuid[0],
			'amt'			=>	$in['amt'],
			'page_notify_url'=>	$in['purl'],
			'back_notify_url'=>	$in['burl'],
			);
		$data['signature']	= $this->rsaSV($data);
		return $this->Form('500002.action',$data);
	}

	public function RsaSV($arr,$sign = null)
	{ # Sign or Verify
		ksort($arr);
		$str = join('|',
			array_values(
				array_filter($arr,
				function ($k){
					return gettype($k) == 'string' ?  $k !== '' : gettype($k) == 'integer' ? true : false;
				})
			)
		);
		return is_null($sign)?self::rsaSign($str):self::rsaVerify($str,$sign);
	}

}