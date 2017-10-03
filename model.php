<?php
$portal = mysqli_connect("db2.webapp.coredial.com", "spbilling", "b1cycl3s", "portal") or die("No joy");
$pbx = mysqli_connect("db01.phl.coredial.com", "widget", "zm75wrgm", "service_pbx") or die("Whoops");
$analytics = mysqli_connect("db01.phl.coredial.com", "widget", "zm75wrgm", "analytics") or die("WILHELM SCREAM");

function getAsterisk($conn) {
   $sqla = "SELECT COUNT(e.extensionId) as ext
  FROM extension e
  LEFT JOIN branch b ON (e.branchId=b.branchId)
  LEFT JOIN customer c ON (b.customerId=c.customerId)
  LEFT JOIN reseller r ON (r.resellerId = c.resellerId)
 WHERE c.statusId=1
   AND e.extensionTypeId=1
   AND e.isFreeExtension=0
   AND c.companyName NOT LIKE '%test%'
   AND c.companyName NOT LIKE '%demo%'";

    $sqlb = "SELECT COUNT(e.extensionId) as ext
  FROM extension e
  LEFT JOIN branch b ON (e.branchId=b.branchId)
  LEFT JOIN customer c ON (b.customerId=c.customerId)
  LEFT JOIN reseller r ON (r.resellerId = c.resellerId)
 WHERE c.statusId=1
   AND e.extensionTypeId=2
   AND e.isFreeExtension=0";
  
   $sqlc = "SELECT COUNT(b.branchId) as sip
  FROM sipTrunk s
  LEFT JOIN branch b ON (s.branchId=b.branchId)
  LEFT JOIN customer c ON (b.customerId=c.customerId)
  LEFT JOIN reseller r ON (c.resellerId=r.resellerId)
 WHERE c.statusId=1";
 
   $dataA = mysqli_query($conn, $sqla);
   $dataB = mysqli_query($conn, $sqlb);
   $dataC = mysqli_query($conn, $sqlc);

   $rowA = mysqli_fetch_assoc($dataA);
   $rowB = mysqli_fetch_assoc($dataB);
   $rowC = mysqli_fetch_assoc($dataC);
   return $rowA['ext']+$rowB['ext']+$rowC['sip'];
}

function getBroadworks($conn) {
   $sql = "SELECT COUNT(*) as total FROM seat";
   $dr = mysqli_query($conn, $sql);
   $row = mysqli_fetch_assoc($dr);
   return $row['total'];
}

function getMobileUsers($conn) {
  $sql = "SELECT DISTINCT " .
        "SUM(uniqueUsersAllTime) as unique_users_all_time, " .
        "SUM(uniqueUsersYearToDate) as unique_users_year_to_date, " .
        "SUM(uniqueUsersQuarterToDate) as unique_users_quarter_to_date, " .
        "SUM(uniqueUsersMonthToDate) as unique_users_month_to_date, " .
        "MAX(DATE(mar.reportDate)) as lastReport, " .
        "DATE(reportDate) as report_date " .
        "FROM mobileAdoption ma " .
        "JOIN mobileAdoptionReport mar USING (mobileAdoptionReportId) " .
        "WHERE DATE(mar.reportDate) = (SELECT MAX(DATE(reportDate)) FROM mobileAdoptionReport)";
  $dr = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($dr);
  return $row;
}

function getEZTax($conn) {
   $sql = "select COUNT(distinct(r.resellerId)) as total FROM resellerBilling rb " .
      "LEFT JOIN reseller r ON r.resellerId = rb.resellerId " .
      "WHERE rb.activeTaxSystemId=2 " .
      "AND r.status_id=1";
   $dr = mysqli_query($conn, $sql);
   $row = mysqli_fetch_assoc($dr);
   return $row['total'];
}

function getIOSUsers($conn) {
  $sql = "select count(*) as count from(" .
         "select user_id, hardware, max(app_version) app_version, max(os_version) os_version, max(time) last_ping " .
         "from analytics.mobile_activity " .
         "where hardware not in ('Android', 'NULL') " .
         "group by user_id, hardware " .
         "order by user_id " .
         ") devices " .
         "order by user_id";
  $dr = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($dr);
  return $row;
}

function getActiveIOSUsers($conn) {
  $sql = "select count(*) as count from(" .
         "select user_id, hardware, max(app_version) app_version, max(os_version) os_version, max(time) last_ping " .
         "from analytics.mobile_activity " .
         "where hardware not in ('Android', 'NULL') " .
         "group by user_id, hardware " .
         "order by user_id " .
         ") devices " .
         "where " .
         "last_ping >= DATE_SUB(NOW(), INTERVAL 1 MONTH) " .
         "order by user_id";
  $dr = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($dr);
  return $row;
}

function getAndroidUsers($conn) {
  $sql = "select count(*) as count from(" .
         "select user_id, hardware, max(app_version) app_version, max(os_version) os_version, max(time) last_ping " .
         "from analytics.mobile_activity " .
         "where hardware not in ('iPhone', 'iPad', 'iPod touch', 'NULL') " .
         "group by user_id, hardware " .
         "order by user_id " .
         ") devices " .
         "order by user_id";
  $dr = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($dr);
  return $row;
}

function getActiveAndroidUsers($conn) {
  $sql = "select count(*) as count from(" .
         "select user_id, hardware, max(app_version) app_version, max(os_version) os_version, max(time) last_ping " .
         "from analytics.mobile_activity " .
         "where hardware not in ('iPhone', 'iPad', 'iPod touch', 'NULL') " .
         "group by user_id, hardware " .
         "order by user_id " .
         ") devices " .
         "where " .
         "last_ping >= DATE_SUB(NOW(), INTERVAL 1 MONTH) " .
         "order by user_id";
  $dr = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($dr);
  return $row;
}


$data = array();
$data['broadworks'] = getBroadworks($pbx);
$data['asterisk'] = getAsterisk($portal);
$data['ezTax'] = getEZTax($portal);
$data['mobile']['ios'] = getIOSUsers($analytics);
$data['mobile']['activeIOS'] = getActiveIOSUsers($analytics);
$data['mobile']['android'] = getAndroidUsers($analytics);
$data['mobile']['activeAndroid'] = getActiveAndroidUsers($analytics);
$portal->close();
print_r(json_encode($data))
?>
