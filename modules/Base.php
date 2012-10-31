<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Stefan
 * Date: 09.09.12
 * Time: 13:39
 */
class CP_Base
{
    protected $module_name = "";

    public function get_comments($crmID) {

    }

    public function get_products($crmID) {
        global $adb;

        $query = 'SELECT vtiger_products.productid, vtiger_products.productname, vtiger_products.productcode,
      		 		  vtiger_products.commissionrate, vtiger_products.qty_per_unit, vtiger_products.unit_price,
      				  vtiger_crmentity.crmid, vtiger_crmentity.smownerid
      				FROM vtiger_products
      				INNER JOIN vtiger_seproductsrel
      					ON vtiger_seproductsrel.productid=vtiger_products.productid
      				INNER JOIN vtiger_crmentity
      					ON vtiger_crmentity.crmid = vtiger_products.productid
      			   WHERE vtiger_seproductsrel.crmid = '.$crmID.' and vtiger_crmentity.deleted = 0';
        $result = $adb->query($query);

        $records = array();
        while($row = $adb->fetch_array($result)) {
            $records[] = array(
                "id" => $row["productid"],
                "productname" => $row["productname"],
                "productcode" => $row["productcode"],
            );
        }
        return $records;

    }
    /**
   	* Function to get Contact related Tickets.
   	* @param  integer   $id      - contactid
   	* returns related Ticket records in array format
   	*/
   	function get_tickets($crmID) {
   		global $adb;

   		$userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
   							'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
   		$query = "select case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name,
   				vtiger_crmentity.crmid, vtiger_troubletickets.title, vtiger_contactdetails.contactid, vtiger_troubletickets.parent_id,
   				vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_troubletickets.status, vtiger_troubletickets.priority,
   				vtiger_crmentity.smownerid, vtiger_troubletickets.ticket_no
   				from vtiger_troubletickets inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_troubletickets.ticketid
   				left join vtiger_contactdetails on vtiger_contactdetails.contactid=vtiger_troubletickets.parent_id
   				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
   				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
   				where vtiger_crmentity.deleted=0 and vtiger_contactdetails.contactid=".$crmID;
        $result = $adb->query($query);

        $records = array();
        while($row = $adb->fetch_array($result)) {
           $records[] = array(
               "id" => getTabId("HelpDesk")."x".$row["crmid"],
               "title" => $row["title"],
               "status" => $row["status"],
               "priority" => $row["priority"]
           );
        }

   		return $records;
   	}


}
