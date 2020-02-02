<?php
/*
 * script to update IP using dinahosting API, using a Task Scheduler from Synology NAS 
 */
$EMAIL_SUBJECT = 'Synology: nsupdate';
$EMAIL_BODY = "Script ".$argv[0]."\n";
$EMAIL_TO = 'xxx@gmail.com';


$LOGFILE = 'log_php.txt';

$USER = 'xxx';
$PASSWORD = 'xxx';

$dominios = array(
    'example.com'    => 'fotos',
    'example.gal'         => 'alex'
);

file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] --- --- ---\n", FILE_APPEND);
file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] Comezo\n", FILE_APPEND);

// get NAS external IP
$req = curl_init();
curl_setopt($req, CURLOPT_URL, 'https://ipv4.nsupdate.info/myip');
curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($req);
curl_close($req);

$IP = $res;
file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] IP [$IP]\n", FILE_APPEND);

$EMAIL_BODY .= "Data: ".date("Y-m-d H:i:s")."\n\n";

foreach($dominios as $dominio => $host) {

    $domainall = "$host.$dominio";
    file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] dominio $domainall\n", FILE_APPEND);

    // get IP from each domain
    $OLDIP = `nslookup $domainall |fgrep $domainall -A1 |fgrep Address |awk '{print $2}'`;
    $OLDIP = trim($OLDIP);
    file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] \t OLDIP [$OLDIP]\n", FILE_APPEND);

 

    if($IP == $OLDIP) {
        file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] \t IP eq OLDIP: $IP eq $OLDIP\n", FILE_APPEND);
        $EMAIL_BODY .= "$domainall IP eq OLDIP: $IP eq $OLDIP\n";
        continue;
    }

    // dinahosting API URL
    $url = "https://dinahosting.com/special/api.php?AUTH_USER=$USER&AUTH_PWD=$PASSWORD&responseType=Json&domain=$dominio&hostname=$host&ip=$IP&command=Domain_Zone_UpdateTypeA";

    file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] \t ".$url."\n", FILE_APPEND);
    
    // update IP    
    $req = curl_init();
    curl_setopt($req, CURLOPT_URL, $url);
    curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($req);
    curl_close($req);

    file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] \t response: ".$res."\n", FILE_APPEND);

    // check response    
    $data = json_decode($res, true);
    file_put_contents($LOGFILE, "[".date("Y-m-d H:i:s")."] \t date: ".var_export($data, true)."\n", FILE_APPEND);
    if($data['responseCode'] == 1000) {
        echo "good";
        $EMAIL_BODY .= "\n$domainall IP: [$IP]  cambio OK \n$res\n\n";

    }
    else{
        echo "badresolv";
        $EMAIL_BODY .= "\n$domainall IP: [$IP] ERROR \n$res\n\n";
    }
}

mail($EMAIL_TO, $EMAIL_SUBJECT, $EMAIL_BODY);
