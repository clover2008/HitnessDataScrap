<?php 
/**
 * 健身会员数据抓包出处理
 * @auth yxp
 * @date 2017/09/05
 * @version 1.5
 * @access public
 * */

class scrap {
	const DB_HOST = 'localhost';		//主机地址
	const DB_NAME = 'jianshen_scrap';	//数据库名
	const DB_USER = 'root';				//用户名
	const DB_PWD  = 'root';				//密码
	const DB_PORT = '3306';				//端口
	const AUTHORIZATION = '5f464d44-515b-4826-ae31-99020981d2a1';//授权验证码
	
	public $member_list_url;			//用户列表抓取地址
	public $basic_data_url;				//会员基本信息抓取地址
	public $contracts_data_url;			//合同信息抓取地址
	public $products_data_url;			//产品信息抓取地址
	public $transfer_data_url;			//客户介绍抓取地址
	public $contracts_info_url;			//合同详细信息列表地址
	public $user_headimg_prefixurl;     //用户头像地址前缀
	public $user_imgs_dir;				//头像存储目录
	
	public static  $_pdoinstance;		//pdo单例
	
	public function __construct() {
		header('Content-type: text/html;charset=utf-8');
		error_reporting(0);
		ini_set('max_execution_time', '0');
		ini_set('memory_limit', '128M');	
		
		$this->member_list_url = 'https://nextfitness.cn/nfitness/vip/members?start=0&limit=1000000&name=&is_open=-1&start_date=&end_date=&mobile=&card_type=-1&card_name=-1&Incumbency=-1&mc_id=-1&card_no=&status=-1&_=1506315296109';
		$this->basic_data_url = 'https://nextfitness.cn/nfitness/vip/member?_=1504580176878';
		$this->contracts_data_url = 'https://nextfitness.cn/nfitness/vip/contracts?_=1504580176879';
		$this->products_data_url = 'https://nextfitness.cn/nfitness/vip/products?_=1504580176880';
		$this->transfer_data_url = 'https://nextfitness.cn/nfitness/vip/transfers?_=1504580176881';
		$this->contracts_info_url = 'https://nextfitness.cn/nfitness/report/contracts?start=0&limit=1000000&name=&contract_type=-1&is_incumbency=-1&sale_user_id=-1&mobile=&status=4497976b-f314-455c-b2a6-21607de58d09&card_no=&aeceipt_starttime=&aeceipt_endtime=&_=1506059824097';
		$this->user_headimg_prefixurl = 'https://nextfitness.cn:8085/mheadimg/';
		$this->user_imgs_dir = 'userimgs';
	}
	
	/**
	 * 入口
	 * */
	public static function _run() {
		(new self)->logic();
	}
	
	/**
	 * 逻辑部分
	 * @access public
	 * @return void
	 * */
	public function logic() {
		try {
			$tables = self::_getMysqlDpoInstance()->query("show tables")->fetchAll();
			foreach ($tables as $table) {
				self::_getMysqlDpoInstance()->exec("TRUNCATE TABLE `".current($table)."`");
			} 
			
			$this->reactone();
			$this->reacttwo();
			$this->reactthree();
			
			die ('爬取成功！');
		} catch (Exception $e) {
			die ($e->getMessage());
		}
	}
	
	/**
	 * 爬取会员及合同
	 * */
	public function reactone() {
		$user_lists = json_decode(self::_toDoCurlScrap($this->member_list_url, true));
		
		foreach ($user_lists->datas->default->values->rows as $val) {
			//scrap_users
			$sql_users = "insert into `scrap_users`(
						`open_date`,`quantity`,`is_open`,`sex`,`over_date`,
						`mobile`,`joinDate`,`card_no`,`mc`,`name`,`statusName`,
						`mcName`,`id`,`pro_name`,`status`
					) values(
						'".$val->open_date."','".$val->quantity."','".$val->is_open."',
						'".$val->sex."','".$val->over_date."','".$val->mobile."',
						'".$val->joinDate."','".$val->card_no."','".$val->mc."',
						'".$val->name."','".$val->statusName."','".$val->mcName."',
						'".$val->id."','".$val->pro_name."','".$val->status."'
					)";
				
			//scrap_users_detail
			$details = current(json_decode(self::_toDoCurlScrap(
					$this->basic_data_url . '&member_id=' . $val->id))->datas->default->values->memberData
			);
			$sql_users_detail = "insert into `scrap_users_detail`(
						`emcontact_name`,`birthdate`,`mobile`,`id_card`,`photo`,
						`medical_history`,`joinDate`,`sexName`,`name`,`emcontact_mobile`,
						`mcName`,`id`,`email`,`remarks`
					) values(
						'".$details->emcontact_name."','".$details->birthdate."',
						'".$details->mobile."','".$details->id_card."','".$details->photo."',
						'".$details->medical_history."','".$details->joinDate."',
						'".$details->sexName."','".$details->name."','".$details->emcontact_mobile."',
						'".$details->mcName."','".$details->id."','".$details->email."',
						'".$details->remarks."'
					)";
				
			//scrap_user_contracts
			$contracts = current(json_decode(self::_toDoCurlScrap(
					$this->contracts_data_url . '&member_id=' . $val->id))->datas->default->values->memberContract
			);
			$sql_contracts = "insert into `scrap_user_contracts`(
						`contractStatusName`,`pro_content`,`other_sale_user`,
						`contract_code`,`contract_content`,`paid`,`sale_user_name`,
						`should_amount`,`contractTypeName`,`create_date`,`pro_name`,
						`status`,`id`
					) values(
						'".$contracts->contractStatusName."','".$contracts->pro_content."',
						'".$contracts->other_sale_user."','".$contracts->contract_code."',
						'".$contracts->contract_content."','".$contracts->paid."',
						'".$contracts->sale_user_name."','".$contracts->should_amount."',
						'".$contracts->contractTypeName."','".$contracts->create_date."',
						'".$contracts->pro_name."','".$contracts->status."','".$val->id."'
					)";
				
			//scarp_user_products
			$products = current(json_decode(self::_toDoCurlScrap(
					$this->products_data_url . '&member_id=' . $val->id))->datas->default->values->memberProduct
			);
			$sql_products = "insert into `scrap_user_products`(
						`overDate`,`ststusName`,`proTypeName`,`statsDate`,
						`information`,`cardNo`,`pro_name`,`id`
					) values(
						'".$products->overDate."','".$products->ststusName."',
						'".$products->proTypeName."','".$products->statsDate."',
						'".$products->information."','".$products->cardNo."',
						'".$products->pro_name."','".$val->id."'
					)";
				
			self::_getMysqlDpoInstance()->exec($sql_users);
			self::_getMysqlDpoInstance()->exec($sql_users_detail);
			self::_getMysqlDpoInstance()->exec($sql_contracts);
			self::_getMysqlDpoInstance()->exec($sql_products);
		}
	}
	
	/**
	 * 爬取合同信息
	 * */
	public function reacttwo() {
		$contracts = json_decode(self::_toDoCurlScrap($this->contracts_info_url));
		foreach ($contracts->datas->default->values->rows as $val) {
			$sql_contractsinfo = "insert into `scrap_contract_info`(
					`emcontact_name`,`birthdate`,`contract_id`,`discount`,`omember_id`,
					`package_id`,`medical_history`,`cardNo`,`contract_code`,`price`,`sexName`,
					`id`,`create_date`,`del_flag`,`sale_user_id`,`card_id`,`brand_id`,
					`contract_type`,`memberMobile`,`status`,`pro_name`,`id_card`,`birthdates`,
					`memberName`,`cashier_name`,`aeceipt_time`,`other_sale_user_id`,`operate_by`,
					`create_by`,`pro_content`,`other_sale_user`,`storage_id`,`business_type`,
					`emcontact_mobile`,`statusName`,`pay_type`,`should_amount`,`email`,
					`member_id`,`store_id`,`pay_status_name`,`cashier_id`,`amount`,`address`,
					`quantity`,`isEnter`,`contract_content`,`sex`,`mobile`,`sale_user_name`,
					`pro_type`,`member_name`,`pro_id`,`paid`,`contractTypeName`,`customer_id`,
					`remarks`,`create_name`
				) values(
					'".$val->emcontact_name."','".$val->birthdate."','".$val->contract_id."',
					'".$val->discount."','".$val->omember_id."','".$val->package_id."',
					'".$val->medical_history."','".$val->cardNo."','".$val->contract_code."',
					'".$val->price."','".$val->sexName."','".$val->id."','".$val->create_date."',
					'".$val->del_flag."','".$val->sale_user_id."','".$val->card_id."','".$val->brand_id."',
					'".$val->contract_type."','".$val->memberMobile."','".$val->status."','".$val->pro_name."',
					'".$val->id_card."','".$val->birthdates."','".$val->memberName."','".$val->cashier_name."',
					'".$val->aeceipt_time."','".$val->other_sale_user_id."','".$val->operate_by."','".$val->create_by."',
					'".$val->pro_content."','".$val->other_sale_user."','".$val->storage_id."','".$val->business_type."',
					'".$val->emcontact_mobile."','".$val->statusName."','".$val->pay_type."','".$val->should_amount."',
					'".$val->email."','".$val->member_id."','".$val->store_id."','".$val->pay_status_name."',
					'".$val->cashier_id."','".$val->amount."','".$val->address."','".$val->quantity."',
					'".$val->isEnter."','".$val->contract_content."','".$val->sex."','".$val->mobile."',
					'".$val->sale_user_name."','".$val->pro_type."','".$val->member_name."','".$val->pro_id."',
					'".$val->paid."','".$val->contractTypeName."','".$val->customer_id."','".$val->remarks."',
					'".$val->create_name."'
				)";
				
			self::_getMysqlDpoInstance()->exec($sql_contractsinfo);
		}
	}
	
	/**
	 * 爬取会员头像
	 * */
	public function reactthree() {
		$ids_sql = "select `id` from `scrap_users_detail`";
		$ids = self::_getMysqlDpoInstance()->query($ids_sql)->fetchAll();
		
		if (!is_dir($this->user_imgs_dir)) {
			mkdir($this->user_imgs_dir);
		}
		
		foreach ($ids as $val) {
			$img_name = $val['id'] . 'mheadimg.png';
			$img_basedir = $this->user_imgs_dir . '/' . $img_name;
			$tmp_url = $this->user_headimg_prefixurl . $img_name; 
			
			if (!file_exists($img_basedir)) {
				$img_stream = self::_toDoCurlScrap($tmp_url);
				$fp = @fopen($img_basedir, 'w');
				@fwrite($fp, $img_stream);
				@fclose($fp);
			}
		}
	}
	
	/**
	 * curl整合
	 * */
	public static function _toDoCurlScrap($url) {
		$url = preg_replace('/(?:^[\'"]+|[\'"\/]+$)/', '', $url);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3600);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . self::AUTHORIZATION]);
		
		ob_start();
		curl_exec($ch);
		$contents = ob_get_contents();
		ob_end_clean();
		
		if (false === $contents) {
			$contents = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		} 
		
		curl_close($ch);
		return $contents;
	}
	
	/**
	 * 本地mysql连接并获取实例
	 * */
	public static  function _getMysqlDpoInstance() {
		if (self::$_pdoinstance instanceof PDO) {
			return self::$_pdoinstance;
		} else {
			try {
				$pdo = new PDO(
						'mysql:host=' . self::DB_HOST . ';port=' . self::DB_PORT .';dbname=' . self::DB_NAME,
						self::DB_USER,
						self::DB_PWD,
						[
							PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
							PDO::ATTR_CASE => PDO::CASE_LOWER,
							PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
							PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8"
						]
				);
					
				return  $pdo;
			} catch (PDOException $e) {
				die($e->getMessage());
			}	
		}
	}
}

scrap::_run();



?>