
<?php
// Start the session
session_start();
// In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
// of $_FILES.

echo $_POST['useremail'];
$email =$_POST['useremail'];
$phone =$_POST['phone'];

$uploaddir = '/tmp/';   
//$uploaddir = '/home/ubuntu/img/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

//echo " \n upload file is :" ; echo $uploadfile ; /// tmp/b1.jpg

echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    echo "File is valid, and was successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}

echo 'Here is some more debugging info:';
print_r($_FILES);

print "</pre>";
require '/home/ubuntu/vendor/autoload.php';
use Aws\S3\S3Client;

// https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/getting-started_basic-usage.html
//Create a S3Client
$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => 'us-east-1'
]);

$bucket="can-2019-s3-bucket";

//$key = $uploadfile; // prof code
$key = $_FILES['userfile']['name']; 


echo " \n This is the file name : "; echo $key;

$result = $s3->putObject([
    'ACL' => 'public-read',
    'Bucket' => $bucket,
    'Key' => $key,
    'SourceFile' => $uploadfile 
]);

$url = $result['ObjectURL'];

echo " Here is the raw URL : " ; echo $url;
echo "<br>";


############################################# DYnamo DB ##########################################3
   use Aws\DynamoDb\DynamoDbClient;
     $dbclient = new Aws\DynamoDb\DynamoDbClient([
        'region'  => 'us-east-1',
        'version' => 'latest'
        ]);

echo "<br> -----------Dynamodb Client created-----------";

# https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-dynamodb-2012-08-10.html#putitem
# PHP UUID generator for Receipt- https://www.php.net/manual/en/function.uniqid.php
    $receipt = uniqid(); 
    echo "<br> Receipt id is : "; echo $receipt;

    $dbresult = $dbclient->putItem([
        'TableName' => "RecordsCAN", // REQUIRED
        'Item' => [ // REQUIRED
            'Receipt' => ['S' => $receipt],
            'Email' => ['S' => $email],
            'Phone' => ['S' => $phone],
            'Filename' => ['S' => $key],
            'S3rawurl' => ['S' => $url],
            'S3finishedurl' => ['S' => "NA"],     
            'Status' => ['BOOL' => false],
            'Issubscribed' => ['BOOL' => false]     
            ]   
        ]);
        print_r($dbresult);

        echo "<br>--------------PUT ITEM Dynamodb is done----------------";

######################################## SNS using LAmda ################################3

use Aws\Sns\SnsClient;
$snsclient = new Aws\Sns\SnsClient([
    'region'  => 'us-east-1',
    'version' => 'latest'
]);

use Aws\Lambda\LambdaClient;

$lambdaclient = new Aws\Lambda\LambdaClient([
    'region'  => 'us-east-1',
    'version' => 'latest'
    ]);

echo "<br> ------------SNS AND LAMBDA client created------------";

$lambdaresult = $lambdaclient->listFunctions(array());
$lambdaArn = $lambdaresult['Functions'][0]['FunctionArn'];
echo "<br>" ; echo $lambdaArn ;

$lambdaname = $lambdaresult['Functions'][0]['FunctionName'];
echo "<br>" ; echo $lambdaname ;


# list topic ARN
# https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#listtopics
$snsresult = $snsclient->listTopics([
   // no need to call anything, as it will list all
]);
echo "<br>";
print_r($snsresult);

$TopicArn = $snsresult['Topics'][0]['TopicArn'];
echo "<br>" ; echo $TopicArn ;

$snsresult = $snsclient->subscribe([
    'Endpoint' => $phone,  // this number is taken from the form on index.php POST action
    'Protocol' => 'sms', // REQUIRED 
    'ReturnSubscriptionArn' => true,
    'TopicArn' => $TopicArn, // REQUIRED
]);

$snsresult = $snsclient->subscribe([
    'Endpoint' => $lambdaArn,  // this number is taken from the form on index.php POST action
    'Protocol' => 'lambda', // REQUIRED //prof: sms
    'ReturnSubscriptionArn' => true,
    'TopicArn' => $TopicArn, // REQUIRED
]);

$messageforlambda =  $receipt . "&" . $key . "&" . $email;
$snsresult = $snsclient->publish([
    'Message' => $messageforlambda, // REQUIRED
    'TopicArn' => $TopicArn ,
]);

echo "<br>---------------USER is subscribed to messages------------------";

################################################# SQS #################################

  use Aws\Sqs\SqsClient;

  $sqsclient = new Aws\Sqs\SqsClient([
    'region'  => 'us-east-1',
    'version' => 'latest'
   ]);
   
   echo " <br>------------ SQS Client created-------------";
# https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#getqueueurl
// or this is a valid approach too
$sqsresult = $sqsclient->getQueueUrl([
    'QueueName' => 'inclass-can', // REQUIRED
    //'QueueOwnerAWSAccountId' => '<string>',  // optional
]);
print_r($sqsresult['QueueUrl']);
$sqsURL = $sqsresult['QueueUrl'];
echo "URL: " . $sqsURL;

# https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#sendmessage
$params = [
    'MessageAttributes' => [
        "Title" => [
            'DataType' => "String",
            'StringValue' => $key
        ],
        "Email" => [
            'DataType' => "String",
            'StringValue' => $email
        ]
    ],  
    'MessageBody' => $receipt,
    'QueueUrl' => $sqsURL,
];

$resultsqs1 = $sqsclient->sendMessage($params);
print_r($resultsqs1);
echo "<br>" ;

/*
$getmsgresult = $sqsclient->receiveMessage([  //pthon for lambda
    'QueueUrl' => $sqsURL, // REQUIRED
    'VisibilityTimeout' => 300,
    'WaitTimeSeconds' => 10,
]);
*/
/*
$handle = $getmsgresult['Messages'][0]['ReceiptHandle'];
echo "<br>-----------------Received msg-------------------";

echo "<br>-----------------SQS COMPLETED------------------------";
*/
    
/*$result = $lambdaclient->invoke([
        // The name your created Lamda function
    'FunctionName' => $lambdaname,
    ]);

    echo "<br>----------- Lambda triggered successfully--------------";
    */
    
    
    
    /*
    # https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletemessage
    $delresult = $sqsclient->deleteMessage([
        'QueueUrl' => $URL, // REQUIRED
        'ReceiptHandle' => $handle, // REQUIRED
    ]);
    echo "<br>-----------------Delete msg-------------------";
    
/*
# https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sns-2010-03-31.html#subscribe
$snsresult = $snsclient->subscribe([
    'Endpoint' => $_POST['phone'],  // this number is taken from the form on index.php POST action
    'Protocol' => 'lambda', // REQUIRED //prof: sms
    'ReturnSubscriptionArn' => true,
    'TopicArn' => $TopicArn, // REQUIRED
]);
*/



     
// code for creating thumbnail and pushing finished url into another s3 bucket
//thumbnail
/*
$thumbImage = new Imagick($uploadfile);
$thumbImage->thumbnailImage(150,150);
$thumbImage->writeImage();

$resultimg = $s3->putObject([
    'ACL' => 'public-read',
    'Bucket' => "can-afterimg-process",
    'Key'    =>  $_FILES['userfile']['name'],
    'SourceFile' => $uploadfile 
    ]);


$finishurl=$resultimg['ObjectURL'];
echo "</br>";
echo "\n". "This is your S3 Image URL: " . $finishurl ."\n"; echo "</br>";

//code for conection to DB

use Aws\Rds\RdsClient;
 $client = new Aws\Rds\RdsClient([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

 echo  "created rds client <br>";

$dbresult = $client->describeDBInstances(array(
    'DBInstanceIdentifier' => 'can-database'
));

//echo "rds db instance result done <br>" ;

$address = $dbresult['DBInstances'][0]['Endpoint']['Address'];
echo "DB endpoint is : " ;
echo $address;
echo "<br>";

$link = mysqli_connect($address,"master","cloud-db","records") or die("Error ". mysqli_error($link));
//check connection 
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}



echo "DB connection success <br>" ;

// code for inserting to database

// Prepared statement for inserting into DB 
if (!($stmt = $link->prepare("INSERT INTO items (id, email, phone,filename,s3rawurl,s3finishedurl,status,issubscribed) VALUES (NULL,?,?,?,?,?,?,?)"))) {
    echo "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
}
$email = $_POST["useremail"];
$phone = $_POST["phone"];
$filename = $_FILES['userfile']['name'];
$s3rawurl = $url; // $result['ObjectURL']; from above
$s3finishedurl = $finishurl;
$status =0;
$issubscribed=0;


$stmt->bind_param("sssssii",$email,$phone,$filename,$s3rawurl,$s3finishedurl,$status,$issubscribed);
if (!$stmt->execute()) {
    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
}
echo "<br>";
printf("%d Row inserted.\n", $stmt->affected_rows);

// explicit close recommended 
$stmt->close();
$link->close();

*/
?>
