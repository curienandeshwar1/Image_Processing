<html>
<head>
<title>Gallery</title>
</head>
<body>
<div align="center">
<h3>GALLERY</h3>
</div>

<?php session_start();
$email =$_POST['useremail'];

require '/home/ubuntu/vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
$client = new DynamoDbClient([
    'region'  => 'us-east-1',
    'version' => 'latest'
]);
$result = $client->scan([
    'ExpressionAttributeNames' => [
        '#S3R' => 'S3finishedurl',
        '#S3F' => 'S3rawurl',
    ],
    'ExpressionAttributeValues' => [
        ':e' => [
            'S' => $email,
        ],
    ],
    'FilterExpression' => 'Email = :e',
    'ProjectionExpression' => '#S3F, #S3R',
    'TableName' => 'RecordsCAN',
]);
//print_r($result);
//echo "<br> ----------------------------------";
# retrieve the number of elements being returned -- use this to control the for loop
$len = $result['Count'];
# for loop to iterate through all the elements of the returned matches
for ($i=0; $i < $len; $i++) {
    echo '<img src="'.$result['Items'][$i]['S3rawurl']['S'].'" width="500" height="500" hspace="50"/>';
    echo "\t"; echo "\t";
    echo '<img src="'.$result['Items'][$i]['S3finishedurl']['S'].'" />';
    echo "<br>";
}

########################################################################################################
/*
use Aws\Rds\RdsClient;
 $client = new Aws\Rds\RdsClient([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

$dbresult = $client->describeDBInstances(array(
    'DBInstanceIdentifier' => 'can-database'
));

$address = $dbresult['DBInstances'][0]['Endpoint']['Address'];

$link = mysqli_connect($address,"master","cloud-db","records") or die("Error ". mysqli_error($link));
// check connection 
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

/*
$stmt = $pdo->prepare("SELECT * FROM items WHERE email=?");
$stmt->execute([$email]); 
$user = $stmt->fetch();
if ($user) {
    echo "email found" ;
    // email found
} else {
    echo "email not found";
    // or not
}
*/
/*
$link->real_query("SELECT * FROM items where email='$email'");
$res = $link->use_result();
//echo "Result set order...\n";
echo "<table>";
while ($row = $res->fetch_assoc()) {
    echo '<img src="'.$row['s3rawurl'].'" width="500" height="500" hspace="50"/>';
    echo "\t"; echo "\t";
    echo '<img src="'.$row['s3finishedurl'].'" />';
    echo "<br>";
    //echo "<img src =\"" . $row['s3rawurl'] . "\" />";
}
echo "</table>";
$link->close();
*/

?>

</body>
</html>