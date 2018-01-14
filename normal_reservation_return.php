<?php
$arr1 = explode($_SERVER['DOCUMENT_ROOT'],__FILE__);
$arr2 = explode("/",$arr1[1]);
$project_dir = $arr2[1];
$path_to_validation = $_SERVER['DOCUMENT_ROOT']."/".$project_dir."/common/SecurityValidation.php";
include_once($path_to_validation); ?>
<?php
if(stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) 
{
  ob_start('ob_gzhandler');
}
else
{
  ob_start();
}
//include_once("../common/session_expire.php");

echo 'aa';

//die;
session_start();
$_SESSION['csrf'] = md5(uniqid(rand(), TRUE));
	include_once("../common/checkPassChnge.php");
	include_once("../common/chklogin.php");	
	include_once("../classes/config.php");
	include_once("../classes/dbconn.class.php");
	include_once("../classes/dbop.class.php");	
	$dbconnect = new dbconn;
	$dbop= new dbop();
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="../css/ibrsTC.css">
<link rel="stylesheet" href="../css/formElements.css" type="text/css" />
<title>Normal Reservation</title>
<script language="javascript" src="../js/ajax.js"></script>
<script language="javascript" src="../js/tcnormajax.php"></script>
<script language="javascript" src="../js/genFunction.js"></script>
<script language="javascript" src="../js/shortcut.js"></script>
<link rel="stylesheet" href="../css/dhtmlwindow.css" type="text/css" />
<script type="text/javascript" src="../js/dhtmlwindow.php"></script>    
<link rel="stylesheet" href="../css/modal.css" type="text/css" />
<script type="text/javascript" src="../js/modal.js"></script>
  <script type='text/JavaScript'>

        function refocus(rownum) {
          document.getElementById('quota'+rownum).focus();
        }
    </script>
<style>
.show {display:block;} 
.hide {display:none;}
th{text-align:center;}
</style>
</head>
<body onLoad="document.getElementById('t1').focus;">
<?php
	include_once("../common/chk_res_timing.php");
	include_once("../menu/tcmenu_new.php");	
	include_once("../common/news.php"); 
	$s = "SELECT iu.AGENT_CD ,AGENT_TYPE, 
		sum(if(REQUEST_TYPE ='B', TOTAL_FARE_AMT ,0)) - sum(if(REQUEST_TYPE ='C', TOTAL_FARE_AMT ,0))  as booking_amt,
		CREDIT_LIMIT_AMT,
		ifnull (DEPOSIT_AMOUNT,0) DEPOSIT_AMOUNT,
		CREDIT_LIMIT_AMT  - ((sum(if(REQUEST_TYPE ='B', TOTAL_FARE_AMT ,0)) - sum(if(REQUEST_TYPE ='C', TOTAL_FARE_AMT ,0)))  
		- ifnull (DEPOSIT_AMOUNT,0) ) as balance
		from ibrs_users iu
		INNER JOIN booking_agents ba ON ba.AGENT_CD = iu.AGENT_CD
		INNER JOIN tickets t ON t.USER_CD = iu.USER_CD
		INNER JOIN pagent_account pa ON pa.AGENT_CD = iu.AGENT_CD
		LEFT JOIN pagent_deposits pd ON pd.AGENT_CD = pa.AGENT_CD
		where iu.USER_CD = '".$_SESSION['ucd']."'";
$q = mysql_query($s) or die(mysql_error());		
$r = mysql_fetch_assoc($q);
/***only for ac bus**/
$qCon = "SELECT GROUP_CONCAT(DISTINCT(cr.CONCESSION_CD)) as concessions_code
FROM concession_rates cr
WHERE cr.CONCESSION_RATE = '100' and BUS_TYPE_CD IN ('AA','AM','AS','SH')";
$sCon = mysql_query($qCon) or die(mysql_error());
$rCon = mysql_fetch_array($sCon);


$quota_nm=array("L","CT","G");
$adult = 0;
$child = 0;

/* Fetch Onward Journey Data */
$c=0;
     $sql = "select opl.*,bs1.BUS_STOP_NM as from_nm,bs2.BUS_STOP_NM as till_nm,bt.BUS_TYPE_CD,bt.BUS_TYPE_NM
            from ORS_PassengerListTemp  opl
            inner join bus_stops bs1 on opl.FROM_STOP_CD=bs1.BUS_STOP_CD
            inner join bus_stops bs2 on opl.TILL_STOP_CD=bs2.BUS_STOP_CD
            inner join bus_services bs on opl.bus_service_no=bs.bus_service_no
            inner join bus_types bt on  bs.bus_type_cd=bt.bus_type_cd
             where USER_CD='".$_SESSION['ucd']."' and application='ORS'";

$sql_Query         =   mysql_query($sql);
while($sql_Query_array   =   mysql_fetch_array($sql_Query)){
    
    $pass_nm[$c]                 =  $sql_Query_array['PASSENGER_NM'];
    $pass_sex[$c]                =  $sql_Query_array['PASSENGER_SEX'];
    $pass_age[$c]                =  $sql_Query_array['PASSENGER_AGE'];
    if($sql_Query_array['CONCESSION_CD']="CT"){
           $pass_quota[$c]              =  "CT";
    }
    else if($sql_Query_array['CONCESSION_CD']="CT"){
    
           $pass_quota[$c]              =  "Gen";
    }
    else{
          $pass_quota[$c]              =  $sql_Query_array['PASSENGER_QUOTA'];
    }
    
    
    
    
    $pass_type[$c]               =  $sql_Query_array['PASSENGER_TYPE'];
    $pass_conc[$c]               =  $sql_Query_array['CONCESSION_CD'];
    $pass_conc_proof[$c]         =  $sql_Query_array['CONCESION_PROFF'];
    $pass_conc_proof_detail[$c]  =  $sql_Query_array['PROFF_DETAIL'];
    if($c==0){
        
        $to_stop                 =   $sql_Query_array['FROM_STOP_CD'];
        $from_stop               =   $sql_Query_array['TILL_STOP_CD'];
        $from_nm                 =   $sql_Query_array['from_nm'];
        $till_nm                 =   $sql_Query_array['till_nm'];
        $bus_type_cd             =   $sql_Query_array['BUS_TYPE_CD'];
        $bus_type_nm             =   $sql_Query_array['BUS_TYPE_NM'];
    }
    if($pass_type[$c]=="A"){
        $adult++;
    }
    if($pass_type[$c]=="C"){
        $child++;
    }
    
    $c++;
}





echo "aa=>".$from_nm;


//echo "<pre>";print_r($r);echo "</pre>";
?>
<form name="normal_reservation" method="POST" action="insert_temp_norm_pass_ors.php" onSubmit="return ValidateTicket();">
<input type = "hidden" id ="agentBalance" value="<?php echo $r['balance'];?>">
<input type = "hidden" name ="ignoreConcessions"  id ="ignoreConcessions" value="<?php echo $rCon['concessions_code']?>">
<input type = "hidden" id ="agentType" value="<?php echo $r['AGENT_TYPE'];?>">
<input type = "hidden" id ="fareType" value="">
<input type = "hidden" id ="busType" value="">
    <table border="0" align="center"><tr><td><span class="col-r">Onward Journey Detail</span></td></tr></table>
<fieldset>
<legend>Normal Reservation</legend>
<table border="0" align="center" width="97%">
<tbody>
<tr>
    <td colspan="6" style="font-size: 16px" >
    	<font color="#FF0000" size="+0"><b><u>Press</u> : </b></font>
        <strong><font color="#FF0000">F2 :</font> </strong>Search According Focused Field 
        <strong><font color="#FF0000">F3 : </font></strong>Seat Layout Display 
        <strong><font color="#FF0000">F4 : </font></strong>View Current Bus Stopages
        <strong><font color="#FF0000">F8 : </font></strong>View All Buses
        <strong><font color="#FF0000">ESC : </font></strong>Refresh Page
    </td>
</tr>
<tr><td colspan="6">&nbsp;</td></tr>
<tr>
	<td align="left" width="34%">&nbsp;</td>
    <td colspan="3"> </td>
    <td width="17%">
    	<div align="right"><b>Last Ticket:</b> </div></td>
    <td width="26%"><input type="text" name="ticketno" id="ticketno" size="25" disabled="disabled"value="
    <?php echo $_SESSION['last_tick_no'];?>"/></td>
</tr>
<tr>
    <td width="20%" valign="top">
        <fieldset>
            <legend>Travel Details</legend>
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="38"><b>Date:</b></td>
                  <td width="124"><b>
                  <input type="text" name="txtDate1" id="t1" size="8" tabindex="1" maxlength="8" 
                 value="2017-03-05"
                  onkeyup="putSlash(this.id);"
                  />
                    <input type="hidden" name="txtDate" id="1"  size="10" tabindex="1" maxlength="10"  value="2017-03-05"
                  onkeyup="this.value=makeNumeric(this.value,event);"       
                  />
                  <div id="1Error" style="color:red;font-size:13px"></div></b>
                  </td>
                  <td width="62"><b>dd/mm/yy</b></td>
                </tr>
                <tr>
                  <td><b>Time:</b></td>
                  <td><b>
                      <input type="text" name="txtTime" id="2" value="" size="10" tabindex="2" maxlength="8"        
                
                          />
                  </b>
                  <div id="valTime"></div>
                  </td>
                  <td><b>hh:mm:ss</b></td>
                </tr>
                <tr>
                  <td><b> Type:</b></td>
                  <td colspan="2"><b>
                    <input type="text" name="txtType" id="3"  maxlength="2" size="4" tabindex="3" 
                                  value='<?php echo $bus_type_cd;  ?>'
                      />
                  </b><b>
                    <input type="text" name="txtTypenm" id="txtTypenm" size="15" readonly="readonly" class="disabled" value='<?php echo $bus_type_nm;  ?>' tabindex="0"/>
                  </b></td>
                  </tr>
                <tr>
                  <td><b>Adult:</b></td>
                  <td colspan="2"><b>
                    <input type="text" name="txtAdult" id="4" tabindex="4" maxlength="1"  value='<?php echo $adult;  ?>'
                  />
                  </b></td><!--activeelement=parseInt(0); addAdultRow(this.value);-->
                  </tr>
                <tr>
                  <td><b >Child:</b></td>
                  <td colspan="2"><b ><!--activeelement=parseInt(0);addChildRow(this.value);-->
                    <input type="text" name="txtChild" id="5" tabindex="5" maxlength="1" value='<?php echo $child;  ?>'
                  />
                  </b></td><!-- clearChildRow(this.value) if(this.value.length==1){document.getElementById('6').focus()}-->
                </tr>
                <tr>
                    <td><b>Mobile Number:</b></td>
                    <td><input type='text' tabindex="5" id='mobno' name='mobno' value='<?php  echo $_SESSION['mobno']; ?>'>
                            <span><font color='green'>SMS will be sent to mobile number</font></span>
                    </td>
                </tr>
            </table>
        </fieldset>        	
        <div id="busformatname">        </div>
    </td>
    <td colspan="3" valign="top" width="30%">
    	<fieldset>
        <legend>Journey Details</legend>
    	<table width="100%" border="0" cellpadding="0" cellspacing="0">
            
            <tr>
                <td><b>From</b></td>
                <td><input type="text" name="fromCd" id="fromCd" size="6" maxlength="10" tabindex="7"  readonly="readonly"  value="<?php echo $from_stop; ?>"
            	/></td><!--setFocusOnComplete('fromCd','6','4'); getBusStopNm(this.value,'fromname');-->
                <td><input type="text" name="fromname" id="fromname" readonly="readonly" value="<?php echo $till_nm; ?>"/></td>
            </tr>
            <tr>
                <td><b>To</b></td>
                <td>
                    <input type="text" name="txtDestCd" id="6"  size="6"  readonly="readonly" maxlength="10" tabindex="8"  value="<?php echo $to_stop; ?>"
                    />
                </td>
                <td><input type="text" name="txtDestNm" id="txtDestNm" readonly="readonly" value="<?php echo $from_nm; ?>"/>
                    <input type="hidden" name="reCd" id="reCd" value="0">
                    <input type="hidden" name="journy_type" id="journy_type" value="R">
                        
                </td>            
            </tr>
            <tr>
          	<td colspan="3"><strong></strong><div id="rtname">Route: </div></td>
            </tr>
            <tr>
          	<td colspan="3"><hr /></td>
            </tr>
            <tr>
                <td colspan = "10" align="left"><?php include_once("less5.php");?></td>
            </tr>           
        </table>        
        </fieldset>        
    	<div id="DestName"></div></td>
    <td colspan="2" valign="top" width="50%">
    	<fieldset>
            <legend align="top">Charges</legend>
            <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td width="40%"><b>Fare:</b></td>
                <td width="60%"><input type="text" name="txtFare" id="txtFare" value="" disabled="disabled" style="text-align:right"/></td>
              </tr>	
              <tr>
                <td width="40%"><b>Res Amt:</b></td>
                <td width="60%"><input type="text" name="txtResAmt" id="txtResAmt" value="" disabled="disabled" style="text-align:right"/></td>
              </tr>
              <tr>
                  <td width="40%"><b><label id="lblTatkal" name="lblTatkal" style="display:none">Tatkal Amt:</label></b></td>
                <td width="60%"><input type="text" name="txtTatkalAmt" id="txtTatkalAmt" value="" disabled="disabled" style="text-align:right;display:none"/></td>
              </tr>
              <tr>
                         <td width="40%"><b>ASN Amt:</b></td>
                         <td width="60%"><input type="text" name="txtASNAmt" id="txtASNAmt" value="" disabled="disabled" style="text-align:right"/></td>
              </tr>
	      <tr>
                         <td width="40%"><b>AC Service Tax:</b></td>
                         <td width="60%"><input type="text" name="txtACServiceTax" id="txtACServiceTax" value="" disabled="disabled" style="text-align:right"/></td>
              </tr>
              <tr>
                <td width="40%"><b>Total (Rounding):</b></td>
                <td width="60%"><input name="txtTotal" type="text" id="txtTotal" value="" disabled="disabled" style="text-align:right; background-color:#FFFFCC" /></td>
              </tr>          
            </table>        
        </fieldset>
    </td>
</tr>
</tbody>
</table>
<input type="hidden" id="childAmt" name="childAmt"/>
<input type="hidden" id="adultAmt" name="adultAmt"/>
<input type="hidden" id="chkblur" name="chkblur" value="0"/>
<input type="hidden" id="bus_service_no" name="bus_service_no"/> <!--Bus Service Number-->
<input type="hidden" id="count" name="count" value="0"/>
<input type="hidden" id="focused" name="focused"/>
<input type="hidden" id="abcd" name="abcd" value="<?php echo $_SESSION['csrf'];?>"/>
<input type="hidden" id="quotaName" name="quotaName" value="NC"/>
<input type="hidden" id="hdnResrvtnType" name="hdnResrvtnType" value="general"/>
</fieldset>
        <div id="error" align="center">
        <?php 
			if(isset($_POST['bookseats']))
			{
				echo $_POST['error'];
				echo $_SESSION['error'];
				unset($_SESSION['error']);
			}
        ?>
    </div>
<div id="timer"></div>
<fieldset>
    <legend align="left">Passenger details</legend >
<table border="0" width="100%" id="seat_table">
<tr>
	<td align="center"> 
    	<table >
            <tr id = 'passHead' >            	
            <th width="4%" id="no" >Sr. No.</th>
            <th width="20%">Name</th>
            <th width="5%"><b>Sex</b></th>
            <th width="5%"><b>A/C</b></th>
            <th width="5%"><b>Age</b></th>
            <th width="6%"><b>Quota</b></th>
            <th width="5%"><b>Seat</b></th>
            <th width="5%"><b>Conc.</b></th>
            <th width="5%"><b>Rate</b></th>
            <th width="9%"><b>Amount</b></th>
            <th width="16%"><b>Conc. Proof</b></th>
            <th ><b>Conc. Proof Details</b></th>
            </tr>
<?php
$tabindex = 100;
for($row=0; $row <$c; $row++)
{
?>

		<tr id = 'pass<?php echo $row; ?>' style="visibility:collapse">
        
			<td>
                             <input type="hidden" name="nmerrid" id="nmerrid"/>
                             <input type="hidden" name="nmerr" id="nmerr"/>
			    <input type ="text" name = "sr<?php echo $row; ?>" id="sr<?php echo $row; ?>" value = "<?php echo $row+1 ; ?>" size = "2" style="text-align:center;" disabled="disabled"/>
                       </td>
			
                        <td>
                            <input type ="text" size="23" 
                            tabindex ="<?php echo $tabindex++; ?>" 
                            name = "passnm<?php echo $row; ?>" 
                            id="passnm<?php echo $row; ?>" 
                            value ="<?php  echo $pass_nm[$row];   ?>"

                           /><!--chkName(this.value,this.id)-->
                        </td>			
                        <td>
                            <input type ="text" name = "sex<?php echo $row; ?>" id="sex<?php echo $row; ?>"  maxlength = "1" size = "2" style="text-align:center" tabindex ="<?php echo $tabindex++; ?>"

                                   value ="<?php  echo $pass_sex[$row];   ?>"
                                   />
                        </td>  
			<td>
                            <input type ="text" name = "ac<?php echo $row; ?>" id="ac<?php echo $row; ?>" maxlength = "1" size = "2" 
                            accept=""value ="<?php  echo $pass_type[$row];   ?>"
                             readonly tabindex ="<?php echo $tabindex++; ?>"
                
                      </td>
            
			<td>
                            <input type ="text" name = "age<?php echo $row; ?>" 
                             id="age<?php echo $row; ?>"   value ="<?php  echo $pass_age[$row];   ?>"  maxlength = "3" size = "2" 
                             style="text-align:center" tabindex ="<?php echo $tabindex++; ?> " 
                 
                             />
                      </td>
			<td>
                                <select name="quota<?php echo $row; ?>" onkeydown="stop(event,'<?php echo $row;?>');" onChange="quotaseat(this.value,'<?php echo $row;?>');" id="quota<?php echo $row; ?>" tabindex ="<?php echo $tabindex++; ?>" onFocus="setTabindex(this.id)">
                                    <option value="Gen" <?php ($pass_quota[$row]=="Gen") ? "selected:selected" :'';    ?>  >Gen</option>
                                    <option value="L">Ldy</option>
                                     <option value="10">CT</option>
                                     <option value="T">T</option>
                                </select>            
                     </td>
			<td>
                            <input type ="text" name = "seatno<?php echo $row; ?>" id="seatno<?php echo $row; ?>" 
                            value = "<?php echo $_POST['seat'.$i]; ?>" 
                            size = "3" style="text-align:center" 
                            maxlength="2" tabindex ="<?php echo $tabindex++; ?>"  readonly="readonly" />
                      </td>
                      <td>
                            <input type ="text" name = "concType<?php echo $row; ?>" 
                            id="concType<?php echo $row; ?>" value = "" size = "3" 
                            style="text-align:center" tabindex ="<?php echo $tabindex++; ?>" 
                            onblur="farechange(this.id,'concRate<?php echo $row; ?>','Amount<?php echo $row; ?>'); 
                                removeHelp(this.id);" 
                            onkeyup=""
                            onfocus="document.getElementById('focused').value = this.id;setTabindex(this.id);addHelp(this.id)" />            
                     </td>
                    
			<!--<td>
                            
                            
                            
                            
                            
            <input type ="text" name = "concType<?php echo $row; ?>" 
            id="concType<?php echo $row; ?>" value = "" size = "3" 
            style="text-align:center" tabindex ="<?php echo $tabindex++; ?>" 
            onblur="farechange(this.id,'concRate<?php echo $row; ?>','Amount<?php echo $row; ?>'); 
            	removeHelp(this.id);"
                onfocus="farechange(this.id,'concRate<?php echo $row; ?>','Amount<?php echo $row; ?>'); 
            	removeHelp(this.id);"
           onfocus="document.getElementById('focused').value = this.id;addHelp(this.id);setTabIndex2(this.id)" 
            onkeyup="document.getElementById('focused').value = this.id;addHelp(this.id);setTabIndex2(this.id)"
            onkeydown="document.getElementById('focused').value = this.id;addHelp(this.id);setTabIndex2(this.id)"/>            
            </td>-->

            
			<td>
            <input type ="text" name = "concRate<?php echo $row; ?>" 
            id="concRate<?php echo $row; ?>" value = "0.00" size = "4" 
            style="text-align:center" disabled="disabled"/>
            </td>
            
			<td>
            <input type ="text" name = "Amount<?php echo $row; ?>" id="Amount<?php echo $row; ?>" value = "" size = "10" style="text-align:right" disabled="disabled"/>
            </td>
            <td>
            	<div id="concdDiv<?php echo $row;?>">
                	<select name="concd<?php echo $row; ?>" id="concd<?php echo $row; ?>"
                	 style="width:170px" tabindex ="<?php echo $tabindex++; ?>" 
                     disabled="disabled"
                     onfocus="setTabindex(this.id)">
                	<option value="">Select</option>
                   <?php
				     $query = "select PROOF_NAME from CONC_PROOFS";
				     $data=mysql_query($query); 
					 while($res=mysql_fetch_array($data)) {
				       echo "<option value='".$res['PROOF_NAME']."'>".$res['PROOF_NAME']."</option>";
				     }				   
				   ?>
                         
                </select>
                </div>            	
            </td>
            
			<td>
            <input type ="text" name = "proofId<?php echo $row; ?>" id="proofId<?php echo $row; ?>" disabled="disabled" tabindex ="<?php echo $tabindex++; ?>" />
            </td>
		
				
        </tr>
<?php
}//End of For
?>
</table>
</td>
</tr>

<tr>
	<td colspan = "10" align = "center">
    	<input type ="submit" name="bookseats" id="bookseats" value="Save" 
        onclick=""  onFocus="setUpArrowOnSave()" onBlur="unsetShortcuts()"
        tabindex="<?php echo $tabindex;?>" />       
	</td>
</tr>
</table>

</fieldset>
<div id="error" align="center">
	
</div>
</form>
</body>
</div>
<script language="javascript" type="text/javascript">

var fromName = document.getElementById('fromname').value;		
		var type = document.getElementById('txtTypenm').value;
		var adults = Number(document.getElementById('4').value);
		var child = Number(document.getElementById('5').value);
		var seats = adults+child;
			
              
		
			var toName = document.getElementById('txtDestNm').value;
			var msg = "Buses going From "+fromName+ " To/Via "+ toName + " Having "+seats+" seats";
			popUp('../TC/buses_lookup.php?nm='+arr[0],msg);
		



document.getElementById('t1').focus();
function addHelp(id) {
shortcut.remove("f2");
shortcut.add("f2",function s()
		{
			var v = id;//document.getElementById('focused').value;
			if(v!=""){
				switch(v)
				{
					case '6':
					case 'fromCd':
					{
						if(v=='fromCd')
						{
							val = 2;
						}
						else if(v=='6')
						{
							val = 3;
						}
						
						if(val == 2 || val == 3)
						{
							popUp('getbuscd.php?s='+val, 'Bus Stop Detail')
						}
						
						/*document.getElementById('error').innerHTML = "";
						if(document.getElementById(v).value!="" && document.getElementById(v).value.length >=3){
							document.getElementById('error').innerHTML = "";
							popUpSearch('../TC/lookup.php');
						//popUp('../masters/getbuscd.php?s='+val, 'Bus Stop Detail')
						}
						else
						{
							document.getElementById('error').innerHTML = "Please Enter Minimum 3 Characters To Be Searched.";
							return;	
						}*/						
					}
					break;
					case '2':
						document.getElementById('error').innerHTML = "No Search For selected Field.";
						break;
					case '3' :
					document.getElementById('error').innerHTML = "";
					popUpSearch('../TC/lookup.php?quotaFlag=G');
					break;
					case 'concType0':
					case 'concType1':
					case 'concType2':
					case 'concType3':
					case 'concType4':
					case 'concType5':
					document.getElementById(id).value="";
					popUpSearch('../TC/lookup.php');
				}				
			}			
		});
	}
	
	function removeHelp() {
	  shortcut.remove("f2");
	}
shortcut.add("f3",function call()	
{
	if(document.getElementById('seatno0').value =="")
	{
		alert('Seats are not selected.');
	}
	else
	{
		var fc = document.getElementById('focused').value;
		switch(fc){
			case 'concType0':
			case 'concType1':
			case 'concType2':
			case 'concType3':
			case 'concType4':
			case 'concType5':
			case 'passnm0':
			case 'passnm1':
			case 'passnm2':
			case 'passnm3':
			case 'passnm4':
			case 'passnm5':
				var time = document.getElementById('1').value + ' ' + document.getElementById('2').value;
				var bus_service_no = document.getElementById('bus_service_no').value;
				var nopassangers = parseInt(document.getElementById('4').value) + parseInt(document.getElementById('5').value);
				popUpSearch('../TC/availability_lookup.php?time='+time+'&bsn='+bus_service_no+'&no='+nopassangers);
			break;
			case 'age0':
			case 'age1':
			case 'age2':
			case 'age3':
			case 'age4':
			case 'age5':
				document.getElementById('error').innerHTML = "Please Enter Age";break;						
		}		
	}	
});
shortcut.add("f4",function showStops()
		{
		if(document.getElementById('bus_service_no').value =="")
		{
			alert('Please select Bus.');
		}
		else
		{
			var fc = document.getElementById('focused').value;
			switch(fc){
				case 'concType0':
				case 'concType1':
				case 'concType2':
				case 'concType3':
				case 'concType4':
				case 'concType5':
				case 'passnm0':
				case 'passnm1':
				case 'passnm2':
				case 'passnm3':
				case 'passnm4':
				case 'passnm5':
					var bus_service_no = document.getElementById('bus_service_no').value;
					var rtnm = document.getElementById('rtname').innerHTML;
					var from = document.getElementById('fromCd').value;
					var till = document.getElementById('6').value;
					var msg = "Bus Stops on selected Bus Service on "+rtnm;
					popUpStopages('../TC/showStopages.php?bus_service_no='+bus_service_no+"&from="+from+"&till="+till+"&conc=NC",msg);
				break;
				case 'age0':
				case 'age1':
				case 'age2':
				case 'age3':
				case 'age4':
				case 'age5':
				document.getElementById('error').innerHTML = "Please Enter Age";break;						
			}
		}	
		});
shortcut.add("f8",function showAllBuses()
		{
		//if(document.getElementById('bus_service_no').value !="")
			{
				var fromStopNm = document.getElementById('fromname').value;
				var tillStopNm = document.getElementById('txtDestNm').value;
				var fromStopCd = document.getElementById('fromCd').value;
				var tillStopCd = document.getElementById('6').value;
				var deptDt = document.getElementById('1').value;
				var msg = "Buses going from/via "+fromStopNm+" to/via "+tillStopNm;
				popUpStopages('../TC/showAllBuses.php?fromCd='+fromStopCd+"&tillCd="+tillStopCd+"&deptDt="+deptDt,msg);
			}		
		});
shortcut.add("esc",function refreshThis()
		{
			//alert(document.getElementById('bus_service_no').value);
			if(document.getElementById('bus_service_no').value !="")
			{
				clear();
			}
			else
			{
				window.location.href = "normal_reservation.php";
			}		
		});
shortcut.add("f6",
function save()
	 {		var r=confirm("Do u want to Continue.");
			if (r==true)
			{
				if(ValidateTicket())
				document.normal_reservation.submit();
			}
	 } );
	 
	 function setShortcut(id) {
	   shortcut.add("f2", function s() {document.getElementById(id).value="";
	   });
	 }
</script>
<div idseat="test"></div> 
</html>
 <?php if(function_exists('timer')){timer(); }?> 
  <?php
ob_end_flush();
?>
