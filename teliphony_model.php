<?php

class Teliphony_model extends CI_Model {

		function __construct() {
			parent::__construct();
		}
       
		public function getRestaurant( $pincode ) {

			$this->db->select('a.name,a.restid,a.area')
					 ->from(TABLES::$SEARCH_MAP.' AS a')
					 ->join(TABLES::$REST_TABLE.' AS b','a.restid=b.id','inner')
					 ->where('b.pincode',$pincode)
					 ->where('a.delivery',1)
					 ->order_by('a.total_orders','DESC')
					 ->limit(10);
			$compiled_query = $this->db->_compile_select();
			
			if($data = $this->rcache->get(md5( $compiled_query),$restid)) {
				$this->db->_reset_select();
				$result = $data;
			}else {
				$query = $this->db->get();
				$result = $query->result_array();
				$this->rcache->set(md5($compiled_query), $result,$restid);
			}
			return $result;
		}
		
		public function getRestaurantList( $areaid ) {
		
			$this->db->select('a.name,a.restid,a.area,a.areaid,a.model_type,a.cityid,a.opentime as motime,a.closetime as mctime,a.eopentime as eotime,a.eclosetime as ectime,b.zone_id')
					 ->from(TABLES::$SEARCH_MAP.' AS a')
					 ->join(TABLES::$REST_TABLE.' AS b','a.restid=b.id','inner')
					 ->join(TABLES::$ZONE_DEL_AREA.' AS c ','b.id = c.restid','inner')
					 ->where('c.areaid',$areaid)
					 ->where('b.areaid',$areaid)
					 ->where('a.delivery',1)
					 ->order_by('a.total_orders','DESC')
					 ->limit(10);
			echo $compiled_query = $this->db->_compile_select();
			$query = $this->db->get();
			$result1 = $query->result_array();
			
			$this->db->select('a.name,a.restid,a.area,a.areaid,a.model_type,a.cityid,a.opentime as motime,a.closetime as mctime,a.eopentime as eotime,a.eclosetime as ectime,b.zone_id')
					 ->from(TABLES::$SEARCH_MAP.' AS a')
					 ->join(TABLES::$REST_TABLE.' AS b','a.restid=b.id','inner')
					 ->join(TABLES::$ZONE_DEL_AREA.' AS c ','b.id = c.restid','inner')
					 ->where('c.areaid',$areaid)
					 ->where('b.areaid !=',$areaid,FALSE)
					 ->where('a.delivery',1)
					 ->order_by('a.total_orders','DESC')
					 ->limit(10);
			$compiled_query = $this->db->_compile_select();
			
			$query = $this->db->get();
			$result2 = $query->result_array();
			$result = array_merge($result1,$result2);
			return $result;
		}
		
		public function getSelectedRestaurantById($restid){
			$this->db->select('a.name,a.restid,a.area,a.areaid,a.model_type,a.cityid,a.opentime as motime,a.closetime as mctime,a.eopentime as eotime,a.eclosetime as ectime,b.zone_id')
					 ->from(TABLES::$SEARCH_MAP.' AS a')
					 ->join(TABLES::$REST_TABLE.' AS b','a.restid=b.id','inner')
					 ->join(TABLES::$ZONE_DEL_AREA.' AS c ','b.id = c.restid','inner')
					 ->where('a.restid',$restid);
			$compiled_query = $this->db->_compile_select();
			$query = $this->db->get();
			$result = $query->result_array();
			return $result[0];
		}
		
		
		public function getMemberDetailByMobile( $mobile ) {
		
			$this->db->select('a.id,a.cityarea,a.firstname,a.address1,a.cityid,a.phone,a.mobile,a.email,b.zone_id')
					 ->from(TABLES::$MEMBER_PROFILE_TABLE.' AS a')
					 ->join(TABLES::$CITY_AREA.' AS b ','a.cityarea = b.areaid','inner')
					 ->where('a.mobile',$mobile)
					 ->limit(1);
			$compiled_query = $this->db->_compile_select();
			$query = $this->db->get();
			$result = $query->result_array();
			return $result;
		}
		
		public function getRestaurantMainCategory($restid){
			$this->db->select('a.mcatid as mcatid,b.name as mcatname')
					 ->from(TABLES::$REST_MAIN_CAT_TABLE.' AS a')
					 ->join(TABLES::$MAIN_CATEGORY_TABLE.' AS b','a.mcatid = b.id','inner')
					 ->where('a.restid',$restid)
					 ->order_by('a.mcatid','asc');
			$compiled_query = $this->db->_compile_select();
			if($data = $this->rcache->get(md5( $compiled_query),$restid)) {
				$this->db->_reset_select();
				$result = $data;
			}else {
				$query = $this->db->get();
				$result = $query->result_array();
				$this->rcache->set(md5($compiled_query), $result,$restid);
			}
			return $result;
		}
		
		public function getPopularItemsByCategory($restid,$mcatid,$slot) {
			$params = array('a.restid'=>$restid,'b.restid'=>$restid,'c.mcatid '=>$mcatid,'a.available'=>1);
			$this->db->select('b.itemid as id,b.frequency,a.global_id,a.name,a.price')
					 ->from(TABLES::$MENU_ITEM_TABLE.' AS a')
					 ->join(TABLES::$POPULAR_ITEMS.' AS b','a.id = b.itemid','inner')
					 ->join(TABLES::$CATEGORY_TABLE.' AS c','a.catid = c.id','inner')
					 ->where($params)
					 ->where('a.available_time IN(7,'.$slot.')','',FALSE)
					 ->order_by('b.frequency','desc')
					 ->limit(10);
			$compiled_query = $this->db->_compile_select();
			if($data = $this->rcache->get(md5( $compiled_query),$restid)) {
				$this->db->_reset_select();
				$result = $data;
			}else {
				$query = $this->db->get();
				$result = $query->result_array();
				$this->rcache->set(md5($compiled_query), $result,$restid);
			}
			return $result;
		}
		
		public function getMemberLastFiveFavouriteOrders($mobile) {
			$this->db->select('a.ordercode,a.restname,a.total,a.date')->from(TABLES::$ORDER_QUEUE_TABLE.' AS a')
					 ->join(TABLES::$MEMBER_FAVOURITES.' as b','a.orderid=b.orderid','inner')
					 ->join(TABLES::$MEMBER_PROFILE_TABLE.' AS c','b.userid=c.id','inner')
					 ->where('c.mobile',$mobile)
					 ->where('a.status',3)
					 ->order_by('a.orderid','DESC')
					 ->limit(5);
			$compiled_query = $this->db->_compile_select();
				
			if($data = $this->cache->get(md5( $compiled_query))) {
				$this->db->_reset_select();
				$result = $data;
			}else {
				$query = $this->db->get();
				$result = $query->result_array();
				$this->cache->set(md5($compiled_query), $result);
			}
			return $result;
		}
	}
?>
