<?php

class Sopresto_MailChimp {
	const DEFAULT_VERSION = '2.0';
	var $errorMessage = '';
	var $errorCode = 0;

	var $apiUrl;
	var $version;
	var $public;
	var $secret;
	var $timeout = 300;
	var $chunkSize = 8192;
	var $secure = false;

	function __construct($public, $secret, $version = self::DEFAULT_VERSION) {
		$this->public = $public;
		$this->secret = $secret;
		$this->apiUrl = parse_url('https://sopresto.socialize-this.com/mailchimp/');
		$this->setVersion($version);
	}

	function getApiUrl($uri = '') {
		return $this->apiUrl['scheme'].'://'.$this->apiUrl['host'].$this->apiUrl['path'] . '/' . $uri;
	}

	function setVersion($version = self::DEFAULT_VERSION) {
		if ( !in_array($version, array('1.3', '2.0') ) ) $version = self::DEFAULT_VERSION;
		$this->version 	= $version;
	}

	function __call($method, $params) {
		if ( $this->version == '2.0' ) {
			list($module, $method) = explode('_', $method, 2);
			$method = str_replace('_','-',$method);

			$method = "$module/$method";
		}

		$this->errorMessage = '';
		$this->errorCode = 0;

		$public = $this->public;
		$secret = $this->secret;

		//some distribs change this to &amp; by default
		$sep_changed = false;
		if (ini_get("arg_separator.output") != "&"){
			$sep_changed = true;
			$orig_sep = ini_get("arg_separator.output");
			ini_set("arg_separator.output", "&");
		}

		//mutate params
		$mutate = array();
		foreach($params as $k=>$v){
			$mutate[$this->function_map[$this->version][$method][$k]] = $v;
		}
		if ( $this->version != self::DEFAULT_VERSION) $method = $this->version . "/$method";

		$post_vars = array();
		$post_vars['api'] 			= $method;
		$post_vars['public_key'] 	= $public;
		$post_vars['hash'] 			= sha1($public.$secret);
		$post_vars['params']		= $mutate;
		//return $post_vars;
		$post_vars 					= http_build_query($post_vars);

		if ($sep_changed) ini_set("arg_separator.output", $orig_sep);


		$payload = "POST " . $this->apiUrl["path"] . " HTTP/1.0\r\n";
		$payload .= "Host: " . $this->apiUrl["host"] . "\r\n";
		$payload .= "User-Agent: Sopresto_MailChimp/1.0\r\n";
		$payload .= "Content-type: application/x-www-form-urlencoded\r\n";
		$payload .= "Content-length: " . strlen($post_vars) . "\r\n";
		$payload .= "Connection: close \r\n\r\n";
		$payload .= $post_vars;

		ob_start();
		if ($this->secure){
			$sock = fsockopen("ssl://".$host, 443, $errno, $errstr, 30);
		} else {
			$sock = fsockopen($this->apiUrl["host"], 80, $errno, $errstr, 30);
		}
		if(!$sock) {
			$this->errorMessage = "Could not connect (ERR $errno: $errstr)";
			$this->errorCode = "-99";
			ob_end_clean();
			return false;
		}

		$response = "";
		fwrite($sock, $payload);
		stream_set_timeout($sock, $this->timeout);
		$info = stream_get_meta_data($sock);
		while ((!feof($sock)) && (!$info["timed_out"])) {
			$response .= fread($sock, $this->chunkSize);
			$info = stream_get_meta_data($sock);
		}
		fclose($sock);
		ob_end_clean();

		if ($info["timed_out"]) {
			$this->errorMessage = "Could not read response (timed out)";
			$this->errorCode = -98;
			return false;
		}

		list($headers, $response) = explode("\r\n\r\n", $response, 2);
		$headers = explode("\r\n", $headers);

		if(ini_get("magic_quotes_runtime")) $response = stripslashes($response);

		$serial = json_decode($response,true);
		if($response && $serial === false) {
			$response = array("error" => "Bad Response.  Got This: " . $response, "code" => "-99");
		} else {
			$response = $serial;
		}

		if ( $this->version == '2.0' ) {
			if ( is_array($response) && isset($response['response']['errors'][0]['code']) && $response['response']['errors'][0]['code'] ) {
				$this->errorMessage = $response['response']['errors'][0]['error'];
				$this->errorCode = $response['response']['errors'][0]['code'];
				return false;
			}
		} else {
			if (is_array($response) && isset($response["result"]) && $response["result"] == 'error') {
				list($code, $message) = explode('||',$response['response'],2);
				$this->errorMessage = $message;
				$this->errorCode = $code;
				return false;
			}
		}
		return $response['response'];
	}

	protected $function_map = array(
		'2.0' => array(
			'campaigns/content'=>array('cid', 'options'),
			'campaigns/create'=>array('type', 'options', 'content', 'segment_opts', 'type_opts'),
			'campaigns/delete'=>array('cid'),
			'campaigns/list'=>array('filters', 'start', 'limit', 'sort_field', 'sort_dir'),
			'campaigns/pause'=>array('cid'),
			'campaigns/ready'=>array('cid'),
			'campaigns/replicate'=>array('cid'),
			'campaigns/resume'=>array('cid'),
			'campaigns/schedule-batch'=>array('cid', 'schedule_time', 'num_batches', 'stagger_mins'),
			'campaigns/schedule'=>array('cid', 'schedule_time', 'schedule_time_b'),
			'campaigns/segment-test'=>array('list_id', 'options'),
			'campaigns/send'=>array('cid'),
			'campaigns/send-test'=>array('cid', 'test_emails', 'send_type'),
			'campaigns/template-content'=>array('cid'),
			'campaigns/unschedule'=>array('cid'),
			'campaigns/update'=>array('cid', 'name', 'value'),
			'ecomm/order-add'=>array('order'),
			'ecomm/order-del'=>array('store_id', 'order_id'),
			'ecomm/orders'=>array('cid', 'start', 'limit', 'since'),
			'folders/add'=>array('name', 'type'),
			'folders/del'=>array('fid', 'type'),
			'folders/list'=>array('type'),
			'folders/update'=>array('fid', 'name', 'type'),
			'gallery/list'=>array('opts'),
			'lists/abuse-reports'=>array('id', 'start', 'limit', 'since'),
			'lists/activity'=>array('id'),
			'lists/batch-subscribe'=>array('id', 'batch', 'double_optin', 'update_existing', 'replace_interests'),
			'lists/batch-unsubscribe'=>array('id', 'batch', 'delete_member', 'send_goodbye', 'send_notify'),
			'lists/clients'=>array('id'),
			'lists/growth-history'=>array('id'),
			'lists/interest-group-add'=>array('id', 'group_name', 'grouping_id'),
			'lists/interest-group-del'=>array('id', 'group_name', 'grouping_id'),
			'lists/interest-group-update'=>array('id', 'old_name', 'new_name', 'grouping_id'),
			'lists/interest-grouping-add'=>array('id', 'name', 'type', 'groups'),
			'lists/interest-grouping-del'=>array('grouping_id'),
			'lists/interest-grouping-update'=>array('grouping_id', 'name', 'value'),
			'lists/interest-groupings'=>array('id', 'counts'),
			'lists/list'=>array('filters', 'start', 'limit', 'sort_field', 'sort_dir'),
			'lists/locations'=>array('id'),
			'lists/member-activity'=>array('id', 'emails'),
			'lists/member-info'=>array('id', 'emails'),
			'lists/members'=>array('id', 'status', 'opts'),
			'lists/merge-var-add'=>array('id', 'tag', 'name', 'options'),
			'lists/merge-var-del'=>array('id', 'tag'),
			'lists/merge-var-reset'=>array('id', 'tag'),
			'lists/merge-var-set'=>array('id', 'tag', 'value'),
			'lists/merge-var-update'=>array('id', 'tag', 'options'),
			'lists/merge-vars'=>array('id'),
			'lists/static-segment-add'=>array('id', 'name'),
			'lists/static-segment-del'=>array('id', 'seg_id'),
			'lists/static-segment-members-add'=>array('id', 'seg_id', 'batch'),
			'lists/static-segment-members-del'=>array('id', 'seg_id', 'batch'),
			'lists/static-segment-reset'=>array('id', 'seg_id'),
			'lists/static-segments'=>array('id'),
			'lists/subscribe'=>array('id', 'email', 'merge_vars', 'email_type', 'double_optin', 'update_existing', 'replace_interests', 'send_welcome'),
			'lists/unsubscribe'=>array('id', 'email', 'delete_member', 'send_goodbye', 'send_notify'),
			'lists/update-member'=>array('id', 'email', 'merge_vars', 'email_type', 'replace_interests'),
			'lists/webhook-add'=>array('id', 'url', 'actions', 'sources'),
			'lists/webhook-del'=>array('id', 'url'),
			'lists/webhooks'=>array('id'),
			'helper/account-details'=>array('exclude'),
			'helper/campaigns-for-email'=>array('email', 'options'),
			'helper/chimp-chatter'=>array(),
			'helper/generate-text'=>array('type', 'content'),
			'helper/inline-css'=>array('html', 'strip_css'),
			'helper/lists-for-email'=>array('email'),
			'helper/ping'=>array(),
			'helper/search-campaigns'=>array('query', 'offset', 'snip_start', 'snip_end'),
			'helper/search-members'=>array('query', 'id', 'offset'),
			'helper/verified-domains'=>array(),
			'reports/abuse'=>array('cid', 'opts'),
			'reports/advice'=>array('cid'),
			'reports/bounce-message'=>array('cid', 'email'),
			'reports/bounce-messages'=>array('cid', 'opts'),
			'reports/click-detail'=>array('cid', 'tid', 'opts'),
			'reports/clicks'=>array('cid'),
			'reports/domain-performance'=>array('cid'),
			'reports/ecomm-orders'=>array('cid', 'opts'),
			'reports/eepurl'=>array('cid'),
			'reports/geo-opens'=>array('cid'),
			'reports/google-analytics'=>array('cid'),
			'reports/member-activity'=>array('cid', 'emails'),
			'reports/not-opened'=>array('cid', 'opts'),
			'reports/opened'=>array('cid', 'opts'),
			'reports/sent-to'=>array('cid', 'opts'),
			'reports/share'=>array('cid', 'opts'),
			'reports/summary'=>array('cid'),
			'reports/unsubscribes'=>array('cid', 'opts'),
			'templates/add'=>array('name', 'html', 'folder_id'),
			'templates/del'=>array('template_id'),
			'templates/info'=>array('template_id', 'type'),
			'templates/list'=>array('types', 'filters'),
			'templates/undel'=>array('template_id'),
			'templates/update'=>array('template_id', 'values'),
			'users/invite'=>array('email', 'role', 'msg'),
			'users/invite-resend'=>array('email'),
			'users/invite-revoke'=>array('email'),
			'users/invites'=>array(),
			'users/login-revoke'=>array('username'),
			'users/logins'=>array(),
			'vip/activity'=>array(),
			'vip/add'=>array('id', 'emails'),
			'vip/del'=>array('id', 'emails'),
			'vip/members'=>array()
		),
    	'1.3' => array(
			'campaignUnschedule'=>array("cid"),
			'campaignSchedule'=>array("cid","schedule_time","schedule_time_b"),
			'campaignScheduleBatch'=>array("cid","schedule_time","num_batches","stagger_mins"),
			'campaignResume'=>array("cid"),
			'campaignPause'=>array("cid"),
			'campaignSendNow'=>array("cid"),
			'campaignSendTest'=>array("cid","test_emails","send_type"),
			'campaignSegmentTest'=>array("list_id","options"),
			'campaignCreate'=>array("type","options","content","segment_opts","type_opts"),
			'campaignUpdate'=>array("cid","name","value"),
			'campaignReplicate'=>array("cid"),
			'campaignDelete'=>array("cid"),
			'campaigns'=>array("filters","start","limit","sort_field","sort_dir"),
			'campaignStats'=>array("cid"),
			'campaignClickStats'=>array("cid"),
			'campaignEmailDomainPerformance'=>array("cid"),
			'campaignMembers'=>array("cid","status","start","limit"),
			'campaignHardBounces'=>array("cid","start","limit"),
			'campaignSoftBounces'=>array("cid","start","limit"),
			'campaignUnsubscribes'=>array("cid","start","limit"),
			'campaignAbuseReports'=>array("cid","since","start","limit"),
			'campaignAdvice'=>array("cid"),
			'campaignAnalytics'=>array("cid"),
			'campaignGeoOpens'=>array("cid"),
			'campaignGeoOpensForCountry'=>array("cid","code"),
			'campaignEepUrlStats'=>array("cid"),
			'campaignBounceMessage'=>array("cid","email"),
			'campaignBounceMessages'=>array("cid","start","limit","since"),
			'campaignEcommOrders'=>array("cid","start","limit","since"),
			'campaignShareReport'=>array("cid","opts"),
			'campaignContent'=>array("cid","for_archive"),
			'campaignTemplateContent'=>array("cid"),
			'campaignOpenedAIM'=>array("cid","start","limit"),
			'campaignNotOpenedAIM'=>array("cid","start","limit"),
			'campaignClickDetailAIM'=>array("cid","url","start","limit"),
			'campaignEmailStatsAIM'=>array("cid","email_address"),
			'campaignEmailStatsAIMAll'=>array("cid","start","limit"),
			'campaignEcommOrderAdd'=>array("order"),
			'lists'=>array("filters","start","limit","sort_field","sort_dir"),
			'listMergeVars'=>array("id"),
			'listMergeVarAdd'=>array("id","tag","name","options"),
			'listMergeVarUpdate'=>array("id","tag","options"),
			'listMergeVarDel'=>array("id","tag"),
			'listMergeVarReset'=>array("id","tag"),
			'listInterestGroupings'=>array("id"),
			'listInterestGroupAdd'=>array("id","group_name","grouping_id"),
			'listInterestGroupDel'=>array("id","group_name","grouping_id"),
			'listInterestGroupUpdate'=>array("id","old_name","new_name","grouping_id"),
			'listInterestGroupingAdd'=>array("id","name","type","groups"),
			'listInterestGroupingUpdate'=>array("grouping_id","name","value"),
			'listInterestGroupingDel'=>array("grouping_id"),
			'listWebhooks'=>array("id"),
			'listWebhookAdd'=>array("id","url","actions","sources"),
			'listWebhookDel'=>array("id","url"),
			'listStaticSegments'=>array("id"),
			'listStaticSegmentAdd'=>array("id","name"),
			'listStaticSegmentReset'=>array("id","seg_id"),
			'listStaticSegmentDel'=>array("id","seg_id"),
			'listStaticSegmentMembersAdd'=>array("id","seg_id","batch"),
			'listStaticSegmentMembersDel'=>array("id","seg_id","batch"),
			'listSubscribe'=>array("id","email_address","merge_vars","email_type","double_optin","update_existing","replace_interests","send_welcome"),
			'listUnsubscribe'=>array("id","email_address","delete_member","send_goodbye","send_notify"),
			'listUpdateMember'=>array("id","email_address","merge_vars","email_type","replace_interests"),
			'listBatchSubscribe'=>array("id","batch","double_optin","update_existing","replace_interests"),
			'listBatchUnsubscribe'=>array("id","emails","delete_member","send_goodbye","send_notify"),
			'listMembers'=>array("id","status","since","start","limit","sort_dir"),
			'listMemberInfo'=>array("id","email_address"),
			'listMemberActivity'=>array("id","email_address"),
			'listAbuseReports'=>array("id","start","limit","since"),
			'listGrowthHistory'=>array("id"),
			'listActivity'=>array("id"),
			'listLocations'=>array("id"),
			'listClients'=>array("id"),
			'templates'=>array("types","category","inactives"),
			'templateInfo'=>array("tid","type"),
			'templateAdd'=>array("name","html"),
			'templateUpdate'=>array("id","values"),
			'templateDel'=>array("id"),
			'templateUndel'=>array("id"),
			'getAccountDetails'=>array("exclude"),
			'getVerifiedDomains'=>array(),
			'generateText'=>array("type","content"),
			'inlineCss'=>array("html","strip_css"),
			'folders'=>array("type"),
			'folderAdd'=>array("name","type"),
			'folderUpdate'=>array("fid","name","type"),
			'folderDel'=>array("fid","type"),
			'ecommOrders'=>array("start","limit","since"),
			'ecommOrderAdd'=>array("order"),
			'ecommOrderDel'=>array("store_id","order_id"),
			'listsForEmail'=>array("email_address"),
			'campaignsForEmail'=>array("email_address","options"),
			'chimpChatter'=>array(),
			'searchMembers'=>array("query","id","offset"),
			'searchCampaigns'=>array("query","offset","snip_start","snip_end"),
			'apikeys'=>array("username","password","expired"),
			'apikeyAdd'=>array("username","password"),
			'apikeyExpire'=>array("username","password"),
			'ping'=>array(),
			'deviceRegister'=>array("mobile_key","details"),
			'deviceUnregister'=>array("mobile_key","device_id"),
			'gmonkeyAdd'=>array("id","email_address"),
			'gmonkeyDel'=>array("id","email_address"),
			'gmonkeyMembers'=>array(),
			'gmonkeyActivity'=>array()
		)
	);
}

